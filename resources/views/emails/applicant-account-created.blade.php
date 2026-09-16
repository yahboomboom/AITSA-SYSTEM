<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your AITSA Student Account Is Ready</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f5f7; font-family: Arial, Helvetica, sans-serif; color:#0B3C5D;">
    <div style="max-width:480px; margin:0 auto; padding:32px 24px;">
        <div style="background:#0B3C5D; border-radius:12px 12px 0 0; padding:20px 24px;">
            <h1 style="margin:0; font-size:18px; color:#ffffff;">Asian Institute of Technology, Science and Arts</h1>
        </div>
        <div style="background:#ffffff; border-radius:0 0 12px 12px; padding:28px 24px; border:1px solid #e5e7eb; border-top:none;">
            <p style="font-size:14px;">Hi {{ $student->name }},</p>
            <p style="font-size:14px; line-height:1.6;">
                Your reservation fee has been confirmed and your AITSA student account has been created automatically.
                Use the credentials below to sign in to the AitsaGate Portal.
            </p>

            <div style="background:#f4f5f7; border-radius:8px; padding:16px 20px; margin:20px 0;">
                <p style="margin:0 0 8px 0; font-size:13px;"><strong>Student ID:</strong> {{ $loginId }}</p>
                <p style="margin:0; font-size:13px;"><strong>Password:</strong> {{ $password }}</p>
            </div>

            <p style="font-size:13px; line-height:1.6; color:#4b5563;">
                For your security, please keep this email confidential and consider updating your password after your
                first login. If you did not apply for admission to AITSA, please disregard this message.
            </p>

            <p style="font-size:13px; margin-top:24px;">— AITSA Admissions</p>
        </div>
    </div>
</body>
</html>
