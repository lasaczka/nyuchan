<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Board extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'is_hidden',
        'description',
        'default_anon_name',
        'bump_limit',
        'post_rate_limit_count',
        'post_rate_limit_window_seconds',
    ];

    public function threads(): HasMany
    {
        return $this->hasMany(Thread::class);
    }

    public function posts(): HasManyThrough
    {
        return $this->hasManyThrough(Post::class, Thread::class);
    }

    public function latestBumpedThread(): HasOne
    {
        return $this->hasOne(Thread::class)->ofMany([
            'bumped_at' => 'max',
            'id' => 'max',
        ]);
    }

    public function getDisplayTitleAttribute(): string
    {
        $key = 'boards.'.$this->slug.'.title';
        $translated = __($key);

        return $translated !== $key ? $translated : $this->title;
    }

    public function getDisplayDescriptionAttribute(): ?string
    {
        $key = 'boards.'.$this->slug.'.description';
        $translated = __($key);

        if ($translated !== $key) {
            return $translated;
        }

        return $this->description;
    }

    public function getDisplayAnonymousNameAttribute(): string
    {
        $key = 'boards.'.$this->slug.'.anonymous';
        $translated = __($key);

        if ($translated !== $key) {
            return $translated;
        }

        return $this->default_anon_name ?: __('ui.anonymous');
    }
}
