<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'HANDS — массажная студия в Могилёве · запись онлайн')</title>
    <meta name="description" content="@yield('meta_description', 'HANDS — массажная студия в Могилёве, переулок Пожарный, 3Б. Классический, спортивный, релакс, массаж спины и лица, коррекция фигуры. Только по предварительной записи через YClients.')">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}">
    @stack('head')
</head>
<body>
<div class="wrap">
    @include('partials.header')

    @yield('content')

    @include('partials.footer')
</div>
@stack('scripts')
</body>
</html>
