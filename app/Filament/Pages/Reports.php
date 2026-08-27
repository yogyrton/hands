<?php

namespace App\Filament\Pages;

use App\Models\Certificate;
use App\Models\Master;
use App\Models\Promotion;
use App\Models\Service;
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
        // Прямая оплата нал/картой.
        $cash = (float) $this->visitsQuery()->where('payment_type', 'cash')->sum('paid_amount');
        $card = (float) $this->visitsQuery()->where('payment_type', 'card')->sum('paid_amount');

        // Доплата (обычный серт с доплатой + старый серт) — живые деньги,
        // падает в нал/карту по методу доплаты.
        $surchargeTypes = ['certificate_surcharge', 'certificate_external'];
        $cash += (float) $this->visitsQuery()
            ->whereIn('payment_type', $surchargeTypes)
            ->where('surcharge_payment_type', 'cash')
            ->sum('paid_amount');
        $card += (float) $this->visitsQuery()
            ->whereIn('payment_type', $surchargeTypes)
            ->where('surcharge_payment_type', 'card')
            ->sum('paid_amount');

        return [
            'cash' => $cash,
            'card' => $card,
            'total' => $cash + $card,
            'visits' => $this->visitsQuery()->count(),
            // Посещения по сертификатам: и по нашим (в БД), и по «старым» (из Excel).
            'cert_visits' => $this->visitsQuery()
                ->where(fn (Builder $q) => $q->whereNotNull('certificate_id')
                    ->orWhereNotNull('external_certificate_number'))
                ->count(),
        ];
    }

    /**
     * Статистика по акциям за период: посещений, деньгами, сумма предоставленной скидки.
     *
     * @return array<int, array<string, mixed>>
     */
    public function byPromotion(): array
    {
        return Promotion::query()
            ->orderBy('sort_order')
            ->get()
            ->map(function (Promotion $promotion): array {
                $agg = $this->visitsQuery()
                    ->where('promotion_id', $promotion->id)
                    ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(paid_amount), 0) as paid, COALESCE(SUM(base_price - service_price), 0) as discount')
                    ->first();

                return [
                    'title' => $promotion->title,
                    'percent' => $promotion->discount_percent,
                    'count' => (int) $agg->cnt,
                    'paid' => (float) $agg->paid,
                    'discount' => (float) $agg->discount,
                ];
            })
            ->filter(fn (array $row): bool => $row['count'] > 0)
            ->values()
            ->all();
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
        // whereDate по границам: sold_at хранится со временем (00:00:00), поэтому
        // обычный whereBetween со строками-датами отбрасывал бы продажи последнего
        // дня периода. whereDate сравнивает только дату — корректно на любой СУБД.
        $query = Certificate::query()
            ->whereDate('sold_at', '>=', $this->from()->toDateString())
            ->whereDate('sold_at', '<=', $this->until()->toDateString());

        return [
            'count' => (clone $query)->count(),
            // Продано за период — общая сумма по всем сертификатам (оба типа).
            'total' => (float) (clone $query)->sum('initial_amount'),
            // Отдельно по типам.
            'visits' => (float) (clone $query)->where('type', 'visits')->sum('initial_amount'),
            'money' => (float) (clone $query)->where('type', 'money')->sum('initial_amount'),
        ];
    }

    /**
     * Спрос по услугам: сколько посещений по каждому виду услуги и длительности
     * за период. Отсортировано по убыванию количества (самые ходовые сверху).
     *
     * @return array<int, array<string, mixed>>
     */
    public function byServiceDuration(): array
    {
        $rows = $this->visitsQuery()
            ->reorder()
            ->toBase()
            ->selectRaw('service_id, duration_minutes, COUNT(*) as cnt')
            ->groupBy('service_id', 'duration_minutes')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $names = Service::query()->whereIn('id', $rows->pluck('service_id'))->pluck('name', 'id');

        return $rows
            ->map(fn (object $row): array => [
                'service' => $names[$row->service_id] ?? 'Услуга',
                'duration' => $row->duration_minutes ? $row->duration_minutes.' мин' : '—',
                'count' => (int) $row->cnt,
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }
}
