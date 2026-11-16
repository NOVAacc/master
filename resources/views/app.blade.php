<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php($seo = $page['props']['seo'])
    <title inertia>{{ $seo['title'] }}</title>
    <meta name="robots" content="{{ $seo['index'] ? 'index, follow, max-image-preview:large' : 'noindex, follow' }}" inertia="robots">
    <link rel="canonical" href="{{ $seo['canonical'] }}" inertia="canonical">
    <meta property="og:title" content="{{ $seo['title'] }}" inertia="og:title">
    <meta property="og:description" content="{{ $seo['description'] }}" inertia="og:description">
    <meta property="og:url" content="{{ $seo['canonical'] }}" inertia="og:url">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ru_KG">
    <meta name="twitter:title" content="{{ $seo['title'] }}" inertia="twitter:title">
    <meta name="twitter:description" content="{{ $seo['description'] }}" inertia="twitter:description">
    @if($seo['schema'])
    <script type="application/ld+json" inertia="schema">{!! json_encode($seo['schema'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endif
    <meta name="application-name" content="BOSH">
    <meta name="apple-mobile-web-app-title" content="BOSH">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="description" content="{{ $seo['description'] }}" inertia="description">
    <meta property="og:site_name" content="BOSH">
    <meta name="theme-color" content="#202321">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=bosh-20261022">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png?v=bosh-20261022">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16.png?v=bosh-20261022">
    <link rel="shortcut icon" href="/favicon.ico?v=bosh-20261022">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png?v=bosh-20261022">
    <link rel="manifest" href="/site.webmanifest?v=bosh-20261022">
    <meta property="og:image" content="{{ url('/brand/bosh-social.png?v=bosh-20261022') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="BOSH — бесплатный сервис онлайн-записи">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ url('/brand/bosh-social.png?v=bosh-20261022') }}">
    @viteReactRefresh
    @vite(['resources/js/app.tsx'])
    @inertiaHead
</head>
<body>
    {{-- Public content is available before JavaScript; React replaces this same root on mount. --}}
    <div id="app" data-page="{{ json_encode($page) }}">@include('seo-content')</div>
</body>
</html>
