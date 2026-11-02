@php($yclients = $studio['yclients_main'] ?? 'https://n1865142.yclients.com')
@php($phone = $studio['phone'] ?? '')
@php($workHours = $studio['work_hours'] ?? '')
<header class="header">
    <a href="{{ route('home') }}" class="logo" aria-label="HANDS — на главную">
        <img src="{{ asset('images/hands-logo.svg') }}" alt="" class="logo__mark" width="38" height="38">
        <span class="logo__text">HANDS</span>
    </a>
    <nav class="header__nav">
        {{-- Порядок пунктов повторяет порядок секций на главной — иначе
             подсветка текущего раздела прыгает по меню. «Цены» — отдельная
             страница, в последовательности якорей не участвует. --}}
        <a href="{{ route('prices') }}" @class(['is-current' => request()->routeIs('prices')])>Цены</a>
        <a href="{{ route('home') }}#services" data-spy="services">Услуги</a>
        <a href="{{ route('home') }}#about" data-spy="about">О студии</a>
        <a href="{{ route('home') }}#masters" data-spy="masters">Мастера</a>
        <a href="{{ route('home') }}#gift" data-spy="gift">Сертификаты</a>
        <a href="{{ route('home') }}#loyalty" data-spy="loyalty">Карта клиента</a>
        <a href="{{ route('home') }}#cabinets" data-spy="cabinets">Кабинеты</a>
        <a href="{{ route('home') }}#map" data-spy="map">Контакты</a>
        <a href="{{ route('home') }}#faq" data-spy="faq">Вопросы</a>
    </nav>
    <div class="header__actions">
        @if($phone)
            <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="header__contact">
                <span class="header__phone">{{ $phone }}</span>
                @if($workHours)<span class="header__hours">{{ $workHours }}</span>@endif
            </a>
        @endif
        <a href="{{ $yclients }}" target="_blank" rel="noopener" class="btn btn-primary">Записаться</a>
    </div>
    {{-- Полоса прочитанного по низу шапки (только на ПК, где шапка липкая) --}}
    <span class="header__progress" aria-hidden="true"></span>
</header>

@push('scripts')
<script>
    // Поведение шапки при скролле: ужимается, кнопка «Записаться» становится
    // золотой, заполняется полоса прочитанного — на всех экранах.
    // Подсветка раздела работает только там, где меню видно (ПК).
    (function () {
        var header = document.querySelector('.header');
        if (!header) return;

        var progress = header.querySelector('.header__progress');
        var links = Array.prototype.slice.call(header.querySelectorAll('[data-spy]'));
        var sections = links.map(function (a) { return document.getElementById(a.getAttribute('data-spy')); });
        var hasSections = sections.some(Boolean);

        function spy() {
            if (!hasSections) return;
            var probe = window.scrollY + 160;
            var best = -1;
            var bestTop = -Infinity;
            sections.forEach(function (section, i) {
                if (!section) return;
                var top = section.offsetTop;
                if (top <= probe && top > bestTop) {
                    bestTop = top;
                    best = i;
                }
            });
            links.forEach(function (link, i) { link.classList.toggle('is-active', i === best); });
        }

        function onScroll() {
            var y = window.scrollY;

            header.classList.toggle('is-compact', y > 40);

            if (progress) {
                var max = document.documentElement.scrollHeight - window.innerHeight;
                progress.style.width = (max > 0 ? (y / max) * 100 : 0) + '%';
            }

            spy();
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        onScroll();
    })();
</script>
@endpush
