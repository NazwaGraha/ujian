<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\Student;
use App\Models\StudentSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StudentSession>
 */
class StudentSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'exam_id' => Exam::factory(),
            'session_token' => Str::random(40),
            'current_score' => 20,
            'safe_score' => 0,
            'current_step' => 1,
            'status' => StudentSession::STATUS_ONGOING,
            'lifeline_5050_used' => false,
            'question_order' => null,
            'started_at' => now(),
            'finished_at' => null,
        ];
    }

    public function eliminated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'current_score' => 0,
            'status' => StudentSession::STATUS_ELIMINATED,
            'finished_at' => now(),
        ]);
    }

    public function finished(int $score = 95): static
    {
        return $this->state(fn (array $attributes): array => [
            'current_score' => $score,
            'current_step' => 15,
            'status' => StudentSession::STATUS_FINISHED,
            'finished_at' => now(),
        ]);
    }
}
