<x-filament-panels::page>
    @php($masters = $this->mastersSummary())

    <div style="font-size: 0.875rem; color: rgb(113 113 122); margin-bottom: 0.5rem;">
        За текущий месяц. Нажмите на мастера — откроется подробная страница с выбором периода и разбивкой по дням.
    </div>

    @if($masters === [])
        <x-filament::section>
            <div style="color: rgb(113 113 122);">Нет посещений за текущий месяц.</div>
        </x-filament::section>
    @else
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem;">
            @foreach($masters as $m)
                <a href="{{ $this->detailUrl($m['id']) }}" style="text-decoration: none; color: inherit;">
                    <x-filament::section style="height: 100%;">
                        <div style="display: flex; align-items: baseline; gap: 0.6rem;">
                            <div style="font-size: 1.05rem; font-weight: 600;">{{ $m['name'] }}</div>
                            <div style="font-size: 0.8rem; color: rgb(113 113 122);">{{ $m['visits'] }} посещений</div>
                        </div>
                        <div style="font-size: 1.5rem; font-weight: 700; margin-top: 0.35rem; color: rgb(217 119 6);">
                            {{ $this->hm($m['total_minutes']) }}
                        </div>
                        <div style="font-size: 0.8rem; color: rgb(113 113 122); margin-top: 0.25rem;">
                            массаж {{ $this->hm($m['massage_minutes']) }} + подгот. {{ $this->hm($m['prep_minutes']) }}
                        </div>
                        <div style="font-size: 0.8rem; color: rgb(217 119 6); margin-top: 0.5rem;">Подробнее →</div>
                    </x-filament::section>
                </a>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
