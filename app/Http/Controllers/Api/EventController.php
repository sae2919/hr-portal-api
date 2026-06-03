<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    /**
     * Get all events (with filters)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }
            
            $query = Event::query();
            
            // Date range filter
            if ($request->start_date) {
                $query->where('event_date', '>=', $request->start_date);
            }
            if ($request->end_date) {
                $query->where('event_date', '<=', $request->end_date);
            }
            
            // Type filter
            if ($request->type) {
                $query->where('type', $request->type);
            }
            
            // Month/Year filter for calendar view
            if ($request->month && $request->year) {
                $query->whereMonth('event_date', $request->month)
                      ->whereYear('event_date', $request->year);
            }
            
            $events = $query->orderBy('event_date')->get();
            
            return response()->json(['success' => true, 'data' => $events]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching events: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to fetch events', 'data' => []], 500);
        }
    }
    
    /**
     * Get upcoming events (for dashboard widget)
     */
    public function upcoming(): JsonResponse
    {
        try {
            $today = Carbon::today();
            $endDate = Carbon::today()->addDays(30);
            
            $events = Event::where('event_date', '>=', $today)
                ->where('event_date', '<=', $endDate)
                ->orderBy('event_date')
                ->limit(10)
                ->get();
            
            return response()->json(['success' => true, 'data' => $events]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching upcoming events: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to fetch upcoming events', 'data' => []], 500);
        }
    }
    
    /**
     * Get today's birthdays and anniversaries from employees table
     */
    public function todaySpecial(): JsonResponse
    {
        try {
            $today = Carbon::today();
            $month = $today->month;
            $day = $today->day;
            
            // Get today's birthdays from employees table (using 'dob' column)
            $birthdays = Employee::where('status', 'active')
                ->whereNotNull('dob')
                ->whereRaw('MONTH(dob) = ?', [$month])
                ->whereRaw('DAY(dob) = ?', [$day])
                ->with(['department', 'designation'])
                ->get();
            
            // Get today's work anniversaries (joining date anniversary)
            $anniversaries = Employee::where('status', 'active')
                ->whereNotNull('joining_date')
                ->whereRaw('MONTH(joining_date) = ?', [$month])
                ->whereRaw('DAY(joining_date) = ?', [$day])
                ->with(['department', 'designation'])
                ->get();
            
            // Calculate age for birthdays
            $birthdaysWithAge = $birthdays->map(function($employee) use ($today) {
                $dob = Carbon::parse($employee->dob);
                $age = $dob->age;
                return [
                    'id' => $employee->id,
                    'name' => $employee->first_name . ' ' . $employee->last_name,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'date_of_birth' => $employee->dob,
                    'age' => $age,
                    'department' => $employee->department?->name,
                    'designation' => $employee->designation?->name,
                    'photo' => $employee->photo,
                ];
            });
            
            // Calculate years of service for anniversaries
            $anniversariesWithYears = $anniversaries->map(function($employee) use ($today) {
                $joiningDate = Carbon::parse($employee->joining_date);
                $yearsOfService = $joiningDate->diffInYears($today);
                return [
                    'id' => $employee->id,
                    'name' => $employee->first_name . ' ' . $employee->last_name,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'joining_date' => $employee->joining_date,
                    'years_of_service' => $yearsOfService,
                    'department' => $employee->department?->name,
                    'designation' => $employee->designation?->name,
                    'photo' => $employee->photo,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'birthdays' => $birthdaysWithAge,
                    'anniversaries' => $anniversariesWithYears,
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching today special: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Failed to fetch today special', 
                'data' => ['birthdays' => [], 'anniversaries' => []]
            ], 500);
        }
    }
    
    /**
     * Get upcoming birthdays in the next X days
     */
    public function upcomingBirthdays(Request $request): JsonResponse
    {
        try {
            $days = $request->days ?? 30;
            $today = Carbon::today();
            
            $birthdays = Employee::where('status', 'active')
                ->whereNotNull('dob')
                ->whereRaw(
                    "DATE_FORMAT(dob, '%m-%d') BETWEEN ? AND ?",
                    [$today->format('m-d'), $today->copy()->addDays($days)->format('m-d')]
                )
                ->orderByRaw('MONTH(dob), DAY(dob)')
                ->with(['department', 'designation'])
                ->get()
                ->map(function($employee) {
                    $nextBirthday = Carbon::parse(
                        Carbon::now()->year . '-' . 
                        Carbon::parse($employee->dob)->format('m-d')
                    );
                    if ($nextBirthday->isPast()) {
                        $nextBirthday->addYear();
                    }
                    $daysUntil = Carbon::now()->diffInDays($nextBirthday);
                    
                    return [
                        'id' => $employee->id,
                        'name' => $employee->first_name . ' ' . $employee->last_name,
                        'date_of_birth' => $employee->dob,
                        'days_until' => $daysUntil,
                        'department' => $employee->department?->name,
                        'designation' => $employee->designation?->name,
                    ];
                });
            
            return response()->json(['success' => true, 'data' => $birthdays]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching upcoming birthdays: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to fetch upcoming birthdays', 'data' => []], 500);
        }
    }
    
    /**
     * Store a new event (Admin/HR only)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            if (!in_array(auth()->user()->role, ['super_admin', 'admin', 'hr'])) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
            $request->validate([
                'title' => 'required|string|max:255',
                'type' => 'required|in:holiday,birthday,company_event,meeting,training,other',
                'event_date' => 'required|date',
                'description' => 'nullable|string',
                'location' => 'nullable|string',
                'color' => 'nullable|string',
            ]);
            
            $event = Event::create([
                'title' => $request->title,
                'description' => $request->description,
                'type' => $request->type,
                'event_date' => $request->event_date,
                'end_date' => $request->end_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'location' => $request->location,
                'color' => $request->color ?? '#3B82F6',
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);
            
            return response()->json(['success' => true, 'data' => $event, 'message' => 'Event created successfully']);
            
        } catch (\Exception $e) {
            Log::error('Error creating event: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create event'], 500);
        }
    }
    
    /**
     * Update event (Admin/HR only)
     */
    public function update(Request $request, Event $event): JsonResponse
    {
        try {
            if (!in_array(auth()->user()->role, ['super_admin', 'admin', 'hr'])) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
            $event->update($request->all());
            
            return response()->json(['success' => true, 'data' => $event, 'message' => 'Event updated successfully']);
            
        } catch (\Exception $e) {
            Log::error('Error updating event: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update event'], 500);
        }
    }
    
    /**
     * Delete event (Admin/HR only)
     */
    public function destroy(Event $event): JsonResponse
    {
        try {
            if (!in_array(auth()->user()->role, ['super_admin', 'admin', 'hr'])) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
            $event->delete();
            
            return response()->json(['success' => true, 'message' => 'Event deleted successfully']);
            
        } catch (\Exception $e) {
            Log::error('Error deleting event: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete event'], 500);
        }
    }
}