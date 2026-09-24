<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoredFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_hash',
        'disk_path',
        'thumbnail_path',
        'mime_type',
        'size',
        'ref_count',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

}
