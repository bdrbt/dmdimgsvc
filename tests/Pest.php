<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');

function createValidImage(string $name = 'avatar.jpg', int $width = 100, int $height = 100): UploadedFile
{
    $tempPath = tempnam(sys_get_temp_dir(), 'test_img_');
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    $gdImage = imagecreatetruecolor($width, $height);
    $bg = imagecolorallocate($gdImage, 200, 200, 200);
    imagefill($gdImage, 0, 0, $bg);

    if (in_array($extension, ['jpg', 'jpeg'])) {
        imagejpeg($gdImage, $tempPath);
        $mime = 'image/jpeg';
    } else {
        imagepng($gdImage, $tempPath);
        $mime = 'image/png';
    }

    return new UploadedFile($tempPath, $name, $mime, null, true);
}
