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
        $master->load('services');

        return view('masters.show', [
            'master' => $master,
        ]);
    }
}
