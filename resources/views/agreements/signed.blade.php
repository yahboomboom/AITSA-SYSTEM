<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>AITSA Enrollment Agreement</title></head>
<body style="font-family: Arial, Helvetica, sans-serif; font-size: 12pt; color:#111;">
    @include('agreements.enrollment', ['student' => $student])

    <div style="margin-top:60px;">
        <img src="{{ $signaturePath }}" alt="Signature" style="max-width:250px; max-height:100px;">
        <p style="border-top:1px solid #333; width:250px; padding-top:4px;">Signature of {{ $student->name }}</p>
    </div>

    <table style="margin-top:30px; font-size:9pt; color:#555;">
        <tr><td style="padding:2px 12px 2px 0;">Signed at:</td><td>{{ $agreement->signed_at->format('F j, Y g:i A') }}</td></tr>
        <tr><td style="padding:2px 12px 2px 0;">IP address:</td><td>{{ $agreement->ip_address }}</td></tr>
        <tr><td style="padding:2px 12px 2px 0;">Device / browser:</td><td>{{ $agreement->user_agent }}</td></tr>
        <tr><td style="padding:2px 12px 2px 0;">Document hash (SHA-256):</td><td style="font-family: monospace;">{{ $agreement->agreement_hash }}</td></tr>
    </table>
</body>
</html>
