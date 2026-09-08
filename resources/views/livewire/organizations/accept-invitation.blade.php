<div class="mx-auto max-w-md py-12">
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-teal-100 text-teal-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 002-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>

            <h2 class="mt-4 text-xl font-bold text-slate-900">Organization Invitation</h2>

            @if ($invitation)
                <p class="mt-2 text-sm text-slate-600">
                    <span class="font-medium text-slate-900">{{ $invitation->invitedBy->name }}</span>
                    has invited you to join
                    <span class="font-bold text-teal-700">{{ $invitation->organization->name }}</span>
                    as <span class="font-semibold text-slate-900">{{ $invitation->role->label() }}</span>.
                </p>
            @endif
        </div>

        @if ($errorMessage)
            <div class="mt-6">
                <x-alert type="danger" title="Unable to Accept Invitation">
                    {{ $errorMessage }}
                </x-alert>

                @auth
                    <div class="mt-4 text-center">
                        <a href="{{ route('app.dashboard') }}" class="text-sm font-medium text-teal-600 hover:text-teal-700">
                            Return to Dashboard &rarr;
                        </a>
                    </div>
                @else
                    <div class="mt-4 text-center">
                        <a href="{{ route('login') }}" class="text-sm font-medium text-teal-600 hover:text-teal-700">
                            Log In &rarr;
                        </a>
                    </div>
                @endauth
            @elseif ($invitation && $invitation->isPending())
                <div class="mt-6 space-y-4">
                    @auth
                        @if (strcasecmp(auth()->user()->email, $invitation->email) === 0)
                            <x-button type="button" variant="primary" class="w-full justify-center" wire:click="accept">
                                Accept Invitation & Join
                            </x-button>
                        @else
                            <x-alert type="warning" title="Account Mismatch">
                                This invitation was sent to <strong class="font-semibold">{{ $invitation->email }}</strong>, but you are currently signed in as <strong class="font-semibold">{{ auth()->user()->email }}</strong>.
                            </x-alert>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-button type="submit" variant="secondary" class="w-full justify-center">
                                    Sign out and switch account
                                </x-button>
                            </form>
                        @endif
                    @else
                        <x-alert type="info" title="Sign In Required">
                            Please log in or create an account with <strong class="font-semibold">{{ $invitation->email }}</strong> to accept this invitation.
                        </x-alert>

                        <div class="mt-4 text-center">
                            <a href="{{ route('login') }}" class="inline-flex justify-center rounded-lg bg-teal-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-700">
                                Log In to Accept
                            </a>
                        </div>
                    @endauth
                </div>
            @endif
    </div>
</div>
