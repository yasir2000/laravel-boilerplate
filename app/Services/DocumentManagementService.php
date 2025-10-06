<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class DocumentManagementService
{
    public function uploadDocument(
        Employee $employee,
        UploadedFile $file,
        array $documentData
    ): EmployeeDocument {
        $validator = Validator::make($documentData, [
            'document_type' => 'required|string|in:' . implode(',', array_keys(EmployeeDocument::getDocumentTypes())),
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'document_number' => 'nullable|string|max:100',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'access_level' => 'required|string|in:' . implode(',', array_keys(EmployeeDocument::getAccessLevels()))
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        // Create document record
        $document = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => $documentData['document_type'],
            'title' => $documentData['title'],
            'description' => $documentData['description'] ?? null,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'document_number' => $documentData['document_number'] ?? null,
            'issue_date' => $documentData['issue_date'] ?? null,
            'expiry_date' => $documentData['expiry_date'] ?? null,
            'status' => 'pending',
            'access_level' => $documentData['access_level'],
            'metadata' => [
                'original_name' => $file->getClientOriginalName(),
                'uploaded_by' => auth()->id(),
                'upload_ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]
        ]);

        // Attach file using Spatie Media Library
        try {
            $document->addMediaFromRequest('file')
                ->usingName($documentData['title'])
                ->usingFileName($this->generateSecureFileName($file))
                ->toMediaCollection('documents');
        } catch (FileDoesNotExist|FileIsTooBig $e) {
            $document->delete();
            throw new \RuntimeException('Failed to upload file: ' . $e->getMessage());
        }

        return $document->refresh();
    }

    public function updateDocument(EmployeeDocument $document, array $data): EmployeeDocument
    {
        $validator = Validator::make($data, [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'document_number' => 'nullable|string|max:100',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'access_level' => 'sometimes|string|in:' . implode(',', array_keys(EmployeeDocument::getAccessLevels())),
            'status' => 'sometimes|string|in:' . implode(',', array_keys(EmployeeDocument::getStatuses()))
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        $document->update($data);

        return $document->refresh();
    }

    public function verifyDocument(EmployeeDocument $document, bool $isVerified = true): EmployeeDocument
    {
        $document->update([
            'is_verified' => $isVerified,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'status' => $isVerified ? 'approved' : 'rejected'
        ]);

        // Send notification to employee
        $document->employee->user->notify(
            new \App\Notifications\DocumentVerificationNotification($document, $isVerified)
        );

        return $document->refresh();
    }

    public function deleteDocument(EmployeeDocument $document): bool
    {
        // Delete associated media files
        $document->clearMediaCollection('documents');

        // Soft delete the document record
        return $document->delete();
    }

    public function replaceDocument(EmployeeDocument $document, UploadedFile $newFile): EmployeeDocument
    {
        // Delete old media
        $document->clearMediaCollection('documents');

        // Add new media
        $document->addMediaFromRequest('file')
            ->usingName($document->title)
            ->usingFileName($this->generateSecureFileName($newFile))
            ->toMediaCollection('documents');

        // Update file metadata
        $document->update([
            'file_name' => $newFile->getClientOriginalName(),
            'file_size' => $newFile->getSize(),
            'mime_type' => $newFile->getMimeType(),
            'status' => 'pending',
            'is_verified' => false,
            'verified_by' => null,
            'verified_at' => null,
            'metadata' => array_merge($document->metadata ?? [], [
                'replaced_at' => now(),
                'replaced_by' => auth()->id(),
                'previous_file' => $document->file_name
            ])
        ]);

        return $document->refresh();
    }

    public function getDocumentsByEmployee(Employee $employee, array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $employee->documents()->with(['verifiedBy']);

        // Apply filters
        if (!empty($filters['document_type'])) {
            $query->where('document_type', $filters['document_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['is_verified'])) {
            $query->where('is_verified', $filters['is_verified']);
        }

        if (!empty($filters['access_level'])) {
            $query->where('access_level', $filters['access_level']);
        }

        if (!empty($filters['expiring_soon'])) {
            $query->expiringSoon($filters['expiring_soon']);
        }

        if (!empty($filters['expired'])) {
            $query->expired();
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    public function getExpiringDocuments(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return EmployeeDocument::with(['employee.user'])
            ->expiringSoon($days)
            ->get();
    }

    public function getExpiredDocuments(): \Illuminate\Database\Eloquent\Collection
    {
        return EmployeeDocument::with(['employee.user'])
            ->expired()
            ->get();
    }

    public function getUnverifiedDocuments(): \Illuminate\Database\Eloquent\Collection
    {
        return EmployeeDocument::with(['employee.user'])
            ->unverified()
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function bulkVerifyDocuments(array $documentIds, bool $isVerified = true): int
    {
        $updated = 0;
        
        foreach ($documentIds as $documentId) {
            $document = EmployeeDocument::find($documentId);
            if ($document) {
                $this->verifyDocument($document, $isVerified);
                $updated++;
            }
        }

        return $updated;
    }

    public function getDocumentStatistics(): array
    {
        return [
            'total_documents' => EmployeeDocument::count(),
            'verified_documents' => EmployeeDocument::verified()->count(),
            'unverified_documents' => EmployeeDocument::unverified()->count(),
            'expired_documents' => EmployeeDocument::expired()->count(),
            'expiring_soon' => EmployeeDocument::expiringSoon()->count(),
            'by_type' => EmployeeDocument::groupBy('document_type')
                ->selectRaw('document_type, count(*) as count')
                ->pluck('count', 'document_type')
                ->toArray(),
            'by_status' => EmployeeDocument::groupBy('status')
                ->selectRaw('status, count(*) as count')
                ->pluck('count', 'status')
                ->toArray(),
            'by_access_level' => EmployeeDocument::groupBy('access_level')
                ->selectRaw('access_level, count(*) as count')
                ->pluck('count', 'access_level')
                ->toArray()
        ];
    }

    public function generateDocumentReport(array $filters = []): array
    {
        $query = EmployeeDocument::with(['employee.user', 'employee.department']);

        // Apply filters
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }

        if (!empty($filters['document_type'])) {
            $query->where('document_type', $filters['document_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $documents = $query->get();

        return [
            'total_documents' => $documents->count(),
            'documents' => $documents,
            'summary' => [
                'by_type' => $documents->groupBy('document_type')->map->count(),
                'by_status' => $documents->groupBy('status')->map->count(),
                'by_employee' => $documents->groupBy('employee.user.name')->map->count(),
                'verification_rate' => $documents->where('is_verified', true)->count() / max(1, $documents->count()) * 100
            ],
            'generated_at' => now(),
            'filters_applied' => $filters
        ];
    }

    private function generateSecureFileName(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $hash = Str::random(40);
        
        return $hash . '.' . $extension;
    }

    public function canAccessDocument(EmployeeDocument $document, $user = null): bool
    {
        $user = $user ?? auth()->user();
        
        if (!$user) {
            return false;
        }

        // Admin and HR can access all documents
        if ($user->hasAnyRole(['admin', 'hr'])) {
            return true;
        }

        // Employee can access their own documents (except restricted)
        if ($document->employee->user_id === $user->id) {
            return $document->access_level !== 'restricted';
        }

        // Manager can access their team's documents (except restricted)
        if ($user->hasRole('manager') && $document->employee->manager_id === $user->employee?->id) {
            return $document->access_level !== 'restricted';
        }

        return false;
    }

    public function archiveOldDocuments(int $daysOld = 365): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        return EmployeeDocument::where('created_at', '<', $cutoffDate)
            ->whereNotIn('status', ['archived'])
            ->update(['status' => 'archived']);
    }
}