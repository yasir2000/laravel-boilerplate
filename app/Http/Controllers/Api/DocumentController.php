<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Services\DocumentManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentManagementService $documentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'nullable|exists:employees,id',
            'document_type' => 'nullable|string',
            'status' => 'nullable|string',
            'is_verified' => 'nullable|boolean',
            'access_level' => 'nullable|string',
            'expiring_soon' => 'nullable|integer|min:1|max:365',
            'expired' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filters = $request->only([
                'document_type', 'status', 'is_verified', 
                'access_level', 'expiring_soon', 'expired'
            ]);

            if ($request->has('employee_id')) {
                $employee = Employee::findOrFail($request->employee_id);
                $documents = $this->documentService->getDocumentsByEmployee($employee, $filters);
            } else {
                $query = EmployeeDocument::with(['employee.user', 'verifiedBy']);
                
                // Apply filters
                foreach ($filters as $key => $value) {
                    if ($value !== null) {
                        if ($key === 'expiring_soon') {
                            $query->expiringSoon($value);
                        } elseif ($key === 'expired' && $value) {
                            $query->expired();
                        } else {
                            $query->where($key, $value);
                        }
                    }
                }

                $documents = $query->orderBy('created_at', 'desc')->paginate(
                    $request->get('per_page', 15)
                );
            }

            return response()->json([
                'success' => true,
                'data' => $documents
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240', // 10MB max
            'document_type' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'document_number' => 'nullable|string|max:100',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'access_level' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $employee = Employee::findOrFail($request->employee_id);
            
            // Check if user can upload documents for this employee
            if (!$this->canManageEmployeeDocuments($employee)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to manage documents for this employee'
                ], 403);
            }

            $documentData = $request->only([
                'document_type', 'title', 'description', 'document_number',
                'issue_date', 'expiry_date', 'access_level'
            ]);

            $document = $this->documentService->uploadDocument(
                $employee,
                $request->file('file'),
                $documentData
            );

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'data' => $document->load(['employee.user', 'verifiedBy'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(EmployeeDocument $document): JsonResponse
    {
        try {
            // Check access permissions
            if (!$this->documentService->canAccessDocument($document)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to access this document'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $document->load(['employee.user', 'verifiedBy'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, EmployeeDocument $document): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'document_number' => 'nullable|string|max:100',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'access_level' => 'sometimes|string',
            'status' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check permissions
            if (!$this->canManageEmployeeDocuments($document->employee)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to update this document'
                ], 403);
            }

            $data = $request->only([
                'title', 'description', 'document_number',
                'issue_date', 'expiry_date', 'access_level', 'status'
            ]);

            $document = $this->documentService->updateDocument($document, $data);

            return response()->json([
                'success' => true,
                'message' => 'Document updated successfully',
                'data' => $document->load(['employee.user', 'verifiedBy'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(EmployeeDocument $document): JsonResponse
    {
        try {
            // Check permissions
            if (!$this->canManageEmployeeDocuments($document->employee)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this document'
                ], 403);
            }

            $this->documentService->deleteDocument($document);

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verify(Request $request, EmployeeDocument $document): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'is_verified' => 'required|boolean',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Only HR and Admin can verify documents
            if (!auth()->user()->hasAnyRole(['admin', 'hr'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to verify documents'
                ], 403);
            }

            $isVerified = $request->boolean('is_verified');
            $document = $this->documentService->verifyDocument($document, $isVerified);

            // Add verification notes if provided
            if ($request->has('notes')) {
                $metadata = $document->metadata ?? [];
                $metadata['verification_notes'] = $request->notes;
                $document->update(['metadata' => $metadata]);
            }

            return response()->json([
                'success' => true,
                'message' => $isVerified ? 'Document verified successfully' : 'Document rejected',
                'data' => $document->load(['employee.user', 'verifiedBy'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function replace(Request $request, EmployeeDocument $document): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check permissions
            if (!$this->canManageEmployeeDocuments($document->employee)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to replace this document'
                ], 403);
            }

            $document = $this->documentService->replaceDocument($document, $request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'Document replaced successfully',
                'data' => $document->load(['employee.user', 'verifiedBy'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to replace document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function download(EmployeeDocument $document)
    {
        try {
            // Check access permissions
            if (!$this->documentService->canAccessDocument($document)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to download this document'
                ], 403);
            }

            $media = $document->getFirstMedia('documents');
            
            if (!$media) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document file not found'
                ], 404);
            }

            return response()->download($media->getPath(), $document->file_name);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkVerify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'exists:employee_documents,id',
            'is_verified' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Only HR and Admin can verify documents
            if (!auth()->user()->hasAnyRole(['admin', 'hr'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to verify documents'
                ], 403);
            }

            $isVerified = $request->boolean('is_verified');
            $updated = $this->documentService->bulkVerifyDocuments(
                $request->document_ids,
                $isVerified
            );

            return response()->json([
                'success' => true,
                'message' => "Successfully {$updated} documents " . ($isVerified ? 'verified' : 'rejected'),
                'updated_count' => $updated
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk verify documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->documentService->getDocumentStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function expiringDocuments(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'days' => 'nullable|integer|min:1|max:365'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $days = $request->get('days', 30);
            $documents = $this->documentService->getExpiringDocuments($days);

            return response()->json([
                'success' => true,
                'data' => $documents
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch expiring documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function unverifiedDocuments(): JsonResponse
    {
        try {
            // Only HR and Admin can see unverified documents
            if (!auth()->user()->hasAnyRole(['admin', 'hr'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view unverified documents'
                ], 403);
            }

            $documents = $this->documentService->getUnverifiedDocuments();

            return response()->json([
                'success' => true,
                'data' => $documents
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch unverified documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getDocumentTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => EmployeeDocument::getDocumentTypes()
        ]);
    }

    public function getAccessLevels(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => EmployeeDocument::getAccessLevels()
        ]);
    }

    public function getStatuses(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => EmployeeDocument::getStatuses()
        ]);
    }

    private function canManageEmployeeDocuments(Employee $employee): bool
    {
        $user = auth()->user();

        // Admin and HR can manage all documents
        if ($user->hasAnyRole(['admin', 'hr'])) {
            return true;
        }

        // Employee can manage their own documents
        if ($employee->user_id === $user->id) {
            return true;
        }

        // Manager can manage their team's documents
        if ($user->hasRole('manager') && $employee->manager_id === $user->employee?->id) {
            return true;
        }

        return false;
    }
}