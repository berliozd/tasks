<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NeedActivity extends Model
{
    protected $fillable = [
        'need_id',
        'user_id',
        'type',
        'from_stage',
        'to_stage',
        'note',
    ];

    public function need(): BelongsTo
    {
        return $this->belongsTo(Need::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
