<x-filament-panels::page>
    {{ $this->form }}

    @php($rev = $this->revenue())
    @php($rows = $this->byMaster())
    @php($certs = $this->certsSold())

    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <x-filament::section>
            <div class="text-sm text-gray-500">Выручка (деньгами)</div>
            <div class="text-2xl font-semibold">{{ number_format($rev['total'], 2, '.', ' ') }} р</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500">Наличные</div>
            <div class="text-2xl font-semibold">{{ number_format($rev['cash'], 2, '.', ' ') }} р</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500">Карта</div>
            <div class="text-2xl font-semibold">{{ number_format($rev['card'], 2, '.', ' ') }} р</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500">Посещений</div>
            <div class="text-2xl font-semibold">{{ $rev['visits'] }}</div>
            <div class="mt-1 text-xs text-gray-400">из них по сертификату: {{ $rev['cert_visits'] }}</div>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">Зарплаты по мастерам</x-slot>
        <x-slot name="description">Суммы грязными — до вычета подоходного и прочих налогов. Зарплата = ставка % × сумма оказанных услуг.</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500">
                        <th class="py-2 pr-8">Мастер</th>
                        <th class="py-2 pr-8 text-right">Посещений</th>
                        <th class="py-2 pr-8 text-right">Сумма услуг</th>
                        <th class="py-2 pr-8 text-right">Ставка</th>
                        <th class="py-2 text-right">Зарплата (грязными)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr class="border-t border-gray-100 dark:border-white/10">
                            <td class="py-2 pr-8 font-medium">{{ $row['name'] }}</td>
                            <td class="py-2 pr-8 text-right">{{ $row['count'] }}</td>
                            <td class="py-2 pr-8 text-right">{{ number_format($row['sum'], 2, '.', ' ') }} р</td>
                            <td class="py-2 pr-8 text-right">{{ rtrim(rtrim(number_format($row['rate'], 2, '.', ''), '0'), '.') }}%</td>
                            <td class="py-2 text-right font-semibold">{{ number_format($row['salary'], 2, '.', ' ') }} р</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-3 text-gray-500">Нет посещений за период.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Сертификаты</x-slot>
        <div class="text-sm text-gray-500">Продано за период</div>
        <div class="text-2xl font-semibold">{{ $certs['count'] }} шт · на сумму {{ number_format($certs['amount'], 2, '.', ' ') }} р</div>
        <div class="mt-1 text-xs text-gray-400">Сумма — по денежным сертификатам (номинал).</div>
    </x-filament::section>
</x-filament-panels::page>
