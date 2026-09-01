<x-layouts.guest :title="'Forgot password'">
    <div class="w-full space-y-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Reset your password</h1>
            <p class="mt-2 text-sm text-slate-600">
                Enter the email address associated with your organizer account.
            </p>
        </div>

        @if ($status)
            <x-alert type="success" title="Check your email">{{ $status }}</x-alert>
        @endif

        @if ($errors->any())
            <x-alert type="danger" title="Unable to send reset link">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

            <x-input
                label="Email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                autocomplete="username"
                required
                autofocus
                :error="$errors->first('email')"
            />

            <x-button type="submit" class="w-full">Send reset link</x-button>
        </form>

        <p class="text-center text-sm">
            <a href="{{ route('login') }}" class="font-medium text-teal-800 hover:text-teal-900">
                Back to sign in
            </a>
        </p>
    </div>
</x-layouts.guest>
