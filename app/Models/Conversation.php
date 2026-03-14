<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = ['phone', 'stage', 'last_read_at'];

    protected $attributes = [
        'stage' => 'entry',
    ];

    protected function casts(): array
    {
        return [
            'last_read_at' => 'datetime',
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

    public function getCurrentFlow(): ?ConversationFlow
    {
        return ConversationFlow::where('stage', $this->stage)->first();
    }
}
