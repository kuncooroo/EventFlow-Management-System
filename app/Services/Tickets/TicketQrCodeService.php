<?php

namespace App\Services\Tickets;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class TicketQrCodeService
{
    public function svg(string $payload): string
    {
        $qrCode = new QrCode(
            data: $payload,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 512,
            margin: 8,
        );

        return (new SvgWriter)->write($qrCode)->getString();
    }
}
