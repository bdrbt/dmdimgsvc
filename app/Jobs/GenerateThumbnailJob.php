<?php

namespace App\Jobs;

use App\Models\StoredFile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class GenerateThumbnailJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
      public StoredFile $storedFile
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $disk = Storage::disk(config('filesystems.default', 'local'));

        if (!$disk->exists($this->storedFile->disk_path)) {
            return;
        }

        // temporary folder
        #$originalPath = $disk->path($this->storedFile->disk_path);
        $thumbFilename = 'thumb_' . basename($this->storedFile->disk_path);
        $thumbRelativePath = 'images/thumbnails/' . $thumbFilename;
        $tempThumbPath = storage_path('app/temp_' . $thumbFilename);

        // creating thumbnails
        $sourceImage = @imagecreatefromstring($disk->get($this->storedFile->disk_path));

        if (!$sourceImage) {
            return;
        }

        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);

        // fixed width
        $thumbWidth = 300;
        $thumbHeight = (int) floor($height * ($thumbWidth / $width));

        $thumbImage = imagecreatetruecolor($thumbWidth, $thumbHeight);

        // keep transparency PNG/WebP
        imagealphablending($thumbImage, false);
        imagesavealpha($thumbImage, true);

        imagecopyresampled($thumbImage, $sourceImage, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

        // ... and save thumbnail
        imagejpeg($thumbImage, $tempThumbPath, 85);

        // optimize using Spatie Optimizer
        $optimizer = OptimizerChainFactory::create();
        $optimizer->optimize($tempThumbPath);

        // Save thumbnail to final path
        $disk->put($thumbRelativePath, file_get_contents($tempThumbPath));

        // Cleanup
        if (file_exists($tempThumbPath)) {
            unlink($tempThumbPath);
        }

        // save path to db
        $this->storedFile->update([
            'thumbnail_path' => $thumbRelativePath,
        ]);
    }
}
