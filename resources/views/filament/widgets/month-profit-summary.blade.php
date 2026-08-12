@php($s = $this->summary())

<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Верх: прибыль · в кассу · сертификаты --}}
        <div style="display:flex; flex-wrap:wrap; gap:1.5rem 2.5rem; align-items:flex-start;">
            {{-- Прибыль --}}
            <div style="min-width:11rem;">
                <div style="font-size:.8rem; opacity:.6;">Прибыль за месяц</div>
                <div style="font-size:1.875rem; font-weight:700; line-height:1.2; margin-top:.25rem; color:{{ $s->profit >= 0 ? '#22c55e' : '#ef4444' }};">
                    {{ $this->money($s->profit) }} р
                </div>
                <div style="font-size:.8rem; opacity:.6; margin-top:.35rem;">
                    по кассе (реально получено) − расходы
                    @if ($s->tax_rate > 0)
                        · после налога {{ $this->money($s->after_tax) }} р ({{ $this->taxLabel($s->tax_rate) }}%)
                    @endif
                </div>
            </div>

            {{-- Выручка студии (реальные деньги) --}}
            <div style="min-width:10rem;">
                <div style="font-size:.8rem; opacity:.6;">Выручка студии за месяц</div>
                <div style="font-size:1.5rem; font-weight:600; margin-top:.25rem;">
                    {{ $this->money($s->revenue) }} р
                </div>
                <div style="font-size:.8rem; opacity:.6; margin-top:.35rem;">
                    визиты по кассе {{ $this->money($s->revenue_visits) }} + сертификаты {{ $this->money($s->revenue_certs) }}
                </div>
                <div style="font-size:.75rem; opacity:.5; margin-top:.15rem;">
                    визиты по сертификату = 0 (деньги при продаже) · доплаты по серту учтены
                </div>
            </div>

            {{-- Сертификаты + список --}}
            <div style="min-width:15rem; flex:1;">
                <div style="font-size:.8rem; opacity:.6;">Продано сертификатов за месяц</div>
                <div style="font-size:1.5rem; font-weight:600; margin-top:.25rem;">
                    {{ $this->money($s->revenue_certs) }} р
                </div>

                @if ($s->certs->isNotEmpty())
                    <div style="margin-top:.6rem; display:flex; flex-direction:column; gap:.3rem;">
                        @foreach ($s->certs as $c)
                            <div style="display:flex; flex-wrap:wrap; gap:.4rem .6rem; align-items:baseline; font-size:.9rem;">
                                <span style="opacity:.55; min-width:3.5rem;">№{{ $c->number }}</span>
                                <span style="font-weight:600;">{{ $this->money((float) $c->initial_amount) }} р</span>
                                <span style="opacity:.55;">· {{ $this->soldDate($c) }}</span>
                                <span style="opacity:.55;">· {{ $c->type->label() }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="opacity:.55; font-size:.9rem; margin-top:.4rem;">за месяц продаж нет</div>
                @endif
            </div>
        </div>

        {{-- Низ: наработка по мастерам + расходы --}}
        <div style="margin-top:1.25rem; padding-top:.9rem; border-top:1px solid rgba(128,128,128,.2);
                    display:flex; flex-wrap:wrap; gap:1.25rem 2.5rem; align-items:flex-start; justify-content:space-between;">
            {{-- По мастерам (наработали) --}}
            <div style="min-width:14rem; flex:1;">
                <div style="font-size:.8rem; opacity:.6; margin-bottom:.4rem;">
                    Наработали мастера · полная стоимость (база зарплаты, вкл. визиты по сертификату; не касса)
                </div>
                @if ($s->masters->isNotEmpty())
                    <div style="display:flex; flex-wrap:wrap; gap:.4rem 1.5rem;">
                        @foreach ($s->masters as $m)
                            <div style="display:flex; align-items:baseline; gap:.5rem; min-width:11rem;">
                                <span>{{ $m->name }}</span>
                                <span style="opacity:.5; font-size:.8rem;">· {{ $m->count }}</span>
                                <span style="margin-left:auto; font-weight:600;">{{ $this->money($m->amount) }} р</span>
                            </div>
                        @endforeach
                    </div>
                    <div style="margin-top:.5rem; font-size:.9rem;">
                        <span style="opacity:.6;">Итого наработали:</span>
                        <span style="font-weight:700;">{{ $this->money($s->masters->sum('amount')) }} р</span>
                    </div>
                @else
                    <div style="opacity:.55; font-size:.9rem;">визитов за месяц нет</div>
                @endif
            </div>

            {{-- Расходы --}}
            <div style="min-width:9rem; text-align:right;">
                <div style="font-size:.8rem; opacity:.6;">Расходы за месяц (в журнале)</div>
                <div style="font-size:1.25rem; font-weight:600; margin-top:.15rem;">
                    {{ $this->money($s->expenses) }} р
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
