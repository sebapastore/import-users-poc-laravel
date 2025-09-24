<?php

namespace App\Services;

use App\Models\Import;
use Illuminate\Support\Facades\Storage;

class UserImportFinalizationService
{
    public function finalize(string $importId): void
    {
        $import = Import::query()->find($importId);
        $import->finalize();
        Storage::disk('local')->delete($import->file_path); // optional
    }

}
