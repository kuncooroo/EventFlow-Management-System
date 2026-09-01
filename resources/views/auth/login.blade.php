<x-layouts.guest :title="'Sign in'">
    <div class="w-full space-y-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Welcome back</h1>
            <p class="mt-2 text-sm text-slate-600">Sign in to manage your events.</p>
        </div>

        @if ($status)
            <x-alert type="success" title="Notice">{{ $status }}</x-alert>
        @endif

        @if ($errors->any())
            <x-alert type="danger" title="Unable to sign in">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <x-input
                label="Email"
                name="email"
                type="email"
                value="{{ $email }}"
                autocomplete="username"
                required
                autofocus
                :error="$errors->first('email')"
            />

            <x-input
                label="Password"
                name="password"
                type="password"
                autocomplete="current-password"
                required
                :error="$errors->first('password')"
            />

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                    @checked(old('remember'))
                    class="rounded border-slate-300 text-teal-700 focus:ring-teal-600/20"
                >
                Remember me
            </label>

            <x-button type="submit" class="w-full">Sign in</x-button>
        </form>

        <p class="text-center text-sm">
            <a href="{{ route('password.request') }}" class="font-medium text-teal-800 hover:text-teal-900">
                Forgot password?
            </a>
        </p>
    </div>
</x-layouts.guest>
