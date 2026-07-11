@extends('layouts.app')

@section('title', $site->seo_title ?: 'Массажная студия HANDS в Могилёве — запись онлайн')
@section('meta_description', $site->seo_description ?: 'Массажная студия HANDS в Могилёве, переулок Пожарный, 3Б. Классический, спортивный, релакс, массаж спины и лица, коррекция фигуры. От 50 р. Запись онлайн.')

@section('content')
    @include('partials.home.jsonld')
    @include('partials.home.hero')
    @include('partials.home.strip')
    @include('partials.home.services')
    @include('partials.home.about')
    @include('partials.home.masters')
    @include('partials.home.gift')
    @include('partials.home.map')
    @include('partials.home.faq')
    @include('partials.home.booking')
@endsection
