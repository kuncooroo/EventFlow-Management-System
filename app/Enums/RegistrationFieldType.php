<?php

namespace App\Enums;

enum RegistrationFieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Select = 'select';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case Date = 'date';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::Textarea => 'Textarea',
            self::Select => 'Select',
            self::Radio => 'Radio',
            self::Checkbox => 'Checkbox',
            self::Date => 'Date',
        };
    }

    public function requiresOptions(): bool
    {
        return in_array($this, [self::Select, self::Radio, self::Checkbox], true);
    }
}
