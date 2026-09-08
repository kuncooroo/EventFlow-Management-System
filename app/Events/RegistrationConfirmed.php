<?php

namespace App\Events;

use App\Models\Registration;
use Illuminate\Foundation\Events\Dispatchable;

class RegistrationConfirmed
{
    use Dispatchable;

    public function __construct(
        public readonly Registration $registration,
    ) {}
}
