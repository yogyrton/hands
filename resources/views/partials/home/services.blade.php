<section id="services" class="section">
    <div class="head-row">
        <div>
            <span class="eyebrow">Услуги и цены</span>
            <h2>Выберите свой массаж</h2>
        </div>
        <p>Каждый сеанс — от 60 минут, от 50 р. Мастер работает по вашему запросу: акцент на нужные зоны и комфортная интенсивность.</p>
    </div>

    <div class="grid-3">
        @foreach($services as $service)
            @php($img = $service->cardUrl())
            <a href="{{ route('services.show', $service->slug) }}" class="card-service @if(! $img) ph @endif">
                @if($img)
                    <img src="{{ $img }}" alt="{{ $service->name }} в студии HANDS">
                @endif
                <span class="veil"></span>
                <span class="badge">Проработка {{ $service->level }}/5</span>
                <div class="body">
                    <h3>{{ $service->name }}</h3>
                    <p>{{ $service->lead }}</p>
                    <div class="meta">
                        <span>{{ $service->duration_label }} · {{ $service->price_label }}</span>
                        <span class="more">Подробнее →</span>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</section>
