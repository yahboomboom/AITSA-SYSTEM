<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to Payment | AITSA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    {{-- Auto-continue to PayMongo after a short pause, so it reads as one
         smooth "pop out to payment" step rather than an abrupt page jump. --}}
    <meta http-equiv="refresh" content="2;url={{ $checkoutUrl }}">
</head>
<body class="bg-brandNavy min-h-screen flex items-center justify-center p-4 font-sans">
    <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-8 text-center">
        <div class="w-16 h-16 rounded-full bg-brandGreen/10 text-brandGreen flex items-center justify-center mx-auto mb-5 text-2xl">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <h1 class="text-lg font-extrabold text-brandNavy mb-1">Application Received!</h1>
        <p class="text-sm text-brandNavy/60 mb-6">
            Taking you to a secure page to pay your ₱{{ number_format($reservationFee) }} reservation fee for
            <span class="font-bold text-brandNavy">{{ $programName }}</span>.
        </p>

        <div class="flex items-center justify-center gap-2 text-brandNavy/50 text-xs font-semibold mb-6">
            <i class="fa-solid fa-spinner fa-spin"></i>
            <span>Redirecting…</span>
        </div>

        <a href="{{ $checkoutUrl }}" class="block w-full py-3 rounded-xl bg-brandNavy text-white font-bold text-sm hover:opacity-90 transition-opacity">
            Continue to Payment Now
        </a>
        <p class="text-[11px] text-brandNavy/40 mt-4">You'll be redirected to PayMongo's secure payment page.</p>
    </div>
</body>
</html>