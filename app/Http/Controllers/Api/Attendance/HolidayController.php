<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HolidayController extends Controller
{
    // ── GET /api/v1/holidays?year=&month= ─────────────────────────
    //
    // Public to all authenticated users.
    // Returns holidays for the requested month, recurring ones included.

    public function index(Request $request): JsonResponse
    {
        $year  = (int) $request->query('year',  now()->year);
        $month = (int) $request->query('month', now()->month);

        $holidays = Holiday::forMonth($year, $month)
            ->orderBy('date')
            ->get()
            ->map(fn(Holiday $h) => [
                'id'           => $h->id,
                'name'         => $h->name,
                'date'         => $h->dateForYear($year), // correct year for recurring
                'type'         => $h->type,
                'description'  => $h->description,
                'is_recurring' => $h->is_recurring,
            ]);

        return response()->json([
            'year'  => $year,
            'month' => $month,
            'data'  => $holidays,
        ]);
    }

    // ── POST /api/v1/holidays ─────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $request->validate([
            'name'         => ['required', 'string', 'max:200'],
            'date'         => ['required', 'date'],
            'type'         => ['nullable', 'in:public,optional,restricted'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'is_recurring' => ['nullable', 'boolean'],
        ]);

        $holiday = Holiday::create([
            'name'         => $request->name,
            'date'         => $request->date,
            'type'         => $request->type         ?? 'public',
            'description'  => $request->description  ?? null,
            'is_recurring' => $request->is_recurring ?? false,
        ]);

        return response()->json([
            'message' => 'Holiday created successfully.',
            'data'    => $holiday,
        ], 201);
    }

    // ── GET /api/v1/holidays/{holiday} ────────────────────────────

    public function show(Holiday $holiday): JsonResponse
    {
        return response()->json(['data' => $holiday]);
    }

    // ── PUT /api/v1/holidays/{holiday} ────────────────────────────

    public function update(Request $request, Holiday $holiday): JsonResponse
    {
        $this->authorizeAdmin();

        $request->validate([
            'name'         => ['sometimes', 'string', 'max:200'],
            'date'         => ['sometimes', 'date'],
            'type'         => ['sometimes', 'in:public,optional,restricted'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'is_recurring' => ['sometimes', 'boolean'],
        ]);

        $holiday->update($request->only(['name', 'date', 'type', 'description', 'is_recurring']));

        return response()->json([
            'message' => 'Holiday updated successfully.',
            'data'    => $holiday,
        ]);
    }

    // ── DELETE /api/v1/holidays/{holiday} ─────────────────────────

    public function destroy(Holiday $holiday): JsonResponse
    {
        $this->authorizeAdmin();

        $holiday->delete();

        return response()->json(['message' => 'Holiday deleted successfully.']);
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function authorizeAdmin(): void
    {
        $user = auth()->user();

        if (!$user->hasRole('admin') && !$user->hasRole('hr')) {
            abort(403, 'Only Admin or HR can manage holidays.');
        }
    }
}