<?php

namespace App\Http\Controllers\API\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class FileUploadController extends Controller
{
    /**
     * Upload employee document
     */
    public function uploadEmployeeDocument(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:employees,id',
                'file' => 'required|file|max:10240', // 10MB max
                'document_type' => 'required|in:photo,contract,certificate,id_document,resume,other',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('file');
            $employeeId = $request->input('employee_id');
            $documentType = $request->input('document_type');

            // Additional validation based on document type
            $this->validateFileType($file, $documentType);

            // Get employee
            $employee = Employee::findOrFail($employeeId);

            // Generate unique filename
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $filename = $this->generateFilename($employee, $documentType, $extension);

            // Define upload path
            $uploadPath = "hr/employees/{$employeeId}/documents";

            // Store file
            $filePath = $file->storeAs($uploadPath, $filename, 'public');

            // Create database record
            $document = EmployeeDocument::create([
                'id' => (string) Str::uuid(),
                'employee_id' => $employeeId,
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'document_type' => $documentType,
                'file_name' => $filename,
                'original_name' => $originalName,
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_by' => auth()->id(),
                'uploaded_at' => Carbon::now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'data' => [
                    'id' => $document->id,
                    'title' => $document->title,
                    'document_type' => $document->document_type,
                    'file_name' => $document->file_name,
                    'file_size' => $document->file_size,
                    'uploaded_at' => $document->uploaded_at,
                    'download_url' => Storage::url($document->file_path)
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get employee documents
     */
    public function getEmployeeDocuments($employeeId): JsonResponse
    {
        try {
            $employee = Employee::findOrFail($employeeId);
            
            $documents = EmployeeDocument::where('employee_id', $employeeId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'title' => $doc->title,
                        'description' => $doc->description,
                        'document_type' => $doc->document_type,
                        'file_name' => $doc->file_name,
                        'original_name' => $doc->original_name,
                        'file_size' => $doc->file_size,
                        'mime_type' => $doc->mime_type,
                        'uploaded_at' => $doc->uploaded_at,
                        'uploaded_by_name' => $doc->uploader ? $doc->uploader->name : 'System',
                        'download_url' => Storage::url($doc->file_path),
                        'is_image' => Str::startsWith($doc->mime_type, 'image/')
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $documents
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve documents',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Download employee document
     */
    public function downloadDocument($documentId)
    {
        try {
            $document = EmployeeDocument::findOrFail($documentId);

            if (!Storage::disk('public')->exists($document->file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            return Storage::disk('public')->download($document->file_path, $document->original_name);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download document',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Delete employee document
     */
    public function deleteDocument($documentId): JsonResponse
    {
        try {
            $document = EmployeeDocument::findOrFail($documentId);

            // Delete file from storage
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            // Delete database record
            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update employee profile photo
     */
    public function updateProfilePhoto(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:employees,id',
                'photo' => 'required|image|mimes:jpeg,png,jpg|max:5120' // 5MB max for photos
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('photo');
            $employeeId = $request->input('employee_id');
            $employee = Employee::findOrFail($employeeId);

            // Delete existing profile photo document if exists
            $existingPhoto = EmployeeDocument::where('employee_id', $employeeId)
                ->where('document_type', 'photo')
                ->first();

            if ($existingPhoto) {
                if (Storage::disk('public')->exists($existingPhoto->file_path)) {
                    Storage::disk('public')->delete($existingPhoto->file_path);
                }
                $existingPhoto->delete();
            }

            // Generate filename for profile photo
            $extension = $file->getClientOriginalExtension();
            $filename = "profile_photo.{$extension}";
            $uploadPath = "hr/employees/{$employeeId}/photos";

            // Store file
            $filePath = $file->storeAs($uploadPath, $filename, 'public');

            // Create database record
            $document = EmployeeDocument::create([
                'id' => (string) Str::uuid(),
                'employee_id' => $employeeId,
                'title' => 'Profile Photo',
                'description' => 'Employee profile photograph',
                'document_type' => 'photo',
                'file_name' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_by' => auth()->id(),
                'uploaded_at' => Carbon::now()
            ]);

            // Update employee profile photo URL
            $employee->update([
                'profile_photo_url' => Storage::url($filePath)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile photo updated successfully',
                'data' => [
                    'photo_url' => Storage::url($filePath),
                    'document_id' => $document->id
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile photo',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get file upload statistics
     */
    public function getUploadStats(): JsonResponse
    {
        try {
            $stats = [
                'total_documents' => EmployeeDocument::count(),
                'total_size' => EmployeeDocument::sum('file_size'),
                'documents_by_type' => EmployeeDocument::selectRaw('document_type, COUNT(*) as count')
                    ->groupBy('document_type')
                    ->pluck('count', 'document_type'),
                'recent_uploads' => EmployeeDocument::with(['employee:id,first_name,last_name', 'uploader:id,name'])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($doc) {
                        return [
                            'id' => $doc->id,
                            'title' => $doc->title,
                            'document_type' => $doc->document_type,
                            'employee_name' => $doc->employee->first_name . ' ' . $doc->employee->last_name,
                            'uploaded_by' => $doc->uploader ? $doc->uploader->name : 'System',
                            'uploaded_at' => $doc->uploaded_at,
                            'file_size' => $doc->file_size
                        ];
                    })
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get upload statistics',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Validate file type based on document type
     */
    private function validateFileType($file, $documentType)
    {
        $allowedTypes = [
            'photo' => ['image/jpeg', 'image/png', 'image/jpg'],
            'contract' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'certificate' => ['application/pdf', 'image/jpeg', 'image/png'],
            'id_document' => ['application/pdf', 'image/jpeg', 'image/png'],
            'resume' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'other' => ['*'] // Allow any type for other documents
        ];

        $mimeType = $file->getMimeType();
        $allowed = $allowedTypes[$documentType] ?? ['*'];

        if (!in_array('*', $allowed) && !in_array($mimeType, $allowed)) {
            throw new \Exception("Invalid file type for {$documentType}. Allowed types: " . implode(', ', $allowed));
        }
    }

    /**
     * Generate unique filename
     */
    private function generateFilename($employee, $documentType, $extension)
    {
        $employeeName = Str::slug($employee->first_name . '_' . $employee->last_name);
        $timestamp = Carbon::now()->format('Ymd_His');
        $random = Str::random(6);

        return "{$employeeName}_{$documentType}_{$timestamp}_{$random}.{$extension}";
    }
}