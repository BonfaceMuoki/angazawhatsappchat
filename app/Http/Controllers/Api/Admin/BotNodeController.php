<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotNode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'node_key' => 'required|string|max:100',
            'type' => 'required|in:text,buttons,list',
            'message' => 'required|string',
            'position_x' => 'numeric',
            'position_y' => 'numeric',
            'is_entry' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $node = BotNode::create([
            'flow_id' => $validated['flow_id'],
            'node_key' => $validated['node_key'],
            'type' => $validated['type'],
            'message' => $validated['message'],
            'position_x' => $validated['position_x'] ?? 0,
            'position_y' => $validated['position_y'] ?? 0,
            'is_entry' => $validated['is_entry'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json(['data' => $node->load('flow')], 201);
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
