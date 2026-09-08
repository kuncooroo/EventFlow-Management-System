<?php

namespace App\Actions\Events;

use App\Enums\RegistrationFieldType;
use Illuminate\Validation\ValidationException;

trait ValidatesRegistrationField
{
    /**
     * @throws ValidationException
     */
    private function validateRegistrationField(array $data): void
    {
        $label = $data['label'] ?? null;
        $fieldType = $data['field_type'] ?? null;

        if ($label === null || trim($label) === '') {
            throw ValidationException::withMessages([
                'label' => 'The field label is required.',
            ]);
        }

        if (! in_array($fieldType, array_column(RegistrationFieldType::cases(), 'value'), true)) {
            throw ValidationException::withMessages([
                'field_type' => 'The field type is unsupported.',
            ]);
        }

        $type = RegistrationFieldType::from($fieldType);
        $options = $data['options'] ?? [];

        if ($type->requiresOptions()) {
            $options = array_values(array_filter(($options ?? []), fn ($option) => trim((string) $option) !== ''));

            if ($options === []) {
                throw ValidationException::withMessages([
                    'options' => 'Options are required for '.strtolower($type->label()).' fields.',
                ]);
            }

            if (count($options) > 100) {
                throw ValidationException::withMessages([
                    'options' => 'A field cannot have more than 100 options.',
                ]);
            }

            foreach ($options as $option) {
                if (mb_strlen((string) $option) > 255) {
                    throw ValidationException::withMessages([
                        'options' => 'Each option cannot exceed 255 characters.',
                    ]);
                }
            }
        }
    }

    private function normalizeOptions(array $data): ?array
    {
        $fieldType = RegistrationFieldType::from($data['field_type']);
        $options = array_values(array_filter($data['options'] ?? [], fn ($option) => trim((string) $option) !== ''));

        if (! $fieldType->requiresOptions()) {
            return null;
        }

        return array_map(fn ($option) => trim((string) $option), $options);
    }
}
