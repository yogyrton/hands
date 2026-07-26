@extends('layouts.app')

@section('title', $master->name . ' — мастер студии HANDS, Могилёв')
@section('meta_description', $master->name . ', ' . \Illuminate\Support\Str::lower($master->role) . ' студии HANDS, Могилёв. ' . \Illuminate\Support\Str::limit($master->bio1, 120))

@php($book = $master->yclients_url)
@php($mainImg = $master->mainUrl())
@php($gallery = $master->getMedia('gallery'))

@push('head')
    @include('partials.masters.jsonld')
    @include('partials.jsonld.breadcrumbs', ['items' => [
        ['name' => 'Главная', 'url' => route('home')],
        ['name' => 'Мастера', 'url' => route('home') . '#masters'],
        ['name' => $master->name, 'url' => route('masters.show', $master->slug)],
    ]])
@endpush

@section('content')
    <div class="breadcrumb">
        <a href="{{ route('home') }}">Главная</a> &nbsp;·&nbsp;
        <a href="{{ route('home') }}#masters">Мастера</a> &nbsp;·&nbsp;
        <span>{{ $master->name }}</span>
    </div>

    <section class="mst-hero">
        <div class="mst-hero__media @if(! $mainImg) ph @endif">
            @if($mainImg)
                <img src="{{ $mainImg }}" alt="{{ $master->name }} — мастер студии HANDS" fetchpriority="high" decoding="async">
            @endif
        </div>
        <div class="mst-hero__text">
            <div class="eyebrow">Мастер студии HANDS</div>
            <h1>{{ $master->name }}</h1>
            <div class="role">{{ $master->role }}</div>
            <p>{{ $master->bio1 }}</p>
            <p style="margin-bottom:34px">{{ $master->bio2 }}</p>
            <div class="stats" style="margin-bottom:36px">
                <div><div class="stat__num">{{ $master->experience_label }}</div><div class="stat__label">практики</div></div>
                <div><div class="stat__num">{{ $master->activeServices->count() }}</div><div class="stat__label">направления массажа</div></div>
            </div>
            <a href="{{ $book }}" target="_blank" rel="noopener" class="btn btn-primary" style="align-self:flex-start">Записаться к {{ $master->name_dative }} →</a>
        </div>
    </section>

    @if($master->principles)
        <section class="approach">
            <div class="approach__grid">
                <h2>Подход в работе</h2>
                <div class="principles">
                    @foreach($master->principles as $principle)
                        <div class="principle">
                            <div class="t">{{ $principle['title'] }}</div>
                            <div class="d">{{ $principle['description'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($master->activeServices->isNotEmpty())
        <section class="section section--tight">
            <div class="rule"><h2>Услуги мастера</h2></div>
            <div class="mst-services">
                @foreach($master->activeServices as $service)
                    <a href="{{ route('services.show', $service->slug) }}" class="mst-service">
                        <span class="lvl">Проработка {{ $service->level }}/5</span>
                        <span class="nm">{{ $service->name }}</span>
                        <span class="mt">{{ $service->duration_label }} · {{ $service->price_label }} · подробнее →</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if($gallery->isNotEmpty())
        <section class="section section--tight">
            <div class="rule"><h2>Фотографии</h2></div>
            <div class="gallery">
                @foreach($gallery as $photo)
                    <div class="cell"><img src="{{ $photo->hasGeneratedConversion('webp') ? $photo->getUrl('webp') : $photo->getUrl() }}" alt="Фото — {{ $master->name }}, студия HANDS" loading="lazy" decoding="async"></div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="section">
        <div class="booking-cta">
            <div>
                <div class="eyebrow">Только по записи</div>
                <h2 style="margin-top:14px">Запишитесь к {{ $master->name_dative }} онлайн</h2>
            </div>
            <a href="{{ $book }}" target="_blank" rel="noopener" class="btn btn-primary">Выбрать время →</a>
        </div>
    </section>
@endsection
