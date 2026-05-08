<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Share Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 20px; }
        h1 { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
        h2 { font-size: 14px; font-weight: bold; margin: 16px 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background: #f3f4f6; text-align: left; padding: 6px 10px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #e5e7eb; }
        td { padding: 6px 10px; border-bottom: 1px solid #e5e7eb; }
        .header { border-bottom: 2px solid #333; padding-bottom: 12px; margin-bottom: 16px; }
        .filter-summary { font-size: 11px; color: #666; margin-bottom: 16px; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 10px; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-settled { background: #d1fae5; color: #065f46; }
        .badge-voided { background: #fee2e2; color: #991b1b; }
        @media print { body { margin: 0; } }
    </style>
</head>
<body>

<!-- Hospital header -->
<div class="header">
    @if(!empty($settings['hospital_logo']))
        <img src="{{ asset('storage/' . $settings['hospital_logo']) }}" alt="Logo" style="height:40px; margin-bottom:8px;">
    @endif
    <h1>{{ $settings['hospital_name'] ?? config('app.name') }}</h1>
    @if(!empty($settings['hospital_address']))
        <p style="font-size:11px; color:#666;">{{ $settings['hospital_address'] }}</p>
    @endif
</div>

<!-- Report title -->
<h1>Doctor Share Report</h1>

<!-- Filter summary -->
<div class="filter-summary">
    @if(request('doctor_id') && $doctors->find(request('doctor_id')))
        Doctor: {{ $doctors->find(request('doctor_id'))->name }} &nbsp;|&nbsp;
    @endif
    @if(request('date_from')) From: {{ request('date_from') }} &nbsp;|&nbsp; @endif
    @if(request('date_to')) To: {{ request('date_to') }} &nbsp;|&nbsp; @endif
    @if(request('bill_type')) Bill Type: {{ ucfirst(request('bill_type')) }} @endif
    Printed: {{ now()->format('M d, Y H:i') }}
</div>

<!-- Summary table -->
<h2>Summary by Doctor</h2>
<table>
    <thead>
        <tr>
            <th>Doctor</th>
            <th>Total Earned</th>
            <th>Total Collected</th>
            <th>Total Pending</th>
            <th>Total Settled</th>
        </tr>
    </thead>
    <tbody>
        @forelse($summary as $row)
        <tr>
            <td>{{ $row->doctor->name ?? '— Global —' }}</td>
            <td>{{ currency_symbol() }}{{ number_format($row->total_earned, 2) }}</td>
            <td>{{ currency_symbol() }}{{ number_format($row->total_collected, 2) }}</td>
            <td>{{ currency_symbol() }}{{ number_format($row->total_pending, 2) }}</td>
            <td>{{ currency_symbol() }}{{ number_format($row->total_settled, 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center; color:#999;">No data.</td></tr>
        @endforelse
    </tbody>
</table>

<!-- Detail table -->
<h2>Share Item Detail</h2>
<table>
    <thead>
        <tr>
            <th>Doctor</th>
            <th>Bill #</th>
            <th>Bill Date</th>
            <th>Base Amount</th>
            <th>Share Amount</th>
            <th>Collected</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($details as $item)
        <tr>
            <td>{{ $item->doctor->name ?? '—' }}</td>
            <td>{{ $item->bill->bill_number ?? '—' }}</td>
            <td>{{ $item->bill?->bill_date?->format('M d, Y') ?? '—' }}</td>
            <td>{{ currency_symbol() }}{{ number_format($item->base_amount, 2) }}</td>
            <td>{{ currency_symbol() }}{{ number_format($item->share_amount, 2) }}</td>
            <td>{{ currency_symbol() }}{{ number_format($item->allocations_sum_amount ?? 0, 2) }}</td>
            <td><span class="badge badge-{{ $item->status }}">{{ ucfirst($item->status) }}</span></td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center; color:#999;">No items.</td></tr>
        @endforelse
    </tbody>
</table>

<script>window.print();</script>
</body>
</html>
