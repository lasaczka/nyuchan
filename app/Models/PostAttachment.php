<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostAttachment extends Model
{
    protected $fillable = [
        'post_id',
        'path',
        'thumb_path',
        'original_name',
        'mime',
        'size',
        'width',
        'height',
        'thumb_width',
        'thumb_height',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
