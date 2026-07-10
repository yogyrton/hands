<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\CertificateRepositoryInterface;
use App\Models\Certificate;
use Illuminate\Database\Eloquent\Builder;
use Override;

/**
 * @extends BaseQueryRepository<Certificate>
 */
class CertificateRepository extends BaseQueryRepository implements CertificateRepositoryInterface
{
    /**
     * @return Builder<Certificate>
     */
    #[Override]
    public function query(): Builder
    {
        return Certificate::query();
    }
}
