<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'phone',
        'stage',
        'last_read_at',
        'flow_id',
        'current_node_id',
        'bot_active',
        'assigned_agent_id',
        'human_intervened_at',
        'last_user_message_at',
        'last_bot_message_at',
        'last_human_message_at',
    ];

    protected $attributes = [
        'stage' => 'entry',
        'bot_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'last_read_at' => 'datetime',
            'bot_active' => 'boolean',
            'human_intervened_at' => 'datetime',
            'last_user_message_at' => 'datetime',
            'last_bot_message_at' => 'datetime',
            'last_human_message_at' => 'datetime',
        ];
    }

    public function getUnreadCount(): int
    {
        return (int) Message::where('phone', $this->phone)
            ->where('direction', Message::DIRECTION_INCOMING)
            ->where('created_at', '>', $this->last_read_at ?? '1970-01-01 00:00:00')
            ->count();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'phone', 'phone');
    }

    public function flow(): ?BelongsTo
    {
        return $this->belongsTo(BotFlow::class, 'flow_id');
    }

    public function currentNode(): ?BelongsTo
    {
        return $this->belongsTo(BotNode::class, 'current_node_id');
    }
}
