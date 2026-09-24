<?php

namespace Database\Factories;

use App\Models\Image;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Image>
 */
class ImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'stored_file_id' => StoredFile::factory(),
            'original_name' => $this->faker->word() . '.jpg',
        ];
    }
}
