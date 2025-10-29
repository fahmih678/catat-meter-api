<?php

namespace App\Repositories;

use App\Models\Pam;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PamRepository extends BaseRepository
{
    protected function getModel(): Model
    {
        return new Pam();
    }

    public function getPaginate(int $perPage = 15, array $fields = ['*'], string $pageName = 'page', ?int $page = null)
    {
        return $this->model->select($fields)->paginate($perPage, ['*'], $pageName, $page);
    }

    public function findByCode(string $code): ?Pam
    {
        return $this->model->where('code', $code)->first();
    }

    public function getActiveOnly($fields = ['*']): Collection
    {
        return $this->model->select($fields)->where('is_active', true)->get();
    }

    public function getWithRelations(): Collection
    {
        return $this->model->with(['users', 'areas', 'customers', 'meters'])->get();
    }

    public function searchByName(string $name): Collection
    {
        return $this->model->where('name', 'like', "%{$name}%")->get();
    }

    public function searchPaginate(string $search, int $perPage = 15, array $fields = ['*'], string $pageName = 'page', ?int $page = null)
    {
        $query = $this->model->select($fields)
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });

        return $query->paginate($perPage, ['*'], $pageName, $page);
    }

    public function getStatistics(int $pamId): array
    {
        $pam = $this->findOrFail($pamId);

        return [
            'total_customers' => $pam->customers()->count(),
            'active_customers' => $pam->customers()->where('is_active', true)->count(),
            'total_meters' => $pam->meters()->count(),
            'active_meters' => $pam->meters()->where('is_active', true)->count(),
            'total_areas' => $pam->areas()->count(),
            'pending_bills' => $pam->bills()->where('status', 'pending')->count(),
        ];
    }
}
