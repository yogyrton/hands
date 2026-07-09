<button type="button" id="back-to-top" class="back-to-top" aria-label="Наверх" title="Наверх">↑</button>

@push('scripts')
<script>
    (function () {
        var btn = document.getElementById('back-to-top');
        if (!btn) return;
        var toggle = function () {
            btn.classList.toggle('is-visible', window.scrollY > 500);
        };
        window.addEventListener('scroll', toggle, { passive: true });
        toggle();
        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    })();
</script>
@endpush
