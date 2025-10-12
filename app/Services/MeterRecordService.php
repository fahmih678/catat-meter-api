<?php

namespace App\Services;

use App\Models\MeterRecord;
use App\Repositories\MeterRecordRepository;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class MeterRecordService
{
    private MeterRecordRepository $meterRecordRepository;

    public function __construct(MeterRecordRepository $meterRecordRepository)
    {
        $this->meterRecordRepository = $meterRecordRepository;
    }

    public function getAllRecords(array $filters = []): LengthAwarePaginator
    {
        return $this->meterRecordRepository->getAllWithFilters($filters);
    }

    public function getRecordById(int $id): ?MeterRecord
    {
        return $this->meterRecordRepository->find($id);
    }

    public function createRecord(array $data): MeterRecord
    {
        return DB::transaction(function () use ($data) {
            // Get previous reading if not provided
            if (!isset($data['previous_reading'])) {
                $previousRecord = $this->meterRecordRepository->getLastRecordByMeter($data['meter_id']);
                $data['previous_reading'] = $previousRecord ? $previousRecord->current_reading : 0;
            }

            // Calculate usage if not provided
            if (!isset($data['usage'])) {
                $data['usage'] = max(0, $data['current_reading'] - $data['previous_reading']);
            }

            $record = $this->meterRecordRepository->create($data);
            $this->afterCreate($record, $data);

            return $record;
        });
    }

    public function updateRecord(int $id, array $data): ?MeterRecord
    {
        return DB::transaction(function () use ($id, $data) {
            $record = $this->meterRecordRepository->find($id);

            if (!$record) {
                return null;
            }

            // Recalculate usage if readings changed
            if (isset($data['current_reading']) || isset($data['previous_reading'])) {
                $currentReading = $data['current_reading'] ?? $record->current_reading;
                $previousReading = $data['previous_reading'] ?? $record->previous_reading;
                $data['usage'] = max(0, $currentReading - $previousReading);
            }

            $oldData = $record->toArray();
            $updated = $this->meterRecordRepository->update($record, $data);

            if ($updated) {
                $record->refresh(); // Refresh to get updated data
                $this->afterUpdate($record, $data, $oldData);
                return $record;
            }

            return null;
        });
    }
    public function deleteRecord(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $record = $this->meterRecordRepository->find($id);

            if (!$record) {
                return false;
            }

            $result = $this->meterRecordRepository->delete($record);

            if ($result) {
                $this->afterDelete($record);
            }

            return $result;
        });
    }

    public function getRecordsByMeter(int $meterId, array $filters = []): LengthAwarePaginator
    {
        $filters['meter_id'] = $meterId;
        return $this->meterRecordRepository->getAllWithFilters($filters);
    }

    public function getRecordsByPeriod(int $pamId, string $period, array $filters = []): LengthAwarePaginator
    {
        $filters['pam_id'] = $pamId;
        $filters['period'] = $period;
        return $this->meterRecordRepository->getAllWithFilters($filters);
    }

    public function bulkCreateRecords(array $records): array
    {
        return DB::transaction(function () use ($records) {
            $results = [];
            $errors = [];

            foreach ($records as $index => $recordData) {
                try {
                    $result = $this->createRecord($recordData);
                    $results[] = [
                        'index' => $index,
                        'success' => true,
                        'data' => $result
                    ];
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'success' => false,
                        'error' => $e->getMessage(),
                        'data' => $recordData
                    ];
                }
            }

            return [
                'successful' => $results,
                'failed' => $errors,
                'summary' => [
                    'total' => count($records),
                    'successful_count' => count($results),
                    'failed_count' => count($errors)
                ]
            ];
        });
    }

    public function getUsageData(int $meterId, string $period = 'monthly', int $months = 12): ?array
    {
        $meter = \App\Models\Meter::find($meterId);

        if (!$meter) {
            return null;
        }

        $records = $this->meterRecordRepository->getUsageByPeriod($meterId, $period, $months);

        return [
            'meter' => $meter,
            'period' => $period,
            'months' => $months,
            'usage_data' => $records,
            'statistics' => $this->calculateUsageStatistics($records)
        ];
    }

    public function getReadingStatistics(int $pamId, ?string $period = null): array
    {
        return [
            'total_readings' => $this->meterRecordRepository->countByPam($pamId, $period),
            'meters_read' => $this->meterRecordRepository->countMetersRead($pamId, $period),
            'total_meters' => $this->meterRecordRepository->countTotalMeters($pamId),
            'average_usage' => $this->meterRecordRepository->getAverageUsage($pamId, $period),
            'total_usage' => $this->meterRecordRepository->getTotalUsage($pamId, $period),
            'reading_coverage' => $this->calculateReadingCoverage($pamId, $period)
        ];
    }

    public function getMissingReadings(int $pamId, string $period): array
    {
        return $this->meterRecordRepository->getMissingReadings($pamId, $period);
    }

    private function calculateUsageStatistics(array $records): array
    {
        if (empty($records)) {
            return [
                'average' => 0,
                'total' => 0,
                'min' => 0,
                'max' => 0,
                'trend' => 'stable'
            ];
        }

        $usages = array_column($records, 'usage');
        $total = array_sum($usages);
        $count = count($usages);

        return [
            'average' => round($total / $count, 2),
            'total' => $total,
            'min' => min($usages),
            'max' => max($usages),
            'trend' => $this->calculateTrend($usages)
        ];
    }

    private function calculateTrend(array $usages): string
    {
        if (count($usages) < 3) {
            return 'stable';
        }

        $firstHalf = array_slice($usages, 0, floor(count($usages) / 2));
        $secondHalf = array_slice($usages, ceil(count($usages) / 2));

        $firstAverage = array_sum($firstHalf) / count($firstHalf);
        $secondAverage = array_sum($secondHalf) / count($secondHalf);

        $difference = $secondAverage - $firstAverage;
        $threshold = $firstAverage * 0.1; // 10% threshold

        if ($difference > $threshold) {
            return 'increasing';
        } elseif ($difference < -$threshold) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }

    private function calculateReadingCoverage(int $pamId, ?string $period): float
    {
        $totalMeters = $this->meterRecordRepository->countTotalMeters($pamId);
        $metersRead = $this->meterRecordRepository->countMetersRead($pamId, $period);

        return $totalMeters > 0 ? round(($metersRead / $totalMeters) * 100, 2) : 0;
    }

    protected function afterCreate($model, array $data): void
    {
        // Create activity log
        ActivityLog::create([
            'pam_id' => $model->meter->customer->pam_id,
            'user_id' => Auth::id() ?? 1, // Use default user for testing
            'action' => 'create',
            'activity_type' => 'meter_reading_created',
            'description' => "Reading recorded for meter {$model->meter->meter_number}: {$model->current_reading} ({$model->usage} usage)",
            'table_name' => 'meter_readings',
            'record_id' => $model->id,
            'new_values' => $model->toArray(),
        ]);
    }

    protected function afterUpdate($model, array $data, array $oldData): void
    {
        // Log reading changes
        if (isset($data['current_reading']) && $data['current_reading'] !== $oldData['current_reading']) {
            ActivityLog::create([
                'pam_id' => $model->meter->customer->pam_id,
                'user_id' => Auth::id() ?? 1, // Use default user for testing
                'action' => 'update',
                'activity_type' => 'meter_reading_updated',
                'description' => "Reading updated for meter {$model->meter->meter_number}: {$oldData['current_reading']} → {$data['current_reading']}",
                'table_name' => 'meter_readings',
                'record_id' => $model->id,
                'old_values' => ['current_reading' => $oldData['current_reading']],
                'new_values' => ['current_reading' => $data['current_reading']],
            ]);
        }
    }

    protected function afterDelete($model): void
    {
        // Create activity log
        ActivityLog::create([
            'pam_id' => $model->meter->customer->pam_id,
            'user_id' => Auth::id() ?? 1, // Use default user for testing
            'action' => 'delete',
            'activity_type' => 'meter_reading_deleted',
            'description' => "Reading deleted for meter {$model->meter->meter_number}: {$model->current_reading}",
            'table_name' => 'meter_readings',
            'record_id' => $model->id,
            'old_values' => $model->toArray(),
        ]);
    }
}
