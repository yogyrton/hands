@php($yclients = $studio['yclients_main'] ?? 'https://n1865142.yclients.com')
@php($instagram = $studio['instagram_url'] ?? 'https://www.instagram.com/hands.mg/')
@php($address = $studio['address'] ?? 'проезд Пожарского 3Б, Могилёв')
@php($mapEmbed = $studio['yandex_map_embed'] ?? null)
<section id="map" class="map section--dark">
    <div class="map__text">
        <span class="eyebrow eyebrow--gold">Как нас найти</span>
        <h2>Могилёв,<br>проезд Пожарского 3Б</h2>
        <div class="contact-rows">
            <div class="contact-row"><span class="k">Адрес</span><span class="v">{{ $address }}</span></div>
            <div class="contact-row"><span class="k">Запись</span><span class="v">только онлайн через <a href="{{ $yclients }}" target="_blank" rel="noopener" style="color:var(--gold-light)">YClients</a></span></div>
            <div class="contact-row"><span class="k">Instagram</span><a href="{{ $instagram }}" target="_blank" rel="noopener" style="color:var(--gold-light)">@hands.mg</a></div>
        </div>
        <a href="{{ $yclients }}" target="_blank" rel="noopener" class="btn btn-gold">Записаться онлайн →</a>
    </div>
    <div class="map__frame">
        @if($mapEmbed)
            <iframe src="{{ $mapEmbed }}" width="100%" height="100%" frameborder="0" allowfullscreen loading="lazy"></iframe>
        @endif
    </div>
</section>
