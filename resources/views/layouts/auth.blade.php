<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Selcosi Flota' }}</title>
    <meta name="description" content="Sistema de gestión de flota vehicular — Selcosi Flota Vehicular">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|jetbrains-mono:400,500" rel="stylesheet" />

    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-screen bg-slate-950 antialiased">

    {{-- Fondo decorativo --}}
    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -top-40 -right-40 h-[600px] w-[600px] rounded-full bg-brand-600/15 blur-[128px]"></div>
        <div class="absolute -bottom-40 -left-40 h-[500px] w-[500px] rounded-full bg-brand-800/10 blur-[128px]"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,rgba(255,255,255,0.03)_1px,transparent_0)] [background-size:32px_32px]"></div>
    </div>

    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-8 sm:px-6 lg:px-8">

        <div class="w-full max-w-md">

            {{-- Card del formulario --}}
            <div class="rounded-2xl border border-white/10 bg-white/5 p-8 shadow-2xl shadow-black/20 backdrop-blur-xl">
                {{ $slot }}
            </div>

            {{-- Footer dev --}}
            <p class="mt-8 text-center text-[11px] text-slate-600">
                Desarrollado por <a href="https://www.linkedin.com/in/yaniv-carreon/" target="_blank" rel="noopener" class="text-slate-500 transition-colors hover:text-brand-400">Yanldev</a>
            </p>
        </div>
    </div>

    @fluxScripts
</body>
</html>
