<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * GET /v1/settings
     * Returns all company settings as a flat key→value object.
     * Allowed for all authenticated users to render dynamic company branding.
     */
    public function index(): JsonResponse
    {
        $settings = CompanySetting::all()->pluck('value', 'key');

        return response()->json($settings);
    }

    /**
     * PUT /v1/settings
     * Update one or more company settings.
     * Admin/Super Admin only.
     */
    public function update(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!in_array($user->role, ['super_admin', 'super admin', 'admin'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'pf_enabled'    => ['sometimes', 'boolean'],
            'pt_enabled'    => ['sometimes', 'boolean'],
            'pf_percentage' => ['sometimes', 'numeric', 'min:0', 'max:12'],
            'company_name'  => ['sometimes', 'string', 'max:100'],
            'company_logo'  => ['sometimes'], // Can be file or URL
        ]);

        if ($request->has('pf_enabled')) {
            CompanySetting::set('pf_enabled', $request->boolean('pf_enabled') ? '1' : '0');
        }

        if ($request->has('pt_enabled')) {
            CompanySetting::set('pt_enabled', $request->boolean('pt_enabled') ? '1' : '0');
        }

        if ($request->has('pf_percentage')) {
            CompanySetting::set('pf_percentage', (string) $request->pf_percentage);
        }

        if ($request->has('company_name')) {
            CompanySetting::set('company_name', $request->company_name);
        }

        if ($request->hasFile('company_logo')) {
            $file = $request->file('company_logo');
            $path = $file->store('branding', 'public');
            CompanySetting::set('company_logo', asset('storage/' . $path));
        } elseif ($request->has('company_logo') && !is_null($request->company_logo)) {
            CompanySetting::set('company_logo', $request->company_logo);
        }

        return response()->json(['message' => 'Settings updated successfully.']);
    }
}