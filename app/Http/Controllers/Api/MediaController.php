<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function __construct(
        private MediaService $mediaService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Upload file(s)
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'files' => 'required|array|max:10',
            'files.*' => 'required|file|max:10240', // 10MB max
            'collection' => 'nullable|string|max:255',
            'model_type' => 'nullable|string',
            'model_id' => 'nullable|uuid',
            'conversions' => 'nullable|array',
        ]);

        $uploadedFiles = [];
        $collection = $request->collection ?? 'default';

        foreach ($request->file('files') as $file) {
            // Validate file type
            $this->validateFileType($file);

            if ($request->model_type && $request->model_id) {
                // Attach to model
                $model = $this->resolveModel($request->model_type, $request->model_id);
                $this->authorize('update', $model);

                $mediaItem = $model->addMediaFromRequest('files')
                    ->each(function ($fileAdder) use ($collection, $request) {
                        $fileAdder->toMediaCollection($collection);
                        
                        // Add conversions if specified
                        if ($request->conversions) {
                            foreach ($request->conversions as $conversion) {
                                $fileAdder->performConversions($conversion);
                            }
                        }
                    });

                $uploadedFiles[] = $mediaItem;
            } else {
                // Standalone upload
                $uploadedFile = $this->mediaService->uploadFile($file, [
                    'collection' => $collection,
                    'user_id' => auth()->id(),
                ]);

                $uploadedFiles[] = $uploadedFile;
            }
        }

        return $this->successResponse($uploadedFiles, 'Files uploaded successfully');
    }

    /**
     * Upload avatar
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = auth()->user();

        // Clear existing avatar
        $user->clearMediaCollection('avatars');

        // Upload new avatar
        $avatar = $user->addMediaFromRequest('avatar')
            ->performConversions('thumb')
            ->performConversions('medium')
            ->toMediaCollection('avatars');

        return $this->successResponse([
            'avatar' => $avatar,
            'avatar_url' => $user->getFirstMediaUrl('avatars'),
            'avatar_thumb_url' => $user->getFirstMediaUrl('avatars', 'thumb'),
        ], 'Avatar uploaded successfully');
    }

    /**
     * Get file/media details
     */
    public function show($mediaId): JsonResponse
    {
        $media = $this->mediaService->getMedia($mediaId);
        
        if (!$media) {
            return $this->errorResponse('Media not found', 404);
        }

        $this->authorize('view', $media->model);

        return $this->successResponse([
            'media' => $media,
            'url' => $media->getUrl(),
            'conversions' => $media->getMediaConversions()->map(function ($conversion) use ($media) {
                return [
                    'name' => $conversion->getName(),
                    'url' => $media->getUrl($conversion->getName()),
                ];
            }),
        ], 'Media retrieved successfully');
    }

    /**
     * Download file
     */
    public function download($mediaId)
    {
        $media = $this->mediaService->getMedia($mediaId);
        
        if (!$media) {
            abort(404, 'Media not found');
        }

        $this->authorize('view', $media->model);

        return response()->download($media->getPath(), $media->file_name);
    }

    /**
     * Delete file
     */
    public function destroy($mediaId): JsonResponse
    {
        $media = $this->mediaService->getMedia($mediaId);
        
        if (!$media) {
            return $this->errorResponse('Media not found', 404);
        }

        $this->authorize('update', $media->model);

        $media->delete();

        return $this->successResponse(null, 'Media deleted successfully');
    }

    /**
     * Get user's uploaded files
     */
    public function userFiles(Request $request): JsonResponse
    {
        $user = auth()->user();

        $query = $user->getMedia();

        if ($request->collection) {
            $query = $user->getMedia($request->collection);
        }

        $files = $query->map(function ($media) {
            return [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'collection_name' => $media->collection_name,
                'url' => $media->getUrl(),
                'created_at' => $media->created_at,
            ];
        });

        return $this->successResponse($files, 'User files retrieved successfully');
    }

    /**
     * Validate file type
     */
    private function validateFileType($file): void
    {
        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv',
        ];

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            abort(422, 'File type not allowed');
        }
    }

    /**
     * Resolve model from type and ID
     */
    private function resolveModel(string $modelType, string $modelId)
    {
        $modelClass = match ($modelType) {
            'user' => \App\Models\User::class,
            'company' => \App\Models\Company::class,
            'project' => \App\Models\Project::class,
            'task' => \App\Models\Task::class,
            'employee' => \App\Models\HR\Employee::class,
            'employee_document' => \App\Models\HR\EmployeeDocument::class,
            default => throw new \InvalidArgumentException('Invalid model type'),
        };

        return $modelClass::findOrFail($modelId);
    }
}