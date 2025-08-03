<?php

namespace Database\Factories;

use App\Models\Center;
use App\Models\User;
use App\Enums\RolesEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Center>
 */
class CenterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $states = [
            'Maharashtra',
            'Delhi',
            'Karnataka',
            'Tamil Nadu',
            'Telangana',
            'Gujarat',
            'West Bengal',
            'Uttar Pradesh',
            'Rajasthan',
            'Andhra Pradesh'
        ];

        $cities = [
            'Mumbai',
            'Delhi',
            'Bangalore',
            'Chennai',
            'Hyderabad',
            'Ahmedabad',
            'Kolkata',
            'Lucknow',
            'Jaipur',
            'Vishakhapatnam'
        ];

        return [
            'user_id' => User::factory()->create()->assignRole(RolesEnum::Center->value)->id,
            'name' => fake()->company() . ' ' . fake()->randomElement(['Institute', 'Academy', 'Training Center', 'Learning Center', 'Education Center']),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'state' => fake()->randomElement($states),
            'country' => 'India',
            'email' => fake()->companyEmail(),
            'owner_name' => fake()->name(),
            'aadhar' => fake()->numerify('##########'),
            'pan' => fake()->regexify('[A-Z]{5}[0-9]{4}[A-Z]{1}'),
            'status' => fake()->randomElement(['active', 'inactive']),
            'institute_logo' => null, // Can be set manually if needed
            'front_office_photo' => null, // Can be set manually if needed
            'back_office_photo' => null, // Can be set manually if needed
        ];
    }

    /**
     * Indicate that the center is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the center is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Set specific state for the center.
     */
    public function inState(string $state): static
    {
        return $this->state(fn(array $attributes) => [
            'state' => $state,
        ]);
    }
}
