<?php

namespace App\Console\Commands;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateOwnerCommand extends Command
{
    protected $signature = 'eventflow:create-owner
        {--organization= : Name of the organization to create or reuse}
        {--name= : Display name of the initial owner}
        {--email= : Email address of the initial owner}
        {--password= : Password for the initial owner (hidden prompt if omitted)}';

    protected $description = 'Bootstrap the initial Owner user with an Owner membership in an organization';

    public function handle(): int
    {
        $organizationName = $this->option('organization') ?: $this->askFor('organization', 'Organization name');
        $name = $this->option('name') ?: $this->askFor('name', 'Owner display name');
        $email = strtolower((string) ($this->option('email') ?: $this->askFor('email', 'Owner email address')));

        if ($organizationName === null || $name === null || $email === '') {
            $this->error('Missing required owner details. Rerun with --organization, --name and --email (and --password when non-interactive).');

            return self::FAILURE;
        }

        $validator = Validator::make([
            'organization' => $organizationName,
            'name' => $name,
            'email' => $email,
        ], [
            'organization' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', Rule::email()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $userExists = User::query()->where('email', $email)->exists();
        $password = ($this->option('password') !== null || ! $userExists) ? $this->ownerPassword() : null;

        if (! $userExists && $password === null) {
            $this->error('A password is required when creating a new Owner (pass --password or it will be prompted).');

            return self::FAILURE;
        }

        if ($password !== null && $this->invalidPassword($password)) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        [$organization, $user, $createdUser, $createdOrganization] = DB::transaction(function () use ($organizationName, $name, $email, $password): array {
            $organization = Organization::query()->where('name', $organizationName)->first();
            $createdOrganization = false;

            if ($organization === null) {
                $organization = Organization::query()->create([
                    'name' => $organizationName,
                    'slug' => $this->uniqueSlug($organizationName),
                    'timezone' => 'UTC',
                    'locale' => (string) config('app.locale', 'en'),
                ]);
                $createdOrganization = true;
            }

            $user = User::query()->where('email', $email)->first();
            $createdUser = false;

            if ($user === null) {
                $user = User::query()->create([
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
                $createdUser = true;
            } elseif ($password !== null || $name !== $user->name) {
                $attributes = ['name' => $name];

                if ($password !== null) {
                    $attributes['password'] = $password;
                }

                $user->forceFill($attributes)->save();
            }

            $membership = $organization->activeMemberships()->where('user_id', $user->id)->first();

            if ($membership === null) {
                OrganizationMembership::query()->create([
                    'organization_id' => $organization->id,
                    'user_id' => $user->id,
                    'role' => OrganizationRole::Owner,
                    'joined_at' => now(),
                ]);
            } elseif ($membership->role !== OrganizationRole::Owner) {
                $membership->forceFill(['role' => OrganizationRole::Owner])->save();
            }

            return [$organization, $user, $createdUser, $createdOrganization];
        });

        $this->info($createdOrganization && $createdUser ? 'Owner bootstrapped.' : 'Owner configuration is up to date.');
        $this->line('  Organization: '.$organization->name.' ('.$organization->slug.')');
        $this->line('  Owner user:   '.$user->email);
        $this->line('  Password:     (not shown)');

        return self::SUCCESS;
    }

    private function ownerPassword(): ?string
    {
        if ($this->option('password') !== null) {
            return (string) $this->option('password');
        }

        if ($this->input->isInteractive()) {
            return $this->secret('Owner password (at least 8 characters)');
        }

        return null;
    }

    private function invalidPassword(string $password): bool
    {
        return Validator::make(
            ['password' => $password],
            ['password' => ['required', 'string', Password::defaults()]],
        )->fails();
    }

    private function askFor(string $option, string $question): ?string
    {
        if (! $this->input->isInteractive()) {
            return null;
        }

        $value = $this->ask($question);

        return $value === '' ? null : $value;
    }

    private function uniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'organization';
        $slug = $baseSlug;
        $counter = 2;

        while (Organization::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
