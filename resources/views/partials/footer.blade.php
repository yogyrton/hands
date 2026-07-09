@php($yclients = $studio['yclients_main'] ?? 'https://n1865142.yclients.com')
@php($instagram = $studio['instagram_url'] ?? 'https://www.instagram.com/hands.mg/')
@php($address = $studio['address'] ?? 'переулок Пожарный, 3Б, Могилёв')
@php($legalName = $studio['legal_name'] ?? '')
@php($legalUnp = $studio['legal_unp'] ?? '')
<footer class="footer">
    <div class="footer__top">
        <div>
            <a href="{{ route('home') }}" class="logo">HANDS</a>
            <p class="footer__about">Массажная студия в Могилёве. В наших руках — ваше удовольствие.</p>
        </div>
        <div class="footer__cols">
            <div class="footer__col">
                <span class="h">Студия</span>
                <a href="{{ route('home') }}#services">Услуги</a>
                <a href="{{ route('home') }}#masters">Мастера</a>
                <a href="{{ route('home') }}#about">О студии</a>
            </div>
            <div class="footer__col">
                <span class="h">Контакты</span>
                <span>{{ $address }}</span>
                <a href="{{ $instagram }}" target="_blank" rel="noopener">@hands.mg</a>
                <a href="{{ $yclients }}" target="_blank" rel="noopener" class="gold">Записаться онлайн</a>
            </div>
            <div class="footer__col">
                <span class="h">Документы</span>
                <a href="{{ route('privacy') }}">Политика конфиденциальности</a>
                <a href="{{ route('cookie') }}">Политика cookie</a>
            </div>
        </div>
    </div>
    <div class="footer__legal">
        @if($legalName)
            <span>{{ $legalName }}@if($legalUnp) · УНП {{ $legalUnp }}@endif</span>
        @endif
        <span>© {{ date('Y') }} HANDS · Массажная студия · Приём только по предварительной записи</span>
    </div>
</footer>
