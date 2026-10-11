<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Question;
use App\Models\StudentSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MillionaireExamTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_login_and_receive_20_starting_points(): void
    {
        $exam = Exam::factory()->modeA(30)->create(['starting_score' => 20]);
        Question::factory()->for($exam)->forTier(1)->create();

        $response = $this->post(route('exam.start'), [
            'nis' => '20269901',
            'full_name' => 'Rizky Ramadhan',
            'class_name' => 'XII MIPA 1',
            'exam_id' => $exam->id,
        ]);

        $session = StudentSession::query()->firstOrFail();

        $response->assertRedirect(route('exam.arena', ['token' => $session->session_token]));
        $this->assertSame(20, $session->current_score);
        $this->assertSame(1, $session->current_step);
        $this->assertSame(StudentSession::STATUS_ONGOING, $session->status);
        $this->assertDatabaseHas('students', [
            'nis' => '20269901',
            'full_name' => 'Rizky Ramadhan',
            'class_name' => 'XII MIPA 1',
        ]);
    }

    public function test_correct_answer_adds_points_and_advances_step_via_ajax(): void
    {
        $exam = Exam::factory()->modeA(30)->create(['starting_score' => 20]);
        $q1 = Question::factory()->for($exam)->forTier(1)->create(['correct_opt' => 'B', 'score_gain' => 5]);
        $q2 = Question::factory()->for($exam)->forTier(2)->create(['correct_opt' => 'C', 'score_gain' => 5]);

        $session = StudentSession::factory()->for($exam)->create([
            'current_score' => 20,
            'current_step' => 1,
            'question_order' => [$q1->id, $q2->id],
        ]);

        $response = $this->postJson(route('exam.answer', ['token' => $session->session_token]), [
            'question_id' => $q1->id,
            'chosen_opt' => 'B',
            'time_spent_sec' => 10,
        ]);

        $response->assertOk()
            ->assertJsonPath('feedback.is_correct', true)
            ->assertJsonPath('feedback.score_diff', 5)
            ->assertJsonPath('state.session.current_score', 25)
            ->assertJsonPath('state.session.current_step', 2)
            ->assertJsonPath('state.question.id', $q2->id);

        $this->assertDatabaseHas('student_answers', [
            'session_id' => $session->id,
            'question_id' => $q1->id,
            'chosen_opt' => 'B',
            'is_correct' => true,
            'score_diff' => 5,
        ]);
    }

    public function test_wrong_answer_or_timeout_deducts_5_points_and_eliminates_at_zero(): void
    {
        $exam = Exam::factory()->modeA(30)->create(['starting_score' => 20]);
        $q1 = Question::factory()->for($exam)->forTier(1)->create(['correct_opt' => 'A', 'score_penalty' => 5]);
        $q2 = Question::factory()->for($exam)->forTier(2)->create(['correct_opt' => 'A', 'score_penalty' => 5]);

        $session = StudentSession::factory()->for($exam)->create([
            'current_score' => 5,
            'current_step' => 1,
            'question_order' => [$q1->id, $q2->id],
        ]);

        $response = $this->postJson(route('exam.answer', ['token' => $session->session_token]), [
            'question_id' => $q1->id,
            'chosen_opt' => null,
            'is_timeout' => true,
            'time_spent_sec' => 30,
        ]);

        $response->assertOk()
            ->assertJsonPath('feedback.is_correct', false)
            ->assertJsonPath('feedback.score_diff', -5)
            ->assertJsonPath('state.session.current_score', 0)
            ->assertJsonPath('state.session.status', StudentSession::STATUS_ELIMINATED);

        $session->refresh();
        $this->assertSame(0, $session->current_score);
        $this->assertSame(StudentSession::STATUS_ELIMINATED, $session->status);
    }

    public function test_safe_point_locks_base_score_at_level_5_and_protects_final_score(): void
    {
        $exam = Exam::factory()->modeA(30)->create(['starting_score' => 20]);
        $q5 = Question::factory()->for($exam)->forTier(5)->create(['correct_opt' => 'A', 'score_gain' => 5]);
        $q6 = Question::factory()->for($exam)->forTier(6)->create(['correct_opt' => 'D', 'score_penalty' => 5]);

        $session = StudentSession::factory()->for($exam)->create([
            'current_score' => 40,
            'safe_score' => 0,
            'current_step' => 5,
        ]);

        $resLevel5 = $this->postJson(route('exam.answer', ['token' => $session->session_token]), [
            'question_id' => $q5->id,
            'chosen_opt' => 'A',
            'time_spent_sec' => 8,
        ]);

        $resLevel5->assertOk()
            ->assertJsonPath('feedback.safe_point_unlocked', true)
            ->assertJsonPath('state.session.safe_score', 45)
            ->assertJsonPath('state.session.current_score', 45);

        // Answer final available question (Level 6) wrong: score drops to 40, but safe_score (45) restores final score upon finish
        $resLevel6 = $this->postJson(route('exam.answer', ['token' => $session->session_token]), [
            'question_id' => $q6->id,
            'chosen_opt' => 'B',
            'time_spent_sec' => 12,
        ]);

        $resLevel6->assertOk()
            ->assertJsonPath('state.session.status', StudentSession::STATUS_FINISHED)
            ->assertJsonPath('state.session.current_score', 45);
    }

    public function test_lifeline_5050_can_only_be_used_once_per_session(): void
    {
        $exam = Exam::factory()->create();
        $question = Question::factory()->for($exam)->forTier(1)->create(['correct_opt' => 'C']);
        $session = StudentSession::factory()->for($exam)->create(['lifeline_5050_used' => false]);

        $firstCall = $this->postJson(route('exam.lifeline5050', ['token' => $session->session_token]), [
            'question_id' => $question->id,
        ]);

        $firstCall->assertOk()
            ->assertJsonPath('lifeline_5050_used', true);

        $eliminated = $firstCall->json('eliminated_options');
        $this->assertCount(2, $eliminated);
        $this->assertNotContains('C', $eliminated);

        $secondCall = $this->postJson(route('exam.lifeline5050', ['token' => $session->session_token]), [
            'question_id' => $question->id,
        ]);

        $secondCall->assertUnprocessable();
    }

    public function test_teacher_can_import_questions_from_spreadsheet(): void
    {
        $supervisor = User::factory()->supervisor()->create();
        $exam = Exam::factory()->create();

        $csvContent = implode("\n", [
            'Level_Tier,Pertanyaan,Opsi_A,Opsi_B,Opsi_C,Opsi_D,Kunci_Jawaban,Poin_Benar,Poin_Salah,Durasi_Detik',
            '1,"Berapakah 5 + 5?",10,12,15,20,A,5,5,30',
            '5,"[SAFE POINT] Ibu kota Indonesia?",Bandung,Surabaya,Jakarta,Medan,C,5,5,30',
        ]);

        $file = UploadedFile::fake()->createWithContent('bank_soal.csv', $csvContent);

        $response = $this->actingAs($supervisor)->post(route('teacher.questions.import'), [
            'exam_id' => $exam->id,
            'file' => $file,
            'replace_existing' => true,
        ]);

        $response->assertRedirect(route('teacher.exams', ['exam_id' => $exam->id]));
        $this->assertSame(2, $exam->questions()->count());
        $this->assertDatabaseHas('questions', [
            'exam_id' => $exam->id,
            'level_tier' => 5,
            'correct_opt' => 'C',
        ]);
    }

    public function test_arena_blade_view_renders_millionaire_ui_components(): void
    {
        $exam = Exam::factory()->modeA(30)->create(['starting_score' => 20]);
        $q1 = Question::factory()->for($exam)->forTier(1)->create([
            'question_text' => 'Siapakah penemu teori relativitas?',
        ]);
        $session = StudentSession::factory()->for($exam)->create([
            'current_score' => 20,
            'current_step' => 1,
            'question_order' => [$q1->id],
        ]);

        $response = $this->get(route('exam.arena', ['token' => $session->session_token]));

        $response->assertSee('POIN SAAT INI')
            ->assertSee('SISA WAKTU')
            ->assertSee('Bantuan 50:50 (1x)')
            ->assertSee('TANGGA TINGKAT SOAL')
            ->assertSee('Siapakah penemu teori relativitas?');
    }

    public function test_mode_b_global_timeout_auto_submits_and_finalizes_session(): void
    {
        $exam = Exam::factory()->modeB(1200)->create(['starting_score' => 20]);
        Question::factory()->for($exam)->forTier(1)->create();

        $session = StudentSession::factory()->for($exam)->create([
            'current_score' => 30,
            'safe_score' => 35,
            'current_step' => 6,
            'status' => StudentSession::STATUS_ONGOING,
        ]);

        $response = $this->postJson(route('exam.timeoutGlobal', ['token' => $session->session_token]));

        $response->assertOk()
            ->assertJsonPath('state.session.status', StudentSession::STATUS_FINISHED)
            ->assertJsonPath('state.session.current_score', 35);
    }

    public function test_teacher_can_create_exam_and_input_multiple_questions_manually_to_database(): void
    {
        $admin = User::factory()->admin()->create();

        $examResponse = $this->actingAs($admin)->post(route('teacher.exams.store'), [
            'title' => 'Ujian Fisika Kuantum',
            'subject' => 'Fisika',
            'timer_mode' => 'mode_a',
            'default_question_timer' => 45,
            'total_global_timer' => 1800,
            'starting_score' => 20,
            'randomize_questions' => true,
        ]);

        $exam = Exam::query()->where('title', 'Ujian Fisika Kuantum')->firstOrFail();
        $examResponse->assertRedirect(route('teacher.exams', ['exam_id' => $exam->id]));

        $questionsResponse = $this->actingAs($admin)->post(route('teacher.questions.store'), [
            'exam_id' => $exam->id,
            'replace_existing' => false,
            'questions' => [
                [
                    'level_tier' => 1,
                    'question_text' => 'Apa satuan SI untuk gaya?',
                    'opt_a' => 'Joule',
                    'opt_b' => 'Newton',
                    'opt_c' => 'Watt',
                    'opt_d' => 'Pascal',
                    'correct_opt' => 'B',
                    'score_gain' => 5,
                    'score_penalty' => 5,
                    'duration_sec' => 45,
                ],
                [
                    'level_tier' => 2,
                    'question_text' => 'Berapa kecepatan cahaya di ruang hampa?',
                    'opt_a' => '3 x 10^8 m/s',
                    'opt_b' => '3 x 10^6 m/s',
                    'opt_c' => '3 x 10^5 m/s',
                    'opt_d' => '3 x 10^4 m/s',
                    'correct_opt' => 'A',
                    'score_gain' => 10,
                    'score_penalty' => 5,
                    'duration_sec' => 45,
                ],
            ],
        ]);

        $questionsResponse->assertRedirect(route('teacher.exams', ['exam_id' => $exam->id]));
        $this->assertSame(2, $exam->questions()->count());
        $this->assertDatabaseHas('questions', [
            'exam_id' => $exam->id,
            'level_tier' => 1,
            'question_text' => 'Apa satuan SI untuk gaya?',
            'correct_opt' => 'B',
        ]);

        // Verify the created exam package and question count appear on Student Registration & Login page
        $loginPage = $this->get(route('exam.login'));
        $loginPage->assertOk()
            ->assertSee('Ujian Fisika Kuantum')
            ->assertSee('Fisika')
            ->assertSee('[2 Soal]');
    }

    public function test_teacher_panel_displays_student_participants_table_from_nis_to_final_score(): void
    {
        $supervisor = User::factory()->supervisor()->create();

        $exam = Exam::factory()->modeA(30)->create([
            'title' => 'Paket Ujian Matematika Dasar',
            'subject' => 'Matematika',
            'starting_score' => 20,
        ]);

        $sessionFinished = StudentSession::factory()->for($exam)->create([
            'current_score' => 85,
            'safe_score' => 70,
            'current_step' => 15,
            'status' => StudentSession::STATUS_FINISHED,
        ]);
        $sessionFinished->student->update([
            'nis' => '20265501',
            'full_name' => 'Alya Putri Maharani',
            'class_name' => 'XII MIPA 1',
        ]);

        $sessionEliminated = StudentSession::factory()->for($exam)->create([
            'current_score' => 0,
            'safe_score' => 0,
            'current_step' => 4,
            'status' => StudentSession::STATUS_ELIMINATED,
        ]);
        $sessionEliminated->student->update([
            'nis' => '20265502',
            'full_name' => 'Bima Sakti Pratama',
            'class_name' => 'XII IPS 2',
        ]);

        $response = $this->actingAs($supervisor)->get(route('teacher.exams', ['exam_id' => $exam->id]));

        $response->assertOk()
            ->assertSee('Tabel Daftar Siswa Peserta Ujian')
            ->assertSee('20265501')
            ->assertSee('Alya Putri Maharani')
            ->assertSee('XII MIPA 1')
            ->assertSee('85 Poin')
            ->assertSee('20265502')
            ->assertSee('Bima Sakti Pratama')
            ->assertSee('XII IPS 2')
            ->assertSee('0 Poin');
    }

    public function test_multiple_students_logging_in_simultaneously_receive_randomized_different_question_orders(): void
    {
        $exam = Exam::factory()->modeA(30)->create(['starting_score' => 20]);

        for ($tier = 1; $tier <= 15; $tier++) {
            Question::factory()->for($exam)->forTier($tier)->create();
        }

        $this->post(route('exam.start'), [
            'nis' => '20268801',
            'full_name' => 'Siswa Pertama',
            'class_name' => 'XII MIPA 1',
            'exam_id' => $exam->id,
        ]);
        $session1 = StudentSession::query()->latest('id')->firstOrFail();

        $this->post(route('exam.start'), [
            'nis' => '20268802',
            'full_name' => 'Siswa Kedua',
            'class_name' => 'XII MIPA 1',
            'exam_id' => $exam->id,
        ]);
        $session2 = StudentSession::query()->latest('id')->firstOrFail();

        $this->assertCount(15, $session1->question_order);
        $this->assertCount(15, $session2->question_order);
        $this->assertNotSame($session1->question_order, $session2->question_order);
        $this->assertNotSame($session1->question_order[0], $session2->question_order[0]);
    }

    public function test_teacher_panel_requires_login_and_authenticates_with_username_and_password(): void
    {
        $guestResponse = $this->get(route('teacher.exams'));
        $guestResponse->assertRedirect(route('login'));

        $user = User::factory()->supervisor()->create([
            'name' => 'Supervisor Utama',
            'username' => 'supervisor_cbt',
            'password' => 'rahasia123',
        ]);

        $loginResponse = $this->post(route('login.submit'), [
            'username' => 'supervisor_cbt',
            'password' => 'rahasia123',
        ]);

        $loginResponse->assertRedirect(route('teacher.exams'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_supervisor_has_full_delete_access_while_admin_cannot_delete_data(): void
    {
        $supervisor = User::factory()->supervisor()->create(['username' => 'sup1']);
        $admin = User::factory()->admin()->create(['username' => 'adm1']);

        $exam = Exam::factory()->create();
        $question = Question::factory()->for($exam)->forTier(1)->create();
        $session = StudentSession::factory()->for($exam)->create();

        // Admin tries to delete question, session, exam, and user -> all blocked
        $this->actingAs($admin)->delete(route('teacher.questions.destroy', $question));
        $this->assertDatabaseHas('questions', ['id' => $question->id]);

        $this->actingAs($admin)->delete(route('teacher.sessions.destroy', $session));
        $this->assertDatabaseHas('student_sessions', ['id' => $session->id]);

        $this->actingAs($admin)->delete(route('teacher.exams.destroy', $exam));
        $this->assertDatabaseHas('exams', ['id' => $exam->id]);

        // Supervisor deletes question, session, and exam -> all succeed
        $this->actingAs($supervisor)->delete(route('teacher.questions.destroy', $question));
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);

        $this->actingAs($supervisor)->delete(route('teacher.sessions.destroy', $session));
        $this->assertDatabaseMissing('student_sessions', ['id' => $session->id]);

        $this->actingAs($supervisor)->delete(route('teacher.exams.destroy', $exam));
        $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
    }
}
