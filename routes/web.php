<?php

use App\Http\Controllers\ExamController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ExamController::class, 'showLogin'])->name('exam.login');
Route::get('/login', [ExamController::class, 'showTeacherLogin'])->name('login');
Route::post('/login', [ExamController::class, 'authenticateTeacher'])->name('login.submit');
Route::post('/logout', [ExamController::class, 'logoutTeacher'])->name('logout');
Route::post('/exam/start', [ExamController::class, 'startSession'])->name('exam.start');

Route::prefix('exam/arena/{token}')->group(function (): void {
    Route::get('/', [ExamController::class, 'arena'])->name('exam.arena');
    Route::post('/answer', [ExamController::class, 'submitAnswer'])->name('exam.answer');
    Route::post('/lifeline-5050', [ExamController::class, 'useLifeline5050'])->name('exam.lifeline5050');
    Route::post('/timeout-global', [ExamController::class, 'finishGlobalTimeout'])->name('exam.timeoutGlobal');
});

Route::prefix('teacher')->middleware('auth')->group(function (): void {
    Route::get('/exams', [ExamController::class, 'manageExams'])->name('teacher.exams');
    Route::post('/exams', [ExamController::class, 'storeExam'])->name('teacher.exams.store');
    Route::put('/exams/{exam}', [ExamController::class, 'updateExam'])->name('teacher.exams.update');
    Route::delete('/exams/{exam}', [ExamController::class, 'destroyExam'])->name('teacher.exams.destroy');

    Route::post('/questions', [ExamController::class, 'storeQuestions'])->name('teacher.questions.store');
    Route::put('/questions/{question}', [ExamController::class, 'updateQuestion'])->name('teacher.questions.update');
    Route::delete('/questions/{question}', [ExamController::class, 'destroyQuestion'])->name('teacher.questions.destroy');
    Route::post('/questions/import', [ExamController::class, 'importQuestions'])->name('teacher.questions.import');
    Route::get('/questions/template', [ExamController::class, 'downloadTemplate'])->name('teacher.questions.template');

    Route::delete('/sessions/{session}', [ExamController::class, 'destroySession'])->name('teacher.sessions.destroy');

    Route::post('/users', [ExamController::class, 'storeUser'])->name('teacher.users.store');
    Route::put('/users/{user}', [ExamController::class, 'updateUser'])->name('teacher.users.update');
    Route::delete('/users/{user}', [ExamController::class, 'destroyUser'])->name('teacher.users.destroy');
});
