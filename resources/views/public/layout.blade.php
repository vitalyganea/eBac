{{--
    Layout pentru paginile PUBLICE, indexabile.

    Deliberat fără JavaScript: aceste pagini trebuie să fie citibile de crawlere
    fără randare, rapide pe telefoane ieftine cu date mobile limitate, și să nu
    plătească nimic pentru interactivitatea de care nu au nevoie. Aplicația de
    joc rămâne separat, în Inertia.
--}}
@php($meta = $meta ?? new \App\Domain\Seo\PageMeta(config('seo.default_title'), config('seo.default_description')))
<!DOCTYPE html>
@php($appearance = \App\Support\Appearance::class)
<html lang="ro-MD" class="h-full"
      data-theme="{{ $appearance::theme() }}"
      @if ($font = $appearance::font()) data-font="{{ $font }}" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ $appearance::themeColor() }}">

    <title>{{ $meta->fullTitle() }}</title>
    <meta name="description" content="{{ $meta->trimmedDescription() }}">

    @if ($meta->canonical)
        <link rel="canonical" href="{{ $meta->canonical }}">
    @endif

    @if ($meta->noindex)
        <meta name="robots" content="noindex, nofollow">
    @else
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    @endif

    {{-- Țintire geografică explicită: platforma e pentru Republica Moldova. --}}
    <meta name="geo.region" content="MD">
    <meta name="geo.placename" content="{{ config('seo.city') }}">

    @foreach (config('seo.locales') as $code)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ $meta->canonical ?? url()->current() }}">
    @endforeach

    <meta property="og:type" content="{{ $meta->ogType }}">
    <meta property="og:site_name" content="{{ config('seo.site_name') }}">
    <meta property="og:locale" content="{{ config('seo.locale') }}">
    <meta property="og:title" content="{{ $meta->title }}">
    <meta property="og:description" content="{{ $meta->trimmedDescription() }}">
    @if ($meta->canonical)
        <meta property="og:url" content="{{ $meta->canonical }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">

    @php($graph = \App\Domain\Seo\JsonLd::graph(array_merge(
        [\App\Domain\Seo\JsonLd::organization(), \App\Domain\Seo\JsonLd::breadcrumbs($meta->breadcrumbs)],
        $meta->jsonLd,
    )))
    <script type="application/ld+json">{!! json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

    {{-- Doar CSS. Nicio intrare JS pe paginile publice. --}}
    @vite(['resources/css/app.css'])
</head>
<body class="flex h-full flex-col bg-slate-950 text-slate-100 antialiased">
    @include('public.partials.header')

    <main class="mx-auto w-full max-w-3xl flex-1 px-4 py-8">
        @if (count($meta->breadcrumbs) > 1)
            <nav aria-label="Navigare" class="mb-6 text-sm text-slate-400">
                @foreach ($meta->breadcrumbs as $i => $crumb)
                    @if ($i > 0)<span class="mx-1.5 text-slate-600">/</span>@endif
                    @if ($crumb['url'] && ! $loop->last)
                        <a href="{{ $crumb['url'] }}" class="hover:text-slate-200">{{ $crumb['name'] }}</a>
                    @else
                        <span class="text-slate-300">{{ $crumb['name'] }}</span>
                    @endif
                @endforeach
            </nav>
        @endif

        @yield('content')
    </main>

    @include('public.partials.footer')
</body>
</html>
