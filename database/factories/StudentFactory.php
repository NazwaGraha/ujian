<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nis' => (string) fake()->unique()->numerify('2026####'),
            'full_name' => fake()->name(),
            'class_name' => fake()->randomElement(['XII IPA 1', 'XII IPA 2', 'XII IPS 1', 'XI MIPA 1']),
        ];
    }
}
