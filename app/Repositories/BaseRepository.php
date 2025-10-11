<?php

namespace App\Repositories;

use App\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;
    protected Builder $query;

    public function __construct()
    {
        $this->model = $this->getModel();
        $this->query = $this->model->newQuery();
    }

    abstract protected function getModel(): Model;

    public function all(array $columns = ['*']): Collection
    {
        return $this->query->get($columns);
    }

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->query->paginate($perPage, $columns);
    }

    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->query->find($id, $columns);
    }

    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        return $this->query->findOrFail($id, $columns);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    public function forceDelete(Model $model): bool
    {
        return $model->forceDelete();
    }

    public function restore(Model $model): bool
    {
        return $model->restore();
    }

    public function findWhere(array $criteria, array $columns = ['*']): Collection
    {
        return $this->query->where($criteria)->get($columns);
    }

    public function findWhereFirst(array $criteria, array $columns = ['*']): ?Model
    {
        return $this->query->where($criteria)->first($columns);
    }

    public function with(array $relations): self
    {
        $this->query = $this->query->with($relations);
        return $this;
    }

    public function whereIn(string $field, array $values): self
    {
        $this->query = $this->query->whereIn($field, $values);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->query = $this->query->orderBy($column, $direction);
        return $this;
    }

    public function findWithTrashed(int $id, array $columns = ['*']): ?Model
    {
        return $this->model->withTrashed()->find($id, $columns);
    }

    public function onlyTrashed(): self
    {
        $this->query = $this->query->onlyTrashed();
        return $this;
    }

    protected function resetQuery(): void
    {
        $this->query = $this->model->newQuery();
    }
}
