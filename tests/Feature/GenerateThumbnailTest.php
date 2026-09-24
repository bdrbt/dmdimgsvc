<?php

use App\Jobs\GenerateThumbnailJob;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('dispatches thumbnail job when new unique image is stored', function () {
    Queue::fake();

    $service = new ImageService('local');
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('test_photo.jpg', 800, 600);

    $service->storeImage($file, $user);

    Queue::assertPushed(GenerateThumbnailJob::class);
});

test('generates and saves thumbnail file correctly', function () {
    $service = new ImageService('local');
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('test_photo.jpg', 800, 600);

    $image = $service->storeImage($file, $user);

    // Выполняем задачу прямо в тесте
    $job = new GenerateThumbnailJob($image->storedFile);
    $job->handle();

    $image->storedFile->refresh();

    expect($image->storedFile->thumbnail_path)->not()->toBeNull();
    Storage::disk('local')->assertExists($image->storedFile->thumbnail_path);
});
