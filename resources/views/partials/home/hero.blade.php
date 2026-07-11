@php($yclients = $studio['yclients_main'] ?? 'https://n1865142.yclients.com')
@php($heroImg = $site->heroUrl())
<section class="hero">
    <div class="hero__text">
        <div class="eyebrow hero__eyebrow">Массажная студия · Могилёв</div>
        <h1>Массаж в Могилёве —<br><em>ваше удовольствие</em></h1>
        <p class="hero__lead">Тишина, тёплый свет и точные руки мастера. Классический, спортивный и релакс-массаж, работа со спиной, лицом и фигурой — под ваш запрос.</p>
        <div class="hero__cta">
            <a href="{{ $yclients }}" target="_blank" rel="noopener" class="btn btn-primary">Записаться онлайн →</a>
            <a href="#services" class="btn btn-outline">Наши услуги</a>
        </div>
        <div class="stats hero__stats">
            <div><div class="stat__num">{{ $services->count() ?: 6 }}</div><div class="stat__label">видов массажа</div></div>
            <div><div class="stat__num">от 60′</div><div class="stat__label">длительность сеанса</div></div>
            <div><div class="stat__num">{{ $masters->count() ?: 3 }}</div><div class="stat__label">мастера студии</div></div>
        </div>
    </div>
    <div class="hero__media @if(! $heroImg) ph @endif">
        @if($heroImg)
            <img src="{{ $heroImg }}" alt="Массаж в студии HANDS в Могилёве">
        @endif
    </div>
</section>
