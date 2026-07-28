@php($yclients = $studio['yclients_main'] ?? 'https://n1865142.yclients.com')
@php($instagram = $studio['instagram_url'] ?? 'https://www.instagram.com/hands.mg/')
@php($address = $studio['address'] ?? 'переулок Пожарный, 3Б, Могилёв')
@php($phone = $studio['phone'] ?? '')
@php($mapsUrl = 'https://yandex.ru/maps/?text=' . urlencode($address . ', Могилёв'))
@if(preg_match('/ll=([\d.]+)(?:%2C|,)([\d.]+)/', $studio['yandex_map_embed'] ?? '', $mm))
    @php($mapsUrl = 'https://yandex.ru/maps/?ll=' . $mm[1] . ',' . $mm[2] . '&z=17&pt=' . $mm[1] . ',' . $mm[2])
@endif
@php($legalName = $studio['legal_name'] ?? '')
@php($legalUnp = $studio['legal_unp'] ?? '')
@php($legalAuthority = $studio['legal_reg_authority'] ?? '')
@php($legalRegDate = $studio['legal_reg_date'] ?? '')
@php($legalAddress = $studio['legal_address'] ?? '')
@php($workHours = $studio['work_hours'] ?? '')
@php($paymentReceipt = $studio['payment_receipt'] ?? '')
<footer class="footer">
    <div class="footer__top">
        <div>
            <a href="{{ route('home') }}" class="logo">
                <img src="{{ asset('images/hands-logo.svg') }}" alt="" class="logo__mark" width="38" height="38">
                <span class="logo__text">HANDS</span>
            </a>
            <p class="footer__about">Массажная студия в Могилёве. В наших руках — ваше удовольствие.</p>
        </div>
        <div class="footer__cols">
            <div class="footer__col">
                <span class="h">Контакты</span>
                @if($legalName)
                    <span>{{ $legalName }}@if($legalUnp) · УНП {{ $legalUnp }}@endif</span>
                @endif
                @if($legalAuthority || $legalRegDate)
                    <span>Свидетельство о госрегистрации@if($legalAuthority): выдано {{ $legalAuthority }}@endif@if($legalRegDate) от {{ $legalRegDate }}@endif</span>
                @endif
                @if($legalAddress)
                    <span>Юридический адрес: {{ $legalAddress }}</span>
                @endif
            </div>
            <div class="footer__col">
                <span class="h">Студия</span>
                <a href="{{ route('home') }}#services">Услуги</a>
                <a href="{{ route('home') }}#masters">Мастера</a>
                <a href="{{ route('home') }}#about">О студии</a>
            </div>
            <div class="footer__col">
                <span class="h">Контакты</span>
                <a href="{{ $mapsUrl }}" target="_blank" rel="noopener">{{ $address }}</a>
                @if($phone)
                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">{{ $phone }}</a>
                @endif
                <a href="{{ $instagram }}" target="_blank" rel="noopener">Instagram</a>
                <a href="{{ $yclients }}" target="_blank" rel="noopener" class="gold">Записаться онлайн</a>
            </div>
            <div class="footer__col">
                <span class="h">Документы</span>
                <a href="{{ route('privacy') }}">Политика конфиденциальности</a>
                <a href="{{ route('cookie') }}">Политика cookie</a>
                @if($paymentReceipt)
                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($paymentReceipt) }}" target="_blank" rel="noopener">Образец документа об оплате</a>
                @endif
            </div>
        </div>
    </div>
    <div class="footer__legal">
        <span>Адрес студии: {{ $address }}@if($workHours) · Режим работы: {{ $workHours }}@endif</span>
        <span>© {{ date('Y') }} HANDS · Массажная студия · Приём только по предварительной записи</span>
    </div>
</footer>
