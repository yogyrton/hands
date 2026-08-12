@php($s = $this->summary())

<x-filament-widgets::widget>
    <x-filament::section>
        <div style="display:flex; flex-wrap:wrap; gap:1.5rem 2.5rem; align-items:flex-start;">
            {{-- Полная стоимость услуг (как в Excel), массаж за деньги --}}
            <div style="min-width:13rem;">
                <div style="font-size:.8rem; opacity:.6;">Визиты за месяц · полная стоимость (как в Excel, для расчёта зп мастеров)</div>
                <div style="font-size:1.875rem; font-weight:700; line-height:1.2; margin-top:.25rem;">
                    {{ $this->money($s->services) }} р
                </div>
            </div>

            {{-- Реально по кассе --}}
            <div style="min-width:9rem;">
                <div style="font-size:.8rem; opacity:.6;">По кассе (реально получено)</div>
                <div style="font-size:1.5rem; font-weight:600; margin-top:.25rem;">
                    {{ $this->money($s->cash) }} р
                </div>
            </div>

            {{-- Разница --}}
            <div style="min-width:9rem;">
                <div style="font-size:.8rem; opacity:.6;">Разница (особые условия)</div>
                <div style="font-size:1.5rem; font-weight:600; margin-top:.25rem; color:{{ $s->diff > 0 ? '#f59e0b' : 'inherit' }};">
                    {{ $s->diff > 0 ? '−' : '' }}{{ $this->money($s->diff) }} р
                </div>
            </div>
        </div>

        @if ($s->bartes->isNotEmpty())
            <div style="margin-top:1.25rem; border-top:1px solid rgba(128,128,128,.2); padding-top:.75rem;">
                <div style="font-size:.8rem; opacity:.6; margin-bottom:.5rem;">
                    Почему разница — бартеры и договорные цены:
                </div>
                <div style="display:flex; flex-direction:column; gap:.4rem;">
                    @foreach ($s->bartes as $v)
                        @php($full = (float) $v->service_price)
                        @php($paid = (float) $v->paid_amount)
                        <div style="display:flex; flex-wrap:wrap; gap:.35rem .75rem; align-items:baseline; font-size:.9rem;">
                            <span style="opacity:.55; min-width:3.5rem;">{{ $this->localTime($v) }}</span>
                            <span>{{ $v->service?->name ?? 'Услуга' }}</span>
                            <span style="opacity:.55;">· {{ $v->master?->name ?? 'Мастер' }}</span>
                            <span>· полная {{ $this->money($full) }} → касса {{ $this->money($paid) }}</span>
                            <span style="color:#f59e0b; font-weight:600;">· −{{ $this->money($full - $paid) }}</span>
                            @if ($v->discount_reason)
                                <span style="opacity:.55;">· {{ $v->discount_reason }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
