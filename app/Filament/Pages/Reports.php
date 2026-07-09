<?php

namespace App\Filament\Pages;

use App\Models\Certificate;
use App\Models\Master;
use App\Models\Visit;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class Reports extends Page
{
    protected string $view = 'filament.pages.reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 3;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return 'Учёт';
    }

    public static function getNavigationLabel(): string
    {
        return 'Отчёты';
    }

    public function getTitle(): string
    {
        return 'Отчёты';
    }

    public function mount(): void
    {
        $this->form->fill([
            'from' => now()->startOfMonth()->toDateString(),
            'until' => now()->toDateString(),
            'master_id' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Период')
                ->columns(3)
                ->schema([
                    DatePicker::make('from')->label('С')->live(),
                    DatePicker::make('until')->label('По')->live(),
                    Select::make('master_id')
                        ->label('Мастер')
                        ->placeholder('Все мастера')
                        ->options(fn () => Master::orderBy('sort_order')->pluck('name', 'id'))
                        ->live(),
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
     * @return Builder<Visit>
     */
    private function visitsQuery(): Builder
    {
        return Visit::query()
            ->whereBetween('performed_at', [$this->from(), $this->until()])
            ->when($this->data['master_id'] ?? null, fn (Builder $q, $id) => $q->where('master_id', $id));
    }

    /**
     * @return array<string, float|int>
     */
    public function revenue(): array
    {
        $rows = $this->visitsQuery()
            ->selectRaw('payment_type, SUM(paid_amount) as total')
            ->groupBy('payment_type')
            ->pluck('total', 'payment_type');

        $cash = (float) ($rows['cash'] ?? 0);
        $card = (float) ($rows['card'] ?? 0);
        $mixed = (float) ($rows['mixed'] ?? 0);

        return [
            'cash' => $cash,
            'card' => $card,
            'mixed' => $mixed,
            'total' => $cash + $card + $mixed,
            'visits' => $this->visitsQuery()->count(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function byMaster(): array
    {
        return Master::query()
            ->orderBy('sort_order')
            ->when($this->data['master_id'] ?? null, fn (Builder $q, $id) => $q->whereKey($id))
            ->get()
            ->map(function (Master $master): array {
                $agg = $this->visitsQuery()
                    ->where('master_id', $master->id)
                    ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(service_price), 0) as sum')
                    ->first();

                $sum = (float) $agg->sum;
                $rate = (float) $master->salary_rate;

                return [
                    'name' => $master->name,
                    'count' => (int) $agg->cnt,
                    'sum' => $sum,
                    'rate' => $rate,
                    'salary' => round($sum * $rate / 100, 2),
                ];
            })
            ->filter(fn (array $row): bool => $row['count'] > 0)
            ->values()
            ->all();
    }

    /**
     * @return array<string, float|int>
     */
    public function certsSold(): array
    {
        $query = Certificate::query()
            ->whereBetween('sold_at', [$this->from()->toDateString(), $this->until()->toDateString()]);

        return [
            'count' => (clone $query)->count(),
            'amount' => (float) (clone $query)->where('type', 'money')->sum('initial_amount'),
        ];
    }
}
