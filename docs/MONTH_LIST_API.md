# Month List API - CatatMeterController

## Overview
API untuk mendapatkan daftar bulan yang telah terdaftar dengan data pembacaan meter. API ini mengembalikan data yang sesuai dengan tampilan UI mobile untuk menampilkan informasi bulanan seperti volume air, jumlah pelanggan, dan total tagihan.

## Endpoint

### GET /api/v1/month-list
Mengambil daftar bulan terdaftar dengan data agregat

**Parameters:**
- `year` (optional, query string): Tahun yang akan ditampilkan (default: tahun sekarang)

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

**Example Request:**
```http
GET /api/v1/month-list?year=2025
Authorization: Bearer 1|eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

## Response Format

### Success Response (200)
```json
{
    "status": "success",
    "message": "Month list retrieved successfully",
    "data": {
        "year": 2025,
        "months_displayed": 5,
        "monthly_data": [
            {
                "id": 1,
                "month": 1,
                "month_name": "Januari",
                "year": 2025,
                "period": "2025-01",
                "total_customers": 124,
                "total_volume": 1473.9,
                "total_bill": 2094000,
                "formatted_volume": "1,473.9 m³",
                "formatted_bill": "Rp 2.094.000",
                "formatted_customers": "124/125",
                "status": "closed",
                "has_data": true
            },
            {
                "id": 2,
                "month": 2,
                "month_name": "Februari",
                "year": 2025,
                "period": "2025-02",
                "total_customers": 124,
                "total_volume": 1473.9,
                "total_bill": 2094000,
                "formatted_volume": "1,473.9 m³",
                "formatted_bill": "Rp 2.094.000",
                "formatted_customers": "124/125",
                "status": "closed",
                "has_data": true
            },
            {
                "id": 3,
                "month": 3,
                "month_name": "Maret",
                "year": 2025,
                "period": "2025-03",
                "total_customers": 124,
                "total_volume": 1473.9,
                "total_bill": 2094000,
                "formatted_volume": "1,473.9 m³",
                "formatted_bill": "Rp 2.094.000",
                "formatted_customers": "124/125",
                "status": "closed",
                "has_data": true
            },
            {
                "id": 4,
                "month": 4,
                "month_name": "April",
                "year": 2025,
                "period": "2025-04",
                "total_customers": 124,
                "total_volume": 1473.9,
                "total_bill": 2094000,
                "formatted_volume": "1,473.9 m³",
                "formatted_bill": "Rp 2.094.000",
                "formatted_customers": "124/125",
                "status": "closed",
                "has_data": true
            },
            {
                "id": 5,
                "month": 5,
                "month_name": "Mei",
                "year": 2025,
                "period": "2025-05",
                "total_customers": 124,
                "total_volume": 1473.9,
                "total_bill": 2094000,
                "formatted_volume": "1,473.9 m³",
                "formatted_bill": "Rp 2.094.000",
                "formatted_customers": "124/125",
                "status": "open",
                "has_data": true
            }
        ],
        "summary": {
            "recorder": {
                "name": "Fahmi Habibi",
                "id": 1
            },
            "yearly_totals": {
                "total_bill": 10470000,
                "total_volume": 7369.5,
                "formatted_total_bill": "Rp 10.470.000",
                "formatted_total_volume": "7,369.5 m³"
            }
        }
    }
}
```

### Error Response (500)
```json
{
    "status": "error",
    "message": "Failed to retrieve month list: Error description"
}
```

## Data Structure Explanation

### Monthly Data Array
- **id**: ID dari registered_month
- **month**: Nomor bulan (1-12)
- **month_name**: Nama bulan dalam bahasa Indonesia
- **year**: Tahun
- **period**: Format periode YYYY-MM
- **total_customers**: Jumlah pelanggan yang tercatat
- **total_volume**: Total volume air dalam m³
- **total_bill**: Total tagihan dalam rupiah
- **formatted_volume**: Volume yang sudah diformat dengan satuan
- **formatted_bill**: Tagihan yang sudah diformat mata uang Indonesia
- **formatted_customers**: Format pelanggan "aktual/maksimal"
- **status**: Status periode ('open' atau 'closed')
- **has_data**: Boolean indicator apakah bulan memiliki data

### Summary Section
- **recorder**: Informasi petugas yang login
- **yearly_totals**: Total keseluruhan untuk tahun yang dipilih

## UI Mapping

### Flutter ListView Implementation
```dart
class MonthListScreen extends StatelessWidget {
  final List<MonthData> monthlyData;
  final RecorderInfo recorder;
  final YearlyTotals yearlyTotals;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Catat Meter'),
        backgroundColor: Colors.white,
        foregroundColor: Colors.black,
        elevation: 0,
      ),
      body: Column(
        children: [
          // Year selector
          Container(
            margin: EdgeInsets.all(16),
            padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            decoration: BoxDecoration(
              color: Colors.blue.shade50,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text('Tahun\n2025'),
          ),
          
          // Monthly cards
          Expanded(
            child: ListView.builder(
              padding: EdgeInsets.all(16),
              itemCount: monthlyData.length,
              itemBuilder: (context, index) {
                final month = monthlyData[index];
                return _buildMonthCard(month);
              },
            ),
          ),
          
          // Bottom summary
          _buildBottomSummary(recorder, yearlyTotals),
        ],
      ),
    );
  }

  Widget _buildMonthCard(MonthData month) {
    return Container(
      margin: EdgeInsets.only(bottom: 12),
      padding: EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.shade200,
            blurRadius: 4,
            offset: Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          // Calendar icon
          Container(
            width: 50,
            height: 50,
            decoration: BoxDecoration(
              color: Colors.blue.shade50,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(
              Icons.calendar_month,
              color: Colors.blue.shade600,
            ),
          ),
          
          SizedBox(width: 16),
          
          // Month info
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  month.monthName,
                  style: TextStyle(
                    fontWeight: FontWeight.w600,
                    fontSize: 16,
                  ),
                ),
                Text(
                  month.year.toString(),
                  style: TextStyle(
                    color: Colors.grey.shade600,
                    fontSize: 12,
                  ),
                ),
                SizedBox(height: 4),
                Row(
                  children: [
                    Icon(
                      Icons.water_drop,
                      size: 16,
                      color: Colors.blue.shade400,
                    ),
                    SizedBox(width: 4),
                    Text(
                      month.formattedVolume,
                      style: TextStyle(
                        color: Colors.blue.shade600,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          
          // Customer info and amount
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Container(
                padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.green.shade50,
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  'Pelanggan\n${month.formattedCustomers}',
                  style: TextStyle(
                    color: Colors.green.shade600,
                    fontSize: 10,
                    fontWeight: FontWeight.w500,
                  ),
                  textAlign: TextAlign.center,
                ),
              ),
              SizedBox(height: 8),
              Text(
                month.formattedBill,
                style: TextStyle(
                  color: Colors.blue.shade600,
                  fontWeight: FontWeight.bold,
                  fontSize: 14,
                ),
              ),
            ],
          ),
          
          // Arrow
          SizedBox(width: 8),
          Icon(
            Icons.chevron_right,
            color: Colors.grey.shade400,
          ),
        ],
      ),
    );
  }

  Widget _buildBottomSummary(RecorderInfo recorder, YearlyTotals totals) {
    return Container(
      padding: EdgeInsets.all(16),
      child: Row(
        children: [
          // Recorder info
          Expanded(
            child: Container(
              padding: EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.blue.shade50,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(
                        Icons.person,
                        size: 16,
                        color: Colors.blue.shade600,
                      ),
                      SizedBox(width: 4),
                      Text(
                        'Petugas',
                        style: TextStyle(
                          color: Colors.blue.shade600,
                          fontSize: 12,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                  SizedBox(height: 4),
                  Text(
                    recorder.name,
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 14,
                    ),
                  ),
                ],
              ),
            ),
          ),
          
          SizedBox(width: 12),
          
          // Yearly total
          Expanded(
            child: Container(
              padding: EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.green.shade50,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(
                        Icons.money,
                        size: 16,
                        color: Colors.green.shade600,
                      ),
                      SizedBox(width: 4),
                      Text(
                        'Tahun 2025',
                        style: TextStyle(
                          color: Colors.green.shade600,
                          fontSize: 12,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                  SizedBox(height: 4),
                  Text(
                    totals.formattedTotalBill,
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 14,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
```

## Features

### Key Features
- ✅ **Year Filtering**: Dapat filter berdasarkan tahun
- ✅ **Indonesian Month Names**: Nama bulan dalam bahasa Indonesia
- ✅ **Formatted Data**: Data sudah diformat untuk tampilan (currency, volume)
- ✅ **Status Information**: Status periode open/closed
- ✅ **Yearly Summary**: Total keseluruhan di bagian bawah
- ✅ **Customer Ratio**: Menampilkan perbandingan aktual vs maksimal pelanggan

### Business Logic
- ✅ **PAM Isolation**: Hanya menampilkan data PAM user yang login
- ✅ **Period-based**: Berdasarkan registered_months (periode terdaftar)
- ✅ **Aggregate Data**: Data sudah diagregasi per bulan
- ✅ **User Context**: Informasi petugas yang login

This API provides clean, formatted data that matches exactly with the mobile UI requirements shown in the image.