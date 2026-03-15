<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    /** Stages considered "potential leads" (showed interest in pricing or further). */
    private const LEAD_STAGES = ['pricing', 'education', 'conversion', 'complete'];

    /** Hot = conversion/complete; warm = pricing/education. */
    private const LEAD_TIER_MAP = [
        'complete' => 'hot',
        'conversion' => 'hot',
        'education' => 'warm',
        'pricing' => 'warm',
    ];

    /**
     * List potential leads (conversations in pricing, education, conversion, complete).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Conversation::whereIn('stage', self::LEAD_STAGES);

        $stage = $request->get('stage');
        if ($stage !== null && $stage !== '') {
            $query->where('stage', $stage);
        }

        $perPage = max(5, min(50, (int) $request->get('per_page', 15)));
        $conversations = $query->orderByDesc('updated_at')->get();
        $phones = $conversations->pluck('phone')->all();

        if ($phones === []) {
            return response()->json([
                'data' => [],
                'pagination' => [
                    'total' => 0,
                    'per_page' => $perPage,
                    'current_page' => 1,
                    'last_page' => 1,
                ],
            ]);
        }

        $lastMessages = Message::whereIn('phone', $phones)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('phone')
            ->map(fn ($group) => $group->first());

        $counts = Message::whereIn('phone', $phones)
            ->selectRaw('phone, count(*) as message_count')
            ->groupBy('phone')
            ->pluck('message_count', 'phone');

        $items = $conversations->map(function (Conversation $c) use ($lastMessages, $counts) {
            $last = $lastMessages->get($c->phone);
            return [
                'phone' => $c->phone,
                'stage' => $c->stage,
                'lead_tier' => self::LEAD_TIER_MAP[$c->stage] ?? 'warm',
                'last_message_body' => $last ? $last->body : null,
                'last_message_at' => $last ? $last->created_at->toIso8601String() : null,
                'message_count' => (int) ($counts[$c->phone] ?? 0),
                'unread_count' => $c->getUnreadCount(),
            ];
        })->sortByDesc(fn ($i) => $i['last_message_at'] ?? '')->values();

        $total = $items->count();
        $page = max(1, (int) $request->get('page', 1));
        $lastPage = (int) ceil($total / $perPage);
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;
        $items = $items->slice($offset, $perPage)->values()->all();

        return response()->json([
            'data' => $items,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $lastPage,
            ],
        ]);
    }
}
