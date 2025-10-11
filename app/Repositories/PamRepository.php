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

    public function findByCode(string $code): ?Pam
    {
        return $this->model->where('code', $code)->first();
    }

    public function getActiveOnly(): Collection
    {
        return $this->model->where('status', 'active')->get();
    }

    public function getWithRelations(): Collection
    {
        return $this->model->with(['users', 'areas', 'customers', 'meters'])->get();
    }

    public function searchByName(string $name): Collection
    {
        return $this->model->where('name', 'like', "%{$name}%")->get();
    }

    public function getStatistics(int $pamId): array
    {
        $pam = $this->findOrFail($pamId);

        return [
            'total_customers' => $pam->customers()->count(),
            'active_customers' => $pam->customers()->where('status', 'active')->count(),
            'total_meters' => $pam->meters()->count(),
            'active_meters' => $pam->meters()->where('status', 'active')->count(),
            'total_areas' => $pam->areas()->count(),
            'pending_bills' => $pam->bills()->where('status', 'pending')->count(),
        ];
    }
}
