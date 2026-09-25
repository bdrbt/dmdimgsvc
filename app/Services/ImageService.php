<?php

namespace App\Services;

use App\Jobs\GenerateThumbnailJob;
use App\Models\Image;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ImageService
{
    /**
     * SHA-256 hash for duplicartion preventing
     */
    public function calculateHash(string $filePath): string
    {
        return hash_file('sha256', $filePath);
    }

    /**
     * Check real image content
     */
    public function validateImageContent(UploadedFile $file): void
    {
        $filePath = $file->getRealPath();

        // 1. check signatures
        $imageInfo = @getimagesize($filePath);

        if ($imageInfo === false) {
            throw ValidationException::withMessages([
                'image' => ['Uploaded file is not an image.'],
            ]);
        }

        $imageType = $imageInfo[2]; // IMAGETYPE_JPEG или IMAGETYPE_PNG

        if (!in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            throw ValidationException::withMessages([
                'image' => ['Only PNG and JPEG images allowed.'],
            ]);
        }

        // 2. Deep check content of file
        if ($imageType === IMAGETYPE_JPEG) {
            $gdImage = @imagecreatefromjpeg($filePath);
        } elseif ($imageType === IMAGETYPE_PNG) {
            $gdImage = @imagecreatefrompng($filePath);
        } else {
            $gdImage = false;
        }

        if (!$gdImage) {
            throw ValidationException::withMessages([
                'image' => ['File corrupted'],
            ]);
        }
    }

    /**
     * Save Image with record blocking to avoid duplicating
     */
    public function storeImage(UploadedFile $file, User $user): Image
    {
        $hash = $this->calculateHash($file->getRealPath());

        return DB::transaction(function () use ($file, $user, $hash) {
            // lock record
            $storedFile = StoredFile::where('file_hash', $hash)
                ->lockForUpdate()
                ->first();

            if ($storedFile) {
                // if file exist' just increment ref_count
                $storedFile->increment('ref_count');
            } else {
                // not exist - create and save them
                $extension = $file->getClientOriginalExtension();
                $diskPath = "images/{$hash}." . ($extension ?: 'bin');

                Storage::disk(config('filesystems.default', 's3'))->putFileAs(
                    'images',
                    $file,
                    "{$hash}." . ($extension ?: 'bin')
                );

                $storedFile = StoredFile::create([
                    'file_hash' => $hash,
                    'disk_path' => $diskPath,
                    'mime_type' => $file->getClientMimeType(),
                    'size'      => $file->getSize(),
                    'ref_count' => 1,
                ]);
                GenerateThumbnailJob::dispatch($storedFile);
            }

            // create relation between current user and image
            return Image::create([
                'user_id'        => $user->id,
                'stored_file_id' => $storedFile->id,
                'original_name'  => $file->getClientOriginalName(),
            ]);
        });
    }

    /**
     * drop users image
     */
    public function deleteImage(Image $image): void
    {
        DB::transaction(function () use ($image) {
            $storedFile = StoredFile::where('id', $image->stored_file_id)
                ->lockForUpdate()
                ->first();

            // remove link to user first
            $image->delete();

            if ($storedFile) {
                if ($storedFile->ref_count > 1) {
                    // decrease ref_count
                    $storedFile->decrement('ref_count');
                } else {
                    // if it was last link remove file from storage
                    $fsDefault = config('filesystems.default');
                    Storage::disk($fsDefault)->delete($storedFile->disk_path);
                    if ($storedFile->thumbnail_path) {
                       Storage::disk($fsDefault)->delete($storedFile->thumbnail_path);
                    }
                    $storedFile->delete();
                }
            }
        });
    }
}
