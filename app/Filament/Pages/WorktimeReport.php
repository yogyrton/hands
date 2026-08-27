<?php

namespace App\Filament\Pages;

use App\Models\Master;
use App\Models\Visit;
use App\Support\WorktimeCalculator;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

/**
 * Учёт рабочего времени: выбор периода (по умолчанию текущий месяц) и карточки
 * мастеров с суммарным временем за этот период. Клик по мастеру открывает
 * подробную страницу с сохранённым периодом. Только для администратора.
 */
class WorktimeReport extends Page
{
    protected string $view = 'filament.pages.worktime-report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 6;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Учёт';
    }

    public static function getNavigationLabel(): string
    {
        return 'Учёт рабочего времени';
    }

    public function getTitle(): string
    {
        return 'Учёт рабочего времени';
    }

    public function mount(): void
    {
        // Период может прийти из query (например, при возврате с подробной страницы).
        $this->form->fill([
            'from' => request()->query('from') ?: now()->startOfMonth()->toDateString(),
            'until' => request()->query('until') ?: now()->toDateString(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Период')
                ->columns(2)
                ->schema([
                    DatePicker::make('from')->label('С')->live(),
                    DatePicker::make('until')->label('По')->live(),
                ]),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    private function from(): Carbon
    {
        return Carbon::parse($this->data['from'] ?? now()->startOfMonth())->startOfDay();
    }

    private function until(): Carbon
    {
        return Carbon::parse($this->data['until'] ?? now())->endOfDay();
    }

    /**
     * Мастера с посещениями за выбранный период и их суммарное время.
     *
     * @return array<int, array<string, mixed>>
     */
    public function mastersSummary(): array
    {
        $query = Visit::query()->whereBetween('performed_at', [$this->from(), $this->until()]);
        $worktime = WorktimeCalculator::perMaster($query);

        if ($worktime === []) {
            return [];
        }

        $masters = Master::query()->whereIn('id', array_keys($worktime))->get()->keyBy('id');

        $rows = [];
        foreach ($worktime as $mid => $t) {
            $master = $masters->get($mid);
            $rows[] = [
                'id' => $mid,
                'name' => $master?->name ?? 'Мастер',
                'sort' => $master?->sort_order ?? 999,
                'visits' => $t->visits,
                'massage_minutes' => $t->massage_minutes,
                'prep_minutes' => $t->prep_minutes,
                'total_minutes' => $t->total_minutes,
            ];
        }

        usort($rows, fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);

        return $rows;
    }

    /**
     * Ссылка на подробную страницу мастера с сохранением выбранного периода.
     */
    public function detailUrl(int $masterId): string
    {
        return MasterWorktime::getUrl([
            'master' => $masterId,
            'from' => $this->data['from'] ?? null,
            'until' => $this->data['until'] ?? null,
        ]);
    }

    public function hm(int $minutes): string
    {
        return WorktimeCalculator::hm($minutes);
    }
}
