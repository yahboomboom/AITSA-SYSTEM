<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | Ledger & Payments</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    @include('partials.theme-fonts')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

    <div class="flex h-screen overflow-hidden">
        
        @include('partials.student-sidebar')

        <main class="flex-1 flex flex-col overflow-hidden relative">

            {{-- HEADER --}}
            <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
                <div class="flex items-center">
                    <button onclick="toggleMobileSidebar()" class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white mr-4">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>
                    <h2 class="font-heading text-2xl font-semibold leading-none text-brandNavy dark:text-slate-100">Payments</h2>
                </div>

                <div class="flex items-center space-x-4 border-l border-brandNavy/10 dark:border-slate-700 pl-4">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" class="w-9 h-9 rounded-full bg-lightBg dark:bg-darkBg text-brandNavy dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/10 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.student-status-badge')
                    @include('partials.profile-menu', ['roleLabel' => Auth::user()->major ?? 'BSIT'])
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6 lg:p-10 space-y-6">
                
                @if(session('success'))
                    <div class="p-4 rounded bg-brandGreen/10 border border-brandGreen/20 text-brandGreen text-sm">
                        <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="p-4 rounded bg-red-600/10 border border-red-600/20 text-red-600 text-sm">
                        <i class="fa-solid fa-circle-xmark mr-2"></i>{{ session('error') }}
                    </div>
                @endif

                @php
                    $paymentContext = [
                        'settled' => ($breakdown['balance'] ?? 0) <= 0,
                        'hasPendingGateway' => $hasPendingGateway,
                        'breakdown' => $breakdown,
                        'history' => $history->map(fn ($row) => [
                            'referenceNo' => $row->reference_no,
                            'gatewayLabel' => $row->gateway === 'paymongo' ? 'PayMongo (online)' : 'Cashier window',
                            'createdAtFormatted' => $row->created_at->format('M d, Y g:i A'),
                            'amount' => $row->amount,
                            'status' => $row->status,
                        ])->all(),
                        'cashierCleared' => isset($clearance) && $clearance->cashier_status === 'Approved',
                        'registrarCleared' => isset($clearance) && $clearance->registrar_status === 'Approved',
                        'chairCleared' => isset($clearance) && $clearance->chair_status === 'Approved',
                    ];
                @endphp

                <div id="payment-root"
                     data-context="{{ json_encode($paymentContext) }}"
                     data-csrf-token="{{ csrf_token() }}"
                     data-checkout-url="{{ route('ledger.checkout') }}"
                     data-verify-url="{{ route('ledger.verify') }}">
                    <p class="text-sm text-slate-500">Loading…</p>
                </div>

            </div>
        </main>
    </div>

@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/payment-app.jsx')
</body>
</html>