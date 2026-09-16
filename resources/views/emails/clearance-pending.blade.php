<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your AITSA Clearance Request Has Been Created</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f5f7; font-family: Arial, Helvetica, sans-serif; color:#0B3C5D;">
    <div style="max-width:480px; margin:0 auto; padding:32px 24px;">
        <div style="background:#0B3C5D; border-radius:12px 12px 0 0; padding:20px 24px;">
            <h1 style="margin:0; font-size:18px; color:#ffffff;">Asian Institute of Technology, Science and Arts</h1>
        </div>
        <div style="background:#ffffff; border-radius:0 0 12px 12px; padding:28px 24px; border:1px solid #e5e7eb; border-top:none;">
            <p style="font-size:14px;">Hi {{ $student->name }},</p>
            <p style="font-size:14px; line-height:1.6;">
                A clearance request has been created under your account. It is now waiting on sign-offs from
                the Department Chair, Cashier, Registrar, and the department offices listed in your clearance form.
            </p>
            <p style="font-size:13px; line-height:1.6; color:#4b5563;">
                You'll receive another email every time one of these offices approves or holds an item, so you
                always know where things stand. You can also check live progress anytime in the Clearance
                section of the AitsaGate Portal.
            </p>
            <p style="font-size:13px; margin-top:24px;">— AITSA Registrar's Office</p>
        </div>
    </div>
</body>
</html>