<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #10222D; }
        .letterhead { text-align: center; margin-bottom: 16px; }
        .letterhead img { height: 56px; margin-bottom: 4px; }
        .letterhead h2 { margin: 0; font-size: 16px; letter-spacing: 2px; }
        .letterhead p { margin: 0; font-size: 10px; color: #666; }
        h1 { text-align: center; font-size: 16px; margin: 16px 0 2px; }
        .sub { text-align: center; color: #666; margin-bottom: 16px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .info-table td { padding: 3px 6px; font-size: 12px; }
        .info-table td.label { color: #666; width: 15%; }
        table.grid { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.grid td, table.grid th { border: 1px solid #ccc; padding: 6px 8px; text-align: left; font-size: 11px; }
        table.grid th { background: #f2f2f2; }
        .text-right { text-align: right; }
        .total-row td { font-weight: bold; }
        .registered-box { display: inline-block; border: 2px solid #2A8042; color: #2A8042; padding: 8px 16px; font-weight: bold; text-align: center; }
        .pending-box { display: inline-block; border: 2px solid #999; color: #666; padding: 8px 16px; font-weight: bold; text-align: center; }
        .sig-block { display: inline-block; width: 40%; text-align: center; vertical-align: top; margin-top: 40px; }
        .sig-img { height: 50px; margin-bottom: 4px; }
        .sig-name { font-size: 11px; font-weight: bold; }
        .sig-line { border-top: 1px solid #333; margin-top: 4px; padding-top: 4px; font-size: 10px; }
        .footer-note { margin-top: 20px; font-size: 9px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="letterhead">
        <img src="{{ public_path('assets/bg_aitsa.jpg') }}">
        <h2>AITSA</h2>
        <p>Asian Institute of Technology, Science &amp; Arts</p>
    </div>

    <h1>Certificate of Registration</h1>
    <p class="sub">{{ $enrollment->school_year }} &mdash; Semester {{ $enrollment->semester }}</p>

    <table class="info-table">
        <tr>
            <td class="label">Name</td>
            <td><strong>{{ $enrollment->user->name }}</strong></td>
            <td class="label">Student No.</td>
            <td><strong>{{ $enrollment->user->login_id }}</strong></td>
        </tr>
        <tr>
            <td class="label">Program</td>
            <td><strong>{{ $enrollment->user->major }}</strong></td>
            <td class="label">Year Level</td>
            <td><strong>{{ $enrollment->user->year_level }}</strong></td>
        </tr>
    </table>

    <table class="grid">
        <tr>
            <th>Code</th>
            <th>Description</th>
            <th>Units</th>
            <th>Day</th>
            <th>Time</th>
            <th>Room</th>
            <th>Block</th>
        </tr>
        @foreach ($enrollment->sections as $section)
            <tr>
                <td>{{ $section->subject->code }}</td>
                <td>{{ $section->subject->title }}</td>
                <td>{{ $section->subject->units }}</td>
                <td>{{ is_array($section->days) ? implode('/', $section->days) : $section->days }}</td>
                <td>{{ $section->start_time }}&ndash;{{ $section->end_time }}</td>
                <td>{{ $section->room }}</td>
                <td>{{ $section->block_label }}</td>
            </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="2" class="text-right">Total Units</td>
            <td>{{ $enrollment->sections->sum(fn ($s) => $s->subject->units) }}</td>
            <td colspan="4"></td>
        </tr>
    </table>

    <table class="grid">
        <tr>
            <th>Assessment Breakdown</th>
            <th class="text-right">Amount</th>
        </tr>
        <tr><td>Tuition Fee</td><td class="text-right">{{ number_format($breakdown['tuition'], 2) }}</td></tr>
        <tr><td>Miscellaneous Fee</td><td class="text-right">{{ number_format($breakdown['misc'], 2) }}</td></tr>
        @if ($breakdown['reservation_fee'] > 0)
            <tr><td>Reservation Fee</td><td class="text-right">{{ number_format($breakdown['reservation_fee'], 2) }}</td></tr>
        @endif
        @if ($breakdown['discount_name'])
            <tr><td>Discount &mdash; {{ $breakdown['discount_name'] }} ({{ $breakdown['discount_percent'] }}%)</td><td class="text-right">-{{ number_format($breakdown['discount_amount'], 2) }}</td></tr>
        @endif
        <tr class="total-row"><td>Total Assessment</td><td class="text-right">{{ number_format($breakdown['assessment'], 2) }}</td></tr>
        <tr><td>Less: Amount Paid</td><td class="text-right">{{ number_format($breakdown['paid'], 2) }}</td></tr>
        <tr class="total-row"><td>Balance Due</td><td class="text-right">{{ number_format($breakdown['balance'], 2) }}</td></tr>
    </table>

    <div>
        @if ($clearance?->registrar_status === 'Approved')
            <div class="registered-box">
                REGISTERED<br>
                <span style="font-weight: normal; font-size: 10px;">{{ $clearance->registrar_signed_at?->format('M d, Y') }}</span>
            </div>
        @else
            <div class="pending-box">
                PENDING REGISTRAR SIGNATURE
            </div>
        @endif
    </div>

    <div class="sig-block">
        @if ($clearance?->registrarSignedBy?->signature_path)
            <img class="sig-img" src="{{ public_path('storage/' . $clearance->registrarSignedBy->signature_path) }}">
        @endif
        <div class="sig-name">{{ $clearance?->registrarSignedBy?->name ?? '' }}</div>
        <div class="sig-line">University Registrar</div>
    </div>

    <p class="footer-note">This is a system-generated Certificate of Registration and is not valid without the Registrar's signature.</p>
</body>
</html>
