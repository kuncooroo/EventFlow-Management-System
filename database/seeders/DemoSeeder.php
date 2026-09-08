<?php

namespace Database\Seeders;

use App\Enums\CheckInMethod;
use App\Enums\EventStatus;
use App\Enums\OrganizationRole;
use App\Enums\RegistrationFieldType;
use App\Models\AgendaItem;
use App\Models\CheckIn;
use App\Models\Event;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Registration;
use App\Models\RegistrationAnswer;
use App\Models\RegistrationField;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Models\Venue;
use App\Support\Demo\DemoMode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DemoSeeder extends Seeder
{
    public const DEMO_OWNER_EMAIL = 'demo.owner@eventflow.demo';

    public const DEMO_ADMIN_EMAIL = 'demo.admin@eventflow.demo';

    public const DEMO_STAFF_EMAIL = 'demo.staff@eventflow.demo';

    public const DEMO_PASSWORD = 'eventflow-demo';

    public const DEMO_USER_EMAILS = [
        self::DEMO_OWNER_EMAIL,
        self::DEMO_ADMIN_EMAIL,
        self::DEMO_STAFF_EMAIL,
    ];

    public function run(): void
    {
        $slug = DemoMode::demoOrganizationSlug();

        if (Organization::query()->where('slug', $slug)->exists()) {
            return;
        }

        $organization = Organization::factory()->create([
            'name' => 'EventFlow Demo',
            'slug' => $slug,
            'timezone' => 'UTC',
            'locale' => 'en',
            'default_currency' => 'USD',
        ]);

        $owner = $this->createDemoUser($organization, self::DEMO_OWNER_EMAIL, 'Ava Demo', OrganizationRole::Owner);
        $this->createDemoUser($organization, self::DEMO_ADMIN_EMAIL, 'Noah Demo', OrganizationRole::Admin);
        $staff = $this->createDemoUser($organization, self::DEMO_STAFF_EMAIL, 'Mia Demo', OrganizationRole::Staff);

        $this->createTechnologyConference($organization, $owner, $staff);
        $this->createMarketingWorkshop($organization, $owner, $staff);
        $this->createCampusCareerFair($organization, $owner);
        $this->createProductExpo($organization, $owner, $staff);
    }

    private function createDemoUser(
        Organization $organization,
        string $email,
        string $name,
        OrganizationRole $role,
    ): User {
        $user = User::factory()->create([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => self::DEMO_PASSWORD,
        ]);

        OrganizationMembership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => $role,
            'joined_at' => now()->subMonths(6),
        ]);

        return $user;
    }

    private function createTechnologyConference(Organization $organization, User $owner, User $staff): Event
    {
        $event = $this->createPublishedEvent($organization, $owner, [
            'name' => 'Technology Conference 2026',
            'public_slug' => 'technology-conference-2026',
            'description' => 'A full-day conference on the year ahead: platform engineering, AI-assisted delivery and resilient team practices. Registration includes all sessions and the closing networking reception.',
            'organizer_name' => 'EventFlow Demo',
            'contact_name' => 'Ava Demo',
            'contact_email' => self::DEMO_OWNER_EMAIL,
            'mode' => 'offline',
            'start_at' => now()->addWeeks(6)->startOfDay()->addHours(9),
            'end_at' => now()->addWeeks(6)->startOfDay()->addHours(18),
            'capacity' => 300,
            'require_organization' => true,
        ]);

        Venue::factory()->create([
            'event_id' => $event->id,
            'name' => 'Grand Convention Center',
            'address' => '45 Innovation Ave, San Francisco, CA',
        ]);

        $general = TicketType::factory()->create([
            'event_id' => $event->id,
            'name' => 'General Admission',
            'description' => 'All sessions, lunch and the networking reception.',
            'price_amount' => 149.00,
            'currency' => 'USD',
            'capacity' => 250,
            'sort_order' => 1,
        ]);

        $vip = TicketType::factory()->create([
            'event_id' => $event->id,
            'name' => 'VIP Experience',
            'description' => 'Front rows, private track and an exclusive happy hour.',
            'price_amount' => 299.00,
            'currency' => 'USD',
            'capacity' => 50,
            'sort_order' => 2,
        ]);

        $company = RegistrationField::factory()->required()->create([
            'event_id' => $event->id,
            'field_key' => 'company',
            'label' => 'Company',
            'field_type' => RegistrationFieldType::Text,
            'sort_order' => 1,
        ]);

        $role = RegistrationField::factory()->required()->create([
            'event_id' => $event->id,
            'field_key' => 'role',
            'label' => 'Your role',
            'field_type' => RegistrationFieldType::Select,
            'options_json' => ['Engineer', 'Product', 'Design', 'Leadership'],
            'sort_order' => 2,
        ]);

        foreach ([
            ['Opening Keynote', 0, 45],
            ['Platform Engineering Panel', 1, 90],
            ['Hands-on AI Workshop', 3, 150],
            ['Networking Reception', 6, 60],
        ] as $index => [$title, $hoursAfterStart, $minutes]) {
            AgendaItem::factory()->create([
                'event_id' => $event->id,
                'title' => $title,
                'start_at' => $event->start_at->addHours($hoursAfterStart),
                'end_at' => $event->start_at->addHours($hoursAfterStart)->addMinutes($minutes),
                'sort_order' => $index + 1,
            ]);
        }

        $confirmed = $this->registrations(
            $event,
            ['general' => 3, 'vip' => 3],
            ['general' => $general->id, 'vip' => $vip->id],
            7,
        );

        $this->cancelledRegistrations($event, $general->id, 2);
        $this->answersForTech($confirmed, $company, $role);
        $this->checkedIn($confirmed->take(4), $staff);

        return $event;
    }

    private function createMarketingWorkshop(Organization $organization, User $owner, User $staff): Event
    {
        $event = $this->createPublishedEvent($organization, $owner, [
            'name' => 'Digital Marketing Workshop',
            'public_slug' => 'digital-marketing-workshop',
            'description' => 'A half-day online workshop on planning campaigns that convert: audience research, social strategy and conversion copywriting, with time for Q&A.',
            'organizer_name' => 'EventFlow Demo',
            'contact_name' => 'Mia Demo',
            'contact_email' => self::DEMO_STAFF_EMAIL,
            'mode' => 'online',
            'start_at' => now()->addWeeks(2)->startOfDay()->addHours(9),
            'end_at' => now()->addWeeks(2)->startOfDay()->addHours(13),
            'capacity' => 120,
        ]);

        $seat = TicketType::factory()->create([
            'event_id' => $event->id,
            'name' => 'Workshop Seat',
            'description' => 'Live session including the workbook.',
            'price_amount' => 49.00,
            'currency' => 'USD',
            'capacity' => 120,
            'sort_order' => 1,
        ]);

        RegistrationField::factory()->required()->create([
            'event_id' => $event->id,
            'field_key' => 'audience',
            'label' => 'Agency or brand?',
            'field_type' => RegistrationFieldType::Select,
            'options_json' => ['Agency', 'Brand', 'Freelancer', 'Other'],
            'sort_order' => 1,
        ]);

        foreach ([
            ['Social Strategy That Converts', 0, 90],
            ['Conversion Copywriting', 2, 90],
        ] as $index => [$title, $hoursAfterStart, $minutes]) {
            AgendaItem::factory()->create([
                'event_id' => $event->id,
                'title' => $title,
                'start_at' => $event->start_at->addHours($hoursAfterStart),
                'end_at' => $event->start_at->addHours($hoursAfterStart)->addMinutes($minutes),
                'sort_order' => $index + 1,
            ]);
        }

        $confirmed = $this->registrations(
            $event,
            ['seat' => 4],
            ['seat' => $seat->id],
            4,
        );

        $this->cancelledRegistrations($event, $seat->id, 1);
        $this->checkedIn($confirmed->take(2), $staff);

        return $event;
    }

    private function createCampusCareerFair(Organization $organization, User $owner): Event
    {
        $event = $this->createPublishedEvent($organization, $owner, [
            'name' => 'Campus Career Fair',
            'public_slug' => 'campus-career-fair',
            'description' => 'An afternoon fair connecting students and early-career builders with hiring teams across engineering, design and marketing.',
            'organizer_name' => 'EventFlow Demo',
            'contact_name' => 'Noah Demo',
            'contact_email' => self::DEMO_ADMIN_EMAIL,
            'mode' => 'hybrid',
            'start_at' => now()->addMonths(3)->startOfDay()->addHours(10),
            'end_at' => now()->addMonths(3)->startOfDay()->addHours(15),
        ]);

        Venue::factory()->create([
            'event_id' => $event->id,
            'name' => 'University Quad',
            'address' => '120 Campus Way, Berkeley, CA',
        ]);

        $pass = TicketType::factory()->free()->create([
            'event_id' => $event->id,
            'name' => 'Student Pass',
            'description' => 'Free entry for students.',
            'sort_order' => 1,
        ]);

        RegistrationField::factory()->create([
            'event_id' => $event->id,
            'field_key' => 'major',
            'label' => 'Major',
            'field_type' => RegistrationFieldType::Text,
            'is_required' => false,
            'sort_order' => 1,
        ]);

        AgendaItem::factory()->create([
            'event_id' => $event->id,
            'title' => 'Employer Fair',
            'start_at' => $event->start_at,
            'end_at' => $event->end_at,
            'sort_order' => 1,
        ]);

        $this->registrations($event, ['pass' => 5], ['pass' => $pass->id], 5);

        return $event;
    }

    private function createProductExpo(Organization $organization, User $owner, User $staff): Event
    {
        $event = $this->createPublishedEvent($organization, $owner, [
            'name' => 'Spring Product Expo',
            'public_slug' => 'spring-product-expo',
            'description' => 'A past expo kept as a completed showcase so reports and check-in history have realistic data.',
            'organizer_name' => 'EventFlow Demo',
            'contact_name' => 'Ava Demo',
            'contact_email' => self::DEMO_OWNER_EMAIL,
            'mode' => 'offline',
            'start_at' => now()->subMonths(2)->startOfDay()->addHours(10),
            'end_at' => now()->subMonths(2)->startOfDay()->addHours(16),
            'status' => EventStatus::Completed,
            'started_at' => now()->subMonths(2)->startOfDay()->addHours(10),
            'completed_at' => now()->subMonths(2)->startOfDay()->addHours(16),
        ]);

        Venue::factory()->create([
            'event_id' => $event->id,
            'name' => 'Metro Expo Hall',
            'address' => '8 Harbor Blvd, Portland, OR',
        ]);

        $pass = TicketType::factory()->free()->create([
            'event_id' => $event->id,
            'name' => 'Exhibitor Pass',
            'description' => 'Free entry for exhibitors and press.',
            'sort_order' => 1,
        ]);

        AgendaItem::factory()->create([
            'event_id' => $event->id,
            'title' => 'Expo Kickoff',
            'start_at' => $event->start_at,
            'end_at' => $event->start_at->addMinutes(45),
            'sort_order' => 1,
        ]);

        $confirmed = $this->registrations($event, ['pass' => 5], ['pass' => $pass->id], 10);
        $this->cancelledRegistrations($event, $pass->id, 1);
        $this->checkedIn($confirmed->take(4), $staff);

        return $event;
    }

    /**
     * @param  array<string, int>  $countsByKey
     * @param  array<string, int>  $ticketTypeIdsByKey
     * @return Collection<int, Registration>
     */
    private function registrations(
        Event $event,
        array $countsByKey,
        array $ticketTypeIdsByKey,
        int $seed,
    ): Collection {
        $confirmed = collect();

        foreach ($countsByKey as $key => $count) {
            for ($i = 0; $i < $count; $i++) {
                $registration = Registration::factory()->confirmed()->create([
                    'event_id' => $event->id,
                    'ticket_type_id' => $ticketTypeIdsByKey[$key],
                    'registered_at' => now()->subDays($seed + $i),
                ]);

                Ticket::factory()->create([
                    'registration_id' => $registration->id,
                    'issued_at' => $registration->registered_at,
                ]);

                $confirmed->push($registration);
            }
        }

        return $confirmed;
    }

    private function cancelledRegistrations(Event $event, int $ticketTypeId, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            Registration::factory()->cancelled()->create([
                'event_id' => $event->id,
                'ticket_type_id' => $ticketTypeId,
                'registered_at' => now()->subDays($i * 2 + 1),
                'cancelled_at' => now()->subDay($i + 1),
            ]);
        }
    }

    /**
     * @param  Collection<int, Registration>  $registrations
     */
    private function checkedIn(Collection $registrations, User $staff): void
    {
        foreach ($registrations as $registration) {
            CheckIn::factory()->create([
                'registration_id' => $registration->id,
                'ticket_id' => $registration->ticket?->id,
                'operator_user_id' => $staff->id,
                'method' => fake()->boolean(70) ? CheckInMethod::Qr : CheckInMethod::Manual,
                'checked_in_at' => now()->subHours(fake()->numberBetween(1, 24)),
            ]);
        }
    }

    /**
     * @param  Collection<int, Registration>  $confirmed
     */
    private function answersForTech(
        Collection $confirmed,
        RegistrationField $company,
        RegistrationField $role,
    ): void {
        $roles = ['Engineer', 'Product', 'Design', 'Leadership'];

        foreach ($confirmed as $index => $registration) {
            RegistrationAnswer::factory()->create([
                'registration_id' => $registration->id,
                'registration_field_id' => $company->id,
                'answer_text' => fake()->company(),
            ]);

            RegistrationAnswer::factory()->create([
                'registration_id' => $registration->id,
                'registration_field_id' => $role->id,
                'answer_text' => $roles[$index % count($roles)],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createPublishedEvent(
        Organization $organization,
        User $owner,
        array $overrides,
    ): Event {
        return Event::factory()->published()->create(array_merge([
            'organization_id' => $organization->id,
            'created_by_user_id' => $owner->id,
            'registration_enabled' => true,
            'registration_starts_at' => now()->subWeek(),
            'registration_ends_at' => now()->addWeeks(4),
            'published_at' => now()->subMonth(),
            'require_phone' => false,
            'require_organization' => false,
            'reminder_enabled' => false,
        ], $overrides));
    }
}
