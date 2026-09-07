<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a setting value by key with a fallback.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (!$setting || $setting->value === null) {
            return $default;
        }

        $val = $setting->value;
        if ($val === 'true') return true;
        if ($val === 'false') return false;
        if (is_numeric($val)) return $val + 0;

        $decoded = json_decode($val, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $val;
    }

    /**
     * Set or update a setting value by key.
     */
    public static function set(string $key, mixed $value): void
    {
        if (is_bool($value)) {
            $valString = $value ? 'true' : 'false';
        } elseif (is_array($value)) {
            $valString = json_encode($value);
        } elseif ($value === null) {
            $valString = null;
        } else {
            $valString = (string) $value;
        }

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $valString]
        );
    }
}
