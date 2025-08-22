<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Question>
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
            'category_id' => Category::factory(),
            'question_text' => $this->faker->sentence() . '?',
            'points' => $this->faker->randomElement([1, 2, 3, 5]),
        ];
    }
}
