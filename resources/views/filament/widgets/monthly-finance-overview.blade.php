@php($s = $this->summary())
@php($prev = $this->previousSummary())
@php($panel = 'background:rgba(120,120,120,.08); border:1px solid rgba(120,120,120,.22); border-radius:.75rem;')
@php($rowBorder = 'border-top:1px solid rgba(120,120,120,.18);')

<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Переключатель месяца --}}
        <div style="display:flex; align-items:center; flex-wrap:wrap; gap:.6rem 1rem; margin-bottom:1.25rem;">
            <div style="display:inline-flex; gap:.4rem;">
                <x-filament::button size="sm" color="gray" wire:click="prevMonth">‹ Предыдущий</x-filament::button>
                <x-filament::button size="sm" :color="$this->isCurrentMonth() ? 'primary' : 'gray'" wire:click="currentMonth">Текущий</x-filament::button>
                <x-filament::button size="sm" color="gray" wire:click="nextMonth">Следующий ›</x-filament::button>
            </div>
            <div style="font-size:1.2rem; font-weight:700; letter-spacing:.01em;">{{ $s->label }}</div>
        </div>

        {{-- Три ключевые цифры --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(13rem, 1fr)); gap:1rem;">
            {{-- Прибыль --}}
            <div style="{{ $panel }} padding:1rem 1.1rem; position:relative; overflow:hidden;">
                <div style="position:absolute; inset:0 auto 0 0; width:4px; background:{{ $s->profit >= 0 ? '#22c55e' : '#ef4444' }};"></div>
                <div style="font-size:.78rem; opacity:.6; text-transform:uppercase; letter-spacing:.04em;">Прибыль за месяц</div>
                <div style="font-size:2rem; font-weight:800; line-height:1.15; margin-top:.3rem; color:{{ $s->profit >= 0 ? '#22c55e' : '#ef4444' }};">
                    {{ $this->money($s->profit) }} р
                </div>
                <div style="font-size:.78rem; opacity:.55; margin-top:.4rem;">выручка − зарплата − расходы</div>
                @php($dp = $this->delta($s->profit, $prev->profit))
                <div style="font-size:.78rem; margin-top:.35rem; color:{{ $dp['up'] ? '#22c55e' : '#ef4444' }};">
                    {{ $dp['up'] ? '▲' : '▼' }}
                    @if($dp['percent'] !== null){{ ($dp['up'] ? '+' : '').$dp['percent'] }}%@else{{ ($dp['up'] ? '+' : '').$this->money($dp['diff']) }} р@endif
                    <span style="opacity:.6;">к {{ $prev->label }}</span>
                </div>
            </div>

            {{-- Выручка --}}
            <div style="{{ $panel }} padding:1rem 1.1rem;">
                <div style="font-size:.78rem; opacity:.6; text-transform:uppercase; letter-spacing:.04em;">Выручка (все деньги)</div>
                <div style="font-size:1.6rem; font-weight:700; margin-top:.3rem;">{{ $this->money($s->revenue) }} р</div>
                <div style="font-size:.78rem; opacity:.55; margin-top:.4rem;">визиты по кассе {{ $this->money($s->revenue_visits) }} + сертификаты {{ $this->money($s->revenue_certs) }}</div>
                @php($dr = $this->delta($s->revenue, $prev->revenue))
                <div style="font-size:.78rem; margin-top:.35rem; color:{{ $dr['up'] ? '#22c55e' : '#ef4444' }};">
                    {{ $dr['up'] ? '▲' : '▼' }}
                    @if($dr['percent'] !== null){{ ($dr['up'] ? '+' : '').$dr['percent'] }}%@else{{ ($dr['up'] ? '+' : '').$this->money($dr['diff']) }} р@endif
                    <span style="opacity:.6;">к {{ $prev->label }}</span>
                </div>
            </div>

            {{-- Все траты --}}
            <div style="{{ $panel }} padding:1rem 1.1rem;">
                <div style="font-size:.78rem; opacity:.6; text-transform:uppercase; letter-spacing:.04em;">Все траты за месяц</div>
                <div style="font-size:1.6rem; font-weight:700; margin-top:.3rem;">{{ $this->money($s->costs_total) }} р</div>
                <div style="font-size:.78rem; opacity:.55; margin-top:.4rem;">зарплата {{ $this->money($s->salary_total) }} + постоянные {{ $this->money($s->fixed_total) }}</div>
            </div>
        </div>

        {{-- Две колонки: зарплата мастеров и постоянные расходы --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(18rem, 1fr)); gap:1rem; margin-top:1rem;">
            {{-- Зарплата мастеров --}}
            <div style="{{ $panel }} padding:.9rem 1.1rem;">
                <div style="font-size:.85rem; font-weight:700; margin-bottom:.5rem;">Зарплата мастеров <span style="opacity:.5; font-weight:400;">≈½ наработанного, с налогами</span></div>
                @forelse($s->masters as $m)
                    <div style="display:flex; justify-content:space-between; align-items:baseline; gap:1rem; font-size:.875rem; padding:.4rem 0; {{ $rowBorder }}">
                        <span>{{ $m->name }}@unless($m->active) <span style="opacity:.45;">(ушёл)</span>@endunless</span>
                        <span style="white-space:nowrap; text-align:right;">
                            <span style="opacity:.5; font-size:.8rem;">наработал {{ $this->money($m->earned) }}</span>
                            <b style="margin-left:.35rem;">{{ $this->money($m->cost) }} р</b>
                        </span>
                    </div>
                @empty
                    <div style="font-size:.85rem; opacity:.55; padding:.3rem 0;">Нет визитов за месяц.</div>
                @endforelse
                @if($s->masters->isNotEmpty())
                    <div style="display:flex; justify-content:space-between; gap:1rem; font-size:.9rem; padding:.55rem 0 .1rem; border-top:2px solid rgba(120,120,120,.3); font-weight:700;">
                        <span>Итого зарплата</span>
                        <span>{{ $this->money($s->salary_total) }} р</span>
                    </div>
                @endif
            </div>

            {{-- Постоянные расходы --}}
            <div style="{{ $panel }} padding:.9rem 1.1rem;">
                <div style="font-size:.85rem; font-weight:700; margin-bottom:.5rem;">Постоянные расходы</div>
                @foreach($s->fixed_lines as $line)
                    <div style="display:flex; justify-content:space-between; gap:1rem; font-size:.875rem; padding:.4rem 0; {{ $rowBorder }}">
                        <span>{{ $line['title'] }}</span>
                        <span style="white-space:nowrap;">{{ $this->money($line['amount']) }} р</span>
                    </div>
                @endforeach
                <div style="display:flex; justify-content:space-between; gap:1rem; font-size:.9rem; padding:.55rem 0 .1rem; border-top:2px solid rgba(120,120,120,.3); font-weight:700;">
                    <span>Итого постоянные</span>
                    <span>{{ $this->money($s->fixed_total) }} р</span>
                </div>
                <div style="font-size:.72rem; opacity:.5; margin-top:.5rem;">Меняются в «Настройки студии → Постоянные расходы».</div>
            </div>
        </div>
    </x-filament::section>

    {{-- Отдельный блок: проданные сертификаты + обязательства по действующим --}}
    <x-filament::section style="margin-top:1rem;">
        <div style="display:flex; align-items:baseline; flex-wrap:wrap; gap:.4rem .75rem; margin-bottom:.85rem;">
            <span style="font-size:1rem; font-weight:700;">Продано сертификатов · {{ $s->certs->count() }}</span>
            <span style="opacity:.5;">{{ $s->label }}</span>
            <span style="margin-left:auto; font-size:1.1rem; font-weight:700; color:#f59e0b;">{{ $this->money($s->revenue_certs) }} р</span>
        </div>

        {{-- Обязательства: остаток по действующим сертификатам (истёкшие исключены) --}}
        @php($outstanding = $this->outstandingCerts())
        <div style="{{ $panel }} display:flex; justify-content:space-between; align-items:baseline; gap:1rem; padding:.6rem .85rem; margin-bottom:.85rem;">
            <span style="font-size:.85rem;">
                <b>Не отхожено — наши обязательства</b>
                <span style="opacity:.55;"> · по всем действующим сертификатам, за всё время</span>
            </span>
            <span style="font-size:1.05rem; font-weight:700; white-space:nowrap; color:#f59e0b;">{{ $this->money($outstanding) }} р</span>
        </div>

        @if($s->certs->isNotEmpty())
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(9.5rem, 1fr)); gap:.5rem;">
                @foreach($s->certs as $cert)
                    <a href="{{ $this->certUrl($cert) }}"
                       style="{{ $panel }} display:flex; justify-content:space-between; align-items:baseline; gap:.5rem; padding:.45rem .65rem; font-size:.82rem; text-decoration:none; color:inherit; transition:border-color .15s;"
                       onmouseover="this.style.borderColor='#f59e0b'" onmouseout="this.style.borderColor='rgba(120,120,120,.22)'">
                        <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            <b>№{{ $cert->number }}</b>
                            <span style="opacity:.5;">{{ $this->soldShort($cert) }}</span>
                        </span>
                        <span style="white-space:nowrap; font-weight:600;">{{ $this->money((float) $cert->initial_amount) }}</span>
                    </a>
                @endforeach
            </div>
            <div style="font-size:.72rem; opacity:.5; margin-top:.7rem;">Деньги от продажи сертификатов входят в выручку месяца. Чтобы не задваивать, визиты, оплаченные этими сертификатами, в кассу не идут — только в зарплату мастера.</div>
        @else
            <div style="font-size:.85rem; opacity:.55;">За месяц не продано.</div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
