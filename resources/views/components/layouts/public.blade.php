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
<body class="min-h-screen bg-white text-slate-900 antialiased">
    <header class="border-b border-slate-200">
        <div class="mx-auto flex h-14 max-w-5xl items-center justify-between px-4 sm:px-6">
            <a href="{{ route('home') }}" class="text-sm font-semibold tracking-tight text-slate-900">
                {{ config('app.name', 'EventFlow') }}
            </a>
            <span class="text-xs text-slate-500">Public</span>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-200">
        <div class="mx-auto max-w-5xl px-4 py-6 text-xs text-slate-500 sm:px-6">
            Event operations workspace
        </div>
    </footer>

    @livewireScripts
</body>
</html>
