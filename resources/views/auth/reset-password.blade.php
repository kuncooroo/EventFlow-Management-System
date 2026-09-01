<x-layouts.guest :title="'Reset password'">
    <div class="w-full space-y-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Choose a new password</h1>
            <p class="mt-2 text-sm text-slate-600">
                Enter a new password for your organizer account.
            </p>
        </div>

        @if ($errors->any())
            <x-alert type="danger" title="Unable to reset password">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <x-input
                label="Email"
                name="email"
                type="email"
                value="{{ old('email', $email) }}"
                autocomplete="username"
                required
                autofocus
                :error="$errors->first('email')"
            />

            <x-input
                label="New password"
                name="password"
                type="password"
                autocomplete="new-password"
                required
                :error="$errors->first('password')"
            />

            <x-input
                label="Confirm password"
                name="password_confirmation"
                type="password"
                autocomplete="new-password"
                required
            />

            <x-button type="submit" class="w-full">Reset password</x-button>
        </form>

        <p class="text-center text-sm">
            <a href="{{ route('login') }}" class="font-medium text-teal-800 hover:text-teal-900">
                Back to sign in
            </a>
        </p>
    </div>
</x-layouts.guest>
