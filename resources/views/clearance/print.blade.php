<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #0D1B2A; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 4px; }
        .sub { text-align: center; color: #666; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        td, th { border: 1px solid #ccc; padding: 8px; text-align: left; }

        .sig-block { display: inline-block; width: 30%; text-align: center; vertical-align: top; margin-right: 2%; }
        .sig-img { height: 60px; max-width: 90%; margin-bottom: 4px; }
        .sig-name { font-size: 11px; font-weight: bold; margin-bottom: 4px; }
        .sig-line { border-top: 1px solid #333; margin-top: 4px; }
        .sig-dept { padding-top: 4px; font-size: 10px; color: #555; }
    </style>
</head>
<body>
    <h1>Certificate of Clearance</h1>
    <p class="sub">{{ $clearance->user->name }} — {{ $clearance->user->login_id }} — {{ $clearance->user->major }}</p>

    <table>
        <tr><th>Office</th><th>Status</th></tr>
        <tr><td>Accounting Office</td><td>{{ $clearance->cashier_status }}</td></tr>
        <tr><td>Registrar</td><td>{{ $clearance->registrar_status }}</td></tr>
        <tr><td>Department Chair</td><td>{{ $clearance->chair_status }}</td></tr>
        @foreach ($clearance->items as $item)
            <tr><td>{{ $item->department->name }}</td><td>{{ $item->status }}</td></tr>
        @endforeach
    </table>

    <div>
        <div class="sig-block">
            @if ($clearance->cashierSignedBy?->signature_path)
                <img class="sig-img" src="{{ storage_path('app/public/' . $clearance->cashierSignedBy->signature_path) }}">
            @endif
            <div class="sig-name">{{ $clearance->cashierSignedBy->name ?? '' }}</div>
            <div class="sig-line"></div>
            <div class="sig-dept">
                Accounting Office{{ $clearance->cashier_signed_at ? ' — ' . $clearance->cashier_signed_at->format('M d, Y') : '' }}
            </div>
        </div>

        <div class="sig-block">
            @if ($clearance->registrarSignedBy?->signature_path)
                <img class="sig-img" src="{{ storage_path('app/public/' . $clearance->registrarSignedBy->signature_path) }}">
            @endif
            <div class="sig-name">{{ $clearance->registrarSignedBy->name ?? '' }}</div>
            <div class="sig-line"></div>
            <div class="sig-dept">
                Registrar{{ $clearance->registrar_signed_at ? ' — ' . $clearance->registrar_signed_at->format('M d, Y') : '' }}
            </div>
        </div>

        <div class="sig-block">
            @if ($clearance->chairSignedBy?->signature_path)
                <img class="sig-img" src="{{ storage_path('app/public/' . $clearance->chairSignedBy->signature_path) }}">
            @endif
            <div class="sig-name">{{ $clearance->chairSignedBy->name ?? '' }}</div>
            <div class="sig-line"></div>
            <div class="sig-dept">
                Department Chair{{ $clearance->chair_signed_at ? ' — ' . $clearance->chair_signed_at->format('M d, Y') : '' }}
            </div>
        </div>
    </div>
</body>
</html>