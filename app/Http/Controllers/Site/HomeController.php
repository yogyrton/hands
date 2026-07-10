<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Contracts\Services\HomePageServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected HomePageServiceInterface $homePage,
    ) {}

    public function index(): View
    {
        return view('home', $this->homePage->pageData()->all());
    }
}
