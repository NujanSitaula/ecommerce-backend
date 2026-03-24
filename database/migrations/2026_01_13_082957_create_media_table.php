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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name'); // Original filename
            $table->string('file_name'); // Stored filename
            $table->string('mime_type'); // e.g., image/jpeg, video/mp4
            $table->enum('file_type', ['image', 'video', 'document'])->default('image');
            $table->unsignedBigInteger('file_size'); // Size in bytes
            $table->string('path'); // Relative path in storage
            $table->string('url'); // Full URL
            $table->string('thumbnail_url')->nullable(); // For images/videos
            $table->unsignedInteger('width')->nullable(); // For images/videos
            $table->unsignedInteger('height')->nullable(); // For images/videos
            $table->unsignedInteger('duration')->nullable(); // For videos, in seconds
            $table->string('alt_text')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional metadata
            $table->timestamps();
            
            // Indexes for performance
            $table->index('file_type');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
