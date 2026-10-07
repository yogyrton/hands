@php($instagram = $studio['instagram_url'] ?? 'https://www.instagram.com/hands.mg/')
@php($giftMin = $studio['gift_min_delivery'] ?? '400 р')
<section id="gift" class="section section--panel">
    <div class="container gift">
        <div>
            <span class="eyebrow">Подарочные сертификаты</span>
            <h2>Подарите час тишины<br>и заботы</h2>
            <p class="lead">Сертификат HANDS — на определённую сумму или на количество посещений. Получатель сам выберет услугу, мастера и удобное время. Отличный подарок близким на любой повод.</p>
            <div class="gift__list">
                <div class="gift__item">
                    <span class="gift__num">1</span>
                    <div><div class="t">На сумму или на посещения</div><div class="d">Выберите номинал в рублях или количество сеансов.</div></div>
                </div>
                <div class="gift__item">
                    <span class="gift__num">2</span>
                    <div><div class="t">Бесплатная доставка по городу</div><div class="d">При заказе от {{ $giftMin }} доставим сертификат по Могилёву бесплатно.</div></div>
                </div>
                <div class="gift__item">
                    <span class="gift__num">3</span>
                    <div><div class="t">Оформление в пару сообщений</div><div class="d">Напишите нам в Instagram — подберём номинал и оформим.</div></div>
                </div>
            </div>
            <a href="{{ $instagram }}" target="_blank" rel="noopener" class="btn btn-primary">Заказать сертификат →</a>
        </div>
        <div class="giftcard-photo">
            <img src="{{ asset('images/gift-certificate.webp') }}"
                 alt="Подарочный сертификат студии массажа HANDS, Могилёв"
                 loading="lazy"
                 style="display:block; width:100%; max-width:380px; height:auto; margin:0 auto; border-radius:16px; box-shadow:0 20px 50px rgba(0,0,0,.45);">
        </div>
    </div>
</section>
