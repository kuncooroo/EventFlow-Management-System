<?php

namespace Database\Factories;

use App\Enums\RegistrationFieldType;
use App\Models\Event;
use App\Models\RegistrationField;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RegistrationField>
 */
class RegistrationFieldFactory extends Factory
{
    protected $model = RegistrationField::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = fake()->randomElement(['Institution', 'Job Title', 'Dietary Notes', 'T-Shirt Size', 'City']);

        return [
            'event_id' => Event::factory(),
            'field_key' => 'field_'.Str::slug($label, '_').'_'.fake()->unique()->numberBetween(1000, 9999),
            'label' => $label,
            'field_type' => fake()->randomElement(RegistrationFieldType::cases()),
            'options_json' => null,
            'is_required' => fake()->boolean(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function required(): static
    {
        return $this->state(['is_required' => true]);
    }

    public function withOptions(array $options): static
    {
        return $this->state([
            'field_type' => RegistrationFieldType::Select,
            'options_json' => $options,
        ]);
    }
}
