<?php

namespace Database\Factories;

use App\Models\Exam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'Olimpiade Sains & Logika Millionaire',
            'subject' => 'Matematika & Sains Terpadu',
            'timer_mode' => Exam::TIMER_MODE_PER_QUESTION,
            'default_question_timer' => 30,
            'total_global_timer' => 1200,
            'starting_score' => 20,
            'randomize_questions' => false,
        ];
    }

    public function modeA(int $secondsPerQuestion = 30): static
    {
        return $this->state(fn (array $attributes): array => [
            'timer_mode' => Exam::TIMER_MODE_PER_QUESTION,
            'default_question_timer' => $secondsPerQuestion,
        ]);
    }

    public function modeB(int $totalGlobalSeconds = 1200): static
    {
        return $this->state(fn (array $attributes): array => [
            'timer_mode' => Exam::TIMER_MODE_GLOBAL,
            'total_global_timer' => $totalGlobalSeconds,
        ]);
    }
}
