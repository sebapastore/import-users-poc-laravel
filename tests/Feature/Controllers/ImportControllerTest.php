<?php

use App\Jobs\ProcessUserImport;
use App\Models\Import;
use App\Enums\ImportStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

it('creates a new import record when posting a csv file', function () {
    Storage::fake('local');
    Queue::fake();

    // fake CSV file
    $file = UploadedFile::fake()->createWithContent(
        'test.csv',
        "name,email,role,salary,start_date\nJohn,john@test.com,admin,1000.00,2025-09-22\n"
    );

    // make request
    $response = $this->withHeaders(['Authorization' => 'Bearer test-token'])
        ->postJson('/api/imports', [
            'file' => $file,
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['import_id']);

    // check import record and file
    $importId = $response->json('import_id');
    $import = Import::query()->findOrFail($importId);

    Queue::assertPushed(ProcessUserImport::class, function ($job) use ($importId) {
        return $job->importId === $importId;
    });

    expect($import->status)->toBe(ImportStatus::Pending)
        ->and($import->processed_rows)->toBe(0)
        ->and(Storage::disk('local')->exists($import->file_path))->toBeTrue();
});

it('rejects request with wrong token', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->createWithContent(
        'test.csv',
        "name,email,role,salary,start_date\nJohn,john@test.com,admin,1000.00,2025-09-22\n"
    );

    $response = $this->withHeaders([
        'Authorization' => 'Bearer WRONGTOKEN',
    ])->postJson('/api/imports', ['file' => $file]);

    $response->assertStatus(401);
});

it('rejects request without file', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer test-token',
    ])->postJson('/api/imports', []); // no file

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('file');
});

it('rejects request with wrong file type', function () {
    $file = UploadedFile::fake()->create('test.pdf', 10, 'application/pdf');

    $response = $this->withHeaders([
        'Authorization' => 'Bearer test-token',
    ])->postJson('/api/imports', ['file' => $file]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('file');
});
