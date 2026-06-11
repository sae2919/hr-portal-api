<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MailTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MailTemplateController extends Controller
{
    /**
     * Check if the authenticated user has Admin or HR privileges.
     */
    private function authorizeAdminOrHR(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Support both Spatie Roles and the direct 'role' column check
        $hasSpatieRole = $user->hasAnyRole(['super_admin', 'super admin', 'admin', 'hr']);
        $hasDirectRole = in_array(strtolower($user->role ?? ''), ['super_admin', 'super admin', 'admin', 'hr']);

        return $hasSpatieRole || $hasDirectRole;
    }

    /**
     * Display a listing of the resource.
     * GET /api/v1/mail-templates
     */
    public function index(Request $request): JsonResponse
    {
        if (!$this->authorizeAdminOrHR()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $search = $request->string('search')->trim();
        $query = MailTemplate::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($search->isNotEmpty()) {
            $query->where(function ($q) use ($search) {
                $q->where('template_name', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $templates = $query->orderBy('template_name')->get();

        return response()->json($templates);
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/v1/mail-templates
     */
    public function store(Request $request): JsonResponse
    {
        if (!$this->authorizeAdminOrHR()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'template_name' => ['required', 'string', 'max:255', 'unique:mail_templates,template_name'],
            'type'          => ['nullable', 'string', 'max:50'],
            'subject'       => ['required', 'string'],
            'body'          => ['nullable', 'string'],
            'style'         => ['nullable', 'string'],
            'pdf_file'      => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'pdf_fields'    => ['nullable'],
            'active_status' => ['nullable', 'integer', 'in:0,1'],
        ]);

        if (empty($validated['type'])) {
            $validated['type'] = 'mail';
        }

        // Default active_status to 1 if not specified
        if (!isset($validated['active_status'])) {
            $validated['active_status'] = 1;
        }

        // Handle pdf file upload
        if ($request->hasFile('pdf_file')) {
            $file = $request->file('pdf_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('templates', $filename, 'public');
            $validated['pdf_path'] = 'storage/' . $path;
        }

        // Handle pdf fields mapping config
        if ($request->has('pdf_fields')) {
            $fields = $request->input('pdf_fields');
            if (is_string($fields)) {
                $validated['pdf_fields'] = json_decode($fields, true);
            } else {
                $validated['pdf_fields'] = $fields;
            }
        }

        $template = MailTemplate::create($validated);

        return response()->json([
            'message' => 'Mail template created successfully.',
            'data'    => $template
        ], 201);
    }

    /**
     * Display the specified resource.
     * GET /api/v1/mail-templates/{id}
     */
    public function show($id): JsonResponse
    {
        if (!$this->authorizeAdminOrHR()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $template = MailTemplate::findOrFail($id);

        return response()->json($template);
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /api/v1/mail-templates/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        if (!$this->authorizeAdminOrHR()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $template = MailTemplate::findOrFail($id);

        $validated = $request->validate([
            'template_name' => ['sometimes', 'required', 'string', 'max:255', 'unique:mail_templates,template_name,' . $template->id],
            'type'          => ['nullable', 'string', 'max:50'],
            'subject'       => ['sometimes', 'required', 'string'],
            'body'          => ['nullable', 'string'],
            'style'         => ['nullable', 'string'],
            'pdf_file'      => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'pdf_fields'    => ['nullable'],
            'active_status' => ['nullable', 'integer', 'in:0,1'],
        ]);

        // Support explicit deletion of background PDF file
        if ($request->has('delete_pdf_file') && $request->boolean('delete_pdf_file')) {
            if ($template->pdf_path) {
                $oldPath = str_replace('storage/', '', $template->pdf_path);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
            $validated['pdf_path'] = null;
            $validated['pdf_fields'] = null;
        } else {
            // Handle pdf file upload
            if ($request->hasFile('pdf_file')) {
                if ($template->pdf_path) {
                    $oldPath = str_replace('storage/', '', $template->pdf_path);
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
                }
                $file = $request->file('pdf_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('templates', $filename, 'public');
                $validated['pdf_path'] = 'storage/' . $path;
            }

            // Handle pdf fields mapping config
            if ($request->has('pdf_fields')) {
                $fields = $request->input('pdf_fields');
                if (is_string($fields)) {
                    $validated['pdf_fields'] = json_decode($fields, true);
                } else {
                    $validated['pdf_fields'] = $fields;
                }
            }
        }

        $template->update($validated);

        return response()->json([
            'message' => 'Mail template updated successfully.',
            'data'    => $template
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/v1/mail-templates/{id}
     */
    public function destroy($id): JsonResponse
    {
        if (!$this->authorizeAdminOrHR()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $template = MailTemplate::findOrFail($id);
        if ($template->pdf_path) {
            $oldPath = str_replace('storage/', '', $template->pdf_path);
            \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
        }
        $template->delete();

        return response()->json([
            'message' => 'Mail template deleted successfully.'
        ]);
    }

    /**
     * Preview the PDF template with sample data.
     * GET /api/v1/mail-templates/{id}/preview-pdf
     */
    public function previewPdf($id)
    {
        if (!$this->authorizeAdminOrHR()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $template = MailTemplate::findOrFail($id);

        $sampleVariables = [
            'candidate_name' => 'Ryagati Venkatesh',
            'position'       => 'Software Engineer Intern',
            'duration'       => '3 Months',
            'joining_date'   => '15-Jun-2026',
            'stipend'        => '15,000',
            'letter_date'    => '11-Jun-2026',
            'acceptance_date'=> '13-Jun-2026',
            'employee_name'  => 'Ryagati Venkatesh',
            'designation'    => 'Software Engineer Intern',
            'last_working_day'=> '30-Jun-2026',
            'employee_code'  => 'TS1002',
            'month'          => 'June',
            'year'           => '2026',
            'net_salary'     => '15,000.00',
            'present_days'   => '30',
            'lop_days'       => '0',
            'lop_deduction'  => '0.00',
            'basic_salary'   => '15,000',
            'hra'            => '0',
            'allowances'     => '0',
            'gross_salary'   => '15,000',
            'net_pay_words'  => 'Rupees Fifteen Thousand Only',
            'company_name'   => 'Techsprout AI Labs',
            'company_address'=> 'JNTU Road, KPHB, Hyderabad',
            'salutation'     => 'Mr.',
        ];

        try {
            $pdf = \App\Services\DocumentService::render($template->template_name, $sampleVariables);
            return response($pdf->output())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="preview.pdf"');
        } catch (\Exception $e) {
            return response()->json(['message' => 'PDF Preview generation failed: ' . $e->getMessage()], 500);
        }
    }
}
