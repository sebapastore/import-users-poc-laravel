<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'role' => fake()->randomElement(UserRole::class)->value,
            'salary' => fake()->randomFloat(100_000, 0),
            'start_date' => fake()->optional()
                ->dateTimeBetween('-1 year', 'now')
                ->format('Y-m-d'),
        ];
    }
}
