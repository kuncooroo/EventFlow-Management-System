<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\AgendaItem;
use App\Models\CheckIn;
use App\Models\Event;
use App\Models\EventAssignment;
use App\Models\EventReminderSend;
use App\Models\MediaFile;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMembership;
use App\Models\OrganizationSetting;
use App\Models\Registration;
use App\Models\RegistrationAnswer;
use App\Models\RegistrationField;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Models\Venue;
use App\Support\Demo\DemoMode;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DemoResetCommand extends Command
{
    protected $signature = 'eventflow:demo:reset';

    protected $description = 'Remove the seeded demo data and reseed a fresh baseline';

    public function handle(): int
    {
        $organization = DemoMode::demoOrganization();

        if ($organization === null) {
            $this->info('No demo organization found; seeding a fresh demo baseline.');

            $this->callSilently(DemoSeeder::class);

            return self::SUCCESS;
        }

        $eventIds = $organization->events()->pluck('id');

        $registrationIds = $eventIds->isNotEmpty()
            ? Registration::query()->whereIn('event_id', $eventIds)->pluck('id')
            : collect();

        DB::transaction(function () use ($organization, $eventIds, $registrationIds): void {
            if ($registrationIds->isNotEmpty()) {
                CheckIn::query()->whereIn('registration_id', $registrationIds)->delete();
                Ticket::query()->whereIn('registration_id', $registrationIds)->delete();
                RegistrationAnswer::query()->whereIn('registration_id', $registrationIds)->delete();
                Registration::query()->whereIn('id', $registrationIds)->delete();
            }

            if ($eventIds->isNotEmpty()) {
                RegistrationField::query()->whereIn('event_id', $eventIds)->delete();
                AgendaItem::query()->whereIn('event_id', $eventIds)->delete();
                TicketType::query()->whereIn('event_id', $eventIds)->delete();
                Venue::query()->whereIn('event_id', $eventIds)->delete();
                EventReminderSend::query()->whereIn('event_id', $eventIds)->delete();
            }

            EventAssignment::query()->where('organization_id', $organization->id)->delete();

            $mediaFiles = MediaFile::query()
                ->where('organization_id', $organization->id)
                ->orderBy('id')
                ->get();

            foreach ($mediaFiles as $media) {
                Storage::disk($media->disk)->delete($media->path);
            }

            MediaFile::query()->where('organization_id', $organization->id)->delete();
            Event::withTrashed()->whereIn('id', $eventIds)->forceDelete();

            $memberUserIds = OrganizationMembership::query()
                ->where('organization_id', $organization->id)
                ->pluck('user_id');

            ActivityLog::query()->where('organization_id', $organization->id)->delete();
            OrganizationInvitation::query()->where('organization_id', $organization->id)->delete();
            OrganizationSetting::query()->where('organization_id', $organization->id)->delete();
            OrganizationMembership::query()->where('organization_id', $organization->id)->delete();
            $organization->delete();

            if ($memberUserIds->isNotEmpty()) {
                User::query()->whereIn('id', $memberUserIds)->delete();
            }
        });

        DemoMode::flushDemoOrganizationCache();

        $this->callSilently(DemoSeeder::class);

        $this->info('Demo data has been reset to its baseline.');

        return self::SUCCESS;
    }
}
