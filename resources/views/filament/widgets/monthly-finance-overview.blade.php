@php($s = $this->summary())

<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Переключатель месяца --}}
        <div style="display:flex; align-items:center; flex-wrap:wrap; gap:.5rem 1rem; margin-bottom:1.25rem;">
            <div style="display:inline-flex; gap:.4rem;">
                <x-filament::button size="sm" color="gray" wire:click="prevMonth">‹ Предыдущий</x-filament::button>
                <x-filament::button size="sm" :color="$this->isCurrentMonth() ? 'primary' : 'gray'" wire:click="currentMonth">Текущий</x-filament::button>
                <x-filament::button size="sm" color="gray" wire:click="nextMonth">Следующий ›</x-filament::button>
            </div>
            <div style="font-size:1.15rem; font-weight:700;">{{ $s->label }}</div>
        </div>

        {{-- Верхняя строка: прибыль · выручка --}}
        <div style="display:flex; flex-wrap:wrap; gap:1.5rem 2.5rem; align-items:flex-start;">
            <div style="min-width:12rem;">
                <div style="font-size:.8rem; opacity:.6;">Прибыль за месяц</div>
                <div style="font-size:1.875rem; font-weight:700; line-height:1.2; margin-top:.25rem; color:{{ $s->profit >= 0 ? '#22c55e' : '#ef4444' }};">
                    {{ $this->money($s->profit) }} р
                </div>
                <div style="font-size:.8rem; opacity:.6; margin-top:.35rem;">
                    выручка − зарплата мастеров − постоянные расходы
                </div>
            </div>

            <div style="min-width:11rem;">
                <div style="font-size:.8rem; opacity:.6;">Выручка (деньги)</div>
                <div style="font-size:1.5rem; font-weight:600; margin-top:.25rem;">{{ $this->money($s->revenue) }} р</div>
                <div style="font-size:.8rem; opacity:.6; margin-top:.35rem;">
                    визиты по кассе {{ $this->money($s->revenue_visits) }} + сертификаты {{ $this->money($s->revenue_certs) }}
                </div>
            </div>

            <div style="min-width:11rem;">
                <div style="font-size:.8rem; opacity:.6;">Все траты за месяц</div>
                <div style="font-size:1.5rem; font-weight:600; margin-top:.25rem;">{{ $this->money($s->costs_total) }} р</div>
                <div style="font-size:.8rem; opacity:.6; margin-top:.35rem;">
                    зарплата {{ $this->money($s->salary_total) }} + постоянные {{ $this->money($s->fixed_total) }}
                </div>
            </div>
        </div>

        {{-- Нижняя часть: мастера · расходы · сертификаты --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(15rem, 1fr)); gap:1.5rem; margin-top:1.5rem;">
            {{-- Зарплата мастеров (≈½ наработанного) --}}
            <div>
                <div style="font-size:.85rem; font-weight:600; margin-bottom:.5rem;">Зарплата мастеров (≈½ наработанного)</div>
                @forelse($s->masters as $m)
                    <div style="display:flex; justify-content:space-between; gap:1rem; font-size:.85rem; padding:.3rem 0; border-top:1px solid rgba(113,113,122,.2);">
                        <span>{{ $m->name }}@unless($m->active) <span style="opacity:.5;">(ушёл)</span>@endunless</span>
                        <span style="white-space:nowrap;">
                            <span style="opacity:.6;">наработал {{ $this->money($m->earned) }}</span>
                            → <b>{{ $this->money($m->cost) }} р</b>
                        </span>
                    </div>
                @empty
                    <div style="font-size:.85rem; opacity:.6;">Нет визитов за месяц.</div>
                @endforelse
                @if($s->masters->isNotEmpty())
                    <div style="display:flex; justify-content:space-between; gap:1rem; font-size:.85rem; padding:.4rem 0; border-top:2px solid rgba(113,113,122,.35); font-weight:700;">
                        <span>Итого зарплата</span>
                        <span>{{ $this->money($s->salary_total) }} р</span>
                    </div>
                @endif
            </div>

            {{-- Постоянные расходы --}}
            <div>
                <div style="font-size:.85rem; font-weight:600; margin-bottom:.5rem;">Постоянные расходы</div>
                @foreach($s->fixed_lines as $line)
                    <div style="display:flex; justify-content:space-between; gap:1rem; font-size:.85rem; padding:.3rem 0; border-top:1px solid rgba(113,113,122,.2);">
                        <span>{{ $line['title'] }}</span>
                        <span style="white-space:nowrap;">{{ $this->money($line['amount']) }} р</span>
                    </div>
                @endforeach
                <div style="display:flex; justify-content:space-between; gap:1rem; font-size:.85rem; padding:.4rem 0; border-top:2px solid rgba(113,113,122,.35); font-weight:700;">
                    <span>Итого постоянные</span>
                    <span>{{ $this->money($s->fixed_total) }} р</span>
                </div>
                <div style="font-size:.75rem; opacity:.55; margin-top:.4rem;">Меняются в «Настройки студии → Постоянные расходы».</div>
            </div>

            {{-- Проданные сертификаты --}}
            <div>
                <div style="font-size:.85rem; font-weight:600; margin-bottom:.5rem;">Продано сертификатов · {{ $this->money($s->revenue_certs) }} р</div>
                @forelse($s->certs as $cert)
                    <div style="display:flex; justify-content:space-between; gap:1rem; font-size:.85rem; padding:.3rem 0; border-top:1px solid rgba(113,113,122,.2);">
                        <span>№{{ $cert->number }} <span style="opacity:.5;">{{ $this->soldDate($cert) }}</span></span>
                        <span style="white-space:nowrap;">{{ $this->money((float) $cert->initial_amount) }} р</span>
                    </div>
                @empty
                    <div style="font-size:.85rem; opacity:.6;">За месяц не продано.</div>
                @endforelse
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
