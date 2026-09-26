<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Staff | Billing Configuration</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    @include('partials.theme-fonts')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

    <div class="flex h-screen overflow-hidden">

        @include('partials.cashier-sidebar')

        <main class="flex-1 flex flex-col overflow-hidden relative">
            <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 flex-shrink-0 z-10 transition-colors duration-300">
                <div class="flex items-center gap-3">
                    <button onclick="toggleMobileSidebar()" aria-label="Open sidebar menu" class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                    <span class="font-heading text-2xl font-semibold leading-none text-brandNavy dark:text-slate-200">Cashier operations</span>
                </div>
                <div class="flex items-center gap-3">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" aria-label="Toggle dark mode" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', ['roleLabel' => 'Cashier Staff'])
                </div>
            </header>
            <div class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-5">

                <div>
                    <h1 class="font-heading text-lg font-semibold text-brandNavy dark:text-white">Billing Configuration</h1>
                    <p class="text-sm text-brandNavy/50 dark:text-slate-400">Fee rates, discount types, and per-student discount assignment.</p>
                </div>

                @if(session('success'))
                    <div class="p-4 rounded bg-brandGreen/10 border border-brandGreen/20 text-brandGreen text-sm">
                        <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
                    </div>
                @endif

                @php
                    $context = [
                        'feeRates' => [
                            'tuitionPerUnit' => $tuitionPerUnit,
                            'miscFee' => $miscFee,
                            'reservationFee' => $reservationFee,
                            'tesdaTuitionFee' => $tesdaTuitionFee,
                            'downPaymentPercent' => $downPaymentPercent,
                        ],
                        'discountTypes' => $discountTypes->map(fn ($type) => [
                            'id' => $type->id,
                            'name' => $type->name,
                            'percent' => $type->percent,
                            'isActive' => $type->is_active,
                            'studentsCount' => $type->students_count,
                            'toggleUrl' => route('cashier.billing.discounts.toggle', $type),
                        ])->values(),
                        'students' => $students->map(fn ($student) => [
                            'id' => $student->id,
                            'name' => $student->name,
                            'loginId' => $student->login_id,
                            'major' => $student->major,
                            'yearLevel' => $student->year_level,
                            'discountTypeId' => $student->discount_type_id,
                            'assignUrl' => route('cashier.billing.assign', $student),
                        ])->values(),
                        'errors' => array_map(fn ($m) => $m[0], $errors->getMessages()),
                        'old' => [
                            'tuition_per_unit' => old('tuition_per_unit'),
                            'misc_fee' => old('misc_fee'),
                            'reservation_fee' => old('reservation_fee'),
                            'tesda_tuition_fee' => old('tesda_tuition_fee'),
                            'name' => old('name'),
                            'percent' => old('percent'),
                        ],
                    ];
                @endphp

                <div
                    id="cashier-billing-root"
                    data-context="{{ json_encode($context) }}"
                    data-csrf-token="{{ csrf_token() }}"
                    data-fees-url="{{ route('cashier.billing.fees') }}"
                    data-discounts-url="{{ route('cashier.billing.discounts') }}"
                >
                    <p class="text-sm text-slate-500">Loading…</p>
                </div>

            </div>
        </main>
    </div>

@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/cashier-billing-app.jsx')
</body>
</html>
