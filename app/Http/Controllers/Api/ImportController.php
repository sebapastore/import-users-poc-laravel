<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreImportRequest;
use App\Http\Resources\ImportResource;
use App\Jobs\ProcessUserImport;
use App\Models\Import;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportController
{
    public function store(StoreImportRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $filePath = $file->store('imports');

        $import = Import::newPendingImport($filePath);

        dispatch(new ProcessUserImport($import->id));

        return response()->json(['import_id' => $import->id]);
    }

    public function status(Import $import): JsonResource
    {
        return new ImportResource($import);
    }
}
