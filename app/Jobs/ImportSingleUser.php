<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Models\Import;
use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ImportSingleUser implements ShouldQueue
{
    use Batchable, Queueable;

    private Import $import;

    // properties are public so we can easily access them in tests
    public function __construct(
        readonly string $importId,
        readonly array $rowData,
        readonly int $rowIndex,
    ) {}

    public function handle(): void
    {
        $this->import = Import::query()->findOrFail($this->importId);

        $validatedData = $this->validateData();

        if ($validatedData === false) {
            // Row recorded as failed inside validateData()
            return;
        }

        DB::transaction(function () use ($validatedData) {
            User::query()->create($validatedData);
            $this->import->recordSuccessfulRow($this->rowIndex);
        });
    }

    private function validateData(): false|array
    {
        $data = $this->sanitizeRowData();

        $validator = Validator::make($data, [
            'name' => 'required|string|min:1|max:100',
            'email' => 'required|email|unique:users,email',
            'role' => ['required', Rule::enum(UserRole::class)],
            'salary' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            $messages = $validator->errors()->getMessages();

            $errorMessage = collect($messages)
                ->map(fn($errors, $field) => $field.': '.implode(', ', $errors))
                ->implode('; ');

            $this->import->recordFailedRow($errorMessage, $this->rowIndex);
            return false;
        }

        return $validator->validated();
    }

    private function sanitizeRowData(): array
    {
        return [
            'name' => isset($this->rowData[0]) ? trim($this->rowData[0]) : '',
            'email' => isset($this->rowData[1]) ? trim($this->rowData[1]) : '',
            'role' => isset($this->rowData[2]) ? trim($this->rowData[2]) : '',
            'salary' => isset($this->rowData[3]) ? round((float) trim($this->rowData[3]), 2) : 0.00,
            'start_date' => isset($this->rowData[4]) ? trim($this->rowData[4]) : null,
        ];
    }

}
