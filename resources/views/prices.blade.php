@extends('layouts.app')

@section('title', 'Цены на массаж в Могилёве — студия HANDS')
@section('meta_description', 'Прайс массажной студии HANDS в Могилёве (переулок Пожарный, 3Б): классический, спортивный, релакс, массаж спины и лица, коррекция фигуры. От 50 р за сеанс. Запись онлайн.')

@php($yclients = $studio['yclients_main'] ?? 'https://n1865142.yclients.com')

@push('head')
    @include('partials.jsonld.breadcrumbs', ['items' => [
        ['name' => 'Главная', 'url' => route('home')],
        ['name' => 'Цены', 'url' => route('prices')],
    ]])
@endpush

@section('content')
    <div class="prices">
        @include('partials.back-link')

        <span class="eyebrow">Прайс студии</span>
        <h1>Цены на массаж в Могилёве</h1>
        <p class="prices__lead">Стоимость зависит от услуги, длительности и мастера. В каждом сеансе — время без спешки и внимание только к вам: мы принимаем по предварительной записи, чтобы этот час был полностью вашим.</p>

        <div class="prices__tiers">
            <div class="prices__tier">
                <div class="prices__tier-name">Мастер</div>
                <p class="prices__tier-note">Базовая стоимость сеанса у мастера студии.</p>
            </div>
            <div class="prices__tier">
                <div class="prices__tier-name">Про</div>
                <p class="prices__tier-note">Сеанс у мастера с расширенной квалификацией и большим опытом.</p>
            </div>
        </div>

        @if($services->isNotEmpty())
            <div class="prices__head">
                <span>Длительность</span>
                <span>Мастер</span>
                <span>Про</span>
            </div>

            @foreach($services as $service)
                <article class="price-card">
                    <h2 class="price-card__name">
                        <a href="{{ route('services.show', $service->slug) }}">{{ $service->name }}</a>
                    </h2>
                    @foreach($service->prices as $price)
                        <div class="price-row">
                            <span class="price-row__dur">{{ $price->duration_minutes }} минут</span>
                            <span class="price-row__val">
                                <span class="price-row__label">Мастер</span>{{ (int) $price->price_master }}&nbsp;<small>р</small>
                            </span>
                            <span class="price-row__val price-row__val--pro">
                                <span class="price-row__label">Про</span>{{ (int) $price->price_pro }}&nbsp;<small>р</small>
                            </span>
                        </div>
                    @endforeach
                </article>
            @endforeach
        @endif

        @include('partials.loyalty-card')

        <div class="prices__note">
            <strong>Сертификаты</strong>
            <ul>
                <li>На количество посещений: при покупке от 5 посещений — скидка 5% на весь сертификат, от 10 посещений — 10%.</li>
                <li>На сумму от 300 р — номинал увеличивается на 10% в подарок.</li>
                <li>Бесплатная доставка по Могилёву при номинале от {{ $studio['gift_min_delivery'] ?? '400 р' }}.</li>
            </ul>
        </div>

        <div class="prices__cta">
            <h2>Запишитесь на удобное время</h2>
            <p>Онлайн-запись через YClients — выбирайте мастера, услугу и время сами.</p>
            <a href="{{ $yclients }}" target="_blank" rel="noopener" class="btn btn-gold">Записаться онлайн</a>
        </div>
    </div>
@endsection
