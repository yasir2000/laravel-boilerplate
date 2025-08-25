<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class FileStorageService
{
    protected ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * Upload a file to storage
     */
    public function upload(
        UploadedFile $file,
        string $directory = 'uploads',
        string $disk = 'public',
        ?string $filename = null
    ): array {
        $filename = $filename ?: $this->generateFilename($file);
        $path = $file->storeAs($directory, $filename, $disk);

        return [
            'path' => $path,
            'url' => Storage::disk($disk)->url($path),
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'disk' => $disk,
        ];
    }

    /**
     * Upload multiple files
     */
    public function uploadMultiple(
        array $files,
        string $directory = 'uploads',
        string $disk = 'public'
    ): array {
        $uploaded = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploaded[] = $this->upload($file, $directory, $disk);
            }
        }

        return $uploaded;
    }

    /**
     * Upload and resize image
     */
    public function uploadImage(
        UploadedFile $file,
        string $directory = 'images',
        array $sizes = [],
        string $disk = 'public'
    ): array {
        $filename = $this->generateFilename($file, 'jpg');
        $originalPath = $file->storeAs($directory, $filename, $disk);

        $result = [
            'original' => [
                'path' => $originalPath,
                'url' => Storage::disk($disk)->url($originalPath),
                'width' => null,
                'height' => null,
            ],
            'sizes' => [],
        ];

        // Process image sizes
        if (!empty($sizes)) {
            $image = $this->imageManager->read($file->getPathname());
            $result['original']['width'] = $image->width();
            $result['original']['height'] = $image->height();

            foreach ($sizes as $sizeName => $dimensions) {
                $resizedPath = $this->resizeAndSave(
                    $image,
                    $directory,
                    $filename,
                    $sizeName,
                    $dimensions,
                    $disk
                );

                $result['sizes'][$sizeName] = [
                    'path' => $resizedPath,
                    'url' => Storage::disk($disk)->url($resizedPath),
                    'width' => $dimensions['width'] ?? null,
                    'height' => $dimensions['height'] ?? null,
                ];
            }
        }

        return $result;
    }

    /**
     * Upload avatar image
     */
    public function uploadAvatar(UploadedFile $file, string $disk = 'public'): array
    {
        return $this->uploadImage($file, 'avatars', [
            'thumbnail' => ['width' => 150, 'height' => 150],
            'medium' => ['width' => 300, 'height' => 300],
        ], $disk);
    }

    /**
     * Delete file from storage
     */
    public function delete(string $path, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->delete($path);
    }

    /**
     * Delete multiple files
     */
    public function deleteMultiple(array $paths, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->delete($paths);
    }

    /**
     * Check if file exists
     */
    public function exists(string $path, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->exists($path);
    }

    /**
     * Get file URL
     */
    public function getUrl(string $path, string $disk = 'public'): string
    {
        return Storage::disk($disk)->url($path);
    }

    /**
     * Get file size
     */
    public function getSize(string $path, string $disk = 'public'): int
    {
        return Storage::disk($disk)->size($path);
    }

    /**
     * Copy file
     */
    public function copy(string $from, string $to, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->copy($from, $to);
    }

    /**
     * Move file
     */
    public function move(string $from, string $to, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->move($from, $to);
    }

    /**
     * Generate unique filename
     */
    protected function generateFilename(UploadedFile $file, ?string $extension = null): string
    {
        $extension = $extension ?: $file->getClientOriginalExtension();
        return Str::uuid() . '.' . $extension;
    }

    /**
     * Resize and save image
     */
    protected function resizeAndSave(
        $image,
        string $directory,
        string $filename,
        string $sizeName,
        array $dimensions,
        string $disk
    ): string {
        $resized = clone $image;

        if (isset($dimensions['width']) && isset($dimensions['height'])) {
            $resized->cover($dimensions['width'], $dimensions['height']);
        } elseif (isset($dimensions['width'])) {
            $resized->scaleDown(width: $dimensions['width']);
        } elseif (isset($dimensions['height'])) {
            $resized->scaleDown(height: $dimensions['height']);
        }

        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $resizedFilename = $nameWithoutExt . '_' . $sizeName . '.' . $extension;
        $resizedPath = $directory . '/' . $resizedFilename;

        Storage::disk($disk)->put($resizedPath, $resized->toJpeg());

        return $resizedPath;
    }

    /**
     * Validate file type
     */
    public function validateFileType(UploadedFile $file, array $allowedTypes): bool
    {
        return in_array($file->getMimeType(), $allowedTypes);
    }

    /**
     * Validate file size
     */
    public function validateFileSize(UploadedFile $file, int $maxSizeInBytes): bool
    {
        return $file->getSize() <= $maxSizeInBytes;
    }

    /**
     * Get allowed image types
     */
    public function getAllowedImageTypes(): array
    {
        return [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/bmp',
        ];
    }

    /**
     * Get allowed document types
     */
    public function getAllowedDocumentTypes(): array
    {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv',
        ];
    }

    /**
     * Clean up old files
     */
    public function cleanupOldFiles(string $directory, int $daysOld, string $disk = 'public'): int
    {
        $files = Storage::disk($disk)->files($directory);
        $deleted = 0;
        $cutoffTime = now()->subDays($daysOld)->timestamp;

        foreach ($files as $file) {
            $lastModified = Storage::disk($disk)->lastModified($file);
            if ($lastModified < $cutoffTime) {
                if (Storage::disk($disk)->delete($file)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }
}
