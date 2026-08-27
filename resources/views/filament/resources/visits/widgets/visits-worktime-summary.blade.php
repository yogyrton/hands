@php($s = $this->summary())

<x-filament-widgets::widget>
    <x-filament::section>
        <div style="font-size:.8rem; opacity:.6;">Рабочее время за период · {{ $s->visits }} посещений</div>
        <div style="margin-top:.35rem; display:flex; flex-wrap:wrap; gap:.35rem .6rem; align-items:baseline; font-size:.95rem;">
            <span style="opacity:.6;">Массажи</span>
            <span style="font-weight:600;">{{ $this->hm($s->massage_minutes) }}</span>
            <span style="opacity:.6;">+ подготовка ({{ $this->prepMinutes() }} мин × {{ $s->visits }})</span>
            <span style="font-weight:600;">{{ $this->hm($s->prep_minutes) }}</span>
            <span style="opacity:.6;">=</span>
            <span style="font-size:1.25rem; font-weight:700; color:#f59e0b;">{{ $this->hm($s->total_minutes) }}</span>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
