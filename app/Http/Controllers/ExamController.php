<?php

namespace App\Http\Controllers;

use App\Exports\QuestionsTemplateExport;
use App\Http\Requests\ImportQuestionsRequest;
use App\Http\Requests\StoreExamRequest;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\StudentLoginRequest;
use App\Http\Requests\SubmitAnswerRequest;
use App\Imports\QuestionsImport;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Student;
use App\Models\StudentAnswer;
use App\Models\StudentSession;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException as ExcelValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExamController extends Controller
{
    /**
     * Display the student login & exam selection portal.
     */
    public function showLogin(): View
    {
        $exams = Exam::query()
            ->withCount('questions')
            ->orderBy('id')
            ->get();

        $leaderboard = StudentSession::query()
            ->with(['student', 'exam'])
            ->whereIn('status', [StudentSession::STATUS_FINISHED, StudentSession::STATUS_ELIMINATED])
            ->orderByDesc('current_score')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return view('exam.login', [
            'exams' => $exams,
            'leaderboard' => $leaderboard,
        ]);
    }

    /**
     * Validate student credentials, register/update student, and initialize or resume exam session.
     */
    public function startSession(StudentLoginRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $student = Student::query()->updateOrCreate(
            ['nis' => trim($validated['nis'])],
            [
                'full_name' => trim($validated['full_name']),
                'class_name' => trim($validated['class_name']),
            ]
        );

        /** @var Exam $exam */
        $exam = Exam::query()->findOrFail($validated['exam_id']);

        if (! $request->boolean('force_new_session')) {
            $existingSession = StudentSession::query()
                ->where('student_id', $student->id)
                ->where('exam_id', $exam->id)
                ->where('status', StudentSession::STATUS_ONGOING)
                ->latest('id')
                ->first();

            if ($existingSession !== null) {
                $existingSession->setRelation('exam', $exam);

                if (! $exam->isGlobalTimer() || $existingSession->remainingGlobalSeconds() > 0) {
                    if ($existingSession->current_step <= 1 && $existingSession->answers()->count() === 0) {
                        $existingSession->update([
                            'question_order' => $this->buildQuestionOrder($exam),
                        ]);
                    }

                    return redirect()->route('exam.arena', ['token' => $existingSession->session_token]);
                }

                $existingSession->update([
                    'status' => StudentSession::STATUS_FINISHED,
                    'current_score' => max($existingSession->current_score, $existingSession->safe_score),
                    'finished_at' => now(),
                ]);
            }
        }

        $questionOrder = $this->buildQuestionOrder($exam);
        $startingScore = $exam->starting_score > 0 ? $exam->starting_score : 20;

        $session = StudentSession::query()->create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'session_token' => Str::random(40),
            'current_score' => $startingScore,
            'safe_score' => 0,
            'current_step' => 1,
            'status' => StudentSession::STATUS_ONGOING,
            'lifeline_5050_used' => false,
            'question_order' => $questionOrder,
            'started_at' => now(),
        ]);

        return redirect()->route('exam.arena', ['token' => $session->session_token]);
    }

    /**
     * Display the Millionaire CBT Arena single-page view.
     */
    public function arena(string $token): View
    {
        $session = StudentSession::query()
            ->with(['student', 'exam', 'answers.question'])
            ->where('session_token', $token)
            ->firstOrFail();

        $exam = $session->exam;

        if ($session->isOngoing() && $exam->isGlobalTimer() && $session->remainingGlobalSeconds() <= 0) {
            $session->update([
                'status' => StudentSession::STATUS_FINISHED,
                'current_score' => max($session->current_score, $session->safe_score),
                'finished_at' => now(),
            ]);
            $session->refresh();
        }

        $currentQuestion = $session->isOngoing()
            ? $session->resolveQuestionForStep($session->current_step)
            : null;

        if ($session->isOngoing() && $currentQuestion === null) {
            $session->update([
                'status' => StudentSession::STATUS_FINISHED,
                'current_score' => max($session->current_score, $session->safe_score),
                'finished_at' => now(),
            ]);
            $session->refresh();
        }

        $arenaState = $this->buildArenaState($session, $currentQuestion);

        return view('exam.arena', [
            'session' => $session,
            'exam' => $exam,
            'student' => $session->student,
            'arenaState' => $arenaState,
        ]);
    }

    /**
     * Handle per-question answer submission via AJAX/Fetch API without page reload.
     */
    public function submitAnswer(SubmitAnswerRequest $request, string $token): JsonResponse
    {
        return DB::transaction(function () use ($request, $token): JsonResponse {
            $session = StudentSession::query()
                ->with(['student', 'exam', 'answers.question'])
                ->where('session_token', $token)
                ->lockForUpdate()
                ->firstOrFail();

            $exam = $session->exam;

            if (! $session->isOngoing()) {
                return response()->json([
                    'message' => 'Sesi ujian sudah berakhir.',
                    'state' => $this->buildArenaState($session, null),
                ], 422);
            }

            /** @var Question $question */
            $question = Question::query()
                ->where('exam_id', $exam->id)
                ->findOrFail($request->integer('question_id'));

            $chosenOpt = $request->input('chosen_opt');
            $isTimeout = $request->boolean('is_timeout') || $chosenOpt === null;
            $isGlobalTimeout = $request->boolean('is_global_timeout')
                || ($exam->isGlobalTimer() && $session->remainingGlobalSeconds() <= 0);

            if ($isGlobalTimeout && $chosenOpt === null && ! $request->boolean('is_timeout')) {
                $session->status = StudentSession::STATUS_FINISHED;
                $session->current_score = max($session->current_score, $session->safe_score);
                $session->finished_at = now();
                $session->save();
                $session->load('answers.question');

                return response()->json([
                    'message' => 'Waktu global habis! Ujian otomatis diselesaikan.',
                    'feedback' => [
                        'is_correct' => false,
                        'is_timeout' => true,
                        'is_global_timeout' => true,
                        'chosen_opt' => null,
                        'correct_opt' => $question->correct_opt,
                        'score_diff' => 0,
                        'safe_point_unlocked' => false,
                    ],
                    'state' => $this->buildArenaState($session, null),
                ]);
            }

            $isCorrect = ! $isTimeout && strtoupper((string) $chosenOpt) === strtoupper($question->correct_opt);
            $scoreDiff = $isCorrect
                ? abs((int) $question->score_gain)
                : -abs((int) $question->score_penalty);

            $timeSpent = max(0, $request->integer('time_spent_sec', 0));

            StudentAnswer::query()->updateOrCreate(
                [
                    'session_id' => $session->id,
                    'question_id' => $question->id,
                ],
                [
                    'chosen_opt' => $isTimeout ? null : strtoupper((string) $chosenOpt),
                    'is_correct' => $isCorrect,
                    'score_diff' => $scoreDiff,
                    'time_spent_sec' => $timeSpent,
                ]
            );

            $newScore = $session->current_score + $scoreDiff;
            $safePointUnlocked = false;
            $effectiveStepLevel = is_array($session->question_order) && count($session->question_order) > 0
                ? $session->current_step
                : $question->level_tier;

            if ($isCorrect && in_array($effectiveStepLevel, Exam::SAFE_LEVELS, true)) {
                $session->safe_score = max($session->safe_score, $newScore);
                $safePointUnlocked = true;
            }

            $nextQuestion = null;

            if ($newScore <= 0) {
                $session->current_score = 0;
                $session->status = StudentSession::STATUS_ELIMINATED;
                $session->finished_at = now();
            } else {
                $session->current_score = $newScore;
                $nextStep = $session->current_step + 1;
                $nextQuestion = $nextStep <= Exam::MAX_LEVEL
                    ? $session->resolveQuestionForStep($nextStep)
                    : null;

                if ($isGlobalTimeout || $nextStep > Exam::MAX_LEVEL || $nextQuestion === null) {
                    $session->status = StudentSession::STATUS_FINISHED;
                    $session->current_score = max($session->current_score, $session->safe_score);
                    $session->finished_at = now();
                    $nextQuestion = null;
                } else {
                    $session->current_step = $nextStep;
                }
            }

            $session->save();
            $session->load('answers.question');

            return response()->json([
                'message' => $this->resolveFeedbackMessage($session, $isCorrect, $isTimeout, $safePointUnlocked),
                'feedback' => [
                    'is_correct' => $isCorrect,
                    'is_timeout' => $isTimeout,
                    'is_global_timeout' => $isGlobalTimeout,
                    'chosen_opt' => $isTimeout ? null : strtoupper((string) $chosenOpt),
                    'correct_opt' => $question->correct_opt,
                    'score_diff' => $scoreDiff,
                    'safe_point_unlocked' => $safePointUnlocked,
                ],
                'state' => $this->buildArenaState($session, $nextQuestion),
            ]);
        });
    }

    /**
     * Activate the one-time 50:50 lifeline for the current question via AJAX.
     */
    public function useLifeline5050(Request $request, string $token): JsonResponse
    {
        $request->validate([
            'question_id' => ['required', 'integer', 'exists:questions,id'],
        ]);

        return DB::transaction(function () use ($request, $token): JsonResponse {
            $session = StudentSession::query()
                ->where('session_token', $token)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $session->isOngoing()) {
                return response()->json([
                    'message' => 'Sesi ujian sudah berakhir.',
                ], 422);
            }

            if ($session->lifeline_5050_used) {
                return response()->json([
                    'message' => 'Bantuan 50:50 hanya dapat digunakan 1 kali per sesi ujian!',
                ], 422);
            }

            /** @var Question $question */
            $question = Question::query()
                ->where('exam_id', $session->exam_id)
                ->findOrFail($request->integer('question_id'));

            $wrongOptions = array_values(array_filter(
                ['A', 'B', 'C', 'D'],
                fn (string $opt): bool => $opt !== strtoupper($question->correct_opt)
            ));

            shuffle($wrongOptions);
            $eliminatedOptions = array_slice($wrongOptions, 0, 2);
            sort($eliminatedOptions);

            $session->update([
                'lifeline_5050_used' => true,
            ]);

            return response()->json([
                'message' => 'Bantuan 50:50 diaktifkan! Dua opsi jawaban yang salah telah dieliminasi.',
                'lifeline_5050_used' => true,
                'eliminated_options' => $eliminatedOptions,
            ]);
        });
    }

    /**
     * Auto-submit and finalize session when Mode B Global Timer reaches 0.
     */
    public function finishGlobalTimeout(Request $request, string $token): JsonResponse
    {
        return DB::transaction(function () use ($token): JsonResponse {
            $session = StudentSession::query()
                ->with(['student', 'exam', 'answers.question'])
                ->where('session_token', $token)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->isOngoing()) {
                $session->update([
                    'status' => StudentSession::STATUS_FINISHED,
                    'current_score' => max($session->current_score, $session->safe_score),
                    'finished_at' => now(),
                ]);
                $session->refresh();
            }

            return response()->json([
                'message' => 'Waktu ujian global telah habis. Seluruh jawaban telah disimpan otomatis.',
                'state' => $this->buildArenaState($session, null),
            ]);
        });
    }

    /**
     * Display the Teacher & Backoffice Login form before accessing /teacher/exams.
     */
    public function showTeacherLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('teacher.exams');
        }

        return view('exam.teacher-login');
    }

    /**
     * Authenticate Teacher / Backoffice User by username (or email) and password.
     */
    public function authenticateTeacher(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $loginIdentifier = trim($validated['username']);

        /** @var User|null $user */
        $user = User::query()
            ->where('username', $loginIdentifier)
            ->orWhere('email', $loginIdentifier)
            ->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            return back()
                ->withErrors(['username' => 'Username atau password yang Anda masukkan tidak sesuai.'])
                ->onlyInput('username');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $roleLabel = $user->isSupervisor() ? 'SUPERVISOR (Full Akses)' : 'ADMIN (Tanpa Akses Delete)';

        return redirect()
            ->intended(route('teacher.exams'))
            ->with('success', "Login berhasil! Selamat datang, {$user->name} — Akses: {$roleLabel}.");
    }

    /**
     * Log out the authenticated Teacher / Backoffice User.
     */
    public function logoutTeacher(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Anda telah berhasil keluar (Logout) dari Panel Guru & Backoffice.');
    }

    /**
     * Teacher dashboard for managing Exam packages, timer modes, manual/Excel Question Banks, Student Exam Results, and Backoffice Users.
     */
    public function manageExams(Request $request): View
    {
        $exams = Exam::query()
            ->withCount(['questions', 'sessions'])
            ->orderBy('id')
            ->get();

        $selectedExamId = $request->integer('exam_id', $exams->first()?->id ?? 0);
        $selectedExam = $exams->firstWhere('id', $selectedExamId) ?? $exams->first();

        $questions = $selectedExam !== null
            ? $selectedExam->questions()->orderBy('level_tier')->orderBy('id')->get()
            : collect();

        $sessionExamFilter = $request->string('session_exam_id', 'all')->toString();
        $statusFilter = $request->string('status_filter', 'all')->toString();
        $classFilter = $request->string('class_filter', 'all')->toString();
        $searchQuery = trim($request->string('search', '')->toString());

        $participantSessions = StudentSession::query()
            ->with(['student', 'exam'])
            ->withCount([
                'answers as total_answered_count',
                'answers as correct_answers_count' => fn ($q) => $q->where('is_correct', true),
                'answers as wrong_answers_count' => fn ($q) => $q->where('is_correct', false),
            ])
            ->when(
                $sessionExamFilter !== 'all' && is_numeric($sessionExamFilter),
                fn ($query) => $query->where('exam_id', (int) $sessionExamFilter)
            )
            ->when(
                in_array($statusFilter, [
                    StudentSession::STATUS_ONGOING,
                    StudentSession::STATUS_FINISHED,
                    StudentSession::STATUS_ELIMINATED,
                ], true),
                fn ($query) => $query->where('status', $statusFilter)
            )
            ->when(
                $classFilter !== 'all' && $classFilter !== '',
                fn ($query) => $query->whereHas('student', fn ($sq) => $sq->where('class_name', $classFilter))
            )
            ->when(
                $searchQuery !== '',
                fn ($query) => $query->whereHas('student', function ($sq) use ($searchQuery): void {
                    $sq->where('nis', 'like', "%{$searchQuery}%")
                        ->orWhere('full_name', 'like', "%{$searchQuery}%");
                })
            )
            ->orderByDesc('id')
            ->get();

        $availableClasses = Student::query()
            ->select('class_name')
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name');

        $users = User::query()
            ->orderByRaw("CASE WHEN role = 'supervisor' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get();

        return view('exam.teacher', [
            'exams' => $exams,
            'selectedExam' => $selectedExam,
            'questions' => $questions,
            'recentSessions' => $participantSessions,
            'participantSessions' => $participantSessions,
            'availableClasses' => $availableClasses,
            'users' => $users,
            'filters' => [
                'session_exam_id' => $sessionExamFilter,
                'status_filter' => $statusFilter,
                'class_filter' => $classFilter,
                'search' => $searchQuery,
            ],
        ]);
    }

    /**
     * Create a new Exam package or update an existing Exam package configuration.
     */
    public function storeExam(StoreExamRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['randomize_questions'] = $request->boolean('randomize_questions');

        $exam = Exam::query()->create($validated);

        return redirect()
            ->route('teacher.exams', ['exam_id' => $exam->id])
            ->with('success', "Paket ujian baru \"{$exam->title}\" ({$exam->subject}) berhasil dibuat dan siap dipilih oleh siswa!");
    }

    /**
     * Update an existing Exam's timer mode and configuration.
     */
    public function updateExam(StoreExamRequest $request, Exam $exam): RedirectResponse
    {
        $validated = $request->validated();
        $validated['randomize_questions'] = $request->boolean('randomize_questions');

        $exam->update($validated);

        return redirect()
            ->route('teacher.exams', ['exam_id' => $exam->id])
            ->with('success', "Konfigurasi ujian \"{$exam->title}\" berhasil diperbarui.");
    }

    /**
     * Delete an Exam package and its associated questions/sessions (Supervisor only).
     */
    public function destroyExam(Request $request, Exam $exam): RedirectResponse
    {
        if (! $request->user()?->canDeleteData()) {
            return redirect()
                ->route('teacher.exams', ['exam_id' => $exam->id])
                ->withErrors(['delete' => 'Akses Ditolak: Level Admin tidak memiliki izin untuk menghapus data. Fitur hapus hanya tersedia untuk Supervisor.']);
        }

        $title = $exam->title;
        $exam->delete();

        return redirect()
            ->route('teacher.exams')
            ->with('success', "Paket ujian \"{$title}\" berhasil dihapus.");
    }

    /**
     * Store one or multiple questions manually created from the Teacher Panel form into the database.
     */
    public function storeQuestions(StoreQuestionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /** @var Exam $exam */
        $exam = Exam::query()->findOrFail((int) $validated['exam_id']);

        if ($request->boolean('replace_existing') && ! $request->user()?->canDeleteData()) {
            return redirect()
                ->route('teacher.exams', ['exam_id' => $exam->id])
                ->withErrors(['delete' => 'Akses Ditolak: Level Admin tidak dapat menghapus/menimpa soal lama (khusus Supervisor). Silakan simpan soal tanpa mencentang opsi hapus soal lama.']);
        }

        $createdCount = 0;

        DB::transaction(function () use ($request, $exam, $validated, &$createdCount): void {
            if ($request->boolean('replace_existing') && $request->user()?->canDeleteData()) {
                $exam->questions()->delete();
            }

            foreach ($validated['questions'] as $questionData) {
                $exam->questions()->create([
                    'level_tier' => (int) $questionData['level_tier'],
                    'question_text' => trim((string) $questionData['question_text']),
                    'opt_a' => trim((string) $questionData['opt_a']),
                    'opt_b' => trim((string) $questionData['opt_b']),
                    'opt_c' => trim((string) $questionData['opt_c']),
                    'opt_d' => trim((string) $questionData['opt_d']),
                    'correct_opt' => strtoupper((string) $questionData['correct_opt']),
                    'score_gain' => (int) $questionData['score_gain'],
                    'score_penalty' => abs((int) $questionData['score_penalty']),
                    'duration_sec' => (int) $questionData['duration_sec'],
                ]);
                $createdCount++;
            }
        });

        return redirect()
            ->route('teacher.exams', ['exam_id' => $exam->id])
            ->with('success', "Berhasil menyimpan {$createdCount} soal baru ke database pada paket \"{$exam->title}\".");
    }

    /**
     * Update a single question in the database.
     */
    public function updateQuestion(Request $request, Question $question): RedirectResponse
    {
        $validated = $request->validate([
            'level_tier' => ['required', 'integer', 'min:1', 'max:15'],
            'question_text' => ['required', 'string', 'min:3'],
            'opt_a' => ['required', 'string'],
            'opt_b' => ['required', 'string'],
            'opt_c' => ['required', 'string'],
            'opt_d' => ['required', 'string'],
            'correct_opt' => ['required', 'string', 'in:A,B,C,D,a,b,c,d'],
            'score_gain' => ['required', 'integer', 'min:1', 'max:100'],
            'score_penalty' => ['required', 'integer', 'min:0', 'max:100'],
            'duration_sec' => ['required', 'integer', 'min:5', 'max:3600'],
        ]);

        $question->update([
            'level_tier' => (int) $validated['level_tier'],
            'question_text' => trim($validated['question_text']),
            'opt_a' => trim($validated['opt_a']),
            'opt_b' => trim($validated['opt_b']),
            'opt_c' => trim($validated['opt_c']),
            'opt_d' => trim($validated['opt_d']),
            'correct_opt' => strtoupper(trim($validated['correct_opt'])),
            'score_gain' => (int) $validated['score_gain'],
            'score_penalty' => abs((int) $validated['score_penalty']),
            'duration_sec' => (int) $validated['duration_sec'],
        ]);

        return redirect()
            ->route('teacher.exams', ['exam_id' => $question->exam_id])
            ->with('success', "Soal Level {$question->level_tier} berhasil diperbarui.");
    }

    /**
     * Delete a single question from the database (Supervisor only).
     */
    public function destroyQuestion(Request $request, Question $question): RedirectResponse
    {
        if (! $request->user()?->canDeleteData()) {
            return redirect()
                ->route('teacher.exams', ['exam_id' => $question->exam_id])
                ->withErrors(['delete' => 'Akses Ditolak: Level Admin tidak memiliki izin untuk menghapus soal. Fitur hapus hanya tersedia untuk Supervisor.']);
        }

        $examId = $question->exam_id;
        $level = $question->level_tier;
        $question->delete();

        return redirect()
            ->route('teacher.exams', ['exam_id' => $examId])
            ->with('success', "Soal Level {$level} berhasil dihapus dari bank soal.");
    }

    /**
     * Delete a student exam session record (Supervisor only).
     */
    public function destroySession(Request $request, StudentSession $session): RedirectResponse
    {
        $examId = $request->integer('exam_id', $session->exam_id);

        if (! $request->user()?->canDeleteData()) {
            return redirect()
                ->route('teacher.exams', ['exam_id' => $examId])
                ->withErrors(['delete' => 'Akses Ditolak: Level Admin tidak memiliki izin untuk menghapus riwayat sesi peserta. Fitur hapus hanya tersedia untuk Supervisor.']);
        }

        $studentName = $session->student?->full_name ?? 'Peserta';
        $session->delete();

        return redirect()
            ->route('teacher.exams', ['exam_id' => $examId])
            ->with('success', "Data sesi ujian milik \"{$studentName}\" berhasil dihapus.");
    }

    /**
     * Backoffice: Register a new Teacher / User account with Supervisor or Admin role.
     */
    public function storeUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:60', 'alpha_dash', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:4', 'max:100'],
            'role' => ['required', 'string', Rule::in([User::ROLE_SUPERVISOR, User::ROLE_ADMIN])],
        ], [
            'username.unique' => 'Username tersebut sudah digunakan oleh user/guru lain.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, tanda hubung (-), atau garis bawah (_).',
        ]);

        if (! $request->user()?->isSupervisor() && $validated['role'] === User::ROLE_SUPERVISOR) {
            return redirect()
                ->route('teacher.exams')
                ->withErrors(['role' => 'Akses Ditolak: Hanya user tingkat Supervisor yang dapat mendaftarkan user baru dengan level Supervisor.']);
        }

        $username = strtolower(trim($validated['username']));
        $email = ! empty($validated['email'])
            ? strtolower(trim($validated['email']))
            : "{$username}@ujianonline.sch.id";

        if (User::query()->where('email', $email)->exists()) {
            $email = "{$username}.2@ujianonline.sch.id";
        }

        $newUser = User::query()->create([
            'name' => trim($validated['name']),
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        $roleBadge = $newUser->isSupervisor() ? 'Supervisor (Full Akses)' : 'Admin (Tanpa Delete)';

        return redirect()
            ->route('teacher.exams')
            ->with('success', "User / Guru \"{$newUser->name}\" (username: {$newUser->username}) berhasil didaftarkan sebagai {$roleBadge}.");
    }

    /**
     * Backoffice: Update an existing Teacher / User account.
     */
    public function updateUser(Request $request, User $user): RedirectResponse
    {
        if ($user->isSupervisor() && ! $request->user()?->isSupervisor()) {
            return redirect()
                ->route('teacher.exams')
                ->withErrors(['role' => 'Akses Ditolak: Level Admin tidak dapat mengubah data akun Supervisor.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:60', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:4', 'max:100'],
            'role' => ['required', 'string', Rule::in([User::ROLE_SUPERVISOR, User::ROLE_ADMIN])],
        ]);

        if (! $request->user()?->isSupervisor() && $validated['role'] === User::ROLE_SUPERVISOR) {
            return redirect()
                ->route('teacher.exams')
                ->withErrors(['role' => 'Akses Ditolak: Level Admin tidak dapat menaikkan tingkat akses menjadi Supervisor.']);
        }

        if (
            $user->isSupervisor()
            && $validated['role'] !== User::ROLE_SUPERVISOR
            && User::query()->where('role', User::ROLE_SUPERVISOR)->count() <= 1
        ) {
            return redirect()
                ->route('teacher.exams')
                ->withErrors(['role' => 'Tidak dapat mengubah role: Sistem wajib memiliki minimal 1 akun Supervisor aktif.']);
        }

        $payload = [
            'name' => trim($validated['name']),
            'username' => strtolower(trim($validated['username'])),
            'role' => $validated['role'],
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        return redirect()
            ->route('teacher.exams')
            ->with('success', "Data akun \"{$user->name}\" (@{$user->username}) berhasil diperbarui.");
    }

    /**
     * Backoffice: Delete a Teacher / User account (Supervisor only).
     */
    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        if (! $request->user()?->canDeleteData()) {
            return redirect()
                ->route('teacher.exams')
                ->withErrors(['delete' => 'Akses Ditolak: Level Admin tidak memiliki izin untuk menghapus akun user/guru (khusus Supervisor).']);
        }

        if ($request->user()?->id === $user->id) {
            return redirect()
                ->route('teacher.exams')
                ->withErrors(['delete' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif login.']);
        }

        if ($user->isSupervisor() && User::query()->where('role', User::ROLE_SUPERVISOR)->count() <= 1) {
            return redirect()
                ->route('teacher.exams')
                ->withErrors(['delete' => 'Tidak dapat menghapus akun Supervisor terakhir di sistem.']);
        }

        $deletedName = $user->name;
        $deletedUsername = $user->username;
        $user->delete();

        return redirect()
            ->route('teacher.exams')
            ->with('success', "Akun user/guru \"{$deletedName}\" (@{$deletedUsername}) berhasil dihapus.");
    }

    /**
     * Import questions from an Excel (.xlsx / .csv) file into the selected Exam.
     */
    public function importQuestions(ImportQuestionsRequest $request): RedirectResponse
    {
        /** @var Exam $exam */
        $exam = Exam::query()->findOrFail($request->integer('exam_id'));

        if ($request->boolean('replace_existing') && ! $request->user()?->canDeleteData()) {
            return redirect()
                ->route('teacher.exams', ['exam_id' => $exam->id])
                ->withErrors(['delete' => 'Akses Ditolak: Level Admin tidak memiliki izin untuk menghapus/menimpa soal lama (khusus Supervisor).']);
        }

        try {
            $import = new QuestionsImport($exam);

            DB::transaction(function () use ($request, $exam, $import): void {
                if ($request->boolean('replace_existing') && $request->user()?->canDeleteData()) {
                    $exam->questions()->delete();
                }

                Excel::import($import, $request->file('file'));
            });

            return redirect()
                ->route('teacher.exams', ['exam_id' => $exam->id])
                ->with('success', "Berhasil mengimpor {$import->importedCount} soal ke paket \"{$exam->title}\".");
        } catch (ExcelValidationException $exception) {
            $errorMessages = collect($exception->failures())
                ->map(fn ($failure): string => "Baris {$failure->row()}: ".implode(', ', $failure->errors()))
                ->take(5)
                ->implode(' | ');

            return redirect()
                ->route('teacher.exams', ['exam_id' => $exam->id])
                ->withErrors(['file' => "Validasi Excel gagal: {$errorMessages}"]);
        }
    }

    /**
     * Download a ready-to-use Excel (.xlsx) template for Question Bank import.
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        return Excel::download(
            new QuestionsTemplateExport,
            'Template_Bank_Soal_Millionaire_UjianOnline.xlsx'
        );
    }

    /**
     * Build the 15-level question order for a new student session.
     * Supports deterministic order or random selection per difficulty tier (1-15),
     * as well as custom multi-question sets entered by the teacher.
     *
     * @return list<int>
     */
    private function buildQuestionOrder(Exam $exam): array
    {
        $allQuestions = $exam->questions()
            ->orderBy('level_tier')
            ->orderBy('id')
            ->get();

        if ($allQuestions->isEmpty()) {
            return [];
        }

        $groupedByTier = $allQuestions->groupBy('level_tier');
        $selectedIds = [];

        for ($tier = 1; $tier <= Exam::MAX_LEVEL; $tier++) {
            $tierQuestions = $groupedByTier->get($tier);

            if ($tierQuestions === null || $tierQuestions->isEmpty()) {
                continue;
            }

            $chosen = $tierQuestions->random();
            $selectedIds[] = $chosen->id;
        }

        // If teacher added multiple questions that share a level_tier and total selected < 15, include remaining randomly up to 15
        if (count($selectedIds) < Exam::MAX_LEVEL && $allQuestions->count() > count($selectedIds)) {
            $remainingIds = $allQuestions
                ->pluck('id')
                ->reject(fn (int $id): bool => in_array($id, $selectedIds, true))
                ->shuffle()
                ->take(Exam::MAX_LEVEL - count($selectedIds))
                ->values()
                ->all();

            $selectedIds = array_merge($selectedIds, $remainingIds);
        }

        // Always shuffle question order per student session so concurrent students receive different questions at each step
        shuffle($selectedIds);

        // Ensure two students logging in consecutively do not start with the exact same first question
        if (count($selectedIds) > 1) {
            $lastOrder = StudentSession::query()
                ->where('exam_id', $exam->id)
                ->latest('id')
                ->value('question_order');

            if (is_array($lastOrder) && isset($lastOrder[0]) && (int) $lastOrder[0] === (int) $selectedIds[0]) {
                $swapIndex = random_int(1, count($selectedIds) - 1);
                [$selectedIds[0], $selectedIds[$swapIndex]] = [$selectedIds[$swapIndex], $selectedIds[0]];
            }
        }

        return array_values($selectedIds);
    }

    /**
     * Build the full Arena state payload for Blade and Alpine.js.
     *
     * @return array<string, mixed>
     */
    private function buildArenaState(StudentSession $session, ?Question $currentQuestion): array
    {
        $exam = $session->exam;
        $startingScore = $exam->starting_score > 0 ? $exam->starting_score : 20;
        $answersByQuestionId = $session->answers->keyBy('question_id');
        $answersByTier = $session->answers
            ->filter(fn (StudentAnswer $ans): bool => $ans->question !== null)
            ->keyBy(fn (StudentAnswer $ans): int => $ans->question->level_tier);
        $questionOrder = is_array($session->question_order) ? array_values($session->question_order) : [];

        $ladder = [];
        for ($level = 1; $level <= Exam::MAX_LEVEL; $level++) {
            /** @var StudentAnswer|null $answer */
            $answer = isset($questionOrder[$level - 1])
                ? $answersByQuestionId->get($questionOrder[$level - 1])
                : $answersByTier->get($level);
            $isSafePoint = in_array($level, Exam::SAFE_LEVELS, true);

            $stepStatus = 'upcoming';
            if ($answer !== null || $level < $session->current_step || $session->isFinished()) {
                $stepStatus = 'completed';
            } elseif ($session->isOngoing() && $level === $session->current_step) {
                $stepStatus = 'current';
            }

            $ladder[] = [
                'level' => $level,
                'is_safe_point' => $isSafePoint,
                'status' => $stepStatus,
                'is_correct' => $answer?->is_correct,
                'score_diff' => $answer?->score_diff,
                'safe_locked' => $isSafePoint && ($answer?->is_correct === true),
            ];
        }

        $totalCorrect = $session->answers->where('is_correct', true)->count();
        $totalWrong = $session->answers->where('is_correct', false)->count();

        $questionPayload = $currentQuestion?->toArenaPayload($exam->default_question_timer);
        if ($questionPayload !== null) {
            $questionPayload['level_tier'] = $session->current_step;
            $questionPayload['is_safe_point'] = in_array($session->current_step, Exam::SAFE_LEVELS, true);
        }

        return [
            'session' => [
                'id' => $session->id,
                'token' => $session->session_token,
                'status' => $session->status,
                'starting_score' => $startingScore,
                'current_score' => $session->current_score,
                'net_score' => $session->current_score - $startingScore,
                'safe_score' => $session->safe_score,
                'current_step' => $session->current_step,
                'max_level' => Exam::MAX_LEVEL,
                'lifeline_5050_used' => $session->lifeline_5050_used,
                'timer_mode' => $exam->timer_mode,
                'default_question_timer' => $exam->default_question_timer,
                'total_global_timer' => $exam->total_global_timer,
                'remaining_global_sec' => $exam->isGlobalTimer() ? $session->remainingGlobalSeconds() : null,
                'total_answered' => $session->answers->count(),
                'total_correct' => $totalCorrect,
                'total_wrong' => $totalWrong,
            ],
            'student' => [
                'nis' => $session->student->nis,
                'full_name' => $session->student->full_name,
                'class_name' => $session->student->class_name,
            ],
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'subject' => $exam->subject,
                'timer_mode' => $exam->timer_mode,
                'timer_mode_label' => $exam->isPerQuestionTimer()
                    ? 'Mode A — Timer Per Soal'
                    : 'Mode B — Timer Global',
            ],
            'question' => $questionPayload,
            'ladder' => $ladder,
        ];
    }

    private function resolveFeedbackMessage(
        StudentSession $session,
        bool $isCorrect,
        bool $isTimeout,
        bool $safePointUnlocked
    ): string {
        if ($session->isEliminated()) {
            return 'GAME OVER! Poin Anda menyentuh 0. Anda tereliminasi dari panggung ujian.';
        }

        if ($session->isFinished()) {
            return "SELAMAT! Anda telah menaklukkan panggung Ujian Online dengan skor akhir {$session->current_score} poin!";
        }

        if ($isTimeout) {
            return 'Waktu habis! Jawaban dianggap salah (-5 poin). Lanjut ke soal berikutnya.';
        }

        if ($safePointUnlocked) {
            return "Jawaban Benar! TITIK AMAN [SAFE POINT] berhasil dikunci pada {$session->safe_score} poin!";
        }

        return $isCorrect
            ? 'Jawaban Benar! Poin bertambah, bersiap untuk level berikutnya.'
            : 'Jawaban Kurang Tepat! Poin berkurang 5, tetap fokus di soal berikutnya.';
    }
}
