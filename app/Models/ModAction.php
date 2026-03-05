<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModAction extends Model
{
    protected $fillable = [
        'actor_user_id',
        'action',
        'target_type',
        'target_id',
        'reason',
    ];
}
