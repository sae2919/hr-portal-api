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
        
        // Determine template based on onboarding type
        $onboardingType = $onboardingRequest->onboarding_type;
        $templateName = match ($onboardingType) {
            'free_intern' => 'free_internship_offer_letter',
            'intern'      => 'paid_internship_offer_letter',
            default       => 'full_time_offer_letter',
        };

        $letterDate = \Carbon\Carbon::parse($request->letter_date);
        $joiningDate = \Carbon\Carbon::parse($onboardingRequest->joining_date);

        $variables = [
            'candidate' => $onboardingRequest,
            'candidate_name' => $onboardingRequest->candidate_name,
            'position' => $onboardingRequest->position,
            'duration' => $onboardingRequest->duration ?? '3 months',
            'joining_date' => $joiningDate->format('d/m/Y'),
            'letter_date' => $letterDate->format('d-F Y'),
            'stipend' => number_format((float)($onboardingRequest->ctc ?? 0)),
            'acceptance_date' => $letterDate->copy()->addDays(2)->format('d-m-Y'),
        ];

        // Generate PDF dynamically from DB template
        $pdf = \App\Services\DocumentService::render($templateName, $variables);

        
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
            \App\Jobs\SendReusableMail::dispatch(
                'candidate_offer_letter_delivery',
                $offerLetter->onboardingRequest->email,
                [
                    'name' => $offerLetter->onboardingRequest->candidate_name,
                    'employee_name' => $offerLetter->onboardingRequest->candidate_name,
                    'position' => $offerLetter->onboardingRequest->position,
                    'department' => $offerLetter->onboardingRequest->department,
                    'joining_date' => $offerLetter->onboardingRequest->joining_date,
                ],
                null,
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