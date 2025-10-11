<?php

namespace App\Services;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Exception;

abstract class BaseService
{
    protected BaseRepository $repository;

    public function __construct(BaseRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(): Collection
    {
        return $this->repository->all();
    }

    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function findById(int $id): ?Model
    {
        return $this->repository->find($id);
    }

    public function findByIdOrFail(int $id): Model
    {
        return $this->repository->findOrFail($id);
    }

    public function create(array $data): Model
    {
        DB::beginTransaction();
        try {
            $model = $this->repository->create($data);
            $this->afterCreate($model, $data);
            DB::commit();
            return $model;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data): Model
    {
        DB::beginTransaction();
        try {
            $model = $this->findByIdOrFail($id);
            $oldData = $model->toArray();

            $this->repository->update($model, $data);
            $model->refresh();

            $this->afterUpdate($model, $data, $oldData);
            DB::commit();
            return $model;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        DB::beginTransaction();
        try {
            $model = $this->findByIdOrFail($id);
            $this->beforeDelete($model);

            $result = $this->repository->delete($model);

            $this->afterDelete($model);
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function restore(int $id): bool
    {
        // Find the model including soft deleted ones
        $model = $this->repository->findWithTrashed($id);
        if (!$model) {
            return false;
        }

        // Check if already active (not deleted)
        if (!$model->trashed()) {
            return false;
        }

        return $this->repository->restore($model);
    }

    // Hook methods for child classes to override
    protected function afterCreate(Model $model, array $data): void {}

    protected function afterUpdate(Model $model, array $data, array $oldData): void {}

    protected function beforeDelete(Model $model): void {}

    protected function afterDelete(Model $model): void {}
}
