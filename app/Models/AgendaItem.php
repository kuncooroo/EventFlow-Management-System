<?php

namespace App\Models;

use Database\Factories\AgendaItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaItem extends Model
{
    /** @use HasFactory<AgendaItemFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'title',
        'description',
        'start_at',
        'end_at',
        'location',
        'speaker_text',
        'sort_order',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('start_at')->orderBy('sort_order');
    }
}
