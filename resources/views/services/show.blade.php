@extends('layouts.app')

@section('title', $service->seo_title ?: $service->name . ' в Могилёве — студия HANDS')
@section('meta_description', $service->seo_description ?: $service->lead)

@php($yclients = $studio['yclients_main'] ?? 'https://n1865142.yclients.com')
@php($heroImg = $service->heroUrl())

@section('content')
    <div class="breadcrumb">
        <a href="{{ route('home') }}">Главная</a> &nbsp;·&nbsp;
        <a href="{{ route('home') }}#services">Услуги</a> &nbsp;·&nbsp;
        <span>{{ $service->name }}</span>
    </div>

    <section class="svc-hero">
        <div class="svc-hero__text">
            <div class="svc-hero__tags">
                <span class="eyebrow">Массаж</span>
                <span class="tag-pill">Проработка {{ $service->level }}/5</span>
            </div>
            <h1>{{ $service->name }}</h1>
            <p class="lead" style="margin-bottom:30px">{{ $service->lead }}</p>
            <div class="stats" style="margin-bottom:36px">
                <div><div class="stat__num">{{ $service->duration_label }}</div><div class="stat__label">длительность</div></div>
                <div><div class="stat__num">{{ $service->price_label }}</div><div class="stat__label">стоимость сеанса</div></div>
            </div>
            <div class="hero__cta">
                <a href="{{ $yclients }}" target="_blank" rel="noopener" class="btn btn-primary">Записаться онлайн →</a>
                <a href="{{ route('home') }}#masters" class="btn btn-outline">Выбрать мастера</a>
            </div>
        </div>
        <div class="svc-hero__media @if(! $heroImg) ph @endif">
            @if($heroImg)
                <img src="{{ $heroImg }}" alt="{{ $service->name }} в студии HANDS">
            @endif
        </div>
    </section>

    @if($service->includes)
        <section class="section section--tight">
            <div class="rule"><h2>Что входит</h2></div>
            <div class="includes-grid">
                @foreach($service->includes as $i => $include)
                    <div class="include">
                        <div class="include__n">{{ $include['n'] ?? $i + 1 }}</div>
                        <div>
                            <h3>{{ $include['title'] }}</h3>
                            <p>{{ $include['description'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if($service->requests)
        <section class="request-block">
            <div class="eyebrow eyebrow--gold">Работаем по вашему запросу</div>
            <h2>{{ $service->request_lead }}</h2>
            <p>Вы можете указать зоны, на которые сделать акцент, и комфортную интенсивность — всё зависит от ваших потребностей и ощущений в момент сеанса.</p>
            <div class="chips">
                @foreach($service->requests as $request)
                    <span class="chip">{{ $request }}</span>
                @endforeach
            </div>
        </section>
    @endif

    <section class="section section--tight">
        <div class="rule"><h2>Подробно об услуге</h2></div>
        <div class="detail-grid">
            <div style="display:flex;flex-direction:column;gap:34px">
                @foreach($service->details ?? [] as $detail)
                    <div class="detail">
                        <h3>{{ $detail['title'] }}</h3>
                        <p>{{ $detail['body'] }}</p>
                    </div>
                @endforeach
            </div>
            <div class="detail-side">
                <div class="eyebrow">Идеально, если хочется</div>
                <p class="ideal">{{ $service->ideal }}</p>
                <div class="box">
                    <div class="k">Только по записи</div>
                    <p>Приём ведётся по предварительной записи через YClients — выберите удобное время онлайн.</p>
                    <a href="{{ $yclients }}" target="_blank" rel="noopener" class="btn btn-primary">Записаться →</a>
                </div>
            </div>
        </div>
    </section>

    @if($service->masters->isNotEmpty())
        <section class="section section--tight">
            <div class="rule"><h2>Мастера этой услуги</h2></div>
            <div class="grid-masters">
                @foreach($service->masters as $master)
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
    @endif

    @if($others->isNotEmpty())
        <section class="section section--tight">
            <div class="rule"><h2>Другие услуги</h2></div>
            <div class="others-grid">
                @foreach($others as $other)
                    <a href="{{ route('services.show', $other->slug) }}" class="other-card">
                        <span class="lvl">Проработка {{ $other->level }}/5</span>
                        <span class="nm">{{ $other->name }}</span>
                        <span class="mt">{{ $other->duration_label }} · {{ $other->price_label }} →</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
@endsection
