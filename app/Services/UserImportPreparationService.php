<?php

namespace App\Services;

use App\Exceptions\FileOpenException;
use App\Exceptions\InvalidCsvHeaderException;
use App\Jobs\ImportSingleUser;
use App\Models\Import;
use Generator;
use Illuminate\Support\Facades\Storage;

final class UserImportPreparationService
{
    /** @var false|resource $filePointer */
    private $filePointer;

    const array HEADERS = [
        'name',
        'email',
        'role',
        'salary',
        'start_date'
    ];

    /**
     * @throws FileOpenException
     */
    public function __construct(private readonly Import $import)
    {
        $this->openFile();
    }

    /**
     * @throws InvalidCsvHeaderException
     */
    public function validateHeaders(): self
    {
        rewind($this->filePointer);

        $header = fgetcsv($this->filePointer);

        foreach (self::HEADERS as $index => $expectedHeader) {
            $actualHeader = $header[$index] ?? 'null';
            if ($actualHeader !== $expectedHeader) {
                throw new InvalidCsvHeaderException($expectedHeader, $actualHeader, $index);
            }
        }

        return $this;
    }

    public function toJobs(string $importId): array
    {
        $jobs = [];

        foreach ($this->readRows() as $rowData) {
            $jobs[] = new ImportSingleUser($importId, $rowData['row'], $rowData['index']);
        }

        return $jobs;
    }

    private function readRows(): array
    {
        rewind($this->filePointer);
        fgetcsv($this->filePointer); // read the header and skip it

        $index = 0;
        $rows = [];
        while (($row = fgetcsv($this->filePointer)) !== false) {
            $index++;
            $rows[] = ['row' => $row, 'index' => $index];
        }

        return $rows;
    }

    /**
     * @throws FileOpenException
     */
    private function openFile(): void
    {
        $this->filePointer = Storage::disk('local')->readStream($this->import->file_path);

        if ($this->filePointer === false) {
            throw new FileOpenException("Could not open file: {$this->import->file_path}");
        }
    }

    private function closeFile(): void
    {
        fclose($this->filePointer);
    }

    public function __destruct()
    {
        $this->closeFile();
    }
}
