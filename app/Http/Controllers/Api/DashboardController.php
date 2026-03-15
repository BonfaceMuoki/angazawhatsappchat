<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    private const LEAD_STAGES = ['pricing', 'education', 'conversion', 'complete'];

    private const LEAD_TIER_MAP = [
        'complete' => 'hot',
        'conversion' => 'hot',
        'education' => 'warm',
        'pricing' => 'warm',
    ];

    /**
     * Stats and recent conversations for the dashboard.
     */
    public function stats(): JsonResponse
    {
        $totalChats = Conversation::count();
        $totalUnread = Conversation::all()->sum(fn ($c) => $c->getUnreadCount());
        $messagesToday = Message::whereDate('created_at', today())->count();

        $leadConversations = Conversation::whereIn('stage', self::LEAD_STAGES)->get();
        $totalLeads = $leadConversations->count();

        $conversations = Conversation::all();
        $phones = $conversations->pluck('phone')->all();

        $lastMessages = [];
        if ($phones !== []) {
            $lastMessages = Message::whereIn('phone', $phones)
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('phone')
                ->map(fn ($group) => $group->first());
        }

        $recent = $conversations
            ->map(function ($c) use ($lastMessages) {
                $last = $lastMessages[$c->phone] ?? null;
                return [
                    'phone' => $c->phone,
                    'stage' => $c->stage,
                    'last_message_body' => $last ? $last->body : null,
                    'last_message_at' => $last ? $last->created_at->toIso8601String() : null,
                    'unread_count' => $c->getUnreadCount(),
                ];
            })
            ->sortByDesc(fn ($i) => $i['last_message_at'] ?? '')
            ->values()
            ->take(10)
            ->all();

        $recentLeads = $leadConversations
            ->map(function ($c) use ($lastMessages) {
                $last = $lastMessages[$c->phone] ?? null;
                return [
                    'phone' => $c->phone,
                    'stage' => $c->stage,
                    'lead_tier' => self::LEAD_TIER_MAP[$c->stage] ?? 'warm',
                    'last_message_at' => $last ? $last->created_at->toIso8601String() : null,
                ];
            })
            ->sortByDesc(fn ($i) => $i['last_message_at'] ?? '')
            ->values()
            ->take(5)
            ->all();

        return response()->json([
            'data' => [
                'total_chats' => $totalChats,
                'total_unread' => $totalUnread,
                'messages_today' => $messagesToday,
                'total_leads' => $totalLeads,
                'recent_conversations' => $recent,
                'recent_leads' => $recentLeads,
            ],
        ]);
    }
}
