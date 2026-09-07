<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }

        $val = $setting->value;
        if ($val === 'true') return true;
        if ($val === 'false') return false;
        if (is_numeric($val)) return $val + 0;

        $decoded = json_decode($val, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $val;
    }

    public static function set(string $key, $value): void
    {
        if (is_bool($value)) {
            $valString = $value ? 'true' : 'false';
        } elseif (is_array($value)) {
            $valString = json_encode($value);
        } else {
            $valString = (string) $value;
        }

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $valString]
        );
    }
}
