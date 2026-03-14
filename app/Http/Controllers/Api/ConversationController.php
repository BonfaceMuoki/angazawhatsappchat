<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\WhatsAppMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function __construct(
        private WhatsAppMessageService $messageService
    ) {}

    /**
     * List conversations with last message and count.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Conversation::query();
        $search = $request->get('search');
        if ($search && $search !== '') {
            $query->where('phone', 'like', '%' . $search . '%');
        }
        $conversations = $query->get();
        $phones = $conversations->pluck('phone')->all();

        if ($phones === []) {
            return response()->json(['data' => []]);
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
                'last_message_body' => $last ? $last->body : null,
                'last_message_at' => $last ? $last->created_at->toIso8601String() : null,
                'message_count' => (int) ($counts[$c->phone] ?? 0),
                'unread_count' => $c->getUnreadCount(),
                'last_read_at' => $c->last_read_at?->toIso8601String(),
            ];
        })->sortByDesc(fn ($i) => $i['last_message_at'] ?? '')->values();

        return response()->json(['data' => $items]);
    }

    /**
     * List messages for a conversation (paginated).
     */
    public function messages(string $phone): JsonResponse
    {
        $messages = Message::where('phone', $phone)
            ->orderBy('created_at')
            ->get()
            ->map(fn (Message $m) => [
                'id' => $m->id,
                'direction' => $m->direction,
                'body' => $m->body,
                'created_at' => $m->created_at->toIso8601String(),
            ]);

        return response()->json(['data' => $messages]);
    }

    /**
     * Send a message to the conversation (outgoing via WhatsApp).
     */
    public function sendMessage(Request $request, string $phone): JsonResponse
    {
        $body = $request->input('body');
        if (!is_string($body) || trim($body) === '') {
            return response()->json(['message' => 'Body is required'], 422);
        }

        try {
            $response = $this->messageService->sendText($phone, trim($body));
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Could not reach WhatsApp. Check that the server can connect to the internet (graph.facebook.com).',
            ], 502);
        }

        if ($response === null) {
            return response()->json(['message' => 'Failed to send message'], 502);
        }

        if (isset($response['_error'])) {
            return response()->json([
                'message' => 'WhatsApp API error: ' . $response['_error'],
            ], 502);
        }

        $wamid = $response['messages'][0]['id'] ?? null;
        $message = Message::create([
            'phone' => $phone,
            'direction' => Message::DIRECTION_OUTGOING,
            'body' => trim($body),
            'wamid' => $wamid,
        ]);

        return response()->json([
            'data' => [
                'id' => $message->id,
                'direction' => $message->direction,
                'body' => $message->body,
                'created_at' => $message->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Mark conversation as read (clear unread for this phone).
     */
    public function markRead(string $phone): JsonResponse
    {
        Conversation::where('phone', $phone)->update(['last_read_at' => now()]);
        return response()->json(['ok' => true]);
    }
}
