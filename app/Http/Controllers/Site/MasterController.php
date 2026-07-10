<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Contracts\Services\MasterServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Master;
use Illuminate\Contracts\View\View;

class MasterController extends Controller
{
    public function __construct(
        protected MasterServiceInterface $masters,
    ) {}

    public function show(Master $master): View
    {
        return view('masters.show', $this->masters->showPageData($master)->all());
    }
}
