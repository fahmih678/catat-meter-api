<?php

namespace App\Repositories;

use App\Models\Area;
use App\Models\FixedFee;
use App\Models\Pam;
use App\Models\TariffGroup;
use App\Models\TariffTier;
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

    /**
     * Get PAM areas
     */
    public function getPamAreas($pamId)
    {
        return Area::select('id', 'name', 'code', 'description')
            ->withCount('customers')
            ->where('pam_id', $pamId)
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Get PAM tariff groups
     */
    public function getPamTariffGroups($pamId)
    {
        return TariffGroup::select('id', 'name', 'is_active', 'description')
            ->withCount('customers')
            ->withCount('tariffTiers')
            ->where('pam_id', $pamId)
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Get PAM tariff tiers
     */
    public function getPamTariffTiers($pamId)
    {
        return TariffTier::select('id', 'description', 'meter_min', 'meter_max', 'amount', 'is_active', 'effective_from', 'effective_to', 'tariff_group_id')
            ->with(['tariffGroup:id,name'])
            ->where('pam_id', $pamId)
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Get PAM fixed fees
     */
    public function getPamFixedFees($pamId)
    {
        return FixedFee::select('id', 'name', 'amount', 'effective_from', 'effective_to', 'is_active', 'tariff_group_id')
            ->with(['tariffGroup:id,name'])
            ->where('pam_id', $pamId)
            ->orderBy('id', 'desc')
            ->get();
    }
}
