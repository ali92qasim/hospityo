<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill #{{ $bill->bill_number }}</title>
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
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #0066CC;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #0066CC;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .header p {
            color: #666;
            font-size: 12px;
        }
        
        .bill-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .bill-info-section {
            flex: 1;
        }
        
        .bill-info-section h3 {
            font-size: 14px;
            color: #0066CC;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .bill-info-section p {
            margin: 5px 0;
            font-size: 13px;
        }
        
        .bill-number {
            text-align: right;
        }
        
        .bill-number h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .status-partial {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-pending {
            background: #f8d7da;
            color: #721c24;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table thead {
            background: #f8f9fa;
        }
        
        table th {
            padding: 12px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #dee2e6;
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
        }
        
        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .text-right {
            text-align: right;
        }
        
        .totals {
            margin-top: 20px;
            float: right;
            width: 300px;
        }
        
        .totals table {
            margin-bottom: 0;
        }
        
        .totals table td {
            padding: 8px 12px;
            border: none;
        }
        
        .totals .total-row {
            font-weight: bold;
            font-size: 16px;
            border-top: 2px solid #333;
        }
        
        .totals .paid-row {
            color: #28a745;
        }
        
        .totals .due-row {
            color: #dc3545;
            font-weight: bold;
        }
        
        .payment-history {
            clear: both;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
        }
        
        .payment-history h3 {
            font-size: 16px;
            color: #0066CC;
            margin-bottom: 15px;
        }
        
        .payment-item {
            padding: 10px;
            background: #f8f9fa;
            margin-bottom: 10px;
            border-left: 4px solid #28a745;
        }
        
        .payment-item p {
            margin: 3px 0;
            font-size: 13px;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        
        .notes {
            margin-top: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #0066CC;
        }
        
        .notes h4 {
            font-size: 14px;
            margin-bottom: 8px;
            color: #0066CC;
        }
        
        .notes p {
            font-size: 13px;
            color: #666;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
            
            @page {
                margin: 20mm;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>{{ config('app.name', 'Hospital Management System') }}</h1>
            <p>{{ cache('settings.hospital_address', '') }}</p>
            <p>Phone: {{ cache('settings.hospital_phone', '') }} | Email: {{ cache('settings.hospital_email', '') }}</p>
        </div>

        <!-- Bill Info -->
        <div class="bill-info">
            <div class="bill-info-section">
                <h3>Bill To</h3>
                <p><strong>{{ $bill->patient->name }}</strong></p>
                <p>Patient ID: {{ $bill->patient->patient_no }}</p>
                <p>Phone: {{ $bill->patient->phone }}</p>
                @if($bill->patient->present_address)
                    <p>{{ $bill->patient->present_address }}</p>
                @endif
            </div>
            
            <div class="bill-number">
                <h2>{{ $bill->bill_number }}</h2>
                <p>Date: {{ $bill->bill_date->format('M d, Y') }}</p>
                <p>Type: <strong>{{ strtoupper($bill->bill_type) }}</strong></p>
                <p><span class="status-badge status-{{ $bill->status }}">{{ ucfirst($bill->status) }}</span></p>
            </div>
        </div>

        <!-- Bill Items -->
        <table>
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th style="width: 15%;" class="text-right">Quantity</th>
                    <th style="width: 17.5%;" class="text-right">Unit Price</th>
                    <th style="width: 17.5%;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bill->billItems as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">₨{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">₨{{ number_format($item->total_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <table>
                <tr>
                    <td>Subtotal:</td>
                    <td class="text-right">₨{{ number_format($bill->subtotal, 2) }}</td>
                </tr>
                @if($bill->tax_amount > 0)
                <tr>
                    <td>Tax:</td>
                    <td class="text-right">₨{{ number_format($bill->tax_amount, 2) }}</td>
                </tr>
                @endif
                @if($bill->discount_amount > 0)
                <tr>
                    <td>Discount:</td>
                    <td class="text-right">-₨{{ number_format($bill->discount_amount, 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td>Total Amount:</td>
                    <td class="text-right">₨{{ number_format($bill->total_amount, 2) }}</td>
                </tr>
                <tr class="paid-row">
                    <td>Paid Amount:</td>
                    <td class="text-right">₨{{ number_format($bill->paid_amount, 2) }}</td>
                </tr>
                @if($bill->due_amount > 0)
                <tr class="due-row">
                    <td>Amount Due:</td>
                    <td class="text-right">₨{{ number_format($bill->due_amount, 2) }}</td>
                </tr>
                @endif
            </table>
        </div>

        <!-- Payment History -->
        @if($bill->payments->count() > 0)
        <div class="payment-history">
            <h3>Payment History</h3>
            @foreach($bill->payments as $payment)
            <div class="payment-item">
                <p><strong>₨{{ number_format($payment->amount, 2) }}</strong> - {{ $payment->payment_date->format('M d, Y') }}</p>
                <p>Method: {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                @if($payment->reference_number)
                    | Ref: {{ $payment->reference_number }}
                @endif
                </p>
                <p>Received by: {{ $payment->receivedBy->name }}</p>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Notes -->
        @if($bill->notes)
        <div class="notes">
            <h4>Notes</h4>
            <p>{{ $bill->notes }}</p>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>This is a computer-generated bill and does not require a signature.</p>
            <p>Generated on {{ now()->format('M d, Y h:i A') }} by {{ $bill->createdBy->name }}</p>
        </div>
    </div>

    <script>
        // Auto print when page loads
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
