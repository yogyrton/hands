@if($cabinets->isNotEmpty())
    <section id="cabinets" class="section section--dark cabinets">
        <div class="cabinets__head">
            <span class="eyebrow eyebrow--gold">Атмосфера студии</span>
            <h2>Наши кабинеты</h2>
        </div>

        <div class="grid-cabinets">
            @foreach($cabinets as $cabinet)
                @php($photos = $cabinet->photoUrls())
                <article class="cabinet">
                    <div class="cabinet__frame @if(empty($photos)) ph @endif" @if(count($photos) > 1) data-cab @endif>
                        @foreach($photos as $i => $url)
                            <img src="{{ $url }}"
                                 alt="Кабинет «{{ $cabinet->name }}» — массажная студия HANDS в Могилёве"
                                 class="cabinet__photo @if($i === 0) is-active @endif"
                                 data-cab-slide loading="lazy" decoding="async">
                        @endforeach

                        @if(count($photos) > 1)
                            <div class="cabinet__dots" data-cab-dots>
                                @foreach($photos as $i => $url)
                                    <button type="button" class="cabinet__dot @if($i === 0) is-active @endif"
                                            data-cab-dot="{{ $i }}" aria-label="Показать фото {{ $i + 1 }}"></button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="cabinet__body">
                        <h3>{{ $cabinet->name }}</h3>
                        @if($cabinet->description)
                            <p>{{ $cabinet->description }}</p>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    @push('scripts')
        <script>
            (function () {
                document.querySelectorAll('[data-cab]').forEach(function (frame) {
                    var slides = frame.querySelectorAll('[data-cab-slide]');
                    var dots = frame.querySelectorAll('[data-cab-dot]');
                    if (slides.length < 2) return;
                    var idx = 0;
                    function show(i) {
                        idx = (i + slides.length) % slides.length;
                        slides.forEach(function (s, k) { s.classList.toggle('is-active', k === idx); });
                        dots.forEach(function (d, k) { d.classList.toggle('is-active', k === idx); });
                    }
                    dots.forEach(function (d, k) { d.addEventListener('click', function () { show(k); }); });
                    setInterval(function () { show(idx + 1); }, 10000);
                });
            })();
        </script>
    @endpush
@endif
