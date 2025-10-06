<?php

namespace App\Services;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class MediaService
{
    /**
     * Upload a standalone file
     */
    public function uploadFile(UploadedFile $file, array $options = []): array
    {
        $disk = $options['disk'] ?? 'public';
        $directory = $options['directory'] ?? 'uploads/' . date('Y/m');
        
        // Generate unique filename
        $filename = $this->generateUniqueFilename($file);
        
        // Store file
        $path = Storage::disk($disk)->putFileAs($directory, $file, $filename);
        
        // Process image if it's an image
        $conversions = [];
        if ($this->isImage($file)) {
            $conversions = $this->processImageConversions($file, $path, $disk);
        }
        
        return [
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'url' => Storage::disk($disk)->url($path),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'disk' => $disk,
            'conversions' => $conversions,
            'uploaded_by' => $options['user_id'] ?? null,
            'created_at' => now(),
        ];
    }

    /**
     * Get media by ID
     */
    public function getMedia(string $mediaId): ?Media
    {
        return Media::find($mediaId);
    }

    /**
     * Process image conversions
     */
    public function processImageConversions(UploadedFile $file, string $originalPath, string $disk): array
    {
        $conversions = [];
        $directory = dirname($originalPath);
        $filename = pathinfo($originalPath, PATHINFO_FILENAME);
        $extension = pathinfo($originalPath, PATHINFO_EXTENSION);

        // Thumbnail (150x150)
        $thumbPath = $directory . '/' . $filename . '_thumb.' . $extension;
        $image = Image::make($file->getRealPath());
        $image->fit(150, 150);
        Storage::disk($disk)->put($thumbPath, $image->encode());
        
        $conversions['thumb'] = [
            'path' => $thumbPath,
            'url' => Storage::disk($disk)->url($thumbPath),
            'width' => 150,
            'height' => 150,
        ];

        // Medium (400x400)
        $mediumPath = $directory . '/' . $filename . '_medium.' . $extension;
        $image = Image::make($file->getRealPath());
        $image->fit(400, 400);
        Storage::disk($disk)->put($mediumPath, $image->encode());
        
        $conversions['medium'] = [
            'path' => $mediumPath,
            'url' => Storage::disk($disk)->url($mediumPath),
            'width' => 400,
            'height' => 400,
        ];

        // Large (800x800)
        if ($file->getSize() > 500000) { // 500KB
            $largePath = $directory . '/' . $filename . '_large.' . $extension;
            $image = Image::make($file->getRealPath());
            $image->fit(800, 800);
            Storage::disk($disk)->put($largePath, $image->encode());
            
            $conversions['large'] = [
                'path' => $largePath,
                'url' => Storage::disk($disk)->url($largePath),
                'width' => 800,
                'height' => 800,
            ];
        }

        return $conversions;
    }

    /**
     * Generate unique filename
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return uniqid() . '_' . time() . '.' . $extension;
    }

    /**
     * Check if file is an image
     */
    private function isImage(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'image/');
    }

    /**
     * Get file storage usage for user
     */
    public function getUserStorageUsage(int $userId): array
    {
        $userMedia = Media::where('model_type', 'App\Models\User')
            ->where('model_id', $userId)
            ->get();

        $totalSize = $userMedia->sum('size');
        $fileCount = $userMedia->count();

        return [
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'file_count' => $fileCount,
            'by_collection' => $userMedia->groupBy('collection_name')->map(function ($items) {
                return [
                    'count' => $items->count(),
                    'size' => $items->sum('size'),
                    'size_formatted' => $this->formatBytes($items->sum('size')),
                ];
            }),
        ];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Clean up orphaned files
     */
    public function cleanupOrphanedFiles(): int
    {
        // Get all media records
        $mediaFiles = Media::pluck('id', 'file_name')->toArray();
        
        $cleaned = 0;
        
        foreach (Storage::disk('public')->allFiles('uploads') as $file) {
            $filename = basename($file);
            
            if (!in_array($filename, $mediaFiles)) {
                Storage::disk('public')->delete($file);
                $cleaned++;
            }
        }

        return $cleaned;
    }
}