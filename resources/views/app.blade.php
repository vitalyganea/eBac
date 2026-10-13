<!DOCTYPE html>
@php($appearance = \App\Support\Appearance::class)
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full"
      data-theme="{{ $appearance::theme() }}"
      @if ($font = $appearance::font()) data-font="{{ $font }}" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ $appearance::themeColor() }}">
    <title inertia>{{ config('app.name', 'eBac') }}</title>

    {{-- Aplicația nu are ce căuta în rezultatele căutării: sunt pagini personale,
         cu sesiuni de antrenament identificate prin UUID. robots.txt le exclude
         deja, dar un meta noindex acoperă și cazul unui link scăpat public. --}}
    <meta name="robots" content="noindex, nofollow">

    @routes
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    @inertiaHead
</head>
<body class="h-full bg-slate-950 text-slate-100 antialiased">
    @inertia
</body>
</html>
