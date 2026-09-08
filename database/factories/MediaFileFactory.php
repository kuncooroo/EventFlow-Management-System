<?php

namespace Database\Factories;

use App\Enums\MediaCategory;
use App\Models\Event;
use App\Models\MediaFile;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaFile>
 */
class MediaFileFactory extends Factory
{
    protected $model = MediaFile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'event_id' => null,
            'uploaded_by_user_id' => User::factory(),
            'category' => MediaCategory::EventBanner,
            'visibility' => 'public',
            'is_active' => true,
            'disk' => 'public',
            'path' => 'event_banner/1/'.fake()->uuid().'.png',
            'original_name' => fake()->word().'.png',
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size_bytes' => fake()->numberBetween(1024, 2048000),
        ];
    }

    public function banner(Event $event): static
    {
        return $this->state([
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
            'category' => MediaCategory::EventBanner,
        ]);
    }

    public function organizationLogo(Organization $organization): static
    {
        return $this->state([
            'organization_id' => $organization->id,
            'event_id' => null,
            'category' => MediaCategory::OrganizationLogo,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
