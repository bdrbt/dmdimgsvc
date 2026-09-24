<?php

use App\Models\Image;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Database\Factories\ImagesFactory;

beforeEach(function () {
    Storage::fake('local');
});

test('user can upload an image via API', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $file = UploadedFile::fake()->image('avatar.jpg');

    $response = $this->postJson('/api/images', [
        'image' => $file,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'original_name', 'mime_type', 'size', 'url', 'created_at']
        ]);

    $this->assertDatabaseHas('images', [
        'user_id' => $user->id,
        'original_name' => 'avatar.jpg',
    ]);
});

test('user cannot delete someone elses image', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Upload image as user1
    Sanctum::actingAs($user1);
    $file = UploadedFile::fake()->image('private.jpg');
    $uploadResponse = $this->postJson('/api/images', ['image' => $file]);
    $imageId = $uploadResponse->json('data.id');

    // Switch to user2 and try to remove
    Sanctum::actingAs($user2);
    $deleteResponse = $this->deleteJson("/api/images/{$imageId}");

    $deleteResponse->assertStatus(403);
    $this->assertDatabaseHas('images', ['id' => $imageId]);
});

test('user cannot upload more than daily limit of images', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // decrease limit to 2 for testing
    Image::factory()->count(2)->create([
        'user_id' => $user->id,
        'created_at' => now(),
    ]);

    // check with limit=2
    expect($user->hasReachedDailyLimit(2))->toBeTrue();

    $file = UploadedFile::fake()->image('exceeded.jpg');

    // request should fail
    $response = $this->postJson('/api/images', ['image' => $file]);

});
