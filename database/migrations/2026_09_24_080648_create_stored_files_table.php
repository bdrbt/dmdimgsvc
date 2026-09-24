<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stored_files', function (Blueprint $table) {
            $table->id();
            // hash for files deduplication
            $table->string('file_hash', 64)->unique();
            $table->string('disk_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            // symlinks count for the same file uploaded by different users
            $table->unsignedInteger('ref_count')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stored_files');
    }
};
