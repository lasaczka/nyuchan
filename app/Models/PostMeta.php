<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostMeta extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'abuse_id',
        'epoch',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
