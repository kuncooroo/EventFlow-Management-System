<?php

namespace App\Enums;

enum CheckInOutcome: string
{
    case Success = 'success';
    case Duplicate = 'duplicate';
    case Invalid = 'invalid';

    public function label(): string
    {
        return match ($this) {
            self::Success => 'Checked In',
            self::Duplicate => 'Already Checked In',
            self::Invalid => 'Invalid Ticket',
        };
    }
}
