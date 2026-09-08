<?php

namespace Database\Factories;

use App\Enums\RegistrationFieldType;
use App\Models\Registration;
use App\Models\RegistrationAnswer;
use App\Models\RegistrationField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistrationAnswer>
 */
class RegistrationAnswerFactory extends Factory
{
    protected $model = RegistrationAnswer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'registration_field_id' => RegistrationField::factory(),
            'field_label_snapshot' => fake()->words(3, true),
            'field_type_snapshot' => RegistrationFieldType::Text->value,
            'answer_text' => fake()->sentence(),
            'answer_json' => null,
        ];
    }
}
