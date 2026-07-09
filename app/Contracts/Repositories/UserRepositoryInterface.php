<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\User;

/**
 * @extends BaseQueryRepositoryInterface<User>
 */
interface UserRepositoryInterface extends BaseQueryRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function findByPhone(string $phone): ?User;
}
