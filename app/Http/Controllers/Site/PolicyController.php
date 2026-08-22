<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class PolicyController extends Controller
{
    public function privacy(): View
    {
        return view('policies.privacy');
    }

    public function cookie(): View
    {
        return view('policies.cookie');
    }

    public function certificate(): View
    {
        return view('policies.certificate');
    }
}
