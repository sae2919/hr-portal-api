<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class QuoteController extends Controller
{
    /**
     * Get all quotes (Admin/HR only)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            if (!in_array(auth()->user()->role, ['admin', 'hr'])) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
            $quotes = Quote::orderBy('display_order')->get();
            
            return response()->json(['success' => true, 'data' => $quotes]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching quotes: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to fetch quotes', 'data' => []], 500);
        }
    }
    
    /**
     * Get random quote for dashboard (everyone)
     */
    public function random(): JsonResponse
    {
        try {
            $quote = Quote::getRandomQuote();
            
            if (!$quote) {
                return response()->json([
                    'success' => true,
                    'data' => (object)[
                        'quote' => 'The only way to do great work is to love what you do.',
                        'author' => 'Steve Jobs',
                        'category' => 'motivational'
                    ]
                ]);
            }
            
            return response()->json(['success' => true, 'data' => $quote]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching random quote: ' . $e->getMessage());
            return response()->json([
                'success' => true,
                'data' => (object)[
                    'quote' => 'Stay positive, work hard, make it happen.',
                    'author' => 'Unknown',
                    'category' => 'motivational'
                ]
            ]);
        }
    }
    
    /**
     * Get quote of the day
     */
    public function quoteOfTheDay(): JsonResponse
    {
        try {
            $quote = Quote::getQuoteOfTheDay();
            
            return response()->json(['success' => true, 'data' => $quote]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching quote of the day: ' . $e->getMessage());
            return response()->json(['success' => true, 'data' => null]);
        }
    }
    
    /**
     * Store new quote (Admin/HR only)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            if (!in_array(auth()->user()->role, ['admin', 'hr'])) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
            $request->validate([
                'quote' => 'required|string',
                'author' => 'nullable|string|max:255',
                'category' => 'required|in:motivational,funny,inspirational,daily',
            ]);
            
            $quote = Quote::create([
                'quote' => $request->quote,
                'author' => $request->author,
                'category' => $request->category,
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);
            
            return response()->json(['success' => true, 'data' => $quote, 'message' => 'Quote added successfully']);
            
        } catch (\Exception $e) {
            Log::error('Error creating quote: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create quote'], 500);
        }
    }
    
    /**
     * Update quote (Admin/HR only)
     */
    public function update(Request $request, Quote $quote): JsonResponse
    {
        try {
            if (!in_array(auth()->user()->role, ['admin', 'hr'])) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
            $quote->update($request->all());
            
            return response()->json(['success' => true, 'data' => $quote, 'message' => 'Quote updated successfully']);
            
        } catch (\Exception $e) {
            Log::error('Error updating quote: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update quote'], 500);
        }
    }
    
    /**
     * Delete quote (Admin/HR only)
     */
    public function destroy(Quote $quote): JsonResponse
    {
        try {
            if (!in_array(auth()->user()->role, ['admin', 'hr'])) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
            $quote->delete();
            
            return response()->json(['success' => true, 'message' => 'Quote deleted successfully']);
            
        } catch (\Exception $e) {
            Log::error('Error deleting quote: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete quote'], 500);
        }
    }
    
    /**
     * Toggle quote status (Admin/HR only)
     */
    public function toggleStatus(Quote $quote): JsonResponse
    {
        try {
            if (!in_array(auth()->user()->role, ['admin', 'hr'])) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
            $quote->is_active = !$quote->is_active;
            $quote->save();
            
            return response()->json(['success' => true, 'message' => 'Quote status updated']);
            
        } catch (\Exception $e) {
            Log::error('Error toggling quote status: ' . $e->GetMessage());
            return response()->json(['success' => false, 'message' => 'Failed to toggle quote status'], 500);
        }
    }
}