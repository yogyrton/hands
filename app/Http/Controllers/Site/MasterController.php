<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Master;
use Illuminate\Contracts\View\View;

class MasterController extends Controller
{
    public function show(Master $master): View
    {
        // Только активные услуги (чтобы не вести на 404, если услугу отключили).
        $master->load(['services' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')]);

        return view('masters.show', [
            'master' => $master,
        ]);
    }
}
