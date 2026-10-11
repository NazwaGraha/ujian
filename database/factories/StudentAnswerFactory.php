<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\StudentAnswer;
use App\Models\StudentSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentAnswer>
 */
class StudentAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_id' => StudentSession::factory(),
            'question_id' => Question::factory(),
            'chosen_opt' => 'A',
            'is_correct' => true,
            'score_diff' => 5,
            'time_spent_sec' => 12,
        ];
    }
}
