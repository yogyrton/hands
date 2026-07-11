<section id="services" class="section">
    <div class="head-row">
        <div>
            <span class="eyebrow">Услуги и цены</span>
            <h2>Выберите свой массаж</h2>
        </div>
        <p>Сеанс — от 40 минут до полутора часов, от 50 р. Мастер работает по вашему запросу: акцент на нужные зоны и комфортная интенсивность.</p>
    </div>

    @if(! empty($promotions) && $promotions->isNotEmpty())
        <div class="promos" data-promos>
            @foreach($promotions as $i => $promo)
                <div class="promo" data-promo-slide @if($i > 0) hidden @endif>
                    <span class="promo__badge">−{{ $promo->discount_percent }}%</span>
                    <div>
                        <div class="promo__eyebrow">Акция</div>
                        <div class="promo__title">{{ $promo->title }}</div>
                        @if($promo->description)
                            <div class="promo__desc">{{ $promo->description }}</div>
                        @endif
                    </div>
                </div>
            @endforeach

            @if($promotions->count() > 1)
                <div class="promo__dots" data-promo-dots>
                    @foreach($promotions as $i => $promo)
                        <button type="button" class="promo__dot @if($i === 0) is-active @endif" data-promo-dot="{{ $i }}" aria-label="Показать акцию {{ $i + 1 }}"></button>
                    @endforeach
                </div>
            @endif
        </div>

        @if($promotions->count() > 1)
            @push('scripts')
                <script>
                    (function () {
                        var root = document.querySelector('[data-promos]');
                        if (! root) return;
                        var slides = root.querySelectorAll('[data-promo-slide]');
                        var dots = root.querySelectorAll('[data-promo-dot]');
                        var idx = 0;
                        function show(i) {
                            idx = (i + slides.length) % slides.length;
                            slides.forEach(function (s, k) { s.hidden = k !== idx; });
                            dots.forEach(function (d, k) { d.classList.toggle('is-active', k === idx); });
                        }
                        dots.forEach(function (d, k) { d.addEventListener('click', function () { show(k); }); });
                        setInterval(function () { show(idx + 1); }, 10000);
                    })();
                </script>
            @endpush
        @endif
    @endif

    <div class="grid-3">
        @foreach($services as $service)
            @php($img = $service->cardUrl())
            <a href="{{ route('services.show', $service->slug) }}" class="card-service @if(! $img) ph @endif">
                @if($img)
                    <img src="{{ $img }}" alt="{{ $service->name }} в студии HANDS" loading="lazy" decoding="async">
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
