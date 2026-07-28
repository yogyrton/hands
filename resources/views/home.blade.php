@extends('layouts.app')

@section('title', $site->seo_title ?: 'Массажная студия HANDS в Могилёве — запись онлайн')
@section('meta_description', $site->seo_description ?: 'Массажная студия HANDS в Могилёве, переулок Пожарный, 3Б. Классический, спортивный, релакс, массаж спины и лица, коррекция фигуры. От 50 р. Запись онлайн.')

@push('head')
    {{-- Предзагружаем hero только на мобильных/планшетах (≤980px), где он
         действительно LCP. На десктопе hero — боковая колонка, а LCP — текст H1,
         поэтому там preload только тормозил бы CSS/шрифт. --}}
    @if($heroImg = $site->heroUrl())
        @php($heroPreloadSrcset = $site->heroSrcset())
        {{-- imagesrcset/imagesizes — чтобы preload выбрал тот же файл, что и <img>
             (иначе на мобиле качается и крупный из preload, и мелкий из srcset). --}}
        <link rel="preload" as="image" href="{{ $heroImg }}"@if($heroPreloadSrcset) imagesrcset="{{ $heroPreloadSrcset }}" imagesizes="100vw"@endif media="(max-width: 980px)" fetchpriority="high">
    @endif
@endpush

@section('content')
    @include('partials.home.jsonld')
    @include('partials.home.faq-jsonld')
    @include('partials.home.hero')
    @include('partials.home.strip')
    @include('partials.home.services')
    @include('partials.home.about')
    @include('partials.home.masters')
    @include('partials.home.gift')
    @include('partials.home.cabinets')
    @include('partials.home.booking')
    @include('partials.home.map')
    @include('partials.home.faq')
@endsection
