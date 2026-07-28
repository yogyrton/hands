<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/hands-logo.svg') }}">
    @php($seoDefaultTitle = 'HANDS — массажная студия в Могилёве · запись онлайн')
    @php($seoDefaultDescription = 'HANDS — массажная студия в Могилёве, переулок Пожарный, 3Б. Классический, спортивный, релакс, массаж спины и лица, коррекция фигуры. Только по предварительной записи через YClients.')
    <title>@yield('title', $seoDefaultTitle)</title>
    <meta name="description" content="@yield('meta_description', $seoDefaultDescription)">

    <link rel="canonical" href="{{ url()->current() }}">

    @php($seoOg = ($studio['og_image'] ?? '') ?: asset('images/og-image.png'))
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="HANDS">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', $seoDefaultTitle)">
    <meta property="og:description" content="@yield('meta_description', $seoDefaultDescription)">
    <meta property="og:image" content="{{ $seoOg }}">
    <meta name="twitter:card" content="summary_large_image">
    @if(! empty($studio['google_verification']))
        <meta name="google-site-verification" content="{{ $studio['google_verification'] }}">
    @endif
    @if(! empty($studio['yandex_verification']))
        <meta name="yandex-verification" content="{{ $studio['yandex_verification'] }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @php($fontsHref = 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Manrope:wght@300;400;500;600;700&display=swap')
    {{-- Шрифты грузим неблокирующе: сначала отрисовка в системном шрифте, потом подмена (display=swap) --}}
    <link rel="preload" as="style" href="{{ $fontsHref }}" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="{{ $fontsHref }}"></noscript>
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ @filemtime(public_path('css/site.css')) }}">
    @stack('head')
</head>
<body>
<div class="wrap">
    @include('partials.header')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')
</div>
@include('partials.back-to-top')
@stack('scripts')
</body>
</html>
