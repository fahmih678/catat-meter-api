<?php

namespace App\Services;

use App\Models\Pam;
use App\Repositories\PamRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class PamService extends BaseService
{
    protected PamRepository $pamRepository;

    public function __construct(PamRepository $pamRepository)
    {
        parent::__construct($pamRepository);
        $this->pamRepository = $pamRepository;
    }

    public function findByCode(string $code): ?Pam
    {
        return $this->pamRepository->findByCode($code);
    }

    public function getActiveOnly(): Collection
    {
        return $this->pamRepository->getActiveOnly();
    }

    public function getWithRelations(): Collection
    {
        return $this->pamRepository->getWithRelations();
    }

    public function searchByName(string $name): Collection
    {
        return $this->pamRepository->searchByName($name);
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
}
