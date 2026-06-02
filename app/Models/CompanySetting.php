<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = ['key', 'value', 'value_numeric'];

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * Check if a boolean flag is enabled.
     * e.g. CompanySetting::isEnabled('pf_enabled')
     */
    public static function isEnabled(string $key): bool
    {
        $value = static::where('key', $key)->value('value');
        return in_array($value, ['1', 'true', 'yes', true], true);
    }

    /**
     * Get a setting value as a string.
     * e.g. CompanySetting::getValue('pf_percentage') // "12"
     */
    public static function getValue(string $key): ?string
    {
        return static::where('key', $key)->value('value');
    }

    /**
     * Set or update a setting value.
     * e.g. CompanySetting::set('pf_percentage', '10')
     */
    public static function set(string $key, string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}