<?php

namespace App\Services;

use App\Models\Pam;
use App\Repositories\PamRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class PamService extends BaseService
{
    protected PamRepository $pamRepository;

    public function __construct(PamRepository $pamRepository)
    {
        parent::__construct($pamRepository);
        $this->pamRepository = $pamRepository;
    }

    public function getPaginate(int $perPage = 15, $fields = ['*'], string $pageName = 'page', ?int $page = null): LengthAwarePaginator
    {
        return $this->pamRepository->getPaginate($perPage, $fields, $pageName, $page);
    }

    public function findByCode(string $code): ?Pam
    {
        return $this->pamRepository->findByCode($code);
    }

    public function findById(int $id, array $fields = ['*']): ?Pam
    {
        return $this->repository->findOrFail($id, $fields);
    }

    public function getActiveOnly($fields = ['*']): Collection
    {
        return $this->pamRepository->getActiveOnly($fields);
    }

    public function getWithRelations(): Collection
    {
        return $this->pamRepository->getWithRelations();
    }

    public function searchByName(string $name): Collection
    {
        return $this->pamRepository->searchByName($name);
    }

    public function searchPaginate(string $search, int $perPage = 15, $fields = ['*'], string $pageName = 'page', ?int $page = null): LengthAwarePaginator
    {
        return $this->pamRepository->searchPaginate($search, $perPage, $fields, $pageName, $page);
    }

    public function getStatistics(int $pamId): array
    {
        return $this->pamRepository->getStatistics($pamId);
    }

    public function create(array $data): Pam
    {
        // Generate code if not provided
        if (empty($data['code'])) {
            $data['code'] = $this->generateUniqueCode($data['name']);
        }

        return parent::create($data);
    }

    public function activatePam(int $pamId): Pam
    {
        return $this->update($pamId, ['status' => 'active']);
    }

    public function deactivatePam(int $pamId): Pam
    {
        return $this->update($pamId, ['status' => 'inactive']);
    }

    private function generateUniqueCode(string $name): string
    {
        $baseCode = Str::upper(Str::slug($name, ''));
        $baseCode = Str::limit($baseCode, 8, '');

        $code = $baseCode;
        $counter = 1;

        while ($this->pamRepository->findByCode($code)) {
            $code = $baseCode . $counter;
            $counter++;
        }

        return $code;
    }

    protected function afterCreate($model, array $data): void
    {
        // Log activity - implement your logging mechanism here
        // Example: Log::info('PAM created', ['pam_id' => $model->id, 'user_id' => auth()->id()]);
    }

    protected function afterUpdate($model, array $data, array $oldData): void
    {
        // Log activity untuk perubahan status
        if (isset($data['status']) && $data['status'] !== $oldData['status']) {
            // Example: Log::info('PAM status changed', ['pam_id' => $model->id, 'old_status' => $oldData['status'], 'new_status' => $data['status']]);
        }
    }

    protected function beforeDelete($model): void
    {
        // Validasi sebelum delete - pastikan tidak ada customers aktif
        if ($model->customers()->where('status', 'active')->exists()) {
            throw new \Exception('Cannot delete PAM with active customers');
        }
    }

    /**
     * Get PAM areas
     */
    public function getPamAreas($pamId)
    {
        return $this->pamRepository->getPamAreas($pamId);
    }

    /**
     * Get PAM tariff groups
     */
    public function getPamTariffGroups($pamId)
    {
        return $this->pamRepository->getPamTariffGroups($pamId);
    }

    /**
     * Get PAM tariff tiers
     */
    public function getPamTariffTiers($pamId)
    {
        return $this->pamRepository->getPamTariffTiers($pamId);
    }

    /**
     * Get PAM fixed fees
     */
    public function getPamFixedFees($pamId)
    {
        return $this->pamRepository->getPamFixedFees($pamId);
    }
}
