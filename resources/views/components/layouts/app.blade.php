<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@isset($title){{ $title }} — @endisset{{ config('app.name', 'EventFlow') }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-[16rem_1fr]">
        <aside class="border-b border-slate-200 bg-white lg:border-b-0 lg:border-r">
            <div class="space-y-3 px-4 pb-3 pt-4">
                <a href="{{ route('home') }}" class="block text-sm font-semibold tracking-tight text-slate-900">
                    {{ config('app.name', 'EventFlow') }}
                </a>
                <x-organization-switcher
                    :accessible-organizations="$accessibleOrganizations ?? collect()"
                    :current-organization="$currentOrganization ?? null"
                />
            </div>
            <nav class="space-y-1 px-3 pb-4 text-sm" aria-label="Primary">
                <a href="{{ route('app.dashboard') }}" @class([
                    'block rounded-md px-3 py-2',
                    'bg-slate-100 text-slate-900 font-medium' => request()->routeIs('app.dashboard'),
                    'text-slate-700 hover:bg-slate-100' => ! request()->routeIs('app.dashboard'),
                ])>
                    Dashboard
                </a>
                <span class="block rounded-md px-3 py-2 text-slate-400" title="Coming in later tasks">
                    Events
                </span>
                @if (($currentMembership ?? null)?->role->canViewMembers())
                    <a href="{{ route('app.members.index') }}" @class([
                        'block rounded-md px-3 py-2',
                        'bg-slate-100 text-slate-900 font-medium' => request()->routeIs('app.members.*'),
                        'text-slate-700 hover:bg-slate-100' => ! request()->routeIs('app.members.*'),
                    ])>
                        Members
                    </a>
                @endif
                <span class="block rounded-md px-3 py-2 text-slate-400" title="Coming in later tasks">
                    Reports
                </span>
                <span class="block rounded-md px-3 py-2 text-slate-400" title="Coming in later tasks">
                    Settings
                </span>
                <a href="{{ route('app.profile.edit') }}" @class([
                    'block rounded-md px-3 py-2',
                    'bg-slate-100 text-slate-900 font-medium' => request()->routeIs('app.profile.*'),
                    'text-slate-700 hover:bg-slate-100' => ! request()->routeIs('app.profile.*'),
                ])>
                    Profile
                </a>
            </nav>
        </aside>

        <div class="flex min-w-0 flex-col">
            <header class="flex h-14 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6">
                <div class="min-w-0">
                    @isset($header)
                        {{ $header }}
                    @else
                        <h1 class="truncate text-base font-semibold text-slate-900">{{ $title ?? 'Workspace' }}</h1>
                    @endisset
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <a href="{{ route('app.profile.edit') }}" class="hidden text-slate-600 hover:text-slate-900 sm:inline">
                        {{ auth()->user()->name }}
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-button type="submit" variant="ghost" size="sm">Sign out</x-button>
                    </form>
                </div>
            </header>

            <main class="flex-1 px-4 py-6 sm:px-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
