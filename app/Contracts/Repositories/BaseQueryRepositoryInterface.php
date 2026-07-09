<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

/**
 * @template TModel of Model
 */
interface BaseQueryRepositoryInterface
{
    /**
     * @return Collection<TModel>
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * @return TModel|null
     */
    public function find(int|string $id, array $with = []): ?Model;

    /**
     * @return TModel
     */
    public function create(Data $data): Model;

    public function update(int|string $id, Data $data): bool;

    public function delete(int|string $id): bool;

    /**
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(int $perPage = 10, ?int $page = null): LengthAwarePaginator;
}
