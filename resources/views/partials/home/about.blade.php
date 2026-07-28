@php($aboutImg = $site->aboutUrl())
@php($aboutSrcset = $site->aboutSrcset())
@php($yclients = $studio['yclients_main'] ?? 'https://n1865142.yclients.com')
@php($phone = $studio['phone'] ?? '')
@php($workHours = $studio['work_hours'] ?? '')
@php($address = $studio['address'] ?? 'переулок Пожарный, 3Б, Могилёв')
@php($mapsUrl = $studio['yandex_maps_url'] ?? '')
@if(! $mapsUrl)
    @php($mapsUrl = 'https://yandex.ru/maps/?text=' . urlencode($address . ', Могилёв'))
    @if(preg_match('/ll=([\d.]+)(?:%2C|,)([\d.]+)/', $studio['yandex_map_embed'] ?? '', $mm))
        @php($mapsUrl = 'https://yandex.ru/maps/?ll=' . $mm[1] . ',' . $mm[2] . '&z=17&pt=' . $mm[1] . ',' . $mm[2])
    @endif
@endif
<section id="about" class="about">
    <div class="about__media @if(! $aboutImg) ph @endif">
        @if($aboutImg)
            <img src="{{ $aboutImg }}"@if($aboutSrcset) srcset="{{ $aboutSrcset }}" sizes="(max-width: 980px) 100vw, 45vw"@endif alt="Массажная студия HANDS в Могилёве" loading="lazy" decoding="async">
        @endif
    </div>
    <div class="about__text">
        <span class="eyebrow">О студии</span>
        <h2>Место, где тело<br>наконец выдыхает</h2>
        <div class="about__grid">
            <div class="about__copy">
                <p>HANDS — камерная массажная студия в Могилёве. Тёплый свет, тишина и мастера, которые слушают тело. Мы не про поток — мы про то, чтобы после сеанса вам хотелось идти чуть медленнее и дышать чуть глубже.</p>
                <p>Работаем по вашему запросу: снять напряжение после рабочего дня, восстановиться после нагрузки или просто получить удовольствие.</p>
                <div class="mini-points">
                    <div><div class="t">Индивидуально</div><div class="d">акценты и интенсивность — под вас</div></div>
                    <div><div class="t">По записи</div><div class="d">только ваше время, без спешки</div></div>
                </div>
            </div>
            <aside class="about__card">
                <span class="about__card-eyebrow">Студия HANDS</span>
                <ul class="about__facts">
                    <li><span class="k">Адрес</span><a class="v" href="{{ $mapsUrl }}" target="_blank" rel="noopener">{{ $address }}</a></li>
                    @if($workHours)
                        <li><span class="k">Часы работы</span><span class="v">{{ $workHours }}</span></li>
                    @endif
                    @if($phone)
                        <li><span class="k">Телефон</span><a class="v" href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">{{ $phone }}</a></li>
                    @endif
                </ul>
                <a href="{{ $yclients }}" target="_blank" rel="noopener" class="btn btn-primary about__card-btn">Записаться онлайн →</a>
            </aside>
        </div>
    </div>
</section>
