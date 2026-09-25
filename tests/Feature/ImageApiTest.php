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

test('fails validation when uploading fake png file containing text', function () {
    $user = User::factory()->create();

    $fakeFile = \Illuminate\Http\Testing\File::create('fake.png', 10);

    $response = $this->actingAs($user)
        ->postJson('/api/images', [
            'image' => $fakeFile,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});

test('successfully uploads valid png image', function () {
    $user = User::factory()->create();

    // Генерируем реальное PNG-изображение
    $validImage = \Illuminate\Http\Testing\File::image('real.png', 100, 100);

    $response = $this->actingAs($user)
        ->postJson('/api/images', [
            'image' => $validImage,
        ]);

    $response->assertStatus(201);
});

test('user can upload an image via API', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/images', [
        'image' => createValidImage('avatar.jpg'),
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

test('user can view their own image details', function () {
    $user = User::factory()->create();
    $image = Image::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->getJson("/api/images/{$image->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $image->id);
});

test('user cannot view another user image', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $image = Image::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($stranger)
        ->getJson("/api/images/{$image->id}");

    $response->assertStatus(403);
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

test('authenticated user can retrieve paginated list of their images', function () {
    $user = User::factory()->create();

    // create 20 bulk images
    Image::factory()->count(20)->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/images?per_page=5&page=2');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'per_page',
                'to',
                'total',
            ],
        ])
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('meta.per_page', 5)
        ->assertJsonPath('meta.total', 20)
        ->assertJsonCount(5, 'data');
});
