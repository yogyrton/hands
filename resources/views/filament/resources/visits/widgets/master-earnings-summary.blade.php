@php($s = $this->summary())

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            {{-- Общая сумма + количество посещений --}}
            <div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Заработано за период</div>
                <div class="mt-1 text-3xl font-bold tracking-tight text-gray-950 dark:text-white">
                    {{ $this->money($s->total) }} р
                </div>
                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $s->count }} {{ $this->visitsWord($s->count) }}
                </div>
            </div>

            {{-- Разбивка: наличные / безнал / сертификаты --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Наличными</div>
                    <div class="mt-0.5 text-lg font-semibold text-gray-950 dark:text-white">
                        {{ $this->money($s->cash) }} р
                    </div>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Безналом</div>
                    <div class="mt-0.5 text-lg font-semibold text-gray-950 dark:text-white">
                        {{ $this->money($s->card) }} р
                    </div>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Сертификатами</div>
                    <div class="mt-0.5 text-lg font-semibold text-gray-950 dark:text-white">
                        {{ $this->money($s->cert) }} р
                    </div>
                </div>
            </div>

            {{-- Доля мастера «грязными» --}}
            <div class="rounded-lg bg-primary-50 p-3 dark:bg-primary-500/10">
                <div class="text-xs text-gray-500 dark:text-gray-400">Мастеру грязными</div>

                @forelse ($s->masters as $master)
                    <div class="mt-1 flex items-center justify-between gap-3">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            {{ $master->name }} · {{ rtrim(rtrim(number_format($master->rate, 2, '.', ''), '0'), '.') }}%
                        </span>
                        <span class="text-lg font-semibold text-gray-950 dark:text-white">
                            {{ $this->money($master->earned) }} р
                        </span>
                    </div>
                @empty
                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">—</div>
                @endforelse

                @if ($s->masters->count() > 1)
                    <div class="mt-2 flex items-center justify-between gap-3 border-t border-gray-200 pt-2 dark:border-white/10">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Итого мастерам</span>
                        <span class="text-lg font-bold text-gray-950 dark:text-white">{{ $this->money($s->cut) }} р</span>
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
