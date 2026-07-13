<x-filament-panels::page>
    {{ $this->form }}

    @php($rev = $this->revenue())
    @php($rows = $this->byMaster())
    @php($promos = $this->byPromotion())
    @php($certs = $this->certsSold())

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <x-filament::section>
            <div style="font-size: 0.875rem; color: rgb(113 113 122);">Выручка (деньгами)</div>
            <div style="font-size: 1.5rem; font-weight: 600;">{{ number_format($rev['total'], 2, '.', ' ') }} р</div>
        </x-filament::section>
        <x-filament::section>
            <div style="font-size: 0.875rem; color: rgb(113 113 122);">Наличные</div>
            <div style="font-size: 1.5rem; font-weight: 600;">{{ number_format($rev['cash'], 2, '.', ' ') }} р</div>
        </x-filament::section>
        <x-filament::section>
            <div style="font-size: 0.875rem; color: rgb(113 113 122);">Карта</div>
            <div style="font-size: 1.5rem; font-weight: 600;">{{ number_format($rev['card'], 2, '.', ' ') }} р</div>
        </x-filament::section>
        <x-filament::section>
            <div style="font-size: 0.875rem; color: rgb(113 113 122);">Посещений</div>
            <div style="font-size: 1.5rem; font-weight: 600;">{{ $rev['visits'] }}</div>
            <div style="margin-top: 0.25rem; font-size: 0.75rem; color: rgb(113 113 122);">из них по сертификату: {{ $rev['cert_visits'] }}</div>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">Зарплаты по мастерам</x-slot>
        <x-slot name="description">Суммы грязными — до вычета подоходного и прочих налогов. Зарплата = ставка % × сумма оказанных услуг.</x-slot>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                <thead>
                    <tr style="text-align: left; color: rgb(113 113 122);">
                        <th style="padding: 0.5rem 2rem 0.5rem 0; white-space: nowrap;">Мастер</th>
                        <th style="padding: 0.5rem 2rem 0.5rem 0; text-align: right; white-space: nowrap;">Посещений</th>
                        <th style="padding: 0.5rem 2rem 0.5rem 0; text-align: right; white-space: nowrap;">Сумма услуг</th>
                        <th style="padding: 0.5rem 2rem 0.5rem 0; text-align: right; white-space: nowrap;">Ставка</th>
                        <th style="padding: 0.5rem 0; text-align: right; white-space: nowrap;">Зарплата (грязными)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr style="border-top: 1px solid rgba(113, 113, 122, 0.25);">
                            <td style="padding: 0.5rem 2rem 0.5rem 0; font-weight: 500; white-space: nowrap;">{{ $row['name'] }}</td>
                            <td style="padding: 0.5rem 2rem 0.5rem 0; text-align: right;">{{ $row['count'] }}</td>
                            <td style="padding: 0.5rem 2rem 0.5rem 0; text-align: right; white-space: nowrap;">{{ number_format($row['sum'], 2, '.', ' ') }} р</td>
                            <td style="padding: 0.5rem 2rem 0.5rem 0; text-align: right;">{{ rtrim(rtrim(number_format($row['rate'], 2, '.', ''), '0'), '.') }}%</td>
                            <td style="padding: 0.5rem 0; text-align: right; font-weight: 600; white-space: nowrap;">{{ number_format($row['salary'], 2, '.', ' ') }} р</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="padding: 0.75rem 0; color: rgb(113 113 122);">Нет посещений за период.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Сертификаты</x-slot>
        <div style="font-size: 0.875rem; color: rgb(113 113 122);">Продано за период</div>
        <div style="font-size: 1.5rem; font-weight: 600;">{{ $certs['count'] }} шт · на сумму {{ number_format($certs['total'], 2, '.', ' ') }} р</div>
        <div style="margin-top: 0.5rem; display: flex; gap: 2rem; flex-wrap: wrap; font-size: 0.875rem;">
            <div>
                <span style="color: rgb(113 113 122);">На посещения:</span>
                <span style="font-weight: 600;">{{ number_format($certs['visits'], 2, '.', ' ') }} р</span>
            </div>
            <div>
                <span style="color: rgb(113 113 122);">На сумму:</span>
                <span style="font-weight: 600;">{{ number_format($certs['money'], 2, '.', ' ') }} р</span>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Акции</x-slot>
        <x-slot name="description">Сколько посещений пришло по каждой акции за период, деньгами и на какую сумму предоставлена скидка.</x-slot>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                <thead>
                    <tr style="text-align: left; color: rgb(113 113 122);">
                        <th style="padding: 0.5rem 2rem 0.5rem 0; white-space: nowrap;">Акция</th>
                        <th style="padding: 0.5rem 2rem 0.5rem 0; text-align: right; white-space: nowrap;">Посещений</th>
                        <th style="padding: 0.5rem 2rem 0.5rem 0; text-align: right; white-space: nowrap;">Сумма деньгами</th>
                        <th style="padding: 0.5rem 0; text-align: right; white-space: nowrap;">Сумма предоставленной скидки</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($promos as $promo)
                        <tr style="border-top: 1px solid rgba(113, 113, 122, 0.25);">
                            <td style="padding: 0.5rem 2rem 0.5rem 0; font-weight: 500; white-space: nowrap;">{{ $promo['title'] }} · −{{ $promo['percent'] }}%</td>
                            <td style="padding: 0.5rem 2rem 0.5rem 0; text-align: right;">{{ $promo['count'] }}</td>
                            <td style="padding: 0.5rem 2rem 0.5rem 0; text-align: right; white-space: nowrap;">{{ number_format($promo['paid'], 2, '.', ' ') }} р</td>
                            <td style="padding: 0.5rem 0; text-align: right; font-weight: 600; white-space: nowrap;">{{ number_format($promo['discount'], 2, '.', ' ') }} р</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="padding: 0.75rem 0; color: rgb(113 113 122);">За период по акциям посещений не было.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
