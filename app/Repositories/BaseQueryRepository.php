<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\BaseQueryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\LaravelData\Data;
use Throwable;

/**
 * @template TModel of Model
 *
 * @implements BaseQueryRepositoryInterface<TModel>
 */
abstract class BaseQueryRepository implements BaseQueryRepositoryInterface
{
    /**
     * @return Builder<TModel>
     */
    abstract public function query(): Builder;

    /**
     * @return Collection<TModel>
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->query()->get($columns);
    }

    public function create(Data $data): Model
    {
        return $this->query()->create($data->toArray());
    }

    /**
     * @throws Throwable
     */
    public function update(int|string $id, Data $data): bool
    {
        $model = $this->query()
            ->where('id', $id)->first();

        throw_if(empty($model), ModelNotFoundException::class);

        return $model->update($data->toArray());
    }

    public function find(int|string $id, array $with = []): ?Model
    {
        return $this->query()->with($with)->find($id);
    }

    /**
     * @throws Throwable
     */
    public function delete(int|string $id): bool
    {
        $model = $this->query()
            ->where('id', $id)->first();

        throw_if(empty($model), ModelNotFoundException::class);

        return $model->delete();
    }

    /**
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(int $perPage = 10, ?int $page = null): LengthAwarePaginator
    {
        return $this->query()->paginate(perPage: $perPage, page: $page);
    }
}
