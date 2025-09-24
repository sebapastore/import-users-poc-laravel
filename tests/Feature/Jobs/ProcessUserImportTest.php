<?php

namespace Tests\Feature\Jobs;

use App\Enums\ImportStatus;
use App\Jobs\ImportSingleUser;
use App\Services\UserImportFinalizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ProcessUserImport;
use App\Models\Import;

uses(RefreshDatabase::class);

beforeEach(function () {
    Bus::fake();
    Storage::fake();
});

it('dispatches a batch with jobs', function () {
    // Create a fake import record
    $import = Import::factory()->pending()->create([
        'file_path' => 'imports/test.csv',
        'total_rows' => 1,
    ]);

    // Put a fake CSV file
    Storage::put('imports/test.csv', "name,email,role,salary,start_date\nJohn,john@test.com,admin,1000.00");

    $job = new ProcessUserImport($import->id);
    $job->handle();

    // Assert batch was dispatched
    Bus::assertBatched(function ($batch) use ($import) {
        expect($batch->name)->toBe('Import users');

        // Assert batch contains correct job types
        $batchJobs = collect($batch->jobs);
        expect($batchJobs->first())->toBeInstanceOf(ImportSingleUser::class);

        return true;
    });
});

it('updates import status and metadata when starts processing', function () {
    // Create a fake import record
    /** @var Import $import */
    $import = Import::factory()->pending()->create(['file_path' => 'imports/test.csv']);

    // Put a fake CSV file
    Storage::put('imports/test.csv', "name,email,role,salary,start_date\nJohn,john@test.com,admin,1000.00");

    $job = new ProcessUserImport($import->id);
    $job->handle();
    $import->refresh();

    // Assert import status and more
    expect($import->status)->toBe(ImportStatus::Processing)
        ->and($import->total_rows)->toBe(1)
        ->and($import->started_at)->not()->toBeNull()
        ->and($import->finished_at)->toBeNull();
});

it('calls finalization service when the batch is completed', function () {
    // Create a fake import record
    /** @var Import $import */
    $import = Import::factory()->pending()->create([
        'file_path' => 'imports/test.csv',
        'total_rows' => 1,
    ]);

    // Put a fake CSV file
    Storage::put('imports/test.csv', "name,email,role,salary,start_date\nJohn,john@test.com,admin,1000.00");

    $this->mock(UserImportFinalizationService::class)
        ->shouldReceive('finalize')
        ->once()
        ->withArgs(fn($arg) => $arg === $import->id);

    $job = new ProcessUserImport($import->id);
    $job->handle();

    Bus::assertBatched(function ($batch) use ($import) {
        // get then callback
        [$thenCallback] = $batch->thenCallbacks();
        // execute it
        $thenCallback->getClosure()->call($this, $this);
        return true;
    });
});

