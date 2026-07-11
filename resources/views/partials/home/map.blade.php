@php($yclients = $studio['yclients_main'] ?? 'https://n1865142.yclients.com')
@php($instagram = $studio['instagram_url'] ?? 'https://www.instagram.com/hands.mg/')
@php($address = $studio['address'] ?? 'переулок Пожарный, 3Б, Могилёв')
@php($mapEmbed = $studio['yandex_map_embed'] ?? null)
<section id="map" class="map section--dark">
    <div class="map__text">
        <span class="eyebrow eyebrow--gold">Как нас найти</span>
        <h2>{{ $address }}</h2>
        <div class="contact-rows">
            <div class="contact-row"><span class="k">Запись</span><span class="v">только онлайн через <a href="{{ $yclients }}" target="_blank" rel="noopener" style="color:var(--gold-light)">YClients</a></span></div>
            <div class="contact-row"><span class="k">Instagram</span><a href="{{ $instagram }}" target="_blank" rel="noopener" style="color:var(--gold-light)">@hands.mg</a></div>
        </div>
        <a href="{{ $yclients }}" target="_blank" rel="noopener" class="btn btn-gold">Записаться онлайн →</a>
    </div>
    <div class="map__frame">
        @if($mapEmbed)
            {{-- Фасад: карту (сторонний виджет Яндекса ~264 КиБ JS + куки) грузим
                 только по клику. До клика — лёгкая заглушка, ноль стороннего кода. --}}
            <button type="button" class="map-facade" data-map-embed="{{ $mapEmbed }}"
                    aria-label="Показать интерактивную карту: переулок Пожарный, 3Б, Могилёв">
                <span class="map-facade__pin" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11Z"/>
                        <circle cx="12" cy="10" r="2.5"/>
                    </svg>
                </span>
                <span class="map-facade__label">Показать карту</span>
                <span class="map-facade__hint">переулок Пожарный, 3Б, Могилёв</span>
            </button>
        @endif
    </div>
</section>

@push('scripts')
    <script>
        (function () {
            var facades = document.querySelectorAll('.map-facade');
            facades.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var src = btn.getAttribute('data-map-embed');
                    if (! src) return;
                    var frame = btn.parentNode;
                    var iframe = document.createElement('iframe');
                    iframe.src = src;
                    iframe.title = 'Карта — как нас найти: HANDS, переулок Пожарный, 3Б, Могилёв';
                    iframe.loading = 'lazy';
                    iframe.setAttribute('allowfullscreen', '');
                    frame.innerHTML = '';
                    frame.appendChild(iframe);
                }, { once: true });
            });
        })();
    </script>
@endpush
