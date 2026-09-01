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
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <div class="flex min-h-screen flex-col">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex h-14 max-w-lg items-center justify-between px-4">
                <a href="{{ route('home') }}" class="text-sm font-semibold tracking-tight text-slate-900">
                    {{ config('app.name', 'EventFlow') }}
                </a>
            </div>
        </header>

        <main class="mx-auto flex w-full max-w-lg flex-1 flex-col justify-center px-4 py-10">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
