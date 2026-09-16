<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>AITSA Enrollment Agreement</title></head>
<body style="font-family: Arial, Helvetica, sans-serif; font-size: 12pt; color:#111;">
    <h2>Asian Institute of Technology, Science and Arts</h2>
    <h3>Enrollment & Reservation Agreement</h3>

    <p>This confirms that <strong>{{ $student->name }}</strong> ({{ $student->email }}) is applying for
        admission to the program <strong>{{ $student->major }}</strong> and, by signing below, agrees to:</p>

    <ul>
        <li>Pay the required reservation and tuition fees according to AITSA's published schedule.</li>
        <li>Comply with AITSA's academic and conduct policies.</li>
        <li>Understand that the reservation fee secures a slot but does not guarantee admission approval.</li>
    </ul>

    {{-- Replace this placeholder document with an actual DocuSign Template
         (see config('services.docusign.template_id')) once the Registrar's
         Office finalizes the official agreement wording. --}}

    <p style="margin-top:60px;">Signature: _______________________________</p>
</body>
</html>