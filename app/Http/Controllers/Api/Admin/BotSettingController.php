<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotSettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = BotSetting::all()->pluck('setting_value', 'setting_key');
        return response()->json(['data' => $settings]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ai_enabled' => 'sometimes|boolean',
            'ai_mode' => 'sometimes|string|in:off,intent_detection,response_interpretation,full',
        ]);

        foreach ($validated as $key => $value) {
            BotSetting::setValue($key, is_bool($value) ? ($value ? '1' : '0') : $value);
        }

        $settings = BotSetting::all()->pluck('setting_value', 'setting_key');
        return response()->json(['data' => $settings]);
    }
}
