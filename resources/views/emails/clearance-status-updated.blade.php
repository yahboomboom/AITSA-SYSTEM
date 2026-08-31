<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clearance Status Update</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f5f7; font-family: Arial, Helvetica, sans-serif; color:#0B3C5D;">
    <div style="max-width:480px; margin:0 auto; padding:32px 24px;">
        <div style="background:#0B3C5D; border-radius:12px 12px 0 0; padding:20px 24px;">
            <h1 style="margin:0; font-size:18px; color:#ffffff;">Asian Institute of Technology, Science and Arts</h1>
        </div>
        <div style="background:#ffffff; border-radius:0 0 12px 12px; padding:28px 24px; border:1px solid #e5e7eb; border-top:none;">
            <p style="font-size:14px;">Hi {{ $student->name }},</p>

            @if($status === 'Approved')
                <p style="font-size:14px; line-height:1.6;">
                    Good news — the <strong>{{ $office }}</strong> has approved your clearance item.
                </p>
            @else
                <p style="font-size:14px; line-height:1.6;">
                    The <strong>{{ $office }}</strong> has put your clearance on <strong>hold</strong> and needs
                    something from you before it can proceed.
                </p>
                @if($remarks)
                    <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:14px 16px; margin:16px 0;">
                        <p style="margin:0; font-size:13px; color:#991b1b;"><strong>Remarks:</strong> {{ $remarks }}</p>
                    </div>
                @endif
            @endif

            <p style="font-size:13px; line-height:1.6; color:#4b5563;">
                Log in to the AitsaGate Portal to view your full clearance progress.
            </p>

            <p style="font-size:13px; margin-top:24px;">— AITSA Registrar's Office</p>
        </div>
    </div>
</body>
</html>