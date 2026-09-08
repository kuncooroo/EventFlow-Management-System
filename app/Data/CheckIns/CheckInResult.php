<?php

namespace App\Data\CheckIns;

use App\Enums\CheckInOutcome;
use App\Models\CheckIn;

final class CheckInResult
{
    public function __construct(
        public readonly CheckInOutcome $outcome,
        public readonly ?CheckIn $checkIn = null,
        public readonly ?CheckIn $previousCheckIn = null,
        public readonly ?string $message = null,
    ) {}

    public static function success(CheckIn $checkIn): self
    {
        return new self(CheckInOutcome::Success, $checkIn);
    }

    public static function duplicate(CheckIn $previousCheckIn): self
    {
        return new self(CheckInOutcome::Duplicate, null, $previousCheckIn);
    }

    public static function invalid(string $message): self
    {
        return new self(CheckInOutcome::Invalid, null, null, $message);
    }

    public function isSuccess(): bool
    {
        return $this->outcome === CheckInOutcome::Success;
    }
}
