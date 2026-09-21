<x-filament-panels::page>
    {{ $this->form }}

    @php($days = $this->days())
    @php($t = $this->totals())
    @php($mt = $this->moneyTotals())
    @php($breakdown = $this->breakdown())

    <x-filament::section>
        <x-slot name="heading">По дням</x-slot>
        <x-slot name="description">
            Часы массажа + доп. часы на подготовку кабинета ({{ $this->prepMinutes() }} мин на каждый визит) = всего за день.
            Справа — деньги за день: наличные, безнал и сертификаты.
        </x-slot>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                <thead>
                    <tr style="text-align: left; color: rgb(113 113 122);">
                        <th style="padding: 0.5rem 1.5rem 0.5rem 0; white-space: nowrap;">Дата</th>
                        <th style="padding: 0.5rem 1.5rem 0.5rem 0; text-align: right; white-space: nowrap;">Сеансов</th>
                        <th style="padding: 0.5rem 1.5rem 0.5rem 0; text-align: right; white-space: nowrap;">Часы массажа</th>
                        <th style="padding: 0.5rem 1.5rem 0.5rem 0; text-align: right; white-space: nowrap;">Доп. часы</th>
                        <th style="padding: 0.5rem 1.5rem 0.5rem 0; text-align: right; white-space: nowrap;">Всего за день</th>
                        <th style="padding: 0.5rem 1.5rem 0.5rem 0; text-align: right; white-space: nowrap;">Нал</th>
                        <th style="padding: 0.5rem 1.5rem 0.5rem 0; text-align: right; white-space: nowrap;">Безнал</th>
                        <th style="padding: 0.5rem 1.5rem 0.5rem 0; text-align: right; white-space: nowrap;">Серт</th>
                        <th style="padding: 0.5rem 0; text-align: right; white-space: nowrap;">Сумма за день</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($days as $d)
                        <tr style="border-top: 1px solid rgba(113, 113, 122, 0.25);">
                            <td style="padding: 0.5rem 1.5rem 0.5rem 0; font-weight: 500; white-space: nowrap;">{{ $d['date'] }}</td>
                            <td style="padding: 0.5rem 1.5rem 0.5rem 0; text-align: right; white-space: nowrap;">{{ $d['visits'] }}</td>
                            <td style="padding: 0.5rem 1.5rem 0.5rem 0; text-align: right; white-space: nowrap;">{{ $this->hm($d['massage_minutes']) }}</td>
                            <td style="padding: 0.5rem 1.5rem 0.5rem 0; text-align: right; white-space: nowrap;">{{ $this->hm($d['prep_minutes']) }}</td>
                            <td style="padding: 0.5rem 1.5rem 0.5rem 0; text-align: right; font-weight: 600; white-space: nowrap;">{{ $this->hm($d['total_minutes']) }}</td>
                            <td style="padding: 0.5rem 1.5rem 0.5rem 0; text-align: right; white-space: nowrap;">{{ $this->money($d['cash']) }}</td>
                            <td style="padding: 0.5rem 1.5rem 0.5rem 0; text-align: right; white-space: nowrap;">{{ $this->money($d['card']) }}</td>
                            <td style="padding: 0.5rem 1.5rem 0.5rem 0; text-align: right; white-space: nowrap;">{{ $this->money($d['cert']) }}</td>
                            <td style="padding: 0.5rem 0; text-align: right; font-weight: 600; white-space: nowrap; color: rgb(22 163 74);">{{ $this->money($d['money_total']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" style="padding: 0.75rem 0; color: rgb(113 113 122);">Нет посещений за период.</td></tr>
                    @endforelse
                </tbody>
                @if($days !== [])
                    <tfoot>
                        <tr style="border-top: 2px solid rgba(113, 113, 122, 0.4);">
                            <td style="padding: 0.6rem 1.5rem 0.6rem 0; font-weight: 700;">Итого</td>
                            <td style="padding: 0.6rem 1.5rem 0.6rem 0; text-align: right; font-weight: 700; white-space: nowrap;">{{ $t->visits }}</td>
                            <td style="padding: 0.6rem 1.5rem 0.6rem 0; text-align: right; font-weight: 700; white-space: nowrap;">{{ $this->hm($t->massage_minutes) }}</td>
                            <td style="padding: 0.6rem 1.5rem 0.6rem 0; text-align: right; font-weight: 700; white-space: nowrap;">{{ $this->hm($t->prep_minutes) }}</td>
                            <td style="padding: 0.6rem 1.5rem 0.6rem 0; text-align: right; font-weight: 700; color: rgb(217 119 6); white-space: nowrap;">{{ $this->hm($t->total_minutes) }}</td>
                            <td style="padding: 0.6rem 1.5rem 0.6rem 0; text-align: right; font-weight: 700; white-space: nowrap;">{{ $this->money($mt['cash']) }}</td>
                            <td style="padding: 0.6rem 1.5rem 0.6rem 0; text-align: right; font-weight: 700; white-space: nowrap;">{{ $this->money($mt['card']) }}</td>
                            <td style="padding: 0.6rem 1.5rem 0.6rem 0; text-align: right; font-weight: 700; white-space: nowrap;">{{ $this->money($mt['cert']) }}</td>
                            <td style="padding: 0.6rem 0; text-align: right; font-weight: 700; color: rgb(22 163 74); white-space: nowrap;">{{ $this->money($mt['total']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </x-filament::section>

    @if($breakdown !== [])
        <x-filament::section>
            <x-slot name="heading">Разбивка по услугам</x-slot>
            <x-slot name="description">
                Посещения по каждому виду услуги и длительности за период.
                @if($this->anyInferred())
                    <br><span style="color: rgb(217 119 6);">≈</span> — длительность определена по цене из прайса (у посещения она не была указана).
                @endif
            </x-slot>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                    <thead>
                        <tr style="text-align: left; color: rgb(113 113 122);">
                            <th style="padding: 0.5rem 2rem 0.5rem 0; white-space: nowrap;">Услуга</th>
                            <th style="padding: 0.5rem 2rem 0.5rem 0; white-space: nowrap;">Длительность</th>
                            <th style="padding: 0.5rem 2rem 0.5rem 0; text-align: right; white-space: nowrap;">Кол-во</th>
                            <th style="padding: 0.5rem 0; text-align: right; white-space: nowrap;">Время массажей</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($breakdown as $item)
                            <tr style="border-top: 1px solid rgba(113, 113, 122, 0.25);">
                                <td style="padding: 0.5rem 2rem 0.5rem 0; font-weight: 500; white-space: nowrap;">{{ $item['service'] }}</td>
                                <td style="padding: 0.5rem 2rem 0.5rem 0; white-space: nowrap;">{{ $item['duration'] }}</td>
                                <td style="padding: 0.5rem 2rem 0.5rem 0; text-align: right;">{{ $item['count'] }}</td>
                                <td style="padding: 0.5rem 0; text-align: right; white-space: nowrap;">{{ $this->hm($item['minutes']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
