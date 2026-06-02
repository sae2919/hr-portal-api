<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'date',
        'type',
        'description',
        'is_recurring',
    ];

    protected $casts = [
        'date'         => 'date',
        'is_recurring' => 'boolean',
    ];

    // ── Scopes ────────────────────────────────────────────────────

    /**
     * All holidays that fall within a given month/year.
     * Handles is_recurring: recurring holidays match any year.
     */
    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->where(function ($q) use ($year, $month) {
            // Non-recurring: exact year + month
            $q->where('is_recurring', false)
              ->whereYear('date', $year)
              ->whereMonth('date', $month);
        })->orWhere(function ($q) use ($month) {
            // Recurring: only month matters
            $q->where('is_recurring', true)
              ->whereMonth('date', $month);
        });
    }

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * Returns the display date string for the given year
     * (recurring holidays substitute the requested year).
     */
    public function dateForYear(int $year): string
    {
        if ($this->is_recurring) {
            return Carbon::createFromDate(
                $year,
                $this->date->month,
                $this->date->day
            )->toDateString();
        }

        return $this->date->toDateString();
    }
}