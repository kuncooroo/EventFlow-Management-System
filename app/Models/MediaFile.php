<?php

namespace App\Models;

use App\Enums\MediaCategory;
use Database\Factories\MediaFileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MediaFile extends Model
{
    /** @use HasFactory<MediaFileFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'event_id',
        'uploaded_by_user_id',
        'category',
        'visibility',
        'is_active',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'extension',
        'size_bytes',
    ];

    protected $casts = [
        'category' => MediaCategory::class,
        'is_active' => 'boolean',
        'size_bytes' => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfCategory(Builder $query, MediaCategory $category): Builder
    {
        return $query->where('category', $category->value);
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
