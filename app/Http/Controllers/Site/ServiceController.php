<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Contracts\Services\ServiceServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Contracts\View\View;

class ServiceController extends Controller
{
    public function __construct(
        protected ServiceServiceInterface $services,
    ) {}

    public function show(Service $service): View
    {
        return view('services.show', $this->services->showPageData($service)->all());
    }
}
