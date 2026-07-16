<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Lab Report — {{ $settings['hospital_name'] }}</title>
    @vite(['resources/css/app.css'])
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            color: #111;
            margin: 0;
            min-height: 100vh;
        }
        .wrap {
            max-width: 440px;
            margin: 0 auto;
            padding: 32px 16px;
        }
        .card {
            background: #fff;
            border: 1px solid #d1d5db;
            padding: 24px 20px;
        }
        .letterhead {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #111;
        }
        .logo {
            width: 56px;
            height: 56px;
            object-fit: contain;
            margin: 0 auto 8px;
            display: block;
        }
        .hospital-name {
            font-size: 1.15rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .hospital-meta {
            font-size: 0.8rem;
            color: #555;
            margin-top: 4px;
        }
        .title {
            font-size: 1.05rem;
            font-weight: 700;
            margin: 0 0 6px;
        }
        .hint {
            font-size: 0.9rem;
            color: #4b5563;
            margin: 0 0 18px;
            line-height: 1.4;
        }
        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        input[type="text"],
        input[type="tel"] {
            width: 100%;
            border: 1px solid #111;
            padding: 10px 12px;
            font-size: 1rem;
            margin-bottom: 14px;
            box-sizing: border-box;
        }
        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 10px 12px;
            font-size: 0.875rem;
            margin-bottom: 14px;
        }
        button[type="submit"] {
            width: 100%;
            background: #111;
            color: #fff;
            border: none;
            padding: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
        }
        .order-ref {
            font-size: 0.8rem;
            color: #6b7280;
            text-align: center;
            margin-top: 16px;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="letterhead">
                @if($settings['hospital_logo'])
                    <img src="{{ asset('storage/' . $settings['hospital_logo']) }}" alt="{{ $settings['hospital_name'] }}" class="logo">
                @endif
                <div class="hospital-name">{{ $settings['hospital_name'] }}</div>
                @if($settings['hospital_address'])
                    <div class="hospital-meta">{{ $settings['hospital_address'] }}</div>
                @endif
                @if($settings['hospital_phone'] || $settings['hospital_email'])
                    <div class="hospital-meta">
                        @if($settings['hospital_phone'])Tel: {{ $settings['hospital_phone'] }}@endif
                        @if($settings['hospital_phone'] && $settings['hospital_email']) · @endif
                        @if($settings['hospital_email']){{ $settings['hospital_email'] }}@endif
                    </div>
                @endif
            </div>

            <h1 class="title">Laboratory Report Access</h1>
            <p class="hint">Enter your Patient Number and Mobile Number to view or download your report.</p>

            @if($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('lab-report.verify', $order->share_token) }}">
                @csrf
                <label for="patient_no">Patient Number</label>
                <input
                    type="text"
                    id="patient_no"
                    name="patient_no"
                    value="{{ old('patient_no') }}"
                    placeholder="e.g. P000123"
                    autocomplete="off"
                    required
                >

                <label for="phone">Mobile Number</label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    value="{{ old('phone') }}"
                    placeholder="e.g. 03001234567"
                    autocomplete="tel"
                    required
                >

                <button type="submit">View Report</button>
            </form>

            <p class="order-ref">Order reference: {{ $order->order_number }}</p>
        </div>
    </div>
</body>
</html>
