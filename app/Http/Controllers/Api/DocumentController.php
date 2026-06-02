<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OnboardingRequest;
use App\Models\OnboardingDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Upload document for onboarding request
     */
    public function upload(Request $request, OnboardingRequest $onboardingRequest): JsonResponse
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120', // 5MB max
            'document_type' => 'required|in:resume,id_proof,address_proof,degree,previous_employment,bank_details,pan_card,aadhaar_card,passport,other',
        ]);
        
        $file = $request->file('document');
        $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();
        
        // Store file
        $path = $file->store("onboarding/{$onboardingRequest->id}", 'public');
        
        $document = OnboardingDocument::create([
            'onboarding_request_id' => $onboardingRequest->id,
            'document_type' => $request->document_type,
            'original_name' => $originalName,
            'file_path' => $path,
            'file_size' => $this->formatBytes($fileSize),
            'mime_type' => $mimeType,
            'status' => 'pending',
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'data' => $document
        ]);
    }
    
    /**
     * Verify document
     */
    public function verify(Request $request, OnboardingDocument $document): JsonResponse
    {
        $user = auth()->user();
        
        $request->validate([
            'status' => 'required|in:verified,rejected',
            'verification_notes' => 'nullable|string|max:500',
        ]);
        
        $document->update([
            'status' => $request->status,
            'verification_notes' => $request->verification_notes,
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Document verified successfully',
            'data' => $document
        ]);
    }
    
    /**
     * Delete document
     */
    public function destroy(OnboardingDocument $document): JsonResponse
    {
        Storage::disk('public')->delete($document->file_path);
        $document->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully'
        ]);
    }
    
    /**
     * Download document
     */
    public function download(OnboardingDocument $document)
    {
        $filePath = storage_path("app/public/{$document->file_path}");
        
        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }
        
        return response()->download($filePath, $document->original_name);
    }
    
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}