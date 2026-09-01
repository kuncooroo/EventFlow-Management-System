<x-layouts.app title="Profile">
    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Your profile</h2>
            <p class="mt-1 text-sm text-slate-600">
                Update your personal account information. Organization roles are managed separately.
            </p>
        </div>

        @if ($status)
            <x-alert type="success" title="Saved">{{ $status }}</x-alert>
        @endif

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('app.profile.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <x-input
                    label="Name"
                    name="name"
                    type="text"
                    value="{{ old('name', $user->name) }}"
                    autocomplete="name"
                    required
                    autofocus
                    :error="$errors->first('name')"
                />

                <x-input
                    label="Email"
                    name="email"
                    type="email"
                    value="{{ old('email', $user->email) }}"
                    autocomplete="email"
                    required
                    :error="$errors->first('email')"
                />

                <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    Password changes use the
                    <a href="{{ route('password.request') }}" class="font-medium text-teal-800 hover:text-teal-900">
                        forgot password
                    </a>
                    flow. Password cannot be changed on this page.
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <x-button type="submit">Save changes</x-button>
                    <a href="{{ route('app.dashboard') }}">
                        <x-button type="button" variant="ghost">Cancel</x-button>
                    </a>
                </div>
            </form>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6 text-sm text-slate-600">
            <p class="font-medium text-slate-900">Account information</p>
            <dl class="mt-3 space-y-2">
                <div class="flex justify-between gap-4">
                    <dt>Member since</dt>
                    <dd class="text-slate-900">{{ $user->created_at?->format('M j, Y') ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt>Last updated</dt>
                    <dd class="text-slate-900">{{ $user->updated_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>
</x-layouts.app>
