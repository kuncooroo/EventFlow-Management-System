<x-layouts.public :title="'Welcome'">
    <div class="space-y-8">
        <section class="space-y-3">
            <p class="text-sm font-medium uppercase tracking-wide text-teal-800">EventFlow</p>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">
                Event operations in one workspace
            </h1>
            <p class="max-w-2xl text-base text-slate-600">
                Sign in to manage events, registrations, check-ins, and reports from one organizer workspace.
            </p>
            <div class="flex flex-wrap gap-3">
                @auth
                    <a href="{{ route('app.dashboard') }}" class="inline-flex">
                        <x-button type="button">Open dashboard</x-button>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex">
                        <x-button type="button">Sign in</x-button>
                    </a>
                @endauth
                <a href="{{ route('public.foundation') }}" class="inline-flex">
                    <x-button type="button" variant="secondary">View foundation smoke page</x-button>
                </a>
            </div>
        </section>

        <x-empty-state
            title="No events yet"
            description="Event creation will be available after the organization and event tasks are implemented."
        />
    </div>
</x-layouts.public>
