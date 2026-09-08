<?php

namespace App\Models;

use App\Enums\RegistrationFieldType;
use Database\Factories\RegistrationFieldFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegistrationField extends Model
{
    /** @use HasFactory<RegistrationFieldFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'field_key',
        'label',
        'field_type',
        'options_json',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'field_type' => RegistrationFieldType::class,
        'options_json' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(RegistrationAnswer::class);
    }

    public function options(): array
    {
        return $this->options_json ?? [];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
