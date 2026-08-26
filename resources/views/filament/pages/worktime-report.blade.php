<x-filament-panels::page>
    {{ $this->form }}

    @php($masters = $this->byMaster())

    <x-filament::section>
        <x-slot name="heading">Учёт рабочего времени</x-slot>
        <x-slot name="description">
            По каждому мастеру за период: посещения, разбивка по услугам и длительности,
            суммарное время. К каждому массажу добавляется {{ $this->prepMinutes() }} мин на подготовку кабинета.
        </x-slot>

        @forelse($masters as $m)
            <div style="margin-bottom: 1.75rem;">
                <div style="display: flex; align-items: baseline; gap: 0.75rem; margin-bottom: 0.5rem;">
                    <div style="font-size: 1.05rem; font-weight: 600;">{{ $m['name'] }}</div>
                    <div style="font-size: 0.8rem; color: rgb(113 113 122);">{{ $m['visits'] }} посещений</div>
                </div>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                        <thead>
                            <tr style="text-align: left; color: rgb(113 113 122);">
                                <th style="padding: 0.4rem 2rem 0.4rem 0; white-space: nowrap;">Услуга</th>
                                <th style="padding: 0.4rem 2rem 0.4rem 0; white-space: nowrap;">Длительность</th>
                                <th style="padding: 0.4rem 2rem 0.4rem 0; text-align: right; white-space: nowrap;">Кол-во</th>
                                <th style="padding: 0.4rem 0; text-align: right; white-space: nowrap;">Время массажей</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($m['items'] as $item)
                                <tr style="border-top: 1px solid rgba(113, 113, 122, 0.25);">
                                    <td style="padding: 0.4rem 2rem 0.4rem 0; font-weight: 500; white-space: nowrap;">{{ $item['service'] }}</td>
                                    <td style="padding: 0.4rem 2rem 0.4rem 0; white-space: nowrap;">{{ $item['duration'] }}</td>
                                    <td style="padding: 0.4rem 2rem 0.4rem 0; text-align: right;">{{ $item['count'] }}</td>
                                    <td style="padding: 0.4rem 0; text-align: right; white-space: nowrap;">{{ $this->hm($item['minutes']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 0.6rem; display: flex; flex-wrap: wrap; gap: 1.5rem; font-size: 0.875rem;">
                    <div>
                        <span style="color: rgb(113 113 122);">Массажи:</span>
                        <span style="font-weight: 600;">{{ $this->hm($m['massage_minutes']) }}</span>
                    </div>
                    <div>
                        <span style="color: rgb(113 113 122);">Подготовка ({{ $this->prepMinutes() }} мин × {{ $m['visits'] }}):</span>
                        <span style="font-weight: 600;">{{ $this->hm($m['prep_minutes']) }}</span>
                    </div>
                    <div>
                        <span style="color: rgb(113 113 122);">Итого:</span>
                        <span style="font-weight: 700; color: rgb(217 119 6);">{{ $this->hm($m['total_minutes']) }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div style="color: rgb(113 113 122);">Нет посещений за период.</div>
        @endforelse
    </x-filament::section>
</x-filament-panels::page>
