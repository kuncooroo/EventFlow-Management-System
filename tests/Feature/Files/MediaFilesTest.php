<?php

namespace Tests\Feature\Files;

use App\Actions\Files\StoreMediaFile;
use App\Enums\MediaCategory;
use App\Enums\OrganizationRole;
use App\Livewire\Events\BannerUpload;
use App\Livewire\Events\EventSetup;
use App\Livewire\Organizations\LogoUpload;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class MediaFilesTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function makeOrgWithMember(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $membership = $org->memberships()->create([
            'user_id' => $user->id,
            'role' => $role,
            'joined_at' => now(),
        ]);
        session(['current_organization_id' => $org->id]);

        return [$user, $org, $membership];
    }

    private function oversizedImage(): UploadedFile
    {
        $file = UploadedFile::fake()->image('banner.png');
        file_put_contents($file->getRealPath(), str_repeat('X', 5 * 1024 * 1024), FILE_APPEND);

        return $file;
    }

    // ─── Banner upload ──────────────────────────────────────────────────────

    public function test_owner_can_upload_event_banner(): void
    {
        Storage::fake('public');

        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['name' => 'Media Event']);

        Livewire::actingAs($owner)
            ->test(BannerUpload::class, ['event' => $event])
            ->set('banner', UploadedFile::fake()->image('banner.png'))
            ->call('upload')
            ->assertHasNoErrors();

        $media = $event->mediaFiles()->first();

        $this->assertNotNull($media);
        $this->assertSame(MediaCategory::EventBanner->value, $media->category->value);
        $this->assertSame('public', $media->disk);
        $this->assertTrue($media->is_active);
        $this->assertSame($org->id, $media->organization_id);
        $this->assertSame($event->id, $media->event_id);
        $this->assertSame($owner->id, $media->uploaded_by_user_id);
        $this->assertSame('image/png', $media->mime_type);
        $this->assertGreaterThan(0, $media->size_bytes);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_metadata_only_not_binary_in_mysql(): void
    {
        Storage::fake('public');

        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(BannerUpload::class, ['event' => $event])
            ->set('banner', UploadedFile::fake()->image('banner.png'))
            ->call('upload')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('media_files', [
            'event_id' => $event->id,
            'category' => MediaCategory::EventBanner->value,
            'disk' => 'public',
            'mime_type' => 'image/png',
        ]);
    }

    public function test_replacing_banner_deactivates_previous_active_row(): void
    {
        Storage::fake('public');

        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(BannerUpload::class, ['event' => $event])
            ->set('banner', UploadedFile::fake()->image('first.png'))
            ->call('upload');

        $first = $event->mediaFiles()->first();

        Livewire::actingAs($owner)
            ->test(BannerUpload::class, ['event' => $event])
            ->set('banner', UploadedFile::fake()->image('second.png'))
            ->call('upload');

        $media = $event->mediaFiles()->orderBy('id')->get();

        $this->assertSame(2, $media->count());
        $this->assertFalse($media[0]->is_active);
        $this->assertTrue($media[1]->is_active);
        $this->assertSame($event->banner()?->id, $media[1]->id);
    }

    public function test_unsupported_file_type_is_rejected(): void
    {
        Storage::fake('public');

        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(BannerUpload::class, ['event' => $event])
            ->set('banner', UploadedFile::fake()->create('notes.txt', 10))
            ->call('upload')
            ->assertHasErrors(['banner']);

        $this->assertSame(0, $event->mediaFiles()->count());
        Storage::disk('public')->assertDirectoryEmpty('event_banner');
    }

    public function test_oversized_file_is_rejected(): void
    {
        Storage::fake('public');

        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(BannerUpload::class, ['event' => $event])
            ->set('banner', $this->oversizedImage())
            ->call('upload')
            ->assertHasErrors(['banner']);

        $this->assertSame(0, $event->mediaFiles()->count());
    }

    public function test_owner_can_remove_banner(): void
    {
        Storage::fake('public');

        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($owner)
            ->test(BannerUpload::class, ['event' => $event])
            ->set('banner', UploadedFile::fake()->image('banner.png'))
            ->call('upload');

        $media = $event->mediaFiles()->first();

        Livewire::actingAs($owner)
            ->test(BannerUpload::class, ['event' => $event])
            ->call('remove');

        $this->assertSoftDeleted('media_files', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($media->path);
    }

    // ─── Authorization ──────────────────────────────────────────────────────

    public function test_unassigned_staff_cannot_upload_banner(): void
    {
        [$staff, $org] = $this->makeOrgWithMember(OrganizationRole::Staff);
        $event = Event::factory()->for($org)->create();

        Livewire::actingAs($staff)
            ->test(BannerUpload::class, ['event' => $event])
            ->assertStatus(403);

        $this->assertSame(0, $event->mediaFiles()->count());
    }

    public function test_other_organization_owner_cannot_upload_banner(): void
    {
        [$otherOwner] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $otherOrg = Organization::factory()->create();
        $event = Event::factory()->for($otherOrg)->create();

        Livewire::actingAs($otherOwner)
            ->test(BannerUpload::class, ['event' => $event])
            ->assertStatus(403);
    }

    public function test_event_must_belong_to_organization_passed_to_action(): void
    {
        Storage::fake('public');

        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $actor = User::factory()->create();
        $orgA->memberships()->create(['user_id' => $actor->id, 'role' => OrganizationRole::Owner->value, 'joined_at' => now()]);
        $orgB->memberships()->create(['user_id' => $actor->id, 'role' => OrganizationRole::Owner->value, 'joined_at' => now()]);
        $event = Event::factory()->for($orgB)->create();

        session(['current_organization_id' => $orgB->id]);

        $this->expectException(ValidationException::class);

        $action = new StoreMediaFile;
        $action->handle($orgA, $actor, UploadedFile::fake()->image('banner.png'), MediaCategory::EventBanner, $event);
    }

    // ─── Organization logo ──────────────────────────────────────────────────

    public function test_owner_can_upload_organization_logo(): void
    {
        Storage::fake('public');

        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);

        Livewire::actingAs($owner)
            ->test(LogoUpload::class, ['organization' => $org])
            ->set('logo', UploadedFile::fake()->image('logo.png'))
            ->call('upload')
            ->assertHasNoErrors();

        $logo = $org->activeLogo();

        $this->assertNotNull($logo);
        $this->assertSame(MediaCategory::OrganizationLogo->value, $logo->category->value);
        $this->assertNull($logo->event_id);
        Storage::disk('public')->assertExists($logo->path);
    }

    public function test_admin_can_upload_organization_logo(): void
    {
        Storage::fake('public');

        [$admin, $org] = $this->makeOrgWithMember(OrganizationRole::Admin);

        Livewire::actingAs($admin)
            ->test(LogoUpload::class, ['organization' => $org])
            ->set('logo', UploadedFile::fake()->image('logo.png'))
            ->call('upload')
            ->assertHasNoErrors();

        $this->assertNotNull($org->activeLogo());
    }

    public function test_staff_cannot_upload_organization_logo(): void
    {
        [$staff, $org] = $this->makeOrgWithMember(OrganizationRole::Staff);

        Livewire::actingAs($staff)
            ->test(LogoUpload::class, ['organization' => $org])
            ->assertStatus(403);

        $this->assertNull($org->activeLogo());
    }

    // ─── Setup page integration ─────────────────────────────────────────────

    public function test_event_setup_shows_banner_section(): void
    {
        [$owner, $org] = $this->makeOrgWithMember(OrganizationRole::Owner);
        $event = Event::factory()->for($org)->create(['status' => 'draft']);

        Livewire::actingAs($owner)
            ->test(EventSetup::class, ['event' => $event])
            ->assertOk()
            ->assertSee('Event Banner');
    }
}
