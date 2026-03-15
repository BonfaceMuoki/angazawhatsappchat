<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotEdge extends Model
{
    protected $table = 'bot_edges';

    protected $fillable = [
        'source_node_id',
        'target_node_id',
        'option_label',
        'option_value',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    public function sourceNode(): BelongsTo
    {
        return $this->belongsTo(BotNode::class, 'source_node_id');
    }

    public function targetNode(): BelongsTo
    {
        return $this->belongsTo(BotNode::class, 'target_node_id');
    }
}
