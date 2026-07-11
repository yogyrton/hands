@php($yclients = $studio['yclients_main'] ?? 'https://n1865142.yclients.com')
<header class="header">
    <a href="{{ route('home') }}" class="logo">
        <img src="{{ asset('images/hands-logo.svg') }}" alt="" class="logo__mark" width="38" height="38">
        <span class="logo__text">HANDS</span>
    </a>
    <nav class="header__nav">
        <a href="{{ route('home') }}#services">Услуги</a>
        <a href="{{ route('home') }}#masters">Мастера</a>
        <a href="{{ route('home') }}#about">О студии</a>
        <a href="{{ route('home') }}#cabinets">Кабинеты</a>
        <a href="{{ route('home') }}#map">Контакты</a>
        <a href="{{ route('home') }}#faq">Вопросы</a>
    </nav>
    <a href="{{ $yclients }}" target="_blank" rel="noopener" class="btn btn-primary">Записаться</a>
</header>
