<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AdminMediaController extends Controller
{
    private const MAX_IMAGE_SIZE = 5242880; // 5MB
    private const MAX_VIDEO_SIZE = 104857600; // 100MB
    private const ALLOWED_IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const ALLOWED_VIDEO_MIMES = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
    private const THUMBNAIL_SIZE = 300;

    /**
     * List media with pagination, filtering, and search
     */
    public function index(Request $request)
    {
        $query = Media::query()->with('user:id,name,email');

        // Filter by file type
        if ($request->has('type') && in_array($request->type, ['image', 'video', 'document'])) {
            $query->where('file_type', $request->type);
        }

        // Search by name
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('alt_text', 'like', '%' . $request->search . '%');
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 24), 100);
        $media = $query->paginate($perPage);

        return response()->json($media);
    }

    /**
     * Get single media item
     */
    public function show($id)
    {
        $media = Media::findOrFail($id);
        return response()->json($media);
    }

    /**
     * Upload media files
     */
    public function store(Request $request)
    {
        $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => [
                'required',
                'file',
                'max:' . (self::MAX_VIDEO_SIZE / 1024), // Convert to KB
            ],
        ]);

        $uploadedMedia = [];

        foreach ($request->file('files') as $file) {
            try {
                $media = $this->processFile($file, $request->user()->id ?? null);
                $uploadedMedia[] = $media;
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Failed to process file: ' . $e->getMessage(),
                ], 422);
            }
        }

        return response()->json([
            'data' => $uploadedMedia,
        ], 201);
    }

    /**
     * Update media metadata
     */
    public function update(Request $request, $id)
    {
        $media = Media::findOrFail($id);

        $request->validate([
            'alt_text' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'], // 2MB max for custom thumbnails
        ]);

        $updateData = [
            'alt_text' => $request->alt_text,
            'description' => $request->description,
        ];

        // Handle custom thumbnail upload for videos
        if ($request->hasFile('thumbnail') && $media->file_type === 'video') {
            $thumbnailFile = $request->file('thumbnail');
            $extension = $thumbnailFile->getClientOriginalExtension();
            $thumbnailFileName = 'thumb_' . $media->file_name . '.' . $extension;
            
            // Get the same directory structure as the video
            $videoPathParts = explode('/', $media->path);
            $year = $videoPathParts[1] ?? date('Y');
            $month = $videoPathParts[2] ?? date('m');
            
            $thumbDir = public_path("uploads/media/thumbnails/videos/{$year}/{$month}");
            if (!is_dir($thumbDir)) {
                mkdir($thumbDir, 0755, true);
            }
            
            $thumbnailPath = $thumbDir . '/' . $thumbnailFileName;
            $thumbnailFile->move($thumbDir, $thumbnailFileName);
            
            // Generate resized thumbnail
            $this->generateThumbnail($thumbnailPath, $thumbnailPath, self::THUMBNAIL_SIZE);
            
            $thumbnailRelativePath = "videos/{$year}/{$month}/{$thumbnailFileName}";
            $updateData['thumbnail_url'] = url("/uploads/media/thumbnails/{$thumbnailRelativePath}");
        }

        $media->update($updateData);

        return response()->json($media);
    }

    /**
     * Save edited image as new version
     */
    public function saveEditedVersion(Request $request, $id)
    {
        $originalMedia = Media::findOrFail($id);

        if ($originalMedia->file_type !== 'image') {
            return response()->json(
                ['message' => 'Only images can be edited'],
                400
            );
        }

        $request->validate([
            'file' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:' . (self::MAX_IMAGE_SIZE / 1024)],
            'filename' => ['nullable', 'string', 'max:255'],
        ]);

        $editedFile = $request->file('file');
        $filename = $request->input('filename', $originalMedia->name . '-edited.jpg');
        
        // Ensure filename has extension
        $extension = $editedFile->getClientOriginalExtension();
        if (!str_ends_with(strtolower($filename), '.' . strtolower($extension))) {
            $filename = pathinfo($filename, PATHINFO_FILENAME) . '.' . $extension;
        }

        $userId = auth()->id();
        $now = now();
        $year = $now->format('Y');
        $month = $now->format('m');

        // Create directory structure
        $baseDir = public_path("uploads/media/images/{$year}/{$month}");
        $thumbDir = public_path("uploads/media/thumbnails/images/{$year}/{$month}");

        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }

        // Generate unique filename
        $uniqueFileName = Str::random(40) . '.' . $extension;
        $relativePath = "images/{$year}/{$month}/{$uniqueFileName}";
        $fullPath = $baseDir . '/' . $uniqueFileName;
        $thumbnailPath = $thumbDir . '/' . $uniqueFileName;

        // Move uploaded file
        $editedFile->move($baseDir, $uniqueFileName);

        // Get image dimensions
        [$width, $height] = $this->getImageDimensions($fullPath);

        // Generate thumbnail
        $this->generateThumbnail($fullPath, $thumbnailPath, self::THUMBNAIL_SIZE);
        $thumbnailUrl = url("/uploads/media/thumbnails/{$relativePath}");

        $baseUrl = url('/uploads/media');
        $url = $baseUrl . '/' . $relativePath;

        // Create new Media record
        $newMedia = Media::create([
            'user_id' => $userId,
            'name' => $filename,
            'file_name' => $uniqueFileName,
            'mime_type' => $editedFile->getMimeType(),
            'file_type' => 'image',
            'file_size' => $editedFile->getSize(),
            'path' => $relativePath,
            'url' => $url,
            'thumbnail_url' => $thumbnailUrl,
            'width' => $width,
            'height' => $height,
            'alt_text' => $originalMedia->alt_text,
            'description' => $originalMedia->description . ' (Edited version)',
            'metadata' => [
                'original_media_id' => $originalMedia->id,
                'edited_at' => now()->toISOString(),
            ],
        ]);

        return response()->json($newMedia, 201);
    }

    /**
     * Delete media file
     */
    public function destroy($id)
    {
        $media = Media::findOrFail($id);

        // Delete physical files
        $filePath = public_path('uploads/media/' . $media->path);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        if ($media->thumbnail_url) {
            $thumbnailPath = public_path('uploads/media/thumbnails/' . $media->path);
            if (file_exists($thumbnailPath)) {
                unlink($thumbnailPath);
            }
        }

        // Delete database record
        $media->delete();

        return response()->json([
            'message' => 'Media deleted successfully',
        ]);
    }

    /**
     * Process uploaded file and create Media record
     */
    private function processFile($file, ?int $userId = null): Media
    {
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $extension = $file->getClientOriginalExtension();
        $fileSize = $file->getSize();

        // Determine file type
        $fileType = $this->determineFileType($mimeType);

        // Generate unique filename
        $fileName = Str::random(40) . '.' . $extension;

        // Create directory structure based on date
        $now = now();
        $year = $now->format('Y');
        $month = $now->format('m');
        
        $baseDir = public_path("uploads/media/{$fileType}s/{$year}/{$month}");
        $thumbDir = public_path("uploads/media/thumbnails/{$fileType}s/{$year}/{$month}");

        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }

        $relativePath = "{$fileType}s/{$year}/{$month}/{$fileName}";
        $fullPath = $baseDir . '/' . $fileName;
        // For videos, thumbnail should be .jpg, not the video extension
        $thumbnailFileName = $fileType === 'video' 
            ? pathinfo($fileName, PATHINFO_FILENAME) . '.jpg'
            : $fileName;
        $thumbnailPath = $thumbDir . '/' . $thumbnailFileName;
        $thumbnailRelativePath = "{$fileType}s/{$year}/{$month}/{$thumbnailFileName}";

        // Move uploaded file
        $file->move($baseDir, $fileName);

        // Process based on file type
        $width = null;
        $height = null;
        $duration = null;
        $thumbnailUrl = null;

        if ($fileType === 'image') {
            [$width, $height] = $this->getImageDimensions($fullPath);
            $this->generateThumbnail($fullPath, $thumbnailPath, self::THUMBNAIL_SIZE);
            $thumbnailUrl = url("/uploads/media/thumbnails/{$relativePath}");
        } elseif ($fileType === 'video') {
            [$width, $height, $duration] = $this->getVideoInfo($fullPath);
            // Always generate video thumbnail (will create placeholder if FFmpeg fails)
            $thumbnailGenerated = $this->generateVideoThumbnail($fullPath, $thumbnailPath);
            // Always set thumbnail URL for videos (even if it's a placeholder)
            // Ensure thumbnail file exists - create placeholder if generation failed
            if (!file_exists($thumbnailPath)) {
                $this->createVideoPlaceholder($thumbnailPath);
            }
            // Always set thumbnail URL for videos - the file should exist at this point
            // (either from FFmpeg extraction or placeholder creation)
            $thumbnailUrl = url("/uploads/media/thumbnails/{$thumbnailRelativePath}");
        }

        $baseUrl = url('/uploads/media');
        $url = $baseUrl . '/' . $relativePath;

        // Create Media record
        $media = Media::create([
            'user_id' => $userId,
            'name' => $originalName,
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'file_type' => $fileType,
            'file_size' => $fileSize,
            'path' => $relativePath,
            'url' => $url,
            'thumbnail_url' => $thumbnailUrl,
            'width' => $width,
            'height' => $height,
            'duration' => $duration,
        ]);

        return $media;
    }

    /**
     * Determine file type from MIME type
     */
    private function determineFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }
        return 'document';
    }

    /**
     * Get image dimensions
     */
    private function getImageDimensions(string $filePath): array
    {
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo) {
            return [$imageInfo[0], $imageInfo[1]];
        }
        return [null, null];
    }

    /**
     * Get video information (dimensions and duration)
     */
    private function getVideoInfo(string $filePath): array
    {
        $ffprobePath = trim(shell_exec('which ffprobe 2>/dev/null'));
        
        if ($ffprobePath) {
            // Get video dimensions
            $dimensionsCommand = sprintf(
                '%s -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 %s 2>/dev/null',
                escapeshellarg($ffprobePath),
                escapeshellarg($filePath)
            );
            
            $dimensions = @exec($dimensionsCommand);
            $width = null;
            $height = null;
            
            if ($dimensions && strpos($dimensions, 'x') !== false) {
                [$width, $height] = explode('x', trim($dimensions));
                $width = (int) $width;
                $height = (int) $height;
            }
            
            // Get video duration
            $durationCommand = sprintf(
                '%s -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>/dev/null',
                escapeshellarg($ffprobePath),
                escapeshellarg($filePath)
            );
            
            $duration = @exec($durationCommand);
            $durationSeconds = $duration ? (int) round((float) trim($duration)) : null;
            
            return [$width, $height, $durationSeconds];
        }
        
        return [null, null, null];
    }

    /**
     * Generate thumbnail from image
     */
    private function generateThumbnail(string $sourcePath, string $destinationPath, int $size): void
    {
        if (!function_exists('imagecreatefromjpeg')) {
            // GD not available, copy original
            copy($sourcePath, $destinationPath);
            return;
        }

        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return;
        }

        [$width, $height, $type] = $imageInfo;

        // Create image resource based on type
        $source = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($sourcePath);
                break;
            default:
                copy($sourcePath, $destinationPath);
                return;
        }

        if (!$source) {
            copy($sourcePath, $destinationPath);
            return;
        }

        // Calculate thumbnail dimensions (maintain aspect ratio)
        $ratio = min($size / $width, $size / $height);
        $thumbWidth = (int) ($width * $ratio);
        $thumbHeight = (int) ($height * $ratio);

        // Create thumbnail
        $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);

        // Preserve transparency for PNG and GIF
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefilledrectangle($thumbnail, 0, 0, $thumbWidth, $thumbHeight, $transparent);
        }

        // Resize image
        imagecopyresampled(
            $thumbnail,
            $source,
            0,
            0,
            0,
            0,
            $thumbWidth,
            $thumbHeight,
            $width,
            $height
        );

        // Save thumbnail
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($thumbnail, $destinationPath, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($thumbnail, $destinationPath, 8);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($thumbnail, $destinationPath, 85);
                break;
            case IMAGETYPE_GIF:
                imagegif($thumbnail, $destinationPath);
                break;
        }

        imagedestroy($source);
        imagedestroy($thumbnail);
    }

    /**
     * Generate thumbnail from video (extract frame)
     */
    private function generateVideoThumbnail(string $sourcePath, string $destinationPath): bool
    {
        // Try to use FFmpeg if available
        $ffmpegPath = trim(shell_exec('which ffmpeg 2>/dev/null'));
        
        if ($ffmpegPath) {
            // Extract frame at 1 second (or 10% of duration if available)
            // First try to get duration
            $ffprobePath = trim(shell_exec('which ffprobe 2>/dev/null'));
            $seekTime = '00:00:01';
            
            if ($ffprobePath) {
                $durationCommand = sprintf(
                    '%s -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>/dev/null',
                    escapeshellarg($ffprobePath),
                    escapeshellarg($sourcePath)
                );
                
                $duration = @exec($durationCommand);
                if ($duration && (float) $duration > 0) {
                    $seekSeconds = max(1, min(10, round((float) $duration * 0.1)));
                    $hours = floor($seekSeconds / 3600);
                    $minutes = floor(($seekSeconds % 3600) / 60);
                    $seconds = $seekSeconds % 60;
                    $seekTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                }
            }
            
            // Extract frame and convert to JPEG
            $tempThumbnail = $destinationPath . '.tmp.jpg';
            $command = sprintf(
                '%s -i %s -ss %s -vframes 1 -vf "scale=%d:%d:force_original_aspect_ratio=decrease" -q:v 2 %s 2>&1',
                escapeshellarg($ffmpegPath),
                escapeshellarg($sourcePath),
                escapeshellarg($seekTime),
                self::THUMBNAIL_SIZE,
                self::THUMBNAIL_SIZE,
                escapeshellarg($tempThumbnail)
            );
            
            @exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($tempThumbnail)) {
                // Move temp file to final destination
                rename($tempThumbnail, $destinationPath);
                return true;
            }
            
            // Fallback: try simpler command without scaling
            $command = sprintf(
                '%s -i %s -ss %s -vframes 1 -q:v 2 %s 2>&1',
                escapeshellarg($ffmpegPath),
                escapeshellarg($sourcePath),
                escapeshellarg($seekTime),
                escapeshellarg($destinationPath)
            );
            
            @exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($destinationPath)) {
                // Resize thumbnail if needed
                $this->generateThumbnail($destinationPath, $destinationPath, self::THUMBNAIL_SIZE);
                return true;
            }
        }
        
        // Fallback: create placeholder image
        $this->createVideoPlaceholder($destinationPath);
        return file_exists($destinationPath);
    }
    
    /**
     * Create a placeholder image for videos when thumbnail generation fails
     */
    private function createVideoPlaceholder(string $destinationPath): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            return;
        }
        
        $width = self::THUMBNAIL_SIZE;
        $height = self::THUMBNAIL_SIZE;
        
        $image = imagecreatetruecolor($width, $height);
        
        // Background gradient (darker gray)
        $bgColor1 = imagecolorallocate($image, 200, 200, 200);
        $bgColor2 = imagecolorallocate($image, 180, 180, 180);
        
        // Simple gradient effect
        for ($i = 0; $i < $height; $i++) {
            $color = imagecolorallocate(
                $image,
                200 - ($i / $height) * 20,
                200 - ($i / $height) * 20,
                200 - ($i / $height) * 20
            );
            imageline($image, 0, $i, $width, $i, $color);
        }
        
        // Draw a subtle play icon (smaller, more subtle)
        $centerX = $width / 2;
        $centerY = $height / 2;
        $size = $width / 6; // Smaller play icon
        
        $playColor = imagecolorallocate($image, 100, 100, 100);
        $points = [
            $centerX - $size / 2, $centerY - $size,
            $centerX - $size / 2, $centerY + $size,
            $centerX + $size, $centerY,
        ];
        
        imagefilledpolygon($image, $points, 3, $playColor);
        
        // Add a border
        $borderColor = imagecolorallocate($image, 150, 150, 150);
        imagerectangle($image, 0, 0, $width - 1, $height - 1, $borderColor);
        
        // Save as JPEG
        imagejpeg($image, $destinationPath, 85);
        imagedestroy($image);
    }
}
