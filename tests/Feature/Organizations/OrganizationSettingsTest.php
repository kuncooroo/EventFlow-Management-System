<?php

namespace Tests\Feature\Organizations;

use App\Actions\Organizations\UpdateOrganizationSettings;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\InteractsWithOrganizations;
use Tests\TestCase;

class OrganizationSettingsTest extends TestCase
{
    use InteractsWithOrganizations;
    use RefreshDatabase;

    public function test_owner_updates_settings_and_writes_audit(): void
    {
        $owner = User::factory()->create();
        $organization = $this->createOrganizationForUser($owner, ['name' => 'Original Name']);

        (app(UpdateOrganizationSettings::class))->handle($organization, $owner, [
            'name' => 'Acme Events',
            'timezone' => 'Europe/Paris',
            'locale' => 'en',
            'default_currency' => 'eur',
        ]);

        $organization->refresh();

        $this->assertSame('Acme Events', $organization->name);
        $this->assertSame('Europe/Paris', $organization->timezone);
        $this->assertSame('en', $organization->locale);
        $this->assertSame('EUR', $organization->default_currency);

        $this->assertDatabaseHas('activity_logs', [
            'organization_id' => $organization->id,
            'actor_user_id' => $owner->id,
            'action' => 'organization.settings_changed',
            'subject_type' => 'organization',
            'subject_id' => $organization->id,
        ]);

        $audit = ActivityLog::where('action', 'organization.settings_changed')->first();
        $this->assertSame(['name', 'timezone', 'default_currency'], $audit->properties['changed_fields']);
    }

    public function test_unmodified_settings_do_not_write_audit(): void
    {
        $owner = User::factory()->create();
        $organization = $this->createOrganizationForUser($owner, [
            'name' => 'Acme Events',
            'timezone' => 'UTC',
            'locale' => 'en',
            'default_currency' => null,
        ]);

        (app(UpdateOrganizationSettings::class))->handle($organization, $owner, [
            'name' => 'Acme Events',
            'timezone' => 'UTC',
            'locale' => 'en',
            'default_currency' => null,
        ]);

        $this->assertDatabaseMissing('activity_logs', ['action' => 'organization.settings_changed']);
    }

    public function test_user_cannot_update_settings_for_foreign_organization(): void
    {
        $actor = User::factory()->create();
        $this->createOrganizationForUser($actor);
        $foreign = Organization::factory()->create(['name' => 'Foreign Org']);

        $this->expectException(AuthorizationException::class);

        (app(UpdateOrganizationSettings::class))->handle($foreign, $actor, [
            'name' => 'Hijacked',
            'timezone' => 'UTC',
            'locale' => 'en',
            'default_currency' => null,
        ]);
    }

    public function test_rejects_invalid_timezone_identifier(): void
    {
        $this->assertSettingsRejected(
            ['name' => 'Rename', 'timezone' => 'Mars/Olympus', 'locale' => 'en', 'default_currency' => null],
            ['timezone'],
        );
    }

    public function test_rejects_unsupported_locale(): void
    {
        $this->assertSettingsRejected(
            ['name' => 'Rename', 'timezone' => 'UTC', 'locale' => 'fr', 'default_currency' => null],
            ['locale'],
        );
    }

    public function test_rejects_malformed_currency_code(): void
    {
        $this->assertSettingsRejected(
            ['name' => 'Rename', 'timezone' => 'UTC', 'locale' => 'en', 'default_currency' => 'USDD'],
            ['default_currency'],
        );
    }

    public function test_rejects_empty_required_fields_together(): void
    {
        $this->assertSettingsRejected(
            ['timezone' => 'UTC', 'locale' => 'en', 'default_currency' => null],
            ['name'],
        );
    }

    public function test_organization_settings_table_stores_extensible_values(): void
    {
        $organization = Organization::factory()->create();
        $setting = OrganizationSetting::factory()->create([
            'organization_id' => $organization->id,
            'setting_key' => 'notifications.reminder_enabled',
            'setting_value' => ['enabled' => true],
        ]);

        $this->assertTrue($setting->setting_value['enabled']);
        $this->assertSame($organization->id, $organization->settings()->first()->organization_id);
    }

    public function test_organization_settings_key_is_unique_per_organization(): void
    {
        $organization = Organization::factory()->create();
        OrganizationSetting::factory()->create([
            'organization_id' => $organization->id,
            'setting_key' => 'notifications.reminder_enabled',
        ]);

        $this->expectException(QueryException::class);

        OrganizationSetting::factory()->create([
            'organization_id' => $organization->id,
            'setting_key' => 'notifications.reminder_enabled',
            'setting_value' => ['enabled' => false],
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<string>  $expectedErrorKeys
     */
    private function assertSettingsRejected(array $input, array $expectedErrorKeys): void
    {
        $owner = User::factory()->create();
        $organization = $this->createOrganizationForUser($owner, ['name' => 'Original Name']);

        try {
            (app(UpdateOrganizationSettings::class))->handle($organization, $owner, $input);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            foreach ($expectedErrorKeys as $key) {
                $this->assertArrayHasKey($key, $e->errors());
            }
        }

        $this->assertSame('Original Name', $organization->refresh()->name);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'organization.settings_changed']);
    }
}
