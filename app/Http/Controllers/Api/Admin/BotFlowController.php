<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotFlow;
use App\Models\BotNode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotFlowController extends Controller
{
    public function index(): JsonResponse
    {
        $flows = BotFlow::with('entryNode')->orderBy('display_order')->get();
        return response()->json(['data' => $flows]);
    }

    public function show(string $id): JsonResponse
    {
        $flow = BotFlow::with('entryNode')->findOrFail($id);
        return response()->json(['data' => $flow]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'entry_node_id' => 'nullable|exists:bot_nodes,id',
            'show_in_router' => 'boolean',
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $flow = BotFlow::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'entry_node_id' => $validated['entry_node_id'] ?? null,
            'show_in_router' => $validated['show_in_router'] ?? true,
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json(['data' => $flow->load('entryNode')], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $flow = BotFlow::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'entry_node_id' => 'nullable|exists:bot_nodes,id',
            'show_in_router' => 'boolean',
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $flow->update(array_filter($validated, fn ($v) => $v !== null));
        return response()->json(['data' => $flow->fresh('entryNode')]);
    }

    public function destroy(string $id): JsonResponse
    {
        $flow = BotFlow::findOrFail($id);
        $flow->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
