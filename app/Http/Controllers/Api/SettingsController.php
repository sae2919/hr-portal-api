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
     * Admin only.
     */
    public function index(): JsonResponse
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $settings = CompanySetting::all()->pluck('value', 'key');

        return response()->json($settings);
    }

    /**
     * PUT /v1/settings
     * Update one or more company settings.
     * Admin only.
     *
     * Accepted body:
     *   { pf_enabled: bool, pt_enabled: bool, pf_percentage: float }
     */
    public function update(Request $request): JsonResponse
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'pf_enabled'    => ['sometimes', 'boolean'],
            'pt_enabled'    => ['sometimes', 'boolean'],
            'pf_percentage' => ['sometimes', 'numeric', 'min:0', 'max:12'],
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

        return response()->json(['message' => 'Settings updated successfully.']);
    }
}