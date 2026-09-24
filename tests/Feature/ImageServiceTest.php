<?php

use App\Models\Image;
use App\Models\StoredFile;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('deduplicates identical files and manages ref_count correctly', function () {
    $service = new ImageService('public');
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // create two identical filess
    $file1 = UploadedFile::fake()->createWithContent('photo.jpg', 'fake-image-content-data');
    $file2 = UploadedFile::fake()->createWithContent('same_photo.jpg', 'fake-image-content-data');

    // 1. user1 upload image1
    $image1 = $service->storeImage($file1, $user1);

    expect(StoredFile::count())->toBe(1)
        ->and(Image::count())->toBe(1)
        ->and($image1->storedFile->ref_count)->toBe(1);

    // 2. user2 uplad image2 which the same as image1
    $image2 = $service->storeImage($file2, $user2);

    // check id StoredFile equal 1, аnd ref_count incresed to 2
    expect(StoredFile::count())->toBe(1)
        ->and(Image::count())->toBe(2)
        ->and($image2->storedFile->refresh()->ref_count)->toBe(2);

    // 3. user1 removes imageq
    $service->deleteImage($image1);

    // Record should exists but ref_count should be decreased to 1
    expect(StoredFile::count())->toBe(1)
        ->and(Image::count())->toBe(1)
        ->and($image2->storedFile->refresh()->ref_count)->toBe(1);

    // 4. user2 removes their reference to image
    $service->deleteImage($image2);

    // now record and related file should be completely wiped
    expect(StoredFile::count())->toBe(0)
        ->and(Image::count())->toBe(0);
});
