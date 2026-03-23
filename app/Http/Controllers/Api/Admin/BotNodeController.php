<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotNode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BotNodeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BotNode::with(['flow', 'outgoingEdges.targetNode']);
        if ($request->has('flow_id')) {
            $query->where('flow_id', $request->flow_id);
        }
        $nodes = $query->orderBy('id')->get();
        return response()->json(['data' => $nodes]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'flow_id' => 'required|exists:bot_flows,id',
            'node_key' => 'nullable|string|max:100',
            'type' => 'required|in:text,buttons,list',
            'message' => 'required|string',
            'position_x' => 'numeric',
            'position_y' => 'numeric',
            'is_entry' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $flowId = (int) $validated['flow_id'];
        $key = trim((string) ($validated['node_key'] ?? ''));

        if ($key === '') {
            $key = $this->makeUniqueNodeKey($flowId);
        } elseif (BotNode::where('flow_id', $flowId)->where('node_key', $key)->exists()) {
            throw ValidationException::withMessages([
                'node_key' => ['This key is already used in this flow. Choose another or leave blank to auto-generate.'],
            ]);
        }

        $node = BotNode::create([
            'flow_id' => $flowId,
            'node_key' => $key,
            'type' => $validated['type'],
            'message' => $validated['message'],
            'position_x' => $validated['position_x'] ?? 0,
            'position_y' => $validated['position_y'] ?? 0,
            'is_entry' => $validated['is_entry'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json(['data' => $node->load('flow')], 201);
    }

    /**
     * Opaque unique key per flow so authors do not have to invent names for long journeys.
     */
    private function makeUniqueNodeKey(int $flowId): string
    {
        do {
            $key = 'n_'.bin2hex(random_bytes(8));
        } while (BotNode::where('flow_id', $flowId)->where('node_key', $key)->exists());

        return $key;
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $node = BotNode::findOrFail($id);
        $validated = $request->validate([
            'node_key' => 'sometimes|string|max:100',
            'type' => 'sometimes|in:text,buttons,list',
            'message' => 'sometimes|string',
            'position_x' => 'numeric',
            'position_y' => 'numeric',
            'is_entry' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if (array_key_exists('node_key', $validated)) {
            $k = trim((string) $validated['node_key']);
            if ($k === '') {
                throw ValidationException::withMessages([
                    'node_key' => ['Key cannot be empty.'],
                ]);
            }
            if (BotNode::where('flow_id', $node->flow_id)->where('node_key', $k)->where('id', '!=', $node->id)->exists()) {
                throw ValidationException::withMessages([
                    'node_key' => ['This key is already used in this flow.'],
                ]);
            }
            $validated['node_key'] = $k;
        }

        $node->update(array_filter($validated, fn ($v) => $v !== null));
        return response()->json(['data' => $node->fresh(['flow', 'outgoingEdges.targetNode'])]);
    }

    public function destroy(string $id): JsonResponse
    {
        $node = BotNode::findOrFail($id);
        $node->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
