<?php

namespace App\Enums;

enum ExportType: string
{
    case Attendees = 'attendees';

    case Registrations = 'registrations';

    case Attendance = 'attendance';
}
