<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BotEdge;
use App\Models\BotFlow;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /** All funnel stages in order. */
    private const FUNNEL_STAGES = [
        'entry', 'role', 'commitment', 'experience', 'pricing', 'education', 'conversion', 'complete',
    ];
    private const STAGE_LABELS = [
        'entry' => 'Entry',
        'role' => 'Role',
        'commitment' => 'Commitment',
        'experience' => 'Experience',
        'pricing' => 'Pricing',
        'education' => 'Education',
        'conversion' => 'Conversion',
        'complete' => 'Complete',
        'branch_human' => 'Human Support',
    ];

    /**
     * Funnel counts and recent user responses for analysis.
     */
    public function overview(Request $request): JsonResponse
    {
        $funnel = [];
        foreach (self::FUNNEL_STAGES as $stage) {
            $funnel[$stage] = Conversation::where('stage', $stage)->count();
        }

        $limit = max(10, min(100, (int) $request->get('recent_limit', 50)));
        $leadOnly = $request->boolean('leads_only');

        $conversations = Conversation::all()->keyBy('phone');
        $phones = $conversations->keys()->all();
        $activeFlowMap = $this->getActiveFlowChoiceMap();
        $flowOptionMapCache = [];

        $recentResponses = [];
        if ($phones !== []) {
            $q = Message::where('direction', Message::DIRECTION_INCOMING)
                ->whereIn('phone', $phones)
                ->orderByDesc('created_at')
                ->limit($limit * 2);

            $messages = $q->get();
            foreach ($messages as $m) {
                $conv = $conversations->get($m->phone);
                if (!$conv) {
                    continue;
                }
                if ($leadOnly && !in_array($conv->stage, ['pricing', 'education', 'conversion', 'complete'], true)) {
                    continue;
                }
                $recentResponses[] = [
                    'phone' => $m->phone,
                    'stage' => $conv->stage,
                    'stage_label' => $this->humanizeStage((string) $conv->stage),
                    'body' => $m->body,
                    'response_text' => $this->humanizeResponse(
                        $m->body,
                        $conv,
                        $activeFlowMap,
                        $flowOptionMapCache
                    ),
                    'created_at' => $m->created_at->toIso8601String(),
                ];
                if (count($recentResponses) >= $limit) {
                    break;
                }
            }
        }

        return response()->json([
            'data' => [
                'funnel' => $funnel,
                'recent_responses' => $recentResponses,
            ],
        ]);
    }

    private function humanizeStage(string $stage): string
    {
        if (isset(self::STAGE_LABELS[$stage])) {
            return self::STAGE_LABELS[$stage];
        }
        return $this->humanizeToken($stage);
    }

    private function humanizeResponse(
        ?string $value,
        Conversation $conversation,
        array $activeFlowMap,
        array &$flowOptionMapCache
    ): string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return '—';
        }
        $normalized = strtolower($value);
        // Prefer explicit option labels for historical stored IDs/values.
        if (isset($activeFlowMap[$normalized])) {
            return $activeFlowMap[$normalized];
        }
        $flowOptionMap = $this->getFlowOptionMap((int) ($conversation->flow_id ?? 0), $flowOptionMapCache);
        if (isset($flowOptionMap[$normalized])) {
            return $flowOptionMap[$normalized];
        }
        // Keep URLs untouched so they stay readable/clickable.
        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }
        return $this->humanizeToken($value);
    }

    private function humanizeToken(string $value): string
    {
        $normalized = preg_replace('/[_-]+/', ' ', trim($value));
        $normalized = preg_replace('/\s+/', ' ', (string) $normalized);
        return ucwords(strtolower((string) $normalized));
    }

    /** @return array<string,string> */
    private function getActiveFlowChoiceMap(): array
    {
        $map = [];
        $flows = BotFlow::where('is_active', true)->orderBy('display_order')->get();
        foreach ($flows as $flow) {
            $map['flow_' . $flow->id] = $flow->name;
        }
        return $map;
    }

    /** @return array<string,string> */
    private function getFlowOptionMap(int $flowId, array &$cache): array
    {
        if ($flowId <= 0) {
            return [];
        }
        if (isset($cache[$flowId])) {
            return $cache[$flowId];
        }
        $map = [];
        $edges = BotEdge::whereHas('sourceNode', fn ($q) => $q->where('flow_id', $flowId))->get();
        foreach ($edges as $edge) {
            $map[strtolower(trim((string) $edge->option_value))] = (string) $edge->option_label;
        }
        $cache[$flowId] = $map;
        return $map;
    }
}
