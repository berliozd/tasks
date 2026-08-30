<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTemplate extends Model
{
    use HasFactory;

    public const LANGUAGES = [
        'fr' => 'French',
        'en' => 'English',
        'de' => 'German',
        'da' => 'Danish',
        'sv' => 'Swedish',
        'fi' => 'Finnish',
        'no' => 'Norwegian',
    ];

    protected $fillable = [
        'directory_id',
        'name',
        'subject',
        'language',
        'body',
    ];

    public function directory(): BelongsTo
    {
        return $this->belongsTo(Directory::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ProspectAction::class);
    }
}
