<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Contracts\Services\FaqServiceInterface;
use App\Contracts\Services\MasterServiceInterface;
use App\Contracts\Services\ServiceServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(
        ServiceServiceInterface $services,
        MasterServiceInterface $masters,
        FaqServiceInterface $faqs,
    ): View {
        return view('home', [
            'services' => $services->activeOrdered(),
            'masters' => $masters->activeOrdered(),
            'faqs' => $faqs->activeOrdered(),
        ]);
    }
}
