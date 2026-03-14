<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationFlow extends Model
{
    protected $fillable = ['stage', 'question', 'options', 'next_stage'];

    protected $casts = [
        'options' => 'array',
    ];
}
