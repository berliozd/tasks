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
        'from_label',
        'reply_to_email',
        'message',
        'status',
        'queued_for_send',
        'scheduled_at',
    ];

    protected $casts = [
        'queued_for_send' => 'boolean',
        // Without this, scheduled_at round-trips as a naive DB string with no
        // timezone marker, which the frontend and Carbon::parse() on the way
        // back in can each misinterpret as local time instead of UTC.
        'scheduled_at' => 'datetime',
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
