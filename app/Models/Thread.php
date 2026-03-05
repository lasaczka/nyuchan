<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Thread extends Model
{
    protected $fillable = [
        'board_id',
        'title',
        'bumped_at',
        'owner_token_hash',
        'is_locked',
        'owner_token_issued_at',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
