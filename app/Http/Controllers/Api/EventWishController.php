<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventWish;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class EventWishController extends Controller
{
    /**
     * GET /api/v1/events/wishes/{employeeId}?wish_type=birthday|anniversary
     *
     * Returns all wishes sent to the given employee today.
     */
    public function index(Request $request, int $employeeId): JsonResponse
    {
        $request->validate([
            'wish_type' => 'nullable|in:birthday,anniversary',
        ]);

        $wishes = EventWish::with('sender:id,first_name,last_name,avatar')
            ->where('employee_id', $employeeId)
            ->whereDate('created_at', today())
            ->when($request->wish_type, fn ($q) => $q->where('wish_type', $request->wish_type))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($w) => [
                'id'           => $w->id,
                'employee_id'  => $w->employee_id,
                'sender_id'    => $w->sender_id,
                'sender_name'  => $w->sender
                    ? trim("{$w->sender->first_name} {$w->sender->last_name}")
                    : 'Unknown',
                'sender_avatar' => $w->sender?->avatar,
                'wish_type'    => $w->wish_type,
                'message'      => $w->message,
                'emoji'        => $w->emoji,
                'created_at'   => $w->created_at->toISOString(),
            ]);

        return response()->json(['success' => true, 'data' => $wishes]);
    }

    /**
     * POST /api/v1/events/wishes
     *
     * Send a wish. Non-admin users can only send one per employee per day.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'wish_type'   => 'required|in:birthday,anniversary',
            'message'     => 'required|string|min:1|max:500',
            'emoji'       => 'nullable|string|max:10',
        ]);

        $sender = Auth::user();

        // Prevent duplicate wishes (allow admin / hr to bypass)
        $isPrivileged = in_array($sender->role, ['admin', 'hr']);

        if (!$isPrivileged) {
            $alreadySent = EventWish::where('sender_id', $sender->id)
                ->where('employee_id', $request->employee_id)
                ->where('wish_type', $request->wish_type)
                ->whereDate('created_at', today())
                ->exists();

            if ($alreadySent) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already sent a wish today!',
                ], 409);
            }
        }

        $wish = EventWish::create([
            'employee_id' => $request->employee_id,
            'sender_id'   => $sender->id,
            'wish_type'   => $request->wish_type,
            'message'     => $request->message,
            'emoji'       => $request->emoji,
        ]);

        $wish->load('sender:id,first_name,last_name,avatar');

        return response()->json([
            'success' => true,
            'data'    => [
                'id'           => $wish->id,
                'employee_id'  => $wish->employee_id,
                'sender_id'    => $wish->sender_id,
                'sender_name'  => $wish->sender
                    ? trim("{$wish->sender->first_name} {$wish->sender->last_name}")
                    : 'Unknown',
                'sender_avatar' => $wish->sender?->avatar,
                'wish_type'    => $wish->wish_type,
                'message'      => $wish->message,
                'emoji'        => $wish->emoji,
                'created_at'   => $wish->created_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * DELETE /api/v1/events/wishes/{wishId}
     *
     * Only the sender or admin/hr can delete.
     */
    public function destroy(int $wishId): JsonResponse
    {
        $wish = EventWish::findOrFail($wishId);
        $user = Auth::user();

        $isOwner      = $wish->sender_id === $user->id;
        $isPrivileged = in_array($user->role, ['admin', 'hr']);

        if (!$isOwner && !$isPrivileged) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised',
            ], 403);
        }

        $wish->delete();

        return response()->json(['success' => true]);
    }
}