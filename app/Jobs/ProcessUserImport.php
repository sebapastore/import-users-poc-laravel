<?php

namespace App\Jobs;

use App\Exceptions\FileOpenException;
use App\Exceptions\InvalidCsvHeaderException;
use App\Models\Import;
use App\Services\UserImportFinalizationService;
use App\Services\UserImportPreparationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessUserImport implements ShouldQueue
{
    use Queueable;

    public function __construct(readonly string $importId) {}

    /**
     * @throws FileOpenException
     * @throws InvalidCsvHeaderException
     * @throws Throwable
     */
    public function handle(): void
    {
        $import = Import::query()->findOrFail($this->importId);

        $jobs = new UserImportPreparationService($import)
            ->validateHeaders()
            ->toJobs($this->importId);

        $import->initProcessing(count($jobs));

        Bus::batch($jobs)
            ->then(function ($batch) use ($import) {
                app(UserImportFinalizationService::class)->finalize($import->id);
            })
            ->name('Import users')
            ->dispatch();
    }

    /**
     * @throws Throwable
     */
    public function failed(\Throwable $th)
    {
        $import = Import::query()->findOrFail($this->importId);

        if ($import !== null) {
            if ($th instanceof InvalidCsvHeaderException) {
                $import->markAsFailed($th->getMessage(), 0);
            } else {
                $import->markAsFailed('An unexpected error occurred during the import process.', null);
            }
        }

        Log::error("Error while processing import with id '{$this->importId}'");

        throw $th; // Throw the error so a monitoring tool catches it and send and alert the team
    }
}
