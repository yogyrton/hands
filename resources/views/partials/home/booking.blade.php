@php($yclients = $studio['yclients_main'] ?? 'https://n1865142.yclients.com')
<section id="booking" class="section section--panel">
    <div class="booking">
        <span class="eyebrow">Онлайн-запись</span>
        <h2>Запишитесь на сеанс</h2>
        <p>Оставьте имя и телефон — и переходите к выбору удобного времени в YClients. Приём только по предварительной записи.</p>
        <form id="booking-form" method="get" action="{{ $yclients }}" target="_blank" rel="noopener">
            <input name="name" required placeholder="Ваше имя" autocomplete="name">
            <input name="phone" required placeholder="Телефон" autocomplete="tel" inputmode="tel">
            <select name="service">
                @foreach($services as $service)
                    <option>{{ $service->name }}</option>
                @endforeach
                <option>Не знаю — подскажите</option>
            </select>
            <button type="submit" class="btn btn-primary">Перейти к записи в YClients →</button>
        </form>
    </div>
</section>

@push('scripts')
<script>
    document.getElementById('booking-form').addEventListener('submit', function (e) {
        e.preventDefault();
        window.open(@json($yclients), '_blank', 'noopener');
    });
</script>
@endpush
