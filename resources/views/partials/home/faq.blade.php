<section id="faq" class="section">
    <div class="faq">
        <div class="faq__head">
            <span class="eyebrow">Вопросы и ответы</span>
            <h2>Частые вопросы</h2>
        </div>
        @foreach($faqs as $faq)
            <details @if($loop->first) open @endif>
                <summary>{{ $faq->question }}<span class="mark">+</span></summary>
                <p>{{ $faq->answer }}</p>
            </details>
        @endforeach
    </div>
</section>
