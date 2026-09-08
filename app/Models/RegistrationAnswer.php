<?php

namespace App\Models;

use Database\Factories\RegistrationAnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationAnswer extends Model
{
    /** @use HasFactory<RegistrationAnswerFactory> */
    use HasFactory;

    protected $fillable = [
        'registration_id',
        'registration_field_id',
        'field_label_snapshot',
        'field_type_snapshot',
        'answer_text',
        'answer_json',
    ];

    protected $casts = [
        'answer_json' => 'array',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function registrationField(): BelongsTo
    {
        return $this->belongsTo(RegistrationField::class);
    }
}
