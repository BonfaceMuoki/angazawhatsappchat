<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    /** Normalized lead funnel stages exposed to the dashboard. */
    private const LEAD_STAGES = ['pricing', 'education', 'conversion', 'complete'];

    /** IADL node keys that indicate each normalized lead stage. */
    private const STAGE_NODE_MAP = [
        'pricing' => [
            'branch_tuition', 'tuition_programs', 'tuition_plans', 'tuition_scholarships', 'tuition_includes', 'tuition_cta',
        ],
        'education' => [
            'branch_learn', 'learn_next', 'branch_explore', 'explore_4w', 'explore_4w_next', 'explore_12w', 'explore_12w_next',
            'explore_help', 'branch_parent', 'parent_overview', 'parent_admissions', 'parent_tuition', 'parent_schedule', 'parent_outcomes',
        ],
        'conversion' => [
            'branch_apply', 'apply_readiness', 'apply_steps', 'apply_route', 'branch_info', 'branch_career', 'career_next',
        ],
        'complete' => [
            'branch_human', 'branch_partner', 'partner_escalate',
        ],
    ];

    /** Common IADL option values / labels that signal lead intent. */
    private const RESPONSE_SIGNAL_MAP = [
        'pricing' => ['by_program', 'plans', 'scholarships', 'includes', 'tuition', 'payment plans', 'what tuition includes'],
        'education' => ['explore', '4w', '12w', 'help', 'learn about iadl', 'explore programs', 'program overview', 'schedule & format'],
        'conversion' => ['apply', 'apply now', 'path_4w', 'path_12w', 'book info session', 'info', 'support', 'admissions'],
        'complete' => ['human', 'talk to admissions', 'talk to a person'],
    ];

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
        $stage = $request->get('stage');
        $perPage = max(5, min(50, (int) $request->get('per_page', 15)));
        $conversations = Conversation::orderByDesc('updated_at')->get();
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
            $leadStage = $this->detectLeadStage($c, $last?->body);
            if ($leadStage === null) {
                return null;
            }
            return [
                'phone' => $c->phone,
                'stage' => $leadStage,
                'raw_stage' => $c->stage,
                'lead_tier' => self::LEAD_TIER_MAP[$leadStage] ?? 'warm',
                'last_message_body' => $last ? $last->body : null,
                'last_message_at' => $last ? $last->created_at->toIso8601String() : null,
                'message_count' => (int) ($counts[$c->phone] ?? 0),
                'unread_count' => $c->getUnreadCount(),
            ];
        })->filter()->values();

        if ($stage !== null && $stage !== '') {
            $items = $items->filter(fn ($item) => ($item['stage'] ?? '') === $stage)->values();
        }

        $items = $items->sortByDesc(fn ($i) => $i['last_message_at'] ?? '')->values();

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

    private function detectLeadStage(Conversation $conversation, ?string $lastMessageBody): ?string
    {
        $stage = strtolower(trim((string) $conversation->stage));
        $message = strtolower(trim((string) ($lastMessageBody ?? '')));

        // Highest intent first.
        foreach (['complete', 'conversion', 'pricing', 'education'] as $bucket) {
            if (in_array($stage, self::STAGE_NODE_MAP[$bucket], true)) {
                return $bucket;
            }
            foreach (self::RESPONSE_SIGNAL_MAP[$bucket] as $signal) {
                if ($message !== '' && str_contains($message, strtolower($signal))) {
                    return $bucket;
                }
            }
        }

        // Preserve compatibility for older generic stages if present.
        if (in_array($stage, self::LEAD_STAGES, true)) {
            return $stage;
        }

        return null;
    }
}
