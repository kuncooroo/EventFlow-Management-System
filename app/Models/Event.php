<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\MediaCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'created_by_user_id',
        'public_slug',
        'name',
        'description',
        'organizer_name',
        'contact_name',
        'contact_email',
        'contact_phone',
        'mode',
        'start_at',
        'end_at',
        'status',
        'registration_enabled',
        'registration_starts_at',
        'registration_ends_at',
        'capacity',
        'require_phone',
        'require_organization',
        'reminder_enabled',
        'reminder_hours_before',
        'published_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'archived_at',
    ];

    protected $casts = [
        'status' => EventStatus::class,
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'registration_enabled' => 'boolean',
        'registration_starts_at' => 'datetime',
        'registration_ends_at' => 'datetime',
        'require_phone' => 'boolean',
        'require_organization' => 'boolean',
        'reminder_enabled' => 'boolean',
        'published_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EventAssignment::class);
    }

    public function venue(): HasOne
    {
        return $this->hasOne(Venue::class);
    }

    public function agendaItems(): HasMany
    {
        return $this->hasMany(AgendaItem::class);
    }

    public function registrationFields(): HasMany
    {
        return $this->hasMany(RegistrationField::class);
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function reminderSends(): HasMany
    {
        return $this->hasMany(EventReminderSend::class);
    }

    public function mediaFiles(): HasMany
    {
        return $this->hasMany(MediaFile::class);
    }

    public function banner(): ?MediaFile
    {
        return $this->mediaFiles()
            ->active()
            ->ofCategory(MediaCategory::EventBanner)
            ->orderByDesc('id')
            ->first();
    }

    public function reminderDueAt(): ?Carbon
    {
        if (! $this->reminder_enabled || $this->reminder_hours_before === null || ! $this->start_at) {
            return null;
        }

        return $this->start_at->copy()->subHours((int) $this->reminder_hours_before);
    }

    public function isReminderEligible(?Carbon $now = null): bool
    {
        $now ??= Carbon::now();

        if (! $this->reminder_enabled || $this->reminder_hours_before === null || ! $this->start_at) {
            return false;
        }

        if (in_array($this->status, [
            EventStatus::Draft,
            EventStatus::Completed,
            EventStatus::Cancelled,
            EventStatus::Archived,
        ], true)) {
            return false;
        }

        $dueAt = $this->reminderDueAt();

        return $now->gte($dueAt) && $now->lt($this->start_at);
    }
}
