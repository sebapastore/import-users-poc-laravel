<?php

namespace App\Models;

use App\Enums\ImportStatus;
use Carbon\Carbon;
use Database\Factories\ImportFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read string $id
 * @property string $status
 * @property string $file_path
 * @property ?int $total_rows
 * @property int $processed_rows
 * @property int $success_count
 * @property int $error_count
 * @property array $errors
 * @property ?Carbon $started_at
 * @property ?Carbon $finished_at
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 */

class Import extends Model
{
    /** @use HasFactory<ImportFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'status' => ImportStatus::class,
            'errors' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
