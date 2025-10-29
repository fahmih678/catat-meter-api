<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Pam;
use App\Models\Area;
use App\Models\TariffGroup;
use App\Models\Customer;
use App\Models\Meter;

class ImportController extends Controller
{
    /**
     * Handle file upload and return available sheets
     */
    public function getSheets(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|mimes:xlsx,xls,csv|max:10240', // 10MB max
            ]);

            $file = $request->file('file');
            $sheets = $this->getAvailableSheets($file);

            return response()->json([
                'success' => true,
                'sheets' => $sheets
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing file: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Import data from Excel file
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'file' => 'required|mimes:xlsx,xls,csv|max:10240',
                'sheet' => 'required|string',
                'skip_errors' => 'required|string',
                'update_existing' => 'required|string',
                'validate_only' => 'required|string',
            ]);

            // Convert string boolean to actual boolean
            $skipErrors = filter_var($validated['skip_errors'], FILTER_VALIDATE_BOOLEAN);
            $updateExisting = filter_var($validated['update_existing'], FILTER_VALIDATE_BOOLEAN);
            $validateOnly = filter_var($validated['validate_only'], FILTER_VALIDATE_BOOLEAN);

            $file = $request->file('file');
            $sheet = $validated['sheet'];

            // Validate sheet exists
            $availableSheets = $this->getAvailableSheets($file);
            if (!in_array($sheet, $availableSheets)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sheet not found in file'
                ], 400);
            }

            // Process import
            $result = $this->processImport($file, $sheet, $skipErrors, $updateExisting, $validateOnly);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Import error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available sheets from Excel file
     */
    private function getAvailableSheets($file): array
    {
        try {
            $import = new class implements \Maatwebsite\Excel\Concerns\WithMultipleSheets {
                public function sheets(): array
                {
                    return [];
                }
            };

            $reader = \Maatwebsite\Excel\Facades\Excel::toArray($import, $file);

            $sheets = [];
            foreach ($reader as $index => $sheet) {
                if (!empty($sheet) && count($sheet) > 0) {
                    // Try to get the actual sheet name, otherwise use default
                    $sheetName = 'Sheet ' . ($index + 1);

                    // Try to determine sheet type from first row if it exists
                    if (isset($sheet[0]) && is_array($sheet[0])) {
                        $firstRow = $sheet[0];
                        $sheetName = $this->guessSheetNameFromData($firstRow) ?? $sheetName;
                    }

                    $sheets[] = $sheetName;
                }
            }

            // If no sheets detected, return default sheet names
            if (empty($sheets)) {
                return ['PAMS', 'AREAS', 'TARIFF_GROUPS', 'CUSTOMERS_METERS', 'CUSTOMERS', 'METERS'];
            }

            return $sheets;
        } catch (\Exception $e) {
            Log::error('Error detecting sheets: ' . $e->getMessage());
            // Fallback to default sheet names
            return ['PAMS', 'AREAS', 'TARIFF_GROUPS', 'CUSTOMERS_METERS', 'CUSTOMERS', 'METERS'];
        }
    }

    /**
     * Guess sheet name based on data structure
     */
    private function guessSheetNameFromData(array $firstRow): ?string
    {
        $firstRow = array_map('strtolower', array_map('trim', $firstRow));

        if (in_array('code', $firstRow) && in_array('name', $firstRow) && in_array('email', $firstRow)) {
            return 'PAMS';
        }

        if (in_array('pam_code', $firstRow) && in_array('code', $firstRow) && in_array('name', $firstRow)) {
            return 'AREAS';
        }

        if (in_array('pam_code', $firstRow) && in_array('name', $firstRow) && in_array('description', $firstRow)) {
            return 'TARIFF_GROUPS';
        }

        if (in_array('customer_number', $firstRow) && in_array('name', $firstRow) && in_array('address', $firstRow)) {
            // Check if it also has meter data (combined import)
            if (in_array('meter_number', $firstRow)) {
                return 'CUSTOMERS_METERS';
            }
            return 'CUSTOMERS';
        }

        if (in_array('meter_number', $firstRow) && in_array('customer_number', $firstRow)) {
            return 'METERS';
        }

        return null;
    }

    /**
     * Process import based on sheet type
     */
    private function processImport($file, string $sheet, bool $skipErrors, bool $updateExisting, bool $validateOnly): array
    {
        try {
            $importClass = $this->getImportClass($sheet, $skipErrors, $updateExisting, $validateOnly);

            // Determine file type
            $extension = strtolower($file->getClientOriginalExtension());
            $readerType = match ($extension) {
                'xlsx' => \Maatwebsite\Excel\Excel::XLSX,
                'xls' => \Maatwebsite\Excel\Excel::XLS,
                'csv' => \Maatwebsite\Excel\Excel::CSV,
                default => \Maatwebsite\Excel\Excel::XLSX
            };

            Excel::import($importClass, $file, null, $readerType);

            return [
                'sheet' => $sheet,
                'total_processed' => $importClass->getTotalProcessed(),
                'success_count' => $importClass->getSuccessCount(),
                'error_count' => $importClass->getErrorCount(),
                'errors' => $importClass->getErrors(),
                'validate_only' => $validateOnly
            ];
        } catch (\Exception $e) {
            // If specific sheet class fails, try to process as generic data
            Log::error("Specific import failed for {$sheet}: " . $e->getMessage());

            // Fallback: try to process based on data structure detection
            return $this->processGenericImport($file, $sheet, $skipErrors, $updateExisting, $validateOnly);
        }
    }

    /**
     * Generic import that detects data type from structure
     */
    private function processGenericImport($file, string $selectedSheet, bool $skipErrors, bool $updateExisting, bool $validateOnly): array
    {
        try {
            $extension = strtolower($file->getClientOriginalExtension());
            $readerType = match ($extension) {
                'xlsx' => \Maatwebsite\Excel\Excel::XLSX,
                'xls' => \Maatwebsite\Excel\Excel::XLS,
                'csv' => \Maatwebsite\Excel\Excel::CSV,
                default => \Maatwebsite\Excel\Excel::XLSX
            };

            $data = Excel::toArray(new class {}, $file, null, $readerType);

            // Find the sheet index by name
            $sheetIndex = $this->findSheetIndex($data, $selectedSheet);

            if ($sheetIndex === null) {
                throw new \Exception("Sheet '{$selectedSheet}' not found in file");
            }

            $sheetData = $data[$sheetIndex];

            if (empty($sheetData)) {
                throw new \Exception("Sheet '{$selectedSheet}' is empty");
            }

            // Detect data type from first row
            $firstRow = $sheetData[0];
            $detectedType = $this->guessSheetNameFromData($firstRow);

            if (!$detectedType) {
                throw new \Exception("Could not determine data type for sheet '{$selectedSheet}'");
            }

            $importClass = $this->getImportClass($detectedType, $skipErrors, $updateExisting, $validateOnly);
            Excel::import($importClass, $file, null, $readerType);

            return [
                'sheet' => $selectedSheet,
                'detected_type' => $detectedType,
                'total_processed' => $importClass->getTotalProcessed(),
                'success_count' => $importClass->getSuccessCount(),
                'error_count' => $importClass->getErrorCount(),
                'errors' => $importClass->getErrors(),
                'validate_only' => $validateOnly
            ];
        } catch (\Exception $e) {
            Log::error("Generic import failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Find sheet index by name
     */
    private function findSheetIndex(array $_data, string $_sheetName): ?int
    {
        // For now, use first sheet if name doesn't match exactly
        // In a real implementation, you might want to get actual sheet names
        return 0;
    }

    /**
     * Get appropriate import class based on sheet name
     */
    private function getImportClass(string $sheet, bool $skipErrors, bool $updateExisting, bool $validateOnly)
    {
        return match ($sheet) {
            'PAMS' => new PamsImport($skipErrors, $updateExisting, $validateOnly),
            'AREAS' => new AreasImport($skipErrors, $updateExisting, $validateOnly),
            'TARIFF_GROUPS' => new TariffGroupsImport($skipErrors, $updateExisting, $validateOnly),
            'CUSTOMERS_METERS' => new CustomersMetersImport($skipErrors, $updateExisting, $validateOnly),
            'CUSTOMERS' => new CustomersImport($skipErrors, $updateExisting, $validateOnly),
            'METERS' => new MetersImport($skipErrors, $updateExisting, $validateOnly),
            default => throw new \Exception("Unknown sheet type: {$sheet}")
        };
    }

    /**
     * Download import template
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="import_template.csv"',
        ];

        $csvContent = $this->generateTemplateContent();

        return response($csvContent, 200, $headers);
    }

    /**
     * Generate template content for all data types
     */
    private function generateTemplateContent(): string
    {
        $content = "PAMS\n";
        $content .= "code,name,email,phone,address\n";
        $content .= "PAM001,PAM A,pam@a.com,08123456789,Jl. Merdeka No. 1\n";
        $content .= "PAM002,PAM B,pam@b.com,08123456790,Jl. Sudirman No. 2\n\n";

        $content .= "AREAS\n";
        $content .= "pam_code,code,name,description\n";
        $content .= "PAM001,AREA001,Kelurahan A,Area pertama\n";
        $content .= "PAM001,AREA002,Kelurahan B,Area kedua\n\n";

        $content .= "TARIFF_GROUPS\n";
        $content .= "pam_code,name,description\n";
        $content .= "PAM001,Rumah Tangga,Tarif untuk rumah tangga\n";
        $content .= "PAM001,Bisnis,Tarif untuk bisnis\n\n";

        $content .= "CUSTOMERS_METERS (Gabungan)\n";
        $content .= "pam_code,area_code,tariff_group_name,customer_number,name,address,phone,meter_number,initial_installed_meter,notes\n";
        $content .= "PAM001,AREA001,Rumah Tangga,RT001,\"Budi Santoso\",\"Jl. Gatot Subroto No. 10\",08123456789,M001,0,\"Meter pertama\"\n";
        $content .= "PAM001,AREA002,Bisnis,RT002,\"Toko Jaya\",\"Jl. HOS Cokroaminoto No. 25\",08123456790,M002,0,\"Meter kedua\"\n\n";

        $content .= "CUSTOMERS (Hanya Customer)\n";
        $content .= "pam_code,area_code,tariff_group_name,customer_number,name,address,phone\n";
        $content .= "PAM001,AREA001,Rumah Tangga,RT001,\"Budi Santoso\",\"Jl. Gatot Subroto No. 10\",08123456789\n";
        $content .= "PAM001,AREA002,Bisnis,RT002,\"Toko Jaya\",\"Jl. HOS Cokroaminoto No. 25\",08123456790\n\n";

        $content .= "METERS (Hanya Meter)\n";
        $content .= "pam_code,customer_number,meter_number,initial_installed_meter,notes\n";
        $content .= "PAM001,RT001,M001,0,\"Meter pertama\"\n";
        $content .= "PAM001,RT002,M002,0,\"Meter kedua\"\n";

        return $content;
    }
}

// Base Import Class
abstract class BaseImport implements ToCollection, WithHeadingRow
{
    protected int $totalProcessed = 0;
    protected int $successCount = 0;
    protected int $errorCount = 0;
    protected array $errors = [];
    protected bool $skipErrors;
    protected bool $updateExisting;
    protected bool $validateOnly;

    public function __construct(bool $skipErrors = true, bool $updateExisting = false, bool $validateOnly = false)
    {
        $this->skipErrors = $skipErrors;
        $this->updateExisting = $updateExisting;
        $this->validateOnly = $validateOnly;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $this->totalProcessed++;
            $rowNumber = $index + 2; // +2 for header row (0-indexed + 1 for actual row)

            try {
                $this->processRow($row->toArray(), $rowNumber);
                $this->successCount++;
            } catch (\Exception $e) {
                $this->errorCount++;
                $this->errors[] = [
                    'row' => $rowNumber,
                    'data' => $row->toArray(),
                    'error' => $e->getMessage()
                ];

                if (!$this->skipErrors) {
                    throw $e;
                }
            }
        }
    }

    abstract protected function processRow(array $row, int $rowNumber);

    public function getTotalProcessed(): int
    {
        return $this->totalProcessed;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    protected function validateRow(array $row, array $rules, int $_rowNumber): void
    {
        $validator = Validator::make($row, $rules);

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->all() as $error) {
                $errors[] = $error;
            }
            throw new \Exception('Validation failed: ' . implode(', ', $errors));
        }
    }
}

// PAMS Import
class PamsImport extends BaseImport
{
    protected function processRow(array $row, int $rowNumber): void
    {
        strval($row['phone']);
        $rules = [
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:1000',
        ];

        $this->validateRow($row, $rules, $rowNumber);

        if (!$this->validateOnly) {
            $existingPam = Pam::withTrashed()->where('code', $row['code'])->first();

            if ($existingPam) {
                if ($this->updateExisting) {
                    $existingPam->update([
                        'name' => $row['name'],
                        'email' => $row['email'] ?? null,
                        'phone' => $row['phone'] ?? null,
                        'address' => $row['address'] ?? null,
                    ]);
                    if ($existingPam->trashed()) {
                        $existingPam->restore();
                    }
                }
            } else {
                Pam::create([
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'email' => $row['email'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'address' => $row['address'] ?? null,
                    'created_by' => Auth::check() ? Auth::id() : 1,
                ]);
            }
        }
    }
}

// AREAS Import
class AreasImport extends BaseImport
{
    protected function processRow(array $row, int $rowNumber): void
    {
        $rules = [
            'pam_code' => 'required|exists:pams,code',
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ];

        $this->validateRow($row, $rules, $rowNumber);

        if (!$this->validateOnly) {
            $pam = Pam::where('code', $row['pam_code'])->first();
            if (!$pam) {
                throw new \Exception("PAM with code '{$row['pam_code']}' not found");
            }

            $existingArea = Area::where('pam_id', $pam->id)->where('code', $row['code'])->first();

            if ($existingArea) {
                if ($this->updateExisting) {
                    $existingArea->update([
                        'name' => $row['name'],
                        'description' => $row['description'] ?? null,
                    ]);
                }
            } else {
                Area::create([
                    'pam_id' => $pam->id,
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'description' => $row['description'] ?? null,
                ]);
            }
        }
    }
}

// TARIFF GROUPS Import
class TariffGroupsImport extends BaseImport
{
    protected function processRow(array $row, int $rowNumber): void
    {
        $rules = [
            'pam_code' => 'required|exists:pams,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ];

        $this->validateRow($row, $rules, $rowNumber);

        if (!$this->validateOnly) {
            $pam = Pam::where('code', $row['pam_code'])->first();
            if (!$pam) {
                throw new \Exception("PAM with code '{$row['pam_code']}' not found");
            }

            $existingTariffGroup = TariffGroup::where('pam_id', $pam->id)->where('name', $row['name'])->first();

            if ($existingTariffGroup) {
                if ($this->updateExisting) {
                    $existingTariffGroup->update([
                        'description' => $row['description'] ?? null,
                    ]);
                }
            } else {
                TariffGroup::create([
                    'pam_id' => $pam->id,
                    'name' => $row['name'],
                    'description' => $row['description'] ?? null,
                ]);
            }
        }
    }
}

// CUSTOMERS Import
class CustomersImport extends BaseImport
{
    protected function processRow(array $row, int $rowNumber): void
    {
        $rules = [
            'pam_code' => 'required|exists:pams,code',
            'area_code' => 'required|exists:areas,code',
            'tariff_group_name' => 'required|string|max:255',
            'customer_number' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:1000',
            'phone' => 'nullable|string|max:20',
        ];

        $this->validateRow($row, $rules, $rowNumber);

        if (!$this->validateOnly) {
            $pam = Pam::where('code', $row['pam_code'])->first();
            $area = Area::where('pam_id', $pam->id)->where('code', $row['area_code'])->first();
            $tariffGroup = TariffGroup::where('pam_id', $pam->id)->where('name', $row['tariff_group_name'])->first();

            if (!$pam) {
                throw new \Exception("PAM with code '{$row['pam_code']}' not found");
            }
            if (!$area) {
                throw new \Exception("Area with code '{$row['area_code']}' not found in PAM '{$row['pam_code']}'");
            }
            if (!$tariffGroup) {
                throw new \Exception("Tariff group '{$row['tariff_group_name']}' not found in PAM '{$row['pam_code']}'");
            }

            $existingCustomer = Customer::where('pam_id', $pam->id)
                ->where('customer_number', $row['customer_number'])
                ->first();

            if ($existingCustomer) {
                if ($this->updateExisting) {
                    $existingCustomer->update([
                        'area_id' => $area->id,
                        'tariff_group_id' => $tariffGroup->id,
                        'name' => $row['name'],
                        'address' => $row['address'],
                        'phone' => $row['phone'] ?? null,
                    ]);
                }
            } else {
                Customer::create([
                    'pam_id' => $pam->id,
                    'area_id' => $area->id,
                    'tariff_group_id' => $tariffGroup->id,
                    'customer_number' => $row['customer_number'],
                    'name' => $row['name'],
                    'address' => $row['address'],
                    'phone' => $row['phone'] ?? null,
                ]);
            }
        }
    }
}

// METERS Import
class MetersImport extends BaseImport
{
    protected function processRow(array $row, int $rowNumber): void
    {
        $rules = [
            'pam_code' => 'required|exists:pams,code',
            'customer_number' => 'required|string|max:255',
            'meter_number' => 'required|string|max:255',
            'initial_installed_meter' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ];

        $this->validateRow($row, $rules, $rowNumber);

        if (!$this->validateOnly) {
            $pam = Pam::where('code', $row['pam_code'])->first();
            $customer = Customer::where('pam_id', $pam->id)
                ->where('customer_number', $row['customer_number'])
                ->first();

            if (!$pam) {
                throw new \Exception("PAM with code '{$row['pam_code']}' not found");
            }
            if (!$customer) {
                throw new \Exception("Customer with number '{$row['customer_number']}' not found in PAM '{$row['pam_code']}'");
            }

            $existingMeter = Meter::where('customer_id', $customer->id)
                ->where('meter_number', $row['meter_number'])
                ->first();

            if ($existingMeter) {
                if ($this->updateExisting) {
                    $existingMeter->update([
                        'initial_installed_meter' => $row['initial_installed_meter'],
                        'notes' => $row['notes'] ?? null,
                    ]);
                }
            } else {
                Meter::create([
                    'customer_id' => $customer->id,
                    'meter_number' => $row['meter_number'],
                    'installed_at' => now()->format('Y-m-d H:i:s'),
                    'initial_installed_meter' => $row['initial_installed_meter'],
                    'notes' => $row['notes'] ?? null,
                ]);
            }
        }
    }
}

// CUSTOMERS_METERS Combined Import
class CustomersMetersImport extends BaseImport
{
    protected function processRow(array $row, int $rowNumber): void
    {
        $rules = [
            'pam_code' => 'required|exists:pams,code',
            'area_code' => 'required|exists:areas,code',
            'tariff_group_name' => 'required|string|max:255',
            'customer_number' => 'required|max:255',
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:1000',
            'phone' => 'nullable|string|max:20',
            'meter_number' => 'required|max:255',
            'initial_installed_meter' => 'nullable|min:0',
            'notes' => 'nullable|string|max:1000',
        ];

        $this->validateRow($row, $rules, $rowNumber);

        if (!$this->validateOnly) {
            $pam = Pam::where('code', $row['pam_code'])->first();
            $area = Area::where('pam_id', $pam->id)->where('code', $row['area_code'])->first();
            $tariffGroup = TariffGroup::where('pam_id', $pam->id)->where('name', $row['tariff_group_name'])->first();

            if (!$pam) {
                throw new \Exception("PAM with code '{$row['pam_code']}' not found");
            }
            if (!$area) {
                throw new \Exception("Area with code '{$row['area_code']}' not found in PAM '{$row['pam_code']}'");
            }
            if (!$tariffGroup) {
                throw new \Exception("Tariff group '{$row['tariff_group_name']}' not found in PAM '{$row['pam_code']}'");
            }

            // Process Customer
            $existingCustomer = Customer::where('pam_id', $pam->id)
                ->where('customer_number', $row['customer_number'])
                ->first();

            if ($existingCustomer) {
                if ($this->updateExisting) {
                    $existingCustomer->update([
                        'area_id' => $area->id,
                        'tariff_group_id' => $tariffGroup->id,
                        'name' => $row['name'],
                        'address' => $row['address'],
                        'phone' => $row['phone'] ?? null,
                    ]);
                }
                $customer = $existingCustomer;
            } else {
                $customer = Customer::create([
                    'pam_id' => $pam->id,
                    'area_id' => $area->id,
                    'tariff_group_id' => $tariffGroup->id,
                    'customer_number' => $row['customer_number'],
                    'name' => $row['name'],
                    'address' => $row['address'],
                    'phone' => $row['phone'] ?? null,
                ]);
            }

            // Process Meter (if meter_number is provided)
            if (!empty($row['meter_number'])) {
                $existingMeter = Meter::where('customer_id', $customer->id)
                    ->where('meter_number', $row['meter_number'])
                    ->first();

                if ($existingMeter) {
                    if ($this->updateExisting) {
                        $existingMeter->update([
                            'initial_installed_meter' => $row['initial_installed_meter'] ?? 0,
                            'notes' => $row['notes'] ?? null,
                        ]);
                    }
                } else {
                    Meter::create([
                        'pam_id' => $pam->id,
                        'customer_id' => $customer->id,
                        'meter_number' => $row['meter_number'],
                        'installed_at' => now()->format('Y-m-d H:i:s'),
                        'initial_installed_meter' => $row['initial_installed_meter'] ?? 0,
                        'notes' => $row['notes'] ?? null,
                    ]);
                }
            }
        }
    }
}
