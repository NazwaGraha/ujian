<?php

namespace Database\Seeders;

use App\Exports\QuestionsTemplateExport;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['username' => 'supervisor'],
            [
                'name' => 'Supervisor Utama CBT',
                'email' => 'supervisor@ujianonline.sch.id',
                'password' => Hash::make('supervisor123'),
                'role' => User::ROLE_SUPERVISOR,
            ]
        );

        User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Guru / Admin Ujian',
                'email' => 'admin@ujianonline.sch.id',
                'password' => Hash::make('admin123'),
                'role' => User::ROLE_ADMIN,
            ]
        );

        Student::query()->updateOrCreate(
            ['nis' => '20261001'],
            [
                'full_name' => 'Budi Santoso',
                'class_name' => 'XII MIPA 1',
            ]
        );

        $examModeA = Exam::query()->updateOrCreate(
            ['title' => 'Olimpiade Sains & Logika Millionaire (Mode A)'],
            [
                'subject' => 'Matematika & Sains Terpadu',
                'timer_mode' => Exam::TIMER_MODE_PER_QUESTION,
                'default_question_timer' => 30,
                'total_global_timer' => 1200,
                'starting_score' => 20,
                'randomize_questions' => false,
            ]
        );

        $examModeB = Exam::query()->updateOrCreate(
            ['title' => 'Tryout Nasional Informatika & Matematika (Mode B)'],
            [
                'subject' => 'Informatika & Logika',
                'timer_mode' => Exam::TIMER_MODE_GLOBAL,
                'default_question_timer' => 30,
                'total_global_timer' => 1200,
                'starting_score' => 20,
                'randomize_questions' => true,
            ]
        );

        $sampleRows = (new QuestionsTemplateExport)->array();

        foreach ([$examModeA, $examModeB] as $exam) {
            if ($exam->questions()->count() > 0) {
                continue;
            }

            foreach ($sampleRows as $row) {
                Question::query()->create([
                    'exam_id' => $exam->id,
                    'level_tier' => (int) $row[0],
                    'question_text' => (string) $row[1],
                    'opt_a' => (string) $row[2],
                    'opt_b' => (string) $row[3],
                    'opt_c' => (string) $row[4],
                    'opt_d' => (string) $row[5],
                    'correct_opt' => (string) $row[6],
                    'score_gain' => (int) $row[7],
                    'score_penalty' => (int) $row[8],
                    'duration_sec' => (int) $row[9],
                ]);
            }
        }
    }
}
