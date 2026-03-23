<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotFlow extends Model
{
    protected $table = 'bot_flows';

    protected $fillable = [
        'name',
        'description',
        'entry_node_id',
        'show_in_router',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'show_in_router' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function entryNode(): BelongsTo
    {
        return $this->belongsTo(BotNode::class, 'entry_node_id');
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(BotNode::class, 'flow_id');
    }

    // Edges are on nodes; use $flow->nodes()->with('outgoingEdges') to load
}
