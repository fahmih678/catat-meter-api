<?php

namespace App\Services;

use App\Models\Meter;
use App\Repositories\MeterRepository;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class MeterService
{
    private MeterRepository $meterRepository;

    public function __construct(MeterRepository $meterRepository)
    {
        $this->meterRepository = $meterRepository;
    }

    public function getAllMeters(array $filters = []): LengthAwarePaginator
    {
        return $this->meterRepository->getAllWithFilters($filters);
    }

    public function getMeterById(int $id): ?Meter
    {
        return $this->meterRepository->find($id);
    }

    public function createMeter(array $data): Meter
    {
        return DB::transaction(function () use ($data) {
            // Auto-generate meter number if not provided
            if (empty($data['meter_number'])) {
                $data['meter_number'] = $this->generateMeterNumber($data['customer_id']);
            }

            $meter = $this->meterRepository->create($data);
            $this->afterCreate($meter, $data);

            return $meter;
        });
    }

    public function updateMeter(int $id, array $data): ?Meter
    {
        return DB::transaction(function () use ($id, $data) {
            $meter = $this->meterRepository->find($id);

            if (!$meter) {
                return null;
            }

            $oldData = $meter->toArray();
            $updated = $this->meterRepository->update($meter, $data);

            if ($updated) {
                $meter->refresh(); // Refresh to get updated data
                $this->afterUpdate($meter, $data, $oldData);
                return $meter;
            }

            return null;
        });
    }
    public function deleteMeter(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $meter = $this->meterRepository->find($id);

            if (!$meter) {
                return false;
            }

            $result = $this->meterRepository->delete($meter);

            if ($result) {
                $this->afterDelete($meter);
            }

            return $result;
        });
    }

    public function activateMeter(int $id): ?Meter
    {
        return $this->updateMeter($id, ['status' => 'active']);
    }

    public function deactivateMeter(int $id): ?Meter
    {
        return $this->updateMeter($id, ['status' => 'inactive']);
    }

    public function restore(int $id): bool
    {
        $meter = $this->meterRepository->find($id);
        return $meter ? $this->meterRepository->restore($meter) : false;
    }

    public function getMetersByCustomer(int $customerId): array
    {
        return $this->meterRepository->findByCustomer($customerId);
    }

    public function getMetersByArea(int $areaId, array $filters = []): LengthAwarePaginator
    {
        $filters['area_id'] = $areaId;
        return $this->meterRepository->getAllWithFilters($filters);
    }

    public function searchMeters(int $pamId, array $filters = []): LengthAwarePaginator
    {
        $filters['pam_id'] = $pamId;
        return $this->meterRepository->search($filters);
    }

    public function getMeterStatistics(int $id): ?array
    {
        $meter = $this->meterRepository->find($id);

        if (!$meter) {
            return null;
        }        // Get meter statistics
        $stats = [
            'meter_info' => $meter,
            'total_readings' => $meter->meterReadings()->count(),
            'latest_reading' => $meter->meterReadings()->latest('reading_date')->first(),
            'average_monthly_usage' => $this->calculateAverageMonthlyUsage($meter),
            'last_6_months_usage' => $this->getLast6MonthsUsage($meter),
            'calibration_status' => $this->getCalibrationStatus($meter),
            'maintenance_history' => $meter->meterReadings()
                ->where('notes', 'like', '%maintenance%')
                ->orWhere('notes', 'like', '%repair%')
                ->latest('reading_date')
                ->limit(5)
                ->get()
        ];

        return $stats;
    }

    private function generateMeterNumber(int $customerId): string
    {
        // Get customer to access PAM
        $customer = \App\Models\Customer::find($customerId);
        $pamCode = $customer->pam->code ?? 'METER';

        // Get next meter number for this customer
        $lastMeter = $this->meterRepository->getLastMeterByCustomer($customerId);
        $nextNumber = $lastMeter ? (int)substr($lastMeter->meter_number, -3) + 1 : 1;

        return $pamCode . '-' . str_pad($customer->id, 4, '0', STR_PAD_LEFT) . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    private function calculateAverageMonthlyUsage(Meter $meter): float
    {
        // Get all readings for the last 12 months
        $readings = $meter->meterReadings()
            ->where('reading_date', '>=', now()->subMonths(12))
            ->orderBy('reading_date')
            ->get();

        if ($readings->count() < 2) {
            return 0;
        }

        $totalUsage = 0;
        $monthCount = 0;

        for ($i = 1; $i < $readings->count(); $i++) {
            $usage = $readings[$i]->current_reading - $readings[$i - 1]->current_reading;
            if ($usage > 0) {
                $totalUsage += $usage;
                $monthCount++;
            }
        }

        return $monthCount > 0 ? round($totalUsage / $monthCount, 2) : 0;
    }

    private function getLast6MonthsUsage(Meter $meter): array
    {
        $usage = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $startReading = $meter->meterReadings()
                ->where('reading_date', '<=', $monthStart)
                ->orderBy('reading_date', 'desc')
                ->first();

            $endReading = $meter->meterReadings()
                ->where('reading_date', '<=', $monthEnd)
                ->orderBy('reading_date', 'desc')
                ->first();

            $monthUsage = 0;
            if ($startReading && $endReading && $endReading->current_reading > $startReading->current_reading) {
                $monthUsage = $endReading->current_reading - $startReading->current_reading;
            }

            $usage[] = [
                'month' => $month->format('Y-m'),
                'usage' => $monthUsage
            ];
        }

        return $usage;
    }

    private function getCalibrationStatus(Meter $meter): array
    {
        $status = 'unknown';
        $daysUntilCalibration = null;

        if ($meter->next_calibration_date) {
            $daysUntilCalibration = now()->diffInDays($meter->next_calibration_date, false);

            if ($daysUntilCalibration < 0) {
                $status = 'overdue';
            } elseif ($daysUntilCalibration <= 30) {
                $status = 'due_soon';
            } else {
                $status = 'ok';
            }
        }

        return [
            'status' => $status,
            'days_until_calibration' => $daysUntilCalibration,
            'last_calibration_date' => $meter->last_calibration_date,
            'next_calibration_date' => $meter->next_calibration_date
        ];
    }

    protected function afterCreate($model, array $data): void
    {
        // Create activity log
        ActivityLog::create([
            'pam_id' => $model->customer->pam_id,
            'user_id' => Auth::id() ?? 1, // Use default user for testing
            'action' => 'create',
            'activity_type' => 'meter_created',
            'description' => "Meter {$model->meter_number} created for customer {$model->customer->name}",
            'table_name' => 'meters',
            'record_id' => $model->id,
            'new_values' => $model->toArray(),
        ]);
    }

    protected function afterUpdate($model, array $data, array $oldData): void
    {
        // Log status changes
        if (isset($data['status']) && $data['status'] !== $oldData['status']) {
            ActivityLog::create([
                'pam_id' => $model->customer->pam_id,
                'user_id' => Auth::id() ?? 1, // Use default user for testing
                'action' => 'update',
                'activity_type' => 'meter_status_changed',
                'description' => "Meter {$model->meter_number} status changed from {$oldData['status']} to {$data['status']}",
                'table_name' => 'meters',
                'record_id' => $model->id,
                'old_values' => ['status' => $oldData['status']],
                'new_values' => ['status' => $data['status']],
            ]);
        }
    }

    protected function afterDelete($model): void
    {
        // Create activity log
        ActivityLog::create([
            'pam_id' => $model->customer->pam_id,
            'user_id' => Auth::id() ?? 1, // Use default user for testing
            'action' => 'delete',
            'activity_type' => 'meter_deleted',
            'description' => "Meter {$model->meter_number} deleted",
            'table_name' => 'meters',
            'record_id' => $model->id,
            'old_values' => $model->toArray(),
        ]);
    }
}
