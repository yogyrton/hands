<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Contracts\Services\ServiceServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Contracts\View\View;

class ServiceController extends Controller
{
    public function show(Service $service, ServiceServiceInterface $services): View
    {
        // Только активные мастера, которые оказывают эту услугу.
        $service->load(['masters' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')]);

        $others = $services->activeOrdered()
            ->reject(fn (Service $item): bool => $item->id === $service->id)
            ->values();

        return view('services.show', [
            'service' => $service,
            'others' => $others,
        ]);
    }
}
