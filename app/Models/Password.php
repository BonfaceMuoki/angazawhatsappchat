<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Password extends Model
{
    protected $fillable = ['user_id', 'hashed_password', 'status'];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_REVOKED = 'revoked';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
