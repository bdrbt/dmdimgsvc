<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'original_name' => $this->original_name,
            'mime_type'     => $this->storedFile->mime_type,
            'size'          => $this->storedFile->size,
            'url'           => Storage::disk(config('filesystems.default'))->url($this->storedFile->disk_path),
            'created_at'    => $this->created_at->toIso8601String(),
        ];
    }
}
