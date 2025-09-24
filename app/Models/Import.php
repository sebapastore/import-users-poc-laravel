<?php

namespace App\Models;

use App\Enums\ImportStatus;
use Carbon\Carbon;
use Database\Factories\ImportFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    public static function newPendingImport(string $filePath): self
    {
        $import = new self;
        $import->status = ImportStatus::Pending;
        $import->file_path = $filePath;
        $import->total_rows = null; // Total rows is null (unknown) before start processing
        $import->processed_rows = 0;
        $import->success_count = 0;
        $import->error_count = 0;
        $import->errors = [];
        $import->save();

        return $import;
    }

    public function initProcessing(int $numberOfRows): bool
    {
        $this->status = ImportStatus::Processing;
        $this->total_rows = $numberOfRows;
        $this->started_at = now();
        return $this->save();
    }

    /**
     * The file is not processable
     * If it is because of a header issue, then the $rowNumber is zero
     * If it is a more generic issue or unknown then the $rowNumber is null
     */
    public function markAsFailed(string $error, ?int $rowNumber): bool
    {
        $this->status = ImportStatus::Failed;
        $this->addError(message: $error, row: $rowNumber);
        $this->finished_at = now();
        return $this->save();
    }

    public function finalize(): bool
    {
        $this->status = ImportStatus::Finished;
        $this->finished_at = now();
        return $this->save();
    }

    public function recordSuccessfulRow(int $row): bool
    {
        return DB::transaction(function () use ($row) {
            // Lock the row to prevent race conditions
            // Race condition may happen if we have multiple queue workers running in parallel
            $import = $this->newQuery()->where('id', $this->id)->lockForUpdate()->first();

            $import->success_count++;
            $import->processed_rows++;

            return $import->save();
        });
    }

    public function recordFailedRow(string $error, int $row): bool
    {
        return DB::transaction(function () use ($error, $row) {
            // Lock the row to prevent race conditions
            // Race condition may happen if we have multiple queue workers running in parallel
            $import = $this->newQuery()->where('id', $this->id)->lockForUpdate()->first();

            $import->addError($error, $row);
            $import->error_count++;
            $import->processed_rows++;

            return $import->save();
        });
    }

    public function addError(string $message, ?int $row): void
    {
        $errors = $this->errors ?? [];

        $errors[] = [
            'row' => $row,
            'message' => $message,
        ];

        $this->errors = $errors;
    }

}
