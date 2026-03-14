<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $cacheKey = 'setting:' . $key;

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public static function isEnabled(string $key): bool
    {
        $value = static::getValue($key, false);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    protected static function booted(): void
    {
        static::saved(function (Setting $setting) {
            Cache::forget('setting:' . $setting->key);
        });
        static::deleted(function (Setting $setting) {
            Cache::forget('setting:' . $setting->key);
        });
    }
}
