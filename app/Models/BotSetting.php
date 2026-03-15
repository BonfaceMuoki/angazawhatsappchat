<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BotSetting extends Model
{
    protected $table = 'bot_settings';

    protected $fillable = ['setting_key', 'setting_value'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $cacheKey = 'bot_setting:' . $key;
        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $row = static::where('setting_key', $key)->first();
            return $row ? $row->setting_value : $default;
        });
    }

    public static function isEnabled(string $key): bool
    {
        $value = static::getValue($key, false);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function setValue(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
        );
        Cache::forget('bot_setting:' . $key);
    }

    protected static function booted(): void
    {
        static::saved(fn (BotSetting $s) => Cache::forget('bot_setting:' . $s->setting_key));
        static::deleted(fn (BotSetting $s) => Cache::forget('bot_setting:' . $s->setting_key));
    }
}
