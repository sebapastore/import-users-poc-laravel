<?php

namespace Database\Factories;

use App\Enums\ImportStatus;
use App\Models\Import;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Import>
 */
class ImportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalRows = $this->faker->numberBetween(5, 20);
        $successCount = $this->faker->numberBetween(0, $totalRows);
        $errorCount = $totalRows - $successCount;
        $processedRows = $successCount + $errorCount;

        return [
            'status' => $this->faker->randomElement(ImportStatus::class)->value,
            'total_rows' => $totalRows,
            'processed_rows' => $processedRows,
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'errors' => [],
            'started_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'finished_at' => $this->faker->optional()->dateTimeBetween('now', '+1 hour'),
        ];
    }

    public function pending(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => ImportStatus::Pending,
                'total_rows' => null,
                'processed_rows' => 0,
                'success_count' => 0,
                'error_count' => 0,
                'errors' => [],
                'started_at' => null,
                'finished_at' => null,
            ];
        });
    }
}
