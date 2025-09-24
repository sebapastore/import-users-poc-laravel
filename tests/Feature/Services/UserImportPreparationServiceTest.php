<?php

namespace Tests\Feature\Services;


use App\Exceptions\InvalidCsvHeaderException;
use App\Models\Import;
use App\Services\UserImportPreparationService;
use App\Jobs\ImportSingleUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

it('validates correct CSV headers', function () {
    $csvPath = 'imports/test.csv';
    Storage::disk('local')->put($csvPath, "name,email,role,salary,start_date\nJohn,john@test.com,admin,1000,2025-09-23");

    $import = Import::factory()->create([
        'file_path' => $csvPath,
    ]);

    $service = new UserImportPreparationService($import);

    expect($service->validateHeaders())->toBeInstanceOf(UserImportPreparationService::class);
});

it('throws an exception for invalid headers', function (int $columnIndex, string $expectedHeader, string $wrongHeader) {
    $csvPath = 'imports/test.csv';
    Storage::disk('local')->put($csvPath, "name,email,wrong_column,salary,start_date");

    $import = Import::factory()->create([
        'file_path' => $csvPath,
    ]);

    $service = new UserImportPreparationService($import);

    $this->expectException(InvalidCsvHeaderException::class);
    $this->expectExceptionMessage("Expected header 'role' at column 2, got 'wrong_column'");
    $service->validateHeaders();
})->with([
    [0, 'name', 'wrong_name'],
    [1, 'email', 'wrong_email'],
    [2, 'role', 'wrong_role'],
    [3, 'salary', 'wrong_salary'],
    [4, 'start_date', 'wrong_start_date'],
]);

it('converts CSV rows to ImportSingleUser jobs', function () {
    $csvPath = 'imports/test.csv';
    Storage::disk('local')->put($csvPath, <<<CSV
name,email,role,salary,start_date
John,john@test.com,admin,1000,2025-09-23
Jane,jane@test.com,manager,2000,2025-09-24
CSV
    );

    $import = Import::factory()->create([
        'file_path' => $csvPath,
    ]);

    $service = new UserImportPreparationService($import);
    $service->validateHeaders();

    $jobs = $service->toJobs($import->id);

    expect($jobs)->toHaveCount(2)
        ->and($jobs[0])->toBeInstanceOf(ImportSingleUser::class)
        ->and($jobs[1])->toBeInstanceOf(ImportSingleUser::class)
        ->and($jobs[0]->importId)->toBe($import->id)
        ->and($jobs[1]->rowIndex)->toBe(2);
});

