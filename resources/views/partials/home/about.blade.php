@php($aboutImg = $site->aboutUrl())
<section id="about" class="about">
    <div class="about__media @if(! $aboutImg) ph @endif">
        @if($aboutImg)
            <img src="{{ $aboutImg }}" alt="Массажная студия HANDS в Могилёве">
        @endif
    </div>
    <div class="about__text">
        <span class="eyebrow">О студии</span>
        <h2>Место, где тело<br>наконец выдыхает</h2>
        <p>HANDS — камерная массажная студия в Могилёве. Тёплый свет, тишина и мастера, которые слушают тело. Мы не про поток — мы про то, чтобы после сеанса вам хотелось идти чуть медленнее и дышать чуть глубже.</p>
        <p>Работаем по вашему запросу: снять напряжение после рабочего дня, восстановиться после нагрузки или просто получить удовольствие.</p>
        <div class="mini-points">
            <div><div class="t">Индивидуально</div><div class="d">акценты и интенсивность — под вас</div></div>
            <div><div class="t">По записи</div><div class="d">только ваше время, без спешки</div></div>
        </div>
    </div>
</section>
