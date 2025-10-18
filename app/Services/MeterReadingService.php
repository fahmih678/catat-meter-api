<?php

namespace App\Services;

use App\Models\MeterReading;
use App\Repositories\MeterReadingRepository;
use App\Models\ActivityLog;
use App\Models\Bill;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class MeterReadingService
{
    private MeterReadingRepository $meterReadingRepository;

    public function __construct(MeterReadingRepository $meterReadingRepository)
    {
        $this->meterReadingRepository = $meterReadingRepository;
    }

    public function getAllRecords(array $filters = []): LengthAwarePaginator
    {
        return $this->meterReadingRepository->getAllWithFilters($filters);
    }

    public function getRecordById(int $id): ?MeterReading
    {
        return $this->meterReadingRepository->find($id);
    }

    public function createRecord(array $data): MeterReading
    {
        return DB::transaction(function () use ($data) {
            // Get previous reading if not provided
            if (!isset($data['previous_reading'])) {
                $previousRecord = $this->meterReadingRepository->getLastRecordByMeter($data['meter_id']);
                $data['previous_reading'] = $previousRecord ? $previousRecord->current_reading : 0;
            }

            // Calculate usage if not provided
            if (!isset($data['volume_usage'])) {
                $data['volume_usage'] = max(0, $data['current_reading'] - $data['previous_reading']);
            }

            $record = $this->meterReadingRepository->create($data);
            $this->afterCreate($record, $data);

            return $record;
        });
    }

    public function updateRecord(int $id, array $data): ?MeterReading
    {
        return DB::transaction(function () use ($id, $data) {
            $record = $this->meterReadingRepository->find($id);

            if (!$record) {
                return null;
            }

            // Recalculate usage if readings changed
            if (isset($data['current_reading']) || isset($data['previous_reading'])) {
                $currentReading = $data['current_reading'] ?? $record->current_reading;
                $previousReading = $data['previous_reading'] ?? $record->previous_reading;
                $data['volume_usage'] = max(0, $currentReading - $previousReading);
            }

            $oldData = $record->toArray();
            $updated = $this->meterReadingRepository->update($record, $data);

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
            $record = $this->meterReadingRepository->find($id);

            if (!$record) {
                return false;
            }

            $result = $this->meterReadingRepository->delete($record);

            if ($result) {
                $this->afterDelete($record);
            }

            return $result;
        });
    }

    public function getRecordsByMeter(int $meterId, array $filters = []): LengthAwarePaginator
    {
        $filters['meter_id'] = $meterId;
        return $this->meterReadingRepository->getAllWithFilters($filters);
    }

    public function getRecordsByPeriod(int $pamId, string $period, array $filters = []): LengthAwarePaginator
    {
        $filters['pam_id'] = $pamId;
        $filters['period'] = $period;
        return $this->meterReadingRepository->getAllWithFilters($filters);
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

        $records = $this->meterReadingRepository->getUsageByPeriod($meterId, $period, $months);

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
            'total_readings' => $this->meterReadingRepository->countByPam($pamId, $period),
            'meters_read' => $this->meterReadingRepository->countMetersRead($pamId, $period),
            'total_meters' => $this->meterReadingRepository->countTotalMeters($pamId),
            'average_usage' => $this->meterReadingRepository->getAverageUsage($pamId, $period),
            'total_usage' => $this->meterReadingRepository->getTotalUsage($pamId, $period),
            'reading_coverage' => $this->calculateReadingCoverage($pamId, $period)
        ];
    }

    public function getMissingReadings(int $pamId, string $period): array
    {
        return $this->meterReadingRepository->getMissingReadings($pamId, $period);
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

        $usages = array_column($records, 'volume_usage');
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
        $totalMeters = $this->meterReadingRepository->countTotalMeters($pamId);
        $metersRead = $this->meterReadingRepository->countMetersRead($pamId, $period);

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

    /**
     * Submit meter reading from draft to pending and create billing
     *
     * @param int $meterReadingId
     * @param array $requestData
     * @return array|null
     * @throws \Exception
     */
    public function submitMeterReadingToPending(int $meterReadingId, array $requestData = []): ?array
    {
        return DB::transaction(function () use ($meterReadingId, $requestData) {
            // Find meter reading with required relationships
            $meterReading = $this->meterReadingRepository->find($meterReadingId);
            $registeredMonth = $meterReading->registeredMonth;

            if (!$meterReading) {
                throw new \Exception('Meter reading tidak ditemukan.');
            }

            // Check current status
            if ($meterReading->status !== 'draft') {
                throw new \Exception('Hanya meter reading dengan status draft yang dapat disubmit.');
            }

            // Load meter and customer relationships if not loaded
            $meterReading->load(['meter.customer.tariffGroup.tariffTiers', 'meter.customer.tariffGroup.fixedFees', 'meter.customer.pam']);

            // Validate meter reading has all required data
            if (!$meterReading->meter || !$meterReading->meter->customer) {
                throw new \Exception('Data meter reading tidak valid: missing meter atau customer data.');
            }

            $customer = $meterReading->meter->customer;
            $tariffGroup = $customer->tariffGroup;

            // Update meter reading status to pending
            $oldData = $meterReading->toArray();
            $this->meterReadingRepository->update($meterReading, [
                'status' => 'pending'
            ]);

            // Create comprehensive tariff snapshot
            $tariffSnapshot = [
                'tariff_name' => $tariffGroup ? $tariffGroup->name : null,
                'created_at' => now()->toISOString(),
                'tariff_tiers' => [],
                'fixed_fees' => [],
                'total_fixed_fees' => 0,
            ];

            // Add tariff tiers if available
            if ($tariffGroup && $tariffGroup->relationLoaded('tariffTiers') && $meterReading->volume_usage !== null) {
                // Filter active tiers (status = 'active' and within effective dates)
                $activeTiers = $tariffGroup->tariffTiers->filter(function ($tier) use ($meterReading) {
                    $isActive = $tier->is_active;
                    $isEffective = (!$tier->effective_to || $tier->effective_to >= now()->toDateString())
                        && ($tier->effective_from <= now()->toDateString());
                    $isTierInRange = ($tier->meter_min <= $meterReading->volume_usage) && ($meterReading->volume_usage <= $tier->meter_max);
                    return $isActive && $isEffective && $isTierInRange;
                });

                // Get only the latest/newest active tier (limit to 1)
                if ($activeTiers->count() > 0) {
                    // Sort by effective_from desc, then by id desc to get the most recent tier
                    $latestTier = $activeTiers->sortByDesc(function ($tier) {
                        return [$tier->effective_from, $tier->id];
                    })->first();

                    $tariffSnapshot['tariff_tiers'] =
                        [
                            'range' => $latestTier->meter_min . ' - ' . $latestTier->meter_max,
                            'amount' => $latestTier->amount,
                        ];
                }
            }
            // Add fixed fees if available
            if ($tariffGroup && $tariffGroup->relationLoaded('fixedFees')) {
                // Filter active fees (status = 'active' and within effective dates)
                $activeFees = $tariffGroup->fixedFees->filter(function ($fee) {
                    $isActive = $fee->is_active;
                    $isEffective = (!$fee->effective_to || $fee->effective_to >= now()->toDateString())
                        && ($fee->effective_from <= now()->toDateString());
                    return $isActive && $isEffective;
                });

                if ($activeFees->count() > 0) {
                    $tariffSnapshot['fixed_fees'] = $activeFees->map(function ($fee) {
                        return [
                            'fee_name' => $fee->name ?? $fee->fee_name,
                            'amount' => $fee->amount,
                            'description' => $fee->description,
                        ];
                    })->values()->toArray();
                }
                // Calculate total fixed fees
                $tariffSnapshot['total_fixed_fees'] = $activeFees->sum('amount');
            }

            // Determine base rate from tariff tiers if available
            $totalBill = ($meterReading->volume_usage * $tariffSnapshot['tariff_tiers']['amount']) + $tariffSnapshot['total_fixed_fees'];

            // Create bill record
            $bill = Bill::create([
                'pam_id' => $customer->pam_id,
                'customer_id' => $customer->id,
                'meter_reading_id' => $meterReading->id,
                'bill_number' => $this->generateBillNumber($customer->pam_id),
                'reference_number' => null,
                'volume_usage' => $meterReading->volume_usage,
                'total_bill' => $totalBill,
                'status' => 'pending',
                'due_date' => now()->addDays(10), // Standard 10 days payment term
                'payment_method' => null,
                'paid_at' => null,
                'issued_at' => now(),
                'tariff_snapshot' => json_encode($tariffSnapshot),
            ]);

            $totalUsageInMonth =  MeterReading::where('registered_month_id', $registeredMonth->id)->sum('volume_usage');
            $totalBillInMonth = Bill::whereHas('meterReading', function ($q) use ($registeredMonth) {
                $q->where('registered_month_id', $registeredMonth->id);
            })->sum('total_bill');

            $registeredMonth->update([
                'total_usage' => $totalUsageInMonth,
                'total_bills' => $totalBillInMonth,
            ]);


            // Create activity log for status change
            $this->logMeterReadingStatusChange($meterReading, $oldData, $bill, $requestData);

            // Refresh meter reading to get updated data
            $meterReading->refresh();

            return [
                'success' => true,
                'message' => 'Meter reading berhasil disubmit ke status pending dan billing telah dibuat.',
                'data' => [
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'customer_number' => $customer->customer_number,
                    ],
                    'meter_reading' => [
                        'id' => $meterReading->id,
                        'status' => $meterReading->status,
                    ],
                    'bill' => [
                        'id' => $bill->id,
                        'status' => $bill->status,
                    ],
                ],
            ];
        });
    }

    /**
     * Generate unique bill number for PAM
     *
     * @param int $pamId
     * @return string
     */
    private function generateBillNumber(int $pamId): string
    {
        $prefix = 'BILL';
        $year = date('Y');
        $month = date('m');

        // Get last bill number for this PAM and month
        $lastBill = Bill::where('pam_id', $pamId)
            ->where('bill_number', 'LIKE', "{$prefix}-{$pamId}-{$year}{$month}-%")
            ->orderBy('bill_number', 'desc')
            ->first();

        if ($lastBill) {
            // Extract sequence number and increment
            $lastSequence = (int) substr($lastBill->bill_number, -4);
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return sprintf('%s-%d-%s%s-%04d', $prefix, $pamId, $year, $month, $newSequence);
    }

    /**
     * Log meter reading status change activity
     *
     * @param MeterReading $meterReading
     * @param array $oldData
     * @param Bill $bill
     * @param array $requestData
     * @return void
     */
    private function logMeterReadingStatusChange(MeterReading $meterReading, array $oldData, Bill $bill, array $requestData): void
    {
        ActivityLog::create([
            'pam_id' => $meterReading->meter->customer->pam_id,
            'user_id' => $requestData['user_id'] ?? Auth::id() ?? 1,
            'action' => 'status_change',
            'activity_type' => 'meter_reading_submitted_to_pending',
            'description' => "Meter reading {$meterReading->meter->meter_number} diubah status dari draft ke pending. Bill {$bill->bill_number} dibuat dengan amount Rp " . number_format($bill->total_bill, 0, ',', '.'),
            'table_name' => 'meter_readings',
            'record_id' => $meterReading->id,
            'old_values' => ['status' => $oldData['status']],
            'new_values' => [
                'status' => 'pending',
                'bill_created' => $bill->bill_number,
                'bill_amount' => $bill->total_bill,
            ],
        ]);
    }
}
