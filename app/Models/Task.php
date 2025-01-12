<?php

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    protected $fillable = [
        'label',
        'created_at',
        'updated_date',
        'completed_at',
        'user_id',
        'description',
        'scheduled_at'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, (new User)->getForeignKey());
    }
}
