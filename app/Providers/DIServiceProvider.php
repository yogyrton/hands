<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\CertificateRepositoryInterface;
use App\Contracts\Repositories\FaqRepositoryInterface;
use App\Contracts\Repositories\MasterRepositoryInterface;
use App\Contracts\Repositories\PromotionRepositoryInterface;
use App\Contracts\Repositories\ServiceRepositoryInterface;
use App\Contracts\Repositories\VisitRepositoryInterface;
use App\Contracts\Services\CertificateServiceInterface;
use App\Contracts\Services\FaqServiceInterface;
use App\Contracts\Services\HomePageServiceInterface;
use App\Contracts\Services\MasterServiceInterface;
use App\Contracts\Services\PromotionServiceInterface;
use App\Contracts\Services\ServiceServiceInterface;
use App\Contracts\Services\VisitServiceInterface;
use App\Models\Setting;
use App\Repositories\CertificateRepository;
use App\Repositories\FaqRepository;
use App\Repositories\MasterRepository;
use App\Repositories\PromotionRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\VisitRepository;
use App\Services\CertificateService;
use App\Services\FaqService;
use App\Services\HomePageService;
use App\Services\MasterService;
use App\Services\PromotionService;
use App\Services\ServiceService;
use App\Services\VisitService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Override;
use Throwable;

class DIServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->registerRepositories();
        $this->registerServices();
    }

    public function boot(): void
    {
        // Настройки студии доступны во всех Blade-шаблонах как $studio['key'].
        View::composer('*', static function ($view): void {
            try {
                $view->with('studio', Setting::allKeyed());
            } catch (Throwable) {
                $view->with('studio', []);
            }
        });
    }

    private function registerRepositories(): void
    {
        $this->app->bind(ServiceRepositoryInterface::class, ServiceRepository::class);
        $this->app->bind(MasterRepositoryInterface::class, MasterRepository::class);
        $this->app->bind(FaqRepositoryInterface::class, FaqRepository::class);
        $this->app->bind(PromotionRepositoryInterface::class, PromotionRepository::class);
        $this->app->bind(CertificateRepositoryInterface::class, CertificateRepository::class);
        $this->app->bind(VisitRepositoryInterface::class, VisitRepository::class);
    }

    private function registerServices(): void
    {
        $this->app->bind(ServiceServiceInterface::class, ServiceService::class);
        $this->app->bind(MasterServiceInterface::class, MasterService::class);
        $this->app->bind(FaqServiceInterface::class, FaqService::class);
        $this->app->bind(HomePageServiceInterface::class, HomePageService::class);
        $this->app->bind(PromotionServiceInterface::class, PromotionService::class);
        $this->app->bind(CertificateServiceInterface::class, CertificateService::class);
        $this->app->bind(VisitServiceInterface::class, VisitService::class);
    }
}
