<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotEdge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotEdgeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BotEdge::with(['sourceNode', 'targetNode']);
        if ($request->has('source_node_id')) {
            $query->where('source_node_id', $request->source_node_id);
        }
        if ($request->has('flow_id')) {
            $query->whereHas('sourceNode', fn ($q) => $q->where('flow_id', $request->flow_id));
        }
        $edges = $query->orderBy('source_node_id')->orderBy('order')->get();
        return response()->json(['data' => $edges]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_node_id' => 'required|exists:bot_nodes,id',
            'target_node_id' => 'required|exists:bot_nodes,id',
            'option_label' => 'required|string|max:255',
            'option_value' => 'required|string|max:255',
            'order' => 'integer',
        ]);

        $edge = BotEdge::create([
            'source_node_id' => $validated['source_node_id'],
            'target_node_id' => $validated['target_node_id'],
            'option_label' => $validated['option_label'],
            'option_value' => $validated['option_value'],
            'order' => $validated['order'] ?? 0,
        ]);

        return response()->json(['data' => $edge->load(['sourceNode', 'targetNode'])], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $edge = BotEdge::findOrFail($id);
        $validated = $request->validate([
            'source_node_id' => 'sometimes|exists:bot_nodes,id',
            'target_node_id' => 'sometimes|exists:bot_nodes,id',
            'option_label' => 'sometimes|string|max:255',
            'option_value' => 'sometimes|string|max:255',
            'order' => 'integer',
        ]);

        $edge->update(array_filter($validated, fn ($v) => $v !== null));
        return response()->json(['data' => $edge->fresh(['sourceNode', 'targetNode'])]);
    }

    public function destroy(string $id): JsonResponse
    {
        $edge = BotEdge::findOrFail($id);
        $edge->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
