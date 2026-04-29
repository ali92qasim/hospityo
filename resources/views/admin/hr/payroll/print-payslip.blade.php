<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - {{ $payslip->employee->full_name }} - {{ \Carbon\Carbon::create()->month($payslip->payrollRun->month)->format('F') }} {{ $payslip->payrollRun->year }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            padding: 20px;
            background: white;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #0066CC;
            padding-bottom: 15px;
        }

        .header h1 {
            color: #0066CC;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header p {
            color: #666;
            font-size: 12px;
        }

        .header .payslip-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .employee-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }

        .employee-info-section h3 {
            font-size: 13px;
            color: #0066CC;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .employee-info-section p {
            margin: 3px 0;
            font-size: 13px;
        }

        .employee-info-section p strong {
            display: inline-block;
            min-width: 110px;
            color: #555;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #0066CC;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #dee2e6;
            text-transform: uppercase;
        }

        .salary-grid {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }

        .salary-column {
            flex: 1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table thead {
            background: #f8f9fa;
        }

        table th {
            padding: 10px 12px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #dee2e6;
            font-size: 11px;
            text-transform: uppercase;
            color: #666;
        }

        table th.text-right {
            text-align: right;
        }

        table td {
            padding: 8px 12px;
            border-bottom: 1px solid #dee2e6;
            font-size: 13px;
        }

        table td.text-right {
            text-align: right;
        }

        table tfoot td {
            font-weight: bold;
            border-top: 2px solid #333;
            padding: 10px 12px;
        }

        .earnings-footer td {
            color: #28a745;
        }

        .deductions-footer td {
            color: #dc3545;
        }

        .net-salary-box {
            margin: 25px 0;
            padding: 15px 20px;
            background: #e8f4fd;
            border: 2px solid #0066CC;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .net-salary-box .label {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            text-transform: uppercase;
        }

        .net-salary-box .amount {
            font-size: 24px;
            font-weight: bold;
            color: #0066CC;
        }

        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }

        .signature-line {
            text-align: center;
            width: 200px;
        }

        .signature-line .line {
            border-top: 1px solid #333;
            margin-bottom: 5px;
        }

        .signature-line p {
            font-size: 12px;
            color: #666;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 11px;
            color: #999;
        }

        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }

        .print-btn {
            background: #0066CC;
            color: white;
            border: none;
            padding: 10px 25px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 8px;
        }

        .print-btn:hover {
            background: #0052a3;
        }

        .close-btn {
            background: #6b7280;
            color: white;
            border: none;
            padding: 10px 25px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
        }

        .close-btn:hover {
            background: #4b5563;
        }

        @media print {
            body {
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .container {
                max-width: 100%;
            }

            @page {
                margin: 15mm;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print">
            <button onclick="window.print()" class="print-btn">Print Payslip</button>
            <button onclick="window.close()" class="close-btn">Close</button>
        </div>

        <!-- Hospital Header -->
        <div class="header">
            @if(setting('hospital_logo'))
                <img src="{{ asset('storage/' . setting('hospital_logo')) }}" alt="Logo" style="max-height: 50px; margin-bottom: 8px;">
            @endif
            <h1>{{ setting('hospital_name', 'Hospital Management System') }}</h1>
            @if(setting('hospital_address'))
                <p>{{ setting('hospital_address') }}</p>
            @endif
            @if(setting('hospital_phone') || setting('hospital_email'))
                <p>
                    @if(setting('hospital_phone'))Phone: {{ setting('hospital_phone') }}@endif
                    @if(setting('hospital_phone') && setting('hospital_email')) | @endif
                    @if(setting('hospital_email'))Email: {{ setting('hospital_email') }}@endif
                </p>
            @endif
            <div class="payslip-title">Payslip for {{ \Carbon\Carbon::create()->month($payslip->payrollRun->month)->format('F') }} {{ $payslip->payrollRun->year }}</div>
        </div>

        <!-- Employee Details -->
        <div class="employee-info">
            <div class="employee-info-section">
                <h3>Employee Details</h3>
                <p><strong>Name:</strong> {{ $payslip->employee->full_name }}</p>
                <p><strong>Employee No:</strong> {{ $payslip->employee->employee_no }}</p>
                <p><strong>Department:</strong> {{ $payslip->employee->department->name ?? '—' }}</p>
                <p><strong>Designation:</strong> {{ $payslip->employee->designation->name ?? '—' }}</p>
            </div>
            <div class="employee-info-section">
                <h3>Pay Details</h3>
                <p><strong>Pay Period:</strong> {{ \Carbon\Carbon::create()->month($payslip->payrollRun->month)->format('F') }} {{ $payslip->payrollRun->year }}</p>
                <p><strong>Working Days:</strong> {{ $payslip->working_days ?? 0 }}</p>
                <p><strong>Present Days:</strong> {{ $payslip->present_days ?? 0 }}</p>
                <p><strong>Payment Method:</strong> {{ ucwords(str_replace('_', ' ', $payslip->payment_method ?? 'N/A')) }}</p>
            </div>
        </div>

        <!-- Earnings & Deductions -->
        <div class="salary-grid">
            <!-- Earnings -->
            <div class="salary-column">
                <div class="section-title">Earnings</div>
                <table>
                    <thead>
                        <tr>
                            <th>Component</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $earnings = $payslip->earnings_breakdown ?? [];
                        @endphp
                        @forelse($earnings as $item)
                        <tr>
                            <td>{{ $item['component'] ?? '—' }}</td>
                            <td class="text-right">{{ format_currency($item['amount'] ?? 0) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" style="text-align: center; color: #999;">No earnings data</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="earnings-footer">
                            <td>Total Earnings</td>
                            <td class="text-right">{{ format_currency($payslip->gross_salary) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Deductions -->
            <div class="salary-column">
                <div class="section-title">Deductions</div>
                <table>
                    <thead>
                        <tr>
                            <th>Component</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $deductions = $payslip->deductions_breakdown ?? [];
                        @endphp
                        @forelse($deductions as $item)
                        <tr>
                            <td>{{ $item['component'] ?? '—' }}</td>
                            <td class="text-right">{{ format_currency($item['amount'] ?? 0) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" style="text-align: center; color: #999;">No deductions</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="deductions-footer">
                            <td>Total Deductions</td>
                            <td class="text-right">{{ format_currency($payslip->total_deductions) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Net Salary -->
        <div class="net-salary-box">
            <span class="label">Net Salary</span>
            <span class="amount">{{ format_currency($payslip->net_salary) }}</span>
        </div>

        <!-- Signature Lines -->
        <div class="signature-section">
            <div class="signature-line">
                <div class="line"></div>
                <p>Employee Signature</p>
            </div>
            <div class="signature-line">
                <div class="line"></div>
                <p>Authorized Signature</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is a computer-generated payslip and does not require a physical signature.</p>
            <p>Generated on {{ now()->format('M d, Y h:i A') }}</p>
        </div>
    </div>

    <script>
        if (window.location.search.includes('auto=1')) {
            window.onload = function() { window.print(); };
        }
    </script>
</body>
</html>
