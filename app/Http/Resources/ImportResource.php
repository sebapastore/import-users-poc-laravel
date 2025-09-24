<?php

namespace App\Http\Resources;

use App\Models\Import;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Import
 */
class ImportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            $this->id,
            $this->status,
            $this->total_rows,
            $this->processed_rows,
            $this->success_count,
            $this->error_count,
            $this->error_count,
            $this->errors,
            $this->started_at,
            $this->finished_at,
        ];
    }
}
