<?php

namespace App\Models;

use App\Enums\CheckInMethod;
use Database\Factories\CheckInFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckIn extends Model
{
    /** @use HasFactory<CheckInFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'registration_id',
        'ticket_id',
        'operator_user_id',
        'method',
        'checked_in_at',
        'created_at',
    ];

    protected $casts = [
        'method' => CheckInMethod::class,
        'checked_in_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_user_id');
    }
}
