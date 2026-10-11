<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'level_tier' => fake()->numberBetween(1, 15),
            'question_text' => fake()->sentence(10).'?',
            'opt_a' => 'Pilihan Jawaban A',
            'opt_b' => 'Pilihan Jawaban B',
            'opt_c' => 'Pilihan Jawaban C',
            'opt_d' => 'Pilihan Jawaban D',
            'correct_opt' => 'A',
            'score_gain' => 5,
            'score_penalty' => 5,
            'duration_sec' => 30,
        ];
    }

    public function forTier(int $tier): static
    {
        return $this->state(fn (array $attributes): array => [
            'level_tier' => $tier,
        ]);
    }
}
