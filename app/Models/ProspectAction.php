<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProspectAction extends Model
{
    use HasFactory;

    public const STATUSES = ['planned', 'sent', 'replied', 'bounced', 'no_response', 'lost'];

    protected $fillable = [
        'prospect_id',
        'email_template_id',
        'type',
        'subject',
        'from_email',
        'reply_to_email',
        'message',
        'status',
        'queued_for_send',
        'scheduled_at',
    ];

    protected $casts = [
        'queued_for_send' => 'boolean',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }
}
