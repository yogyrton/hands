@extends('layouts.app')

@section('title', 'Страница не найдена — HANDS, Могилёв')
@section('meta_description', 'Такой страницы на сайте массажной студии HANDS нет. Вернитесь на главную или перейдите в нужный раздел.')

@push('head')
    {{-- Несуществующие адреса не должны попадать в индекс --}}
    <meta name="robots" content="noindex, follow">
@endpush

@section('content')
    <div class="notfound">
        <div class="notfound__code" aria-hidden="true">404</div>
        <h1>Такой страницы у нас нет</h1>
        <p>Возможно, в адресе опечатка или страница переехала. Вернитесь на главную — или загляните сразу в нужный раздел.</p>

        <div class="notfound__links">
            <a href="{{ route('home') }}#services">Услуги</a>
            <a href="{{ route('prices') }}">Цены</a>
            <a href="{{ route('home') }}#masters">Мастера</a>
            <a href="{{ route('home') }}#gift">Сертификаты</a>
            <a href="{{ route('home') }}#map">Контакты</a>
        </div>

        <a href="{{ route('home') }}" class="btn btn-primary">На главную</a>
    </div>
@endsection
