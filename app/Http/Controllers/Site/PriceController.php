<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Contracts\View\View;

class PriceController extends Controller
{
    /**
     * Страница «Цены» — весь прайс одной таблицей. Данные берутся из услуг
     * и строк прайса (service_prices), поэтому правка цены в админке сразу
     * видна на сайте. Услуги без прайса не показываем.
     */
    public function index(): View
    {
        $services = Service::query()
            ->where('is_active', true)
            ->with('prices')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Service $service): bool => $service->prices->isNotEmpty())
            ->values();

        return view('prices', ['services' => $services]);
    }
}
