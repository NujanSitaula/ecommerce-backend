<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'file_name',
        'mime_type',
        'file_type',
        'file_size',
        'path',
        'url',
        'thumbnail_url',
        'width',
        'height',
        'duration',
        'alt_text',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration' => 'integer',
    ];

    /**
     * Get the user that uploaded the media
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if media is an image
     */
    public function isImage(): bool
    {
        return $this->file_type === 'image';
    }

    /**
     * Check if media is a video
     */
    public function isVideo(): bool
    {
        return $this->file_type === 'video';
    }

    /**
     * Check if media is a document
     */
    public function isDocument(): bool
    {
        return $this->file_type === 'document';
    }

    /**
     * Get human-readable file size
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get human-readable duration for videos
     */
    public function getDurationHumanAttribute(): ?string
    {
        if (!$this->duration) {
            return null;
        }

        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
