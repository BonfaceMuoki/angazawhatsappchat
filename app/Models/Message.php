<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = ['phone', 'direction', 'body', 'wamid', 'status', 'node_id'];

    public const DIRECTION_INCOMING = 'incoming';
    public const DIRECTION_OUTGOING = 'outgoing';

    public function node(): ?BelongsTo
    {
        return $this->belongsTo(BotNode::class, 'node_id');
    }
}
