<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreImportRequest;
use App\Models\Import;
use Illuminate\Http\JsonResponse;

class ImportController
{
    public function store(StoreImportRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $filePath = $file->store('imports');

        $import = Import::newPendingImport($filePath);

        //TODO: dispatch job

        return response()->json(['import_id' => $import->id]);
    }
}
