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
            'active_status' => ['nullable', 'integer', 'in:0,1'],
        ]);

        if (empty($validated['type'])) {
            $validated['type'] = 'mail';
        }

        // Default active_status to 1 if not specified
        if (!isset($validated['active_status'])) {
            $validated['active_status'] = 1;
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
            'active_status' => ['nullable', 'integer', 'in:0,1'],
        ]);

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
        $template->delete();

        return response()->json([
            'message' => 'Mail template deleted successfully.'
        ]);
    }
}
