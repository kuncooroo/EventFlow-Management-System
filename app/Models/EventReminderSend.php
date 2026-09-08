<?php

namespace App\Models;

use Database\Factories\EventReminderSendFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventReminderSend extends Model
{
    /** @use HasFactory<EventReminderSendFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'event_id',
        'occurrence_key',
        'sent_at',
        'created_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
