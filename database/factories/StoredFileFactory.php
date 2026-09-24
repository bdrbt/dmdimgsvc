<?php

namespace Database\Factories;

use App\Models\StoredFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoredFile>
 */
class StoredFileFactory extends Factory
{
    protected $model = StoredFile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'file_hash' => $this->faker->sha256(),
            'disk_path' => 'images/' . $this->faker->uuid() . '.jpg',
            'thumbnail_path' => 'images/thumbnails/thumb_' . $this->faker->uuid() . '.jpg',
            'mime_type' => 'image/jpeg',
            'size' => $this->faker->numberBetween(1000, 500000),
            'ref_count' => 1,
        ];
    }
}
