<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Job;
use App\Models\Candidate;

class RecruitmentController extends Controller
{
    public function dashboard(Request $request)
{
    $perPage = $request->integer('per_page', 10);

    // 1. Get your dashboard counts
    $stats = [
        'total_jobs'       => Job::where('status', 'open')->count(),
        'total_candidates' => Candidate::count(),
        'interviews'       => Candidate::where('status', 'interviewing')->count(),
        'selected'         => Candidate::where('status', 'selected')->count(),
    ];

    // 2. Paginate your candidates query collection
    $candidatesPaginator = Candidate::with('job')->orderBy('created_at', 'desc')->paginate($perPage);

    // 3. Return the exact response structure matching the frontend keys
    return response()->json([
        'stats' => $stats,
        'data'  => $candidatesPaginator->items(), // The raw candidates array
        'meta'  => [
            'current_page' => $candidatesPaginator->currentPage(),
            'last_page'    => $candidatesPaginator->lastPage(),
            'per_page'     => $candidatesPaginator->perPage(),
            'total'        => $candidatesPaginator->total(),
        ],
    ]);
}

    public function updateStatus(
        Request $request,
        $candidateId
    ) {

        $request->validate([

            'status' =>
                'required|in:interview,selected,rejected,hired'
        ]);

        $candidate = Candidate::findOrFail(
            $candidateId
        );

        $candidate->update([

            'status' => $request->status,

            'interview_date' =>
                $candidate->interview_date
                    ?? now()->addDays(3),
        ]);

        return response()->json([

            'message' =>
                'Candidate status updated successfully',

            'candidate' => $candidate,
        ]);
    }
}