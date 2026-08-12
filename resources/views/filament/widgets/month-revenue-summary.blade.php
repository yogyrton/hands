@php($s = $this->summary())

<x-filament-widgets::widget>
    <x-filament::section>
        <div style="display:flex; flex-wrap:wrap; gap:1.5rem 2.5rem; align-items:flex-start;">
            {{-- Полная стоимость (для зп) — с сертификатами --}}
            <div style="min-width:12rem;">
                <div style="font-size:.8rem; opacity:.6;">Визиты за месяц · полная стоимость (для расчёта зп мастеров)</div>
                <div style="font-size:1.875rem; font-weight:700; line-height:1.2; margin-top:.25rem;">
                    {{ $this->money($s->active->services) }} р
                </div>
                <div style="font-size:.75rem; opacity:.5; margin-top:.25rem;">массаж {{ $this->money($s->active->money) }}</div>
                <div style="font-size:.75rem; opacity:.5;">сертификаты {{ $this->money($s->active->cert) }}</div>
            </div>

            {{-- Выручка только за массаж (без сертификатов) — как в Excel --}}
            <div style="min-width:10rem;">
                <div style="font-size:.8rem; opacity:.6;">Выручка только за массаж (без сертификатов, как в Excel)</div>
                <div style="font-size:1.5rem; font-weight:600; margin-top:.25rem;">
                    {{ $this->money($s->active->money) }} р
                </div>
            </div>

            {{-- По кассе (реально) --}}
            <div style="min-width:9rem;">
                <div style="font-size:.8rem; opacity:.6;">По кассе (реально)</div>
                <div style="font-size:1.5rem; font-weight:600; margin-top:.25rem;">
                    {{ $this->money($s->active->cash) }} р
                </div>
            </div>

            {{-- Бартер / особые условия — недобор (минус) --}}
            <div style="min-width:8rem;">
                <div style="font-size:.8rem; opacity:.6;">Бартер (особые условия)</div>
                <div style="font-size:1.5rem; font-weight:600; margin-top:.25rem; color:{{ $s->active->barter > 0 ? '#f59e0b' : 'inherit' }};">
                    {{ $s->active->barter > 0 ? '−' : '' }}{{ $this->money($s->active->barter) }} р
                </div>
                <div style="font-size:.75rem; opacity:.5; margin-top:.25rem;">недобор по кассе</div>
            </div>
        </div>

        {{-- Второй подсчёт: с учётом ушедших, кто отработал в этом месяце --}}
        @if ($s->inactive->services > 0)
            <div style="margin-top:.75rem; font-size:.85rem; opacity:.6;">
                С учётом ушедших мастеров (отработали в этом месяце):
                <span style="font-weight:700; opacity:1;">{{ $this->money($s->total->services) }} р</span>
                · неактивные добавили {{ $this->money($s->inactive->services) }} р
            </div>
        @endif

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
