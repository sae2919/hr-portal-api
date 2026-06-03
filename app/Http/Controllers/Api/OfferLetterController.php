<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OnboardingRequest;
use App\Models\OfferLetter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use PDF;

class OfferLetterController extends Controller
{
    /**
     * Generate offer letter
     */
    public function generate(Request $request, OnboardingRequest $onboardingRequest): JsonResponse
    {
        $request->validate([
            'letter_date' => 'required|date',
            'content' => 'nullable|string',
        ]);
        
        // Generate PDF
        $pdf = PDF::loadView('pdf.offer-letter', [
            'candidate' => $onboardingRequest,
            'letter_date' => $request->letter_date,
            'content' => $request->content,
        ]);
        
        $fileName = "offer_letter_{$onboardingRequest->id}_{$onboardingRequest->candidate_name}.pdf";
        $filePath = "offer_letters/{$fileName}";
        
        Storage::disk('public')->put($filePath, $pdf->output());
        
        $offerLetter = OfferLetter::create([
            'onboarding_request_id' => $onboardingRequest->id,
            'letter_date' => $request->letter_date,
            'file_path' => $filePath,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Offer letter generated successfully',
            'data' => $offerLetter
        ]);
    }
    
    /**
     * Send offer letter via email
     */
    public function send(OfferLetter $offerLetter): JsonResponse
    {
        $offerLetter->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        
        $absolutePath = storage_path("app/public/{$offerLetter->file_path}");
        
        if (file_exists($absolutePath)) {
            \App\Services\MailService::sendTemplateMail(
                $offerLetter->onboardingRequest->email,
                'candidate_offer_letter_delivery',
                [
                    'name' => $offerLetter->onboardingRequest->candidate_name,
                    'employee_name' => $offerLetter->onboardingRequest->candidate_name,
                    'position' => $offerLetter->onboardingRequest->position,
                    'department' => $offerLetter->onboardingRequest->department,
                    'joining_date' => $offerLetter->onboardingRequest->joining_date,
                ],
                [
                    [
                        'path' => $absolutePath,
                        'name' => "Offer_Letter_{$offerLetter->onboardingRequest->candidate_name}.pdf",
                        'mime' => 'application/pdf',
                    ]
                ]
            );
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Offer letter sent successfully',
            'data' => $offerLetter
        ]);
    }
    
    /**
     * Download offer letter
     */
    public function download(OfferLetter $offerLetter)
    {
        $filePath = storage_path("app/public/{$offerLetter->file_path}");
        
        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }
        
        return response()->download($filePath, "offer_letter_{$offerLetter->onboardingRequest->candidate_name}.pdf");
    }
}