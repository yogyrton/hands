<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\BaseQueryRepositoryInterface;
use App\Contracts\Services\BaseQueryServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

/**
 * @template TModel of Model
 *
 * @implements BaseQueryServiceInterface<TModel>
 */
class BaseQueryService implements BaseQueryServiceInterface
{
    /**
     * @param  BaseQueryRepositoryInterface<TModel>  $repository
     */
    public function __construct(
        protected BaseQueryRepositoryInterface $repository
    ) {}

    /**
     * @return Collection<TModel>
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->repository->all($columns);
    }

    /**
     * @return Model|null
     */
    public function find(int|string $id): ?Model
    {
        return $this->repository->find($id);
    }

    /**
     * @return Model
     */
    public function create(Data $data): Model
    {
        return $this->repository->create($data);
    }

    public function update(int|string $id, Data $data): bool
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int|string $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(int $perPage = 10, ?int $page = null): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $page);
    }
}
