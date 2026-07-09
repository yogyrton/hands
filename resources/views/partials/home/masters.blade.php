<section id="masters" class="section">
    <div class="masters-head">
        <span class="eyebrow">Наши мастера</span>
        <h2>Те, кому доверяют тело</h2>
    </div>
    <div class="grid-masters">
        @foreach($masters as $master)
            @php($img = $master->mainUrl())
            <a href="{{ route('masters.show', $master->slug) }}" class="card-master">
                <div class="card-master__photo @if(! $img) ph @endif">
                    @if($img)
                        <img src="{{ $img }}" alt="{{ $master->name }} — мастер студии HANDS">
                    @endif
                </div>
                <div class="card-master__body">
                    <h3>{{ $master->name }}</h3>
                    <div class="role">{{ $master->role }}</div>
                    <p>{{ \Illuminate\Support\Str::limit($master->bio1, 130) }}</p>
                    <span class="more">Профиль и запись →</span>
                </div>
            </a>
        @endforeach
    </div>
</section>
