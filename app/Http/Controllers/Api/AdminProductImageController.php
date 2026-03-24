<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminProductImageController extends Controller
{
    private const MAX_FILE_SIZE = 5242880; // 5MB
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    private const THUMBNAIL_SIZE = 300;

    /**
     * Upload product images
     */
    public function upload(Request $request)
    {
        $request->validate([
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:' . (self::MAX_FILE_SIZE / 1024), // Convert to KB
            ],
            'media_ids' => ['nullable', 'array'],
            'media_ids.*' => ['integer', 'exists:media,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'save_to_library' => ['nullable', 'boolean'],
        ]);

        $productId = $request->input('product_id');
        $saveToLibrary = $request->input('save_to_library', true); // Default to true
        $uploadedImages = [];

        // Handle uploaded files
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                try {
                    $imageData = $this->processImage($file, $productId, $saveToLibrary, $request->user()->id ?? null);
                    $uploadedImages[] = $imageData;
                } catch (\Exception $e) {
                    return response()->json([
                        'message' => 'Failed to process image: ' . $e->getMessage(),
                    ], 422);
                }
            }
        }

        // Handle selected media IDs
        if ($request->has('media_ids')) {
            foreach ($request->input('media_ids') as $mediaId) {
                $media = Media::find($mediaId);
                if ($media && $media->isImage()) {
                    $uploadedImages[] = [
                        'id' => (string) $media->id,
                        'media_id' => $media->id,
                        'original_name' => $media->name,
                        'url' => $media->url,
                        'thumbnail_url' => $media->thumbnail_url,
                        'path' => $media->path,
                    ];
                }
            }
        }

        if (empty($uploadedImages)) {
            return response()->json([
                'message' => 'No images provided',
            ], 422);
        }

        return response()->json([
            'images' => $uploadedImages,
        ], 201);
    }

    /**
     * Delete a product image
     */
    public function delete(Request $request, string $imagePath)
    {
        $decodedPath = urldecode($imagePath);
        $fullPath = public_path('uploads/products/' . $decodedPath);

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        // Also delete thumbnail if exists
        $thumbnailPath = public_path('uploads/products/thumbnails/' . $decodedPath);
        if (file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }

        return response()->json([
            'message' => 'Image deleted successfully',
        ]);
    }

    /**
     * Process and store image
     */
    private function processImage($file, ?int $productId = null, bool $saveToLibrary = true, ?int $userId = null): array
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::random(40) . '.' . $extension;

        // Create directory structure
        $baseDir = public_path('uploads/products');
        $thumbDir = public_path('uploads/products/thumbnails');

        if ($productId) {
            $baseDir .= '/' . $productId;
            $thumbDir .= '/' . $productId;
        }

        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }

        $relativePath = ($productId ? $productId . '/' : '') . $fileName;
        $fullPath = $baseDir . '/' . $fileName;
        $thumbnailPath = $thumbDir . '/' . $fileName;

        // Move uploaded file
        $file->move($baseDir, $fileName);

        // Generate thumbnail
        $this->generateThumbnail($fullPath, $thumbnailPath, self::THUMBNAIL_SIZE);

        $baseUrl = url('/uploads/products');
        $url = $baseUrl . '/' . $relativePath;
        $thumbnailUrl = $baseUrl . '/thumbnails/' . $relativePath;

        // Get image dimensions
        $imageInfo = @getimagesize($fullPath);
        $width = $imageInfo ? $imageInfo[0] : null;
        $height = $imageInfo ? $imageInfo[1] : null;

        // Save to media library if requested
        $mediaId = null;
        if ($saveToLibrary) {
            $media = Media::create([
                'user_id' => $userId,
                'name' => $originalName,
                'file_name' => $fileName,
                'mime_type' => $file->getMimeType(),
                'file_type' => 'image',
                'file_size' => $file->getSize(),
                'path' => 'products/' . $relativePath,
                'url' => $url,
                'thumbnail_url' => $thumbnailUrl,
                'width' => $width,
                'height' => $height,
            ]);
            $mediaId = $media->id;
        }

        return [
            'id' => Str::random(20),
            'media_id' => $mediaId,
            'original_name' => $originalName,
            'url' => $url,
            'thumbnail_url' => $thumbnailUrl,
            'path' => $relativePath,
        ];
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
            throw new \Exception('Invalid image file');
        }

        [$width, $height, $type] = $imageInfo;

        // Create image resource based on type
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
            default:
                throw new \Exception('Unsupported image type');
        }

        if (!$source) {
            throw new \Exception('Failed to create image resource');
        }

        // Calculate thumbnail dimensions (maintain aspect ratio)
        $ratio = min($size / $width, $size / $height);
        $thumbWidth = (int) ($width * $ratio);
        $thumbHeight = (int) ($height * $ratio);

        // Create thumbnail
        $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);

        // Preserve transparency for PNG
        if ($type === IMAGETYPE_PNG) {
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
        }

        imagedestroy($source);
        imagedestroy($thumbnail);
    }
}
