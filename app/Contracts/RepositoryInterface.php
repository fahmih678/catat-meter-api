<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface RepositoryInterface
{
    public function all(array $columns = ['*']): Collection;

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    public function find(int $id, array $columns = ['*']): ?Model;

    public function findOrFail(int $id, array $columns = ['*']): Model;

    public function create(array $data): Model;

    public function update(Model $model, array $data): bool;

    public function delete(Model $model): bool;

    public function forceDelete(Model $model): bool;

    public function restore(Model $model): bool;

    public function findWhere(array $criteria, array $columns = ['*']): Collection;

    public function findWhereFirst(array $criteria, array $columns = ['*']): ?Model;

    public function with(array $relations): self;

    public function whereIn(string $field, array $values): self;

    public function orderBy(string $column, string $direction = 'asc'): self;
}
