@extends('layouts.main')

@section('title', 'Import Data')

@section('breadcrumb')
    <li class="breadcrumb-item active">Import Data</li>
@endsection

@section('content')
    <div class="container-fluid p-0">
        <!-- Import Header -->
        <div class="row mb-4 fade-in-up">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="row align-items-center">
                        <div class="col">
                            <h2 class="mb-2">
                                <i class="bi bi-cloud-upload me-2 text-primary"></i>
                                Import Data Master
                            </h2>
                            <p class="text-muted mb-0">Upload data PAM, Area, Tariff Group, Customer, dan Meter dari file
                                Excel</p>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-outline-secondary me-2" onclick="downloadTemplate()">
                                <i class="bi bi-download me-2"></i>Download Template
                            </button>
                            <button class="btn btn-outline-primary"
                                onclick="window.open('https://docs.google.com/spreadsheets/d/1abc123/edit#gid=0', '_blank')">
                                <i class="bi bi-table me-2"></i>Buat di Google Sheets
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import Steps -->
        <div class="row mb-4">
            <div class="col-12 fade-in-up" style="animation-delay: 0.1s;">
                <div class="dashboard-card">
                    <h5 class="card-title mb-4">
                        <i class="bi bi-info-circle me-2 text-info"></i>
                        Cara Import Data
                    </h5>

                    <div class="row">
                        <div class="col-md-3 text-center mb-3">
                            <div class="step-indicator">
                                <div class="step-number">1</div>
                                <div class="step-title">Download Template</div>
                                <div class="step-description">Unduh template Excel yang telah disediakan</div>
                            </div>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="step-indicator">
                                <div class="step-number">2</div>
                                <div class="step-title">Isi Data</div>
                                <div class="step-description">Lengkapi data sesuai format yang diminta</div>
                            </div>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="step-indicator">
                                <div class="step-number">3</div>
                                <div class="step-title">Upload File</div>
                                <div class="step-description">Upload file Excel yang telah diisi</div>
                            </div>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="step-indicator">
                                <div class="step-number">4</div>
                                <div class="step-title">Validasi & Import</div>
                                <div class="step-description">Sistem akan memvalidasi dan mengimport data</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Section -->
        <div class="row mb-4">
            <div class="col-lg-8 mb-4 fade-in-up" style="animation-delay: 0.2s;">
                <div class="dashboard-card">
                    <h5 class="card-title mb-4">
                        <i class="bi bi-upload me-2 text-primary"></i>
                        Upload File Excel
                    </h5>

                    <!-- File Upload Area -->
                    <div class="file-upload-area" id="fileUploadArea">
                        <div class="upload-content">
                            <i class="bi bi-cloud-upload upload-icon"></i>
                            <h5>Drag & Drop File Excel di sini</h5>
                            <p class="text-muted">atau</p>
                            <button class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                                <i class="bi bi-folder2-open me-2"></i>Pilih File
                            </button>
                            <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" style="display: none;">
                            <div class="mt-3">
                                <small class="text-muted">Format yang didukung: .xlsx, .xls, .csv (Maks. 10MB)</small>
                            </div>
                        </div>
                        <div class="file-preview" id="filePreview" style="display: none;">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-file-earmark-excel file-icon"></i>
                                <div class="ms-3 flex-grow-1">
                                    <div class="file-name" id="fileName"></div>
                                    <div class="file-size text-muted small" id="fileSize"></div>
                                </div>
                                <button class="btn btn-outline-danger btn-sm" onclick="clearFile()">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Sheet -->
                    <div class="mt-4" id="sheetSelection" style="display: none;">
                        <label class="form-label">Pilih Sheet yang Akan Diimport:</label>
                        <select class="form-select" id="sheetSelect">
                            <option value="">Pilih sheet...</option>
                        </select>
                    </div>

                    <!-- Import Options -->
                    <div class="mt-4">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="skipErrors" checked>
                            <label class="form-check-label" for="skipErrors">
                                Lewati baris yang error dan lanjutkan import
                            </label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="updateExisting">
                            <label class="form-check-label" for="updateExisting">
                                Update data yang sudah ada (berdasarkan kode/id unik)
                            </label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="validateOnly">
                            <label class="form-check-label" for="validateOnly">
                                Validasi saja (tanpa mengimport data)
                            </label>
                        </div>
                    </div>

                    <!-- Import Button -->
                    <div class="mt-4">
                        <button class="btn btn-primary btn-lg" id="importBtn" onclick="startImport(event)" disabled>
                            <i class="bi bi-cloud-upload me-2"></i>
                            Mulai Import
                        </button>
                        <button class="btn btn-outline-secondary ms-2" onclick="resetAll()">
                            <i class="bi bi-arrow-clockwise me-2"></i>
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4 fade-in-up" style="animation-delay: 0.3s;">
                <div class="dashboard-card">
                    <h5 class="card-title mb-4">
                        <i class="bi bi-list-check me-2 text-success"></i>
                        Format Data yang Diperlukan
                    </h5>

                    <div class="accordion" id="formatAccordion">
                        <!-- PAMS -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#pamsFormat">
                                    <i class="bi bi-building me-2"></i>
                                    PAMS
                                </button>
                            </h2>
                            <div id="pamsFormat" class="accordion-collapse collapse" data-bs-parent="#formatAccordion">
                                <div class="accordion-body">
                                    <strong>Format (1 record per baris):</strong>
                                    <div class="mt-2 mb-0">
                                        <code>code,name,email,phone,address</code>
                                    </div>
                                    <small class="text-muted">PAM001,PAM A,pam@a.com,08123456789,"Jl. Merdeka No.
                                        1"</small>
                                </div>
                            </div>
                        </div>

                        <!-- AREAS -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#areasFormat">
                                    <i class="bi bi-geo-alt me-2"></i>
                                    AREAS
                                </button>
                            </h2>
                            <div id="areasFormat" class="accordion-collapse collapse" data-bs-parent="#formatAccordion">
                                <div class="accordion-body">
                                    <strong>Format (1 record per baris):</strong>
                                    <div class="mt-2 mb-0">
                                        <code>pam_code,code,name,description</code>
                                    </div>
                                    <small class="text-muted">PAM001,AREA001,Kelurahan A,"Area pertama"</small>
                                </div>
                            </div>
                        </div>

                        <!-- TARIFF GROUPS -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#tariffFormat">
                                    <i class="bi bi-cash-stack me-2"></i>
                                    TARIFF GROUPS
                                </button>
                            </h2>
                            <div id="tariffFormat" class="accordion-collapse collapse" data-bs-parent="#formatAccordion">
                                <div class="accordion-body">
                                    <strong>Format (1 record per baris):</strong>
                                    <div class="mt-2 mb-0">
                                        <code>pam_code,name,description</code>
                                    </div>
                                    <small class="text-muted">PAM001,Rumah Tangga,"Tarif untuk rumah tangga"</small>
                                </div>
                            </div>
                        </div>

                        <!-- CUSTOMERS_METERS (Gabungan) -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#customersMetersFormat">
                                    <i class="bi bi-people-fill me-2 text-primary"></i>
                                    CUSTOMERS + METERS (Gabungan)
                                </button>
                            </h2>
                            <div id="customersMetersFormat" class="accordion-collapse collapse"
                                data-bs-parent="#formatAccordion">
                                <div class="accordion-body">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>Format paling direkomendasikan!</strong> Import customer dan meter sekaligus dalam satu file.
                                    </div>
                                    <strong>Format (1 record per baris):</strong>
                                    <div class="mt-2 mb-0">
                                        <code>pam_code,area_code,tariff_group_name,customer_number,name,address,phone,meter_number,initial_installed_meter,notes</code>
                                    </div>
                                    <small class="text-muted">PAM001,AREA001,Rumah Tangga,RT001,"Budi Santoso","Jl. Gatot Subroto No. 10",08123456789,M001,0,"Meter pertama"</small>
                                </div>
                            </div>
                        </div>

                        <!-- CUSTOMERS -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#customersFormat">
                                    <i class="bi bi-people me-2"></i>
                                    CUSTOMERS (Hanya Customer)
                                </button>
                            </h2>
                            <div id="customersFormat" class="accordion-collapse collapse"
                                data-bs-parent="#formatAccordion">
                                <div class="accordion-body">
                                    <strong>Format (1 record per baris):</strong>
                                    <div class="mt-2 mb-0">
                                        <code>pam_code,area_code,tariff_group_name,customer_number,name,address,phone</code>
                                    </div>
                                    <small class="text-muted">PAM001,AREA001,Rumah Tangga,RT001,"Budi Santoso","Jl. Gatot Subroto No. 10",08123456789</small>
                                </div>
                            </div>
                        </div>

                        <!-- METERS -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#metersFormat">
                                    <i class="bi bi-speedometer me-2"></i>
                                    METERS (Hanya Meter)
                                </button>
                            </h2>
                            <div id="metersFormat" class="accordion-collapse collapse" data-bs-parent="#formatAccordion">
                                <div class="accordion-body">
                                    <strong>Format (1 record per baris):</strong>
                                    <div class="mt-2 mb-0">
                                        <code>pam_code,customer_number,meter_number,initial_installed_meter,notes</code>
                                    </div>
                                    <small class="text-muted">PAM001,RT001,M001,0,"Meter pertama"</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import History -->
        <div class="row mb-4">
            <div class="col-12 fade-in-up" style="animation-delay: 0.4s;">
                <div class="dashboard-card">
                    <h5 class="card-title mb-4">
                        <i class="bi bi-clock-history me-2 text-warning"></i>
                        Riwayat Import
                    </h5>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Nama File</th>
                                    <th>Jenis Data</th>
                                    <th>Status</th>
                                    <th>Detail</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>28 Oct 2024, 14:30</td>
                                    <td>master_data_pam_a.xlsx</td>
                                    <td>PAMS</td>
                                    <td><span class="badge bg-success">Success</span></td>
                                    <td>25 data berhasil diimport</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>28 Oct 2024, 13:45</td>
                                    <td>customers_area_b.xlsx</td>
                                    <td>CUSTOMERS</td>
                                    <td><span class="badge bg-warning">Partial</span></td>
                                    <td>48 dari 50 data berhasil (2 error)</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>27 Oct 2024, 16:20</td>
                                    <td>meters_update.xlsx</td>
                                    <td>METERS</td>
                                    <td><span class="badge bg-danger">Failed</span></td>
                                    <td>Format file tidak valid</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Progress Modal -->
    <div class="modal fade" id="importProgressModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sedang Mengimport Data...</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="progress mb-3">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                            style="width: 0%" id="importProgress"></div>
                    </div>
                    <div class="text-center">
                        <small class="text-muted" id="importStatus">Memproses file...</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .step-indicator {
            padding: 1.5rem;
            border-radius: 10px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .step-indicator:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin: 0 auto 1rem;
        }

        .step-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .step-description {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .file-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 3rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-upload-area:hover {
            border-color: var(--primary-color);
            background: #f8f9fa;
        }

        .file-upload-area.dragover {
            border-color: var(--primary-color);
            background: #e7f3ff;
        }

        .upload-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .file-preview {
            padding: 2rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            background: #f8f9fa;
        }

        .file-icon {
            font-size: 2rem;
            color: #28a745;
        }

        .file-name {
            font-weight: 600;
        }

        .accordion-button:not(.collapsed) {
            background-color: var(--primary-color);
            color: white;
        }

        .code {
            background: #f1f3f4;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            border-bottom: 1px solid #f8f9fa;
            border-radius: 15px 15px 0 0;
        }

        .modal-footer {
            border-top: 1px solid #f8f9fa;
            border-radius: 0 0 15px 15px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        let selectedFile = null;

        // File upload handling
        document.getElementById('fileInput').addEventListener('change', function(e) {
            handleFileSelect(e.target.files[0]);
        });

        // Drag and drop
        const uploadArea = document.getElementById('fileUploadArea');

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelect(files[0]);
            }
        });

        async function handleFileSelect(file) {
            if (!file) return;

            try {
                // Validate file type
                const validTypes = ['.xlsx', '.xls', '.csv'];
                const fileExtension = '.' + file.name.split('.').pop().toLowerCase();

                if (!validTypes.includes(fileExtension)) {
                    showNotification('File tidak valid. Harus berformat .xlsx, .xls, atau .csv', 'danger');
                    clearFile();
                    return;
                }

                // Validate file size (10MB)
                const maxSize = 10 * 1024 * 1024; // 10MB
                if (file.size > maxSize) {
                    showNotification('File terlalu besar. Maksimal 10MB', 'danger');
                    clearFile();
                    return;
                }

                selectedFile = file;
                displayFilePreview(file);

                // Show loading state
                const sheetSelect = document.getElementById('sheetSelect');
                sheetSelect.innerHTML = '<option value="">Loading sheets...</option>';
                document.getElementById('sheetSelection').style.display = 'block';
                document.getElementById('importBtn').disabled = true;

                // Load sheets from server
                await loadSheets(file);

                // Enable import button if sheets loaded successfully
                if (sheetSelect.options.length > 1) {
                    document.getElementById('importBtn').disabled = false;
                }

            } catch (error) {
                console.error('Error handling file:', error);
                showNotification('Error processing file: ' + error.message, 'danger');
                clearFile();
            }
        }

        function displayFilePreview(file) {
            const preview = document.getElementById('filePreview');
            const uploadContent = document.querySelector('.upload-content');

            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent = formatFileSize(file.size);

            uploadContent.style.display = 'none';
            preview.style.display = 'block';
        }

        function clearFile() {
            selectedFile = null;
            document.getElementById('fileInput').value = '';
            document.getElementById('filePreview').style.display = 'none';
            document.querySelector('.upload-content').style.display = 'block';
            document.getElementById('sheetSelection').style.display = 'none';
            document.getElementById('importBtn').disabled = true;
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        async function loadSheets(file) {
            try {
                const formData = new FormData();
                formData.append('file', file);

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!csrfToken) {
                    throw new Error('CSRF token not found');
                }

                const response = await fetch('/import/sheets', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    const sheetSelect = document.getElementById('sheetSelect');
                    sheetSelect.innerHTML = '<option value="">Pilih sheet...</option>';

                    if (result.sheets && result.sheets.length > 0) {
                        result.sheets.forEach(sheet => {
                            sheetSelect.innerHTML += `<option value="${sheet}">${sheet}</option>`;
                        });
                        document.getElementById('sheetSelection').style.display = 'block';
                    } else {
                        throw new Error('No sheets found in file');
                    }
                } else {
                    throw new Error(result.message || 'Unknown error loading sheets');
                }
            } catch (error) {
                console.error('Error loading sheets:', error);
                showNotification('Tidak bisa mendeteksi sheet. Menggunakan default sheet...', 'warning');

                // Fallback to default sheets
                const sheetSelect = document.getElementById('sheetSelect');
                sheetSelect.innerHTML = '<option value="">Pilih sheet...</option>';
                const defaultSheets = ['PAMS', 'AREAS', 'TARIFF_GROUPS', 'CUSTOMERS_METERS', 'CUSTOMERS', 'METERS'];
                defaultSheets.forEach(sheet => {
                    const displayName = sheet === 'CUSTOMERS_METERS' ? 'CUSTOMERS + METERS (Gabungan)' : sheet;
                    sheetSelect.innerHTML += `<option value="${sheet}">${displayName}</option>`;
                });
                document.getElementById('sheetSelection').style.display = 'block';
                document.getElementById('importBtn').disabled = false;
            }
        }

        function downloadTemplate() {
            // Download template from server
            window.open('/import/template', '_blank');
            showNotification('Template berhasil diunduh', 'success');
        }

        async function startImport(event) {
            if (event) {
                event.preventDefault();
            }

            if (!selectedFile) {
                showNotification('Pilih file terlebih dahulu', 'warning');
                return;
            }

            const selectedSheet = document.getElementById('sheetSelect').value;
            if (!selectedSheet) {
                showNotification('Pilih sheet yang akan diimport', 'warning');
                return;
            }
            // Disable import button during processing
            const importBtn = document.getElementById('importBtn');
            const originalBtnContent = importBtn.innerHTML;
            importBtn.disabled = true;
            importBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Memproses...';

            // Show progress modal
            const modal = new bootstrap.Modal(document.getElementById('importProgressModal'));
            modal.show();

            try {
                // Start progress animation
                animateProgress();

                const formData = new FormData();
                formData.append('file', selectedFile);
                formData.append('sheet', selectedSheet);
                formData.append('skip_errors', document.getElementById('skipErrors').checked ? 'true' : 'false');
                formData.append('update_existing', document.getElementById('updateExisting').checked ? 'true' : 'false');
                formData.append('validate_only', document.getElementById('validateOnly').checked ? 'true' : 'false');
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!csrfToken) {
                    throw new Error('CSRF token not found');
                }

                const response = await fetch('/import/process', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                // Update progress to 100%
                document.getElementById('importProgress').style.width = '100%';
                document.getElementById('importStatus').textContent = 'Import selesai!';

                setTimeout(() => {
                    modal.hide();
                    if (result.success) {
                        showImportResults(result.data);
                        resetAll();
                    } else {
                        showNotification(result.message || 'Import failed', 'danger');
                        importBtn.disabled = false;
                        importBtn.innerHTML = originalBtnContent;
                    }
                }, 1000);

            } catch (error) {
                console.error('Import error:', error);
                modal.hide();
                showNotification('Error during import: ' + error.message, 'danger');
                importBtn.disabled = false;
                importBtn.innerHTML = originalBtnContent;
            }
        }

        function animateProgress() {
            let progress = 0;
            const progressBar = document.getElementById('importProgress');
            const statusText = document.getElementById('importStatus');

            const interval = setInterval(() => {
                progress += Math.random() * 5;
                if (progress > 85) {
                    progress = 85; // Stop at 85%, real completion will set to 100%
                    clearInterval(interval);
                }
                progressBar.style.width = progress + '%';

                if (progress < 30) {
                    statusText.textContent = 'Membaca file...';
                } else if (progress < 60) {
                    statusText.textContent = 'Memvalidasi data...';
                } else {
                    statusText.textContent = 'Mengimport data...';
                }
            }, 200);

            // Store interval ID so we can clear it when import completes
            window.progressInterval = interval;
        }

        function showImportResults(data) {
            let message = `Import ${data.sheet} selesai!\n\n`;
            message += `Total diproses: ${data.total_processed}\n`;
            message += `Berhasil: ${data.success_count}\n`;
            message += `Error: ${data.error_count}\n`;

            if (data.error_count > 0) {
                message += `\nDetail error:\n`;
                data.errors.slice(0, 5).forEach(error => {
                    message += `Baris ${error.row}: ${error.error}\n`;
                });
                if (data.errors.length > 5) {
                    message += `... dan ${data.errors.length - 5} error lainnya`;
                }
            }

            if (data.validate_only) {
                showNotification('Validasi selesai! ' + message, 'info');
            } else if (data.error_count === 0) {
                showNotification('Semua data berhasil diimport!', 'success');
            } else {
                showNotification('Import selesai dengan beberapa error. ' + message, 'warning');
            }

            // Refresh page to show updated data if import was successful
            if (!data.validate_only && data.success_count > 0) {
                setTimeout(() => {
                    location.reload();
                }, 2000);
            }
        }

        function resetAll() {
            clearFile();
            document.getElementById('skipErrors').checked = true;
            document.getElementById('updateExisting').checked = false;
            document.getElementById('validateOnly').checked = false;

            // Reset import button
            const importBtn = document.getElementById('importBtn');
            importBtn.disabled = true;
            importBtn.innerHTML = '<i class="bi bi-cloud-upload me-2"></i>Mulai Import';

            // Clear progress interval if running
            if (window.progressInterval) {
                clearInterval(window.progressInterval);
                window.progressInterval = null;
            }
        }

        function showNotification(message, type = 'info') {
            // Create toast notification
            const toastHtml = `
        <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

            const container = document.createElement('div');
            container.innerHTML = toastHtml;
            container.style.position = 'fixed';
            container.style.top = '20px';
            container.style.right = '20px';
            container.style.zIndex = '9999';

            document.body.appendChild(container);

            const toast = new bootstrap.Toast(container.querySelector('.toast'));
            toast.show();

            setTimeout(() => {
                container.remove();
            }, 3000);
        }
    </script>
@endpush
