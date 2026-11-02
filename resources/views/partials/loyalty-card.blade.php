{{-- Карта постоянного клиента: условия + обе стороны реальной карты.
     Отдельно от сертификатов: там «от 5 посещений», здесь «на сам 5-й сеанс».
     Проценты зашиты текстом. --}}
<div class="loyalty-card">
    <span class="eyebrow">Постоянным клиентам</span>
    <h2>Карта постоянного клиента</h2>
    <p class="loyalty-card__lead">−15% на 5-й сеанс и −50% на 10-й. Отмечаем каждое посещение — карту выдаём в студии.</p>

    <div class="loyalty-card__images">
        <img src="{{ asset('images/loyalty-card-front.webp') }}"
             alt="Карта постоянного клиента студии HANDS — лицевая сторона"
             width="1040" height="624" loading="lazy" decoding="async">
        <img src="{{ asset('images/loyalty-card-back.webp') }}"
             alt="Оборот карты постоянного клиента HANDS: отметки посещений, скидка на 5-й и 10-й сеанс"
             width="1040" height="624" loading="lazy" decoding="async">
    </div>

    <p class="loyalty-card__note">Не суммируется с другими акциями и скидками.</p>
</div>
