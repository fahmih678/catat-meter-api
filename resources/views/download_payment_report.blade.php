<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>LAPORAN PEMBAYARAN - {{ $periodName }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #333;
            margin: 20mm;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }

        .header-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10px;
            color: #666;
            margin-bottom: 10px;
        }

        .summary {
            margin-bottom: 12px;
            padding: 8px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .summary-row:last-child {
            margin-bottom: 0;
        }

        .summary-label {
            font-weight: bold;
        }

        .summary-value {
            font-weight: bold;
            color: #000;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table.data-table th {
            background: #333;
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 6px 4px;
            font-size: 10px;
            border: 1px solid #333;
        }

        table.data-table td {
            padding: 5px 4px;
            border: 1px solid #333;
            font-size: 10px;
            vertical-align: middle;
        }

        table.data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        table.data-table tr:hover {
            background-color: #f0f0f0;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            font-style: italic;
            color: #666;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #888;
            border-top: 1px solid #ccc;
            padding-top: 8px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>LAPORAN PEMBAYARAN</h1>
        <div style="font-size: 12px; font-weight: bold; margin-top: 5px;">
            {{ $pamName ?? 'PDAM' }}
        </div>
    </div>

    <div class="header-info">
        <span><strong>PERIODE:</strong> {{ $periodName }}</span>
        <span><strong>TGL CETAK:</strong> {{ date('d/m/Y H:i') }}</span>
    </div>

    <div class="summary">
        <div class="summary-row">
            <span class="summary-label">TOTAL TRANSAKSI:</span>
            <span class="summary-value">{{ number_format($totalPayments, 0, ',', '.') }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">TOTAL NOMINAL:</span>
            <span class="summary-value">Rp {{ number_format($totalAmounts, 0, ',', '.') }}</span>
        </div>
    </div>

    @if ($paymentData->isNotEmpty())
        <table class="data-table">
            <thead>
                <tr>
                    <th width="5%">NO</th>
                    <th width="12%">BLN TAGIH</th>
                    <th width="12%">NOPEL</th>
                    <th width="28%">PELANGGAN</th>
                    <th width="15%">TAGIHAN</th>
                    <th width="12%">TGL BAYAR</th>
                    <th width="16%">LOKET</th>
                </tr>
            </thead>
            <tbody>
                @php($no = 1)
                @foreach ($paymentData as $payment)
                    <tr>
                        <td class="text-center">{{ $no++ }}</td>
                        <td class="text-center">
                            {{ $payment->period_formatted ? $payment->period_formatted : '-' }}</td>
                        <td class="text-center">{{ htmlspecialchars($payment->customer_number ?? '-') }}</td>
                        <td class="text-left">{{ htmlspecialchars($payment->customer_name ?? '-') }}</td>
                        <td class="text-right">Rp {{ number_format($payment->total_bill, 0, ',', '.') }}</td>
                        <td class="text-center">{{ $payment->paid_at_formatted ? $payment->paid_at_formatted : '-' }}
                        </td>
                        <td class="text-left">
                            {{ strlen($payment->paid_by_name ?? '') > 12 ? substr($payment->paid_by_name ?? '-', 0, 12) . '...' : $payment->paid_by_name ?? '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="no-data">
            Tidak ada data pembayaran untuk periode ini.
        </div>
    @endif

    <div class="footer">
        <div>LAPORAN INI DIHASILKAN OLEH SISTEM - PDAM</div>
        <div>{{ date('Y') }} - SISTEM MANAJEMEN PELANGGAN</div>
    </div>
</body>

</html>
