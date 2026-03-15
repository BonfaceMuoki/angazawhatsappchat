<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotNode extends Model
{
    protected $table = 'bot_nodes';

    protected $fillable = [
        'flow_id',
        'node_key',
        'type',
        'message',
        'position_x',
        'position_y',
        'is_entry',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'position_x' => 'float',
            'position_y' => 'float',
            'is_entry' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(BotFlow::class, 'flow_id');
    }

    public function outgoingEdges(): HasMany
    {
        return $this->hasMany(BotEdge::class, 'source_node_id')->orderBy('order');
    }

    public function incomingEdges(): HasMany
    {
        return $this->hasMany(BotEdge::class, 'target_node_id');
    }
}
