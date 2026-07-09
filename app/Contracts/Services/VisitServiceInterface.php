<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Data\VisitData;
use App\Models\Visit;

/**
 * @extends BaseQueryServiceInterface<Visit>
 */
interface VisitServiceInterface extends BaseQueryServiceInterface
{
    /**
     * Зарегистрировать посещение (в транзакции), при необходимости списать сертификат.
     */
    public function register(VisitData $data): Visit;

    /**
     * Удалить посещение с откатом списания по сертификату.
     */
    public function deleteWithReversal(Visit $visit): void;
}
