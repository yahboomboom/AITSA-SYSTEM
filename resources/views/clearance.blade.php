<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | Enrollment Clearance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brandNavy: '#0B3C5D', brandGreen: '#1D7A46', brandGold: '#E2A700',
                        darkBg: '#121212', lightBg: '#EFF3F7', panelDark: '#1E1E1E',
                    }
                }
            }
        }
    </script>
    <script>
        function updateThemeIcon() {
            const icon = document.getElementById('theme-icon');
            if (icon) {
                const isDark = document.documentElement.classList.contains('dark');
                icon.className = isDark ? 'fa-solid fa-sun text-sm' : 'fa-solid fa-moon text-sm';
            }
        }
        function initializeTheme() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.classList.toggle('dark', theme === 'dark');
        }
        function toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateThemeIcon();
        }
        initializeTheme();
        document.addEventListener('DOMContentLoaded', updateThemeIcon);
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .drop-zone {
            border: 2px dashed rgba(11,60,93,0.2);
            transition: border-color 0.2s, background-color 0.2s;
        }
        .dark .drop-zone {
            border-color: rgba(148,163,184,0.2);
        }
        .drop-zone.dragover {
            border-color: #1D7A46;
            background-color: rgba(29,122,70,0.05);
        }
        .dark .drop-zone.dragover {
            border-color: #E2A700;
            background-color: rgba(226,167,0,0.05);
        }
        .file-chip {
            animation: chipIn 0.2s cubic-bezier(0.16,1,0.3,1) forwards;
        }
        @keyframes chipIn {
            from { opacity: 0; transform: translateY(6px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .modal-enter { animation: modalIn 0.3s cubic-bezier(0.16,1,0.3,1) forwards; }
        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.96) translateY(10px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }
    </style>
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

<div class="flex h-screen overflow-hidden">

    {{-- SIDEBAR --}}
    <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-panelDark border-r border-brandNavy/10 dark:border-slate-800 transition-colors duration-300">
        <div class="h-20 flex items-center px-8 border-b border-brandNavy/10 dark:border-slate-800">
            <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-8 h-8 rounded-lg object-cover mr-3">
            <h1 class="text-xl font-black tracking-tight text-brandNavy dark:text-white">AITSA</h1>
        </div>
        <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-2">
            <p class="px-4 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest mb-2">Main Menu</p>
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm transition-colors duration-300 {{ Route::is('dashboard') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }}"><span>Dashboard</span>
            </a>
            <a href="{{ route('clearance') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm transition-colors duration-300 {{ Route::is('clearance') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }}"><span>Clearance Routing</span>
            </a>
            <a href="{{ route('enrollment') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm transition-colors duration-300 {{ Route::is('enrollment') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }}"><span>Enrollment System</span>
            </a>
            <a href="{{ route('ledger') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm transition-colors duration-300 {{ Route::is('ledger') ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }}"><span>Ledger & Payments</span>
            </a>
            <a href="/cor" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm transition-colors duration-300 text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium"><span>Schedule</span>
            </a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col overflow-hidden relative">

        {{-- HEADER --}}
        <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
            <div class="flex items-center gap-4">
                <button class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div>
                    <h2 class="text-base font-bold text-brandNavy dark:text-slate-100 uppercase tracking-wider">{{ Auth::user()->name ?? 'Student' }}</h2>
                    <p class="text-[11px] font-mono text-brandNavy/60 dark:text-slate-400">Student No: {{ Auth::user()->login_id ?? '---' }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="flex items-center gap-4 border-l border-brandNavy/10 dark:border-slate-700 pl-4">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" class="w-9 h-9 rounded-full bg-lightBg dark:bg-darkBg text-brandNavy dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/10 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', ['roleLabel' => Auth::user()->major ?? 'BSIT - Web Development'])
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 lg:p-10 space-y-6">

            @if(session('success'))
                <div class="p-4 rounded-xl bg-brandGreen/10 border border-brandGreen/20 text-brandGreen font-bold text-xs">
                    <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 rounded-xl bg-red-600/10 border border-red-600/20 text-red-600 font-bold text-xs">
                    <i class="fa-solid fa-circle-xmark mr-2"></i>{{ $errors->first() }}
                </div>
            @endif

            {{-- PAGE TITLE + ENROLLMENT SHORTCUT --}}
            <div class="text-center py-2 relative">
                <h1 class="text-2xl font-bold tracking-tight text-brandNavy dark:text-white">Enrollment Clearance</h1>
                <div id="enrollmentShortcut" class="hidden justify-center mt-3 animate-bounce">
                    <a href="{{ route('enrollment') }}" class="inline-flex items-center space-x-2 text-xs font-black bg-brandGreen text-white px-5 py-2.5 rounded-xl shadow-lg hover:bg-emerald-600 transition-all">
                        <i class="fa-solid fa-rocket"></i>
                        <span>CONGRATULATIONS! CLICK HERE TO PROCEED TO ENROLLMENT</span>
                    </a>
                </div>
            </div>

            @php
                $isCleared = isset($clearance) && (
                    $clearance->cashier_status === 'Approved' &&
                    $clearance->registrar_status === 'Approved' &&
                    $clearance->admission_status === 'Approved' &&
                    $clearance->chair_status === 'Approved' &&
                    $clearance->allItemsApproved()
                );
                $registrarCleared  = isset($clearance) && $clearance->registrar_status === 'Approved';
                $cashierCleared    = isset($clearance) && $clearance->cashier_status === 'Approved';
                $chairCleared      = isset($clearance) && $clearance->chair_status === 'Approved';
                $hasSubmission     = isset($submission) && $submission !== null;
                $submissionPending = $hasSubmission && ($submission->status ?? '') === 'pending';
            @endphp

            {{-- MASTER STATUS BADGE --}}
            <div id="masterBadgeCard" class="bg-white dark:bg-panelDark border @if($isCleared) border-brandGreen/30 @else border-brandGold/20 @endif rounded-xl p-6 flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-8">
                <div id="masterStatusBadge" class="flex flex-col items-center text-center justify-center md:border-r border-brandNavy/10 dark:border-slate-800 pr-0 md:pr-8 flex-shrink-0 w-full md:w-44">
                    @if($isCleared)
                        <div class="w-12 h-12 rounded-full bg-brandGreen/10 text-brandGreen flex items-center justify-center text-2xl mb-2">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <span class="text-sm font-black text-brandGreen uppercase tracking-wider">Officially Cleared</span>
                    @else
                        <div class="w-12 h-12 rounded-full bg-brandGold/10 text-brandGold flex items-center justify-center text-2xl mb-2 animate-pulse">
                            <i class="fa-solid fa-circle-exclamation"></i>
                        </div>
                        <span class="text-sm font-black text-brandGold uppercase tracking-wider">Pending Sign-off</span>
                    @endif
                </div>
                <div class="flex-1 text-xs space-y-2 w-full">
                    <div class="flex items-start space-x-2">
                        <i id="checkIconAccounting" class="fa-solid @if($cashierCleared) fa-circle-check text-brandGreen @else fa-circle-xmark text-brandGold @endif mt-0.5"></i>
                        <p class="text-brandNavy/70 dark:text-slate-400">Accounting Office — Balance assessment verification.</p>
                    </div>
                    <div class="flex items-start space-x-2">
                        <i id="checkIconRegistrar" class="fa-solid @if($registrarCleared) fa-circle-check text-brandGreen @else fa-circle-xmark text-red-500 @endif mt-0.5"></i>
                        <p class="text-brandNavy/70 dark:text-slate-400">Registrar — On-hold administrative document verification.</p>
                    </div>
                    <div class="flex items-start space-x-2">
                        <i id="checkIconChair" class="fa-solid @if($chairCleared) fa-circle-check text-brandGreen @else fa-circle-xmark text-brandGold @endif mt-0.5"></i>
                        <p class="text-brandNavy/70 dark:text-slate-400">Department Head — Curriculum evaluation sign-off.</p>
                    </div>
                    @foreach($clearance->items as $item)
                        <div class="flex items-start space-x-2">
                            <i class="fa-solid @if($item->status === 'Approved') fa-circle-check text-brandGreen @elseif($item->status === 'Hold') fa-circle-xmark text-red-500 @else fa-circle-xmark text-brandGold @endif mt-0.5"></i>
                            <p class="text-brandNavy/70 dark:text-slate-400">
                                {{ $item->department->name }} —
                                @if($item->status === 'Approved') Cleared.
                                @elseif($item->status === 'Hold') On hold: {{ $item->remarks }}
                                @else Pending review.
                                @endif
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($clearance->remarks)
                <div class="p-4 rounded-xl bg-red-600/10 border border-red-600/20 text-red-600 text-xs">
                    <i class="fa-solid fa-triangle-exclamation mr-2"></i><strong>Remarks:</strong> {{ $clearance->remarks }}
                </div>
            @endif

            {{-- CLEARANCE DETAILS --}}
            <div class="space-y-6">
                <div class="flex items-center justify-between border-b border-brandNavy/5 dark:border-slate-800 pb-2">
                    <p class="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">Clearance Details</p>
                    <p class="text-[10px] text-brandNavy/50 dark:text-slate-400 italic">Updates reflect immediately upon administrative action.</p>
                </div>

                {{-- ACCOUNTING CARD --}}
                <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
                    <div class="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800 flex justify-between items-center text-xs">
                        <span class="font-bold text-brandNavy dark:text-slate-300"><i class="fa-solid fa-credit-card mr-2"></i>Account Status</span>
                        <span class="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">Accounting Office</span>
                    </div>
                    <div id="accountingCardBody" class="p-6 space-y-5">
                        @if($cashierCleared)
                            <div class="text-center space-y-1">
                                <h3 class="text-xl font-black text-brandGreen">Balance: ₱ 0.00</h3>
                                <p class="text-xs text-brandNavy/60 dark:text-slate-400">Your account balance has been fully settled.</p>
                            </div>
                        @else
                            <div class="text-center space-y-2">
                                <h3 class="text-xl font-black text-brandGold">Balance: Pending Assessment</h3>
                                <p class="text-xs text-brandNavy/60 dark:text-slate-400">You have pending tuition or institutional fee obligations. Settle the items below to complete your clearance.</p>
                            </div>
                        @endif

                        {{-- Clearance Action Items --}}
                        <div class="border border-brandNavy/8 dark:border-slate-700 rounded-xl overflow-hidden">
                            <div class="px-4 py-2.5 bg-lightBg dark:bg-slate-800/50 border-b border-brandNavy/8 dark:border-slate-700 flex items-center justify-between">
                                <span class="text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider">Clearance Action Items</span>
                                <span class="text-[9px] font-black text-brandNavy/40 dark:text-slate-500">A.Y. 2025–2026</span>
                            </div>
                            <div class="divide-y divide-brandNavy/5 dark:divide-slate-800">
                                @php
                                $actionItems = [
                                    ['label' => 'Reservation Fee',          'paid' => true,  'amount' => '500.00'],
                                    ['label' => 'Tuition Fee — 1st Payment','paid' => true,  'amount' => '3,500.00'],
                                    ['label' => 'Tuition Fee — 2nd Payment','paid' => false, 'amount' => '3,500.00'],
                                    ['label' => 'Tuition Fee — 3rd Payment','paid' => false, 'amount' => '3,500.00'],
                                    ['label' => 'Tuition Fee — 4th Payment','paid' => false, 'amount' => '3,500.00'],
                                    ['label' => 'Tuition Fee — 5th Payment','paid' => false, 'amount' => '3,500.00'],
                                    ['label' => 'Acquaintance Party',       'paid' => true,  'amount' => '150.00'],
                                    ['label' => 'SportsFest',               'paid' => false, 'amount' => '200.00'],
                                    ['label' => 'Grad Ball (Graduating)',    'paid' => false, 'amount' => '500.00'],
                                ];
                                @endphp
                                @foreach($actionItems as $item)
                                <div class="flex items-center justify-between px-4 py-3 text-xs">
                                    <div class="flex items-center gap-3">
                                        @if($item['paid'])
                                            <div class="w-5 h-5 rounded-full bg-brandGreen/10 flex items-center justify-center flex-shrink-0">
                                            </div>
                                            <span class="font-medium text-brandNavy/60 dark:text-slate-400 line-through">{{ $item['label'] }}</span>
                                        @else
                                            <div class="w-5 h-5 rounded-full bg-brandGold/10 border border-brandGold/30 flex items-center justify-center flex-shrink-0">
                                            </div>
                                            <span class="font-semibold text-brandNavy dark:text-slate-200">{{ $item['label'] }}</span>
                                        @endif
                                    </div>
                                    <div class="text-right flex-shrink-0 ml-4">
                                        @if($item['paid'])
                                            <span class="text-[10px] font-bold text-brandGreen">Settled</span>
                                        @else
                                            <span class="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400">? {{ $item['amount'] }}</span>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        @if(!$cashierCleared)
                            <div class="flex justify-end">
                                <a href="{{ route('ledger') }}" class="inline-flex items-center gap-2 text-xs font-bold bg-brandGold hover:bg-yellow-500 text-brandNavy px-5 py-2.5 rounded-xl border border-brandGold/30 transition-all shadow-sm">
                                    <i class="fa-solid fa-wallet"></i>Go to Payment
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- REGISTRAR CARD — with submission flow --}}
                <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
                    <div class="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800 flex justify-between items-center text-xs">
                        <span class="font-bold text-brandNavy dark:text-slate-300"><i class="fa-solid fa-ban mr-2"></i>On-Hold Record Status</span>
                        <span class="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">Registrar</span>
                    </div>
                    <div id="registrarCardBody" class="p-6 space-y-4">
                        @if($registrarCleared)
                            <div class="text-center space-y-2">
                                <h3 class="text-base font-bold text-brandGreen">Cleared</h3>
                                <p class="text-xs text-brandNavy/60 dark:text-slate-400">No on-hold records with this department.</p>
                            </div>
                        @else
                            {{-- Hold detail --}}
                            <div class="text-left max-w-xl mx-auto space-y-3">
                                <h3 class="text-base font-bold text-red-600 dark:text-red-500 text-center">Not Yet Cleared</h3>
                                <p class="text-xs text-brandNavy/70 dark:text-slate-400">Your account has an active administrative documentation hold:</p>

                                <div class="flex items-start space-x-3 p-3.5 rounded-xl bg-red-600/5 dark:bg-red-500/5 border border-red-600/10 dark:border-red-500/10">
                                    <i class="fa-solid fa-circle-xmark text-red-500 mt-0.5 flex-shrink-0"></i>
                                    <div>
                                        <span class="text-xs font-bold text-brandNavy dark:text-slate-200 block">Office of the University Registrar</span>
                                        <span class="text-[11px] text-brandNavy/60 dark:text-slate-500">Pending Original Copy Submission — Form 137 / Permanent Academic Records</span>
                                    </div>
                                </div>

                                {{-- Submission state --}}
                                @if($submissionPending)
                                    {{-- Already submitted, awaiting review --}}
                                    <div id="submissionPendingBanner" class="flex items-start gap-3 p-4 rounded-xl bg-blue-600/5 border border-blue-600/15 dark:bg-blue-500/5 dark:border-blue-500/15">
                                        <div class="w-8 h-8 rounded-full bg-blue-600/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                                            <i class="fa-solid fa-clock text-blue-600 dark:text-blue-400 text-xs"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-bold text-brandNavy dark:text-slate-200">Documents Submitted — Awaiting Registrar Review</p>
                                            <p class="text-[11px] text-brandNavy/60 dark:text-slate-500 mt-0.5">
                                                Submitted on {{ isset($submission->created_at) ? $submission->created_at->format('M d, Y g:i A') : 'recently' }}.
                                                The Registrar's Office will review your documents within 1–3 business days.
                                            </p>
                                            @if(isset($submission->original_name))
                                                <div class="mt-2 inline-flex items-center gap-1.5 text-[10px] font-mono text-blue-600 dark:text-blue-400 bg-blue-600/5 px-2 py-1 rounded-lg">
                                                    <i class="fa-solid fa-file-pdf"></i>{{ $submission->original_name }}
                                                </div>
                                            @endif
                                        </div>
                                        <button onclick="openSubmitModal(true)" class="text-[10px] font-bold text-blue-600 dark:text-blue-400 hover:underline flex-shrink-0 underline-offset-2">
                                            Resubmit
                                        </button>
                                    </div>
                                @else
                                    {{-- CTA to submit --}}
                                    <div class="flex items-center justify-between gap-4 p-4 rounded-xl bg-brandNavy/3 dark:bg-slate-800/40 border border-brandNavy/8 dark:border-slate-700/40">
                                        <div>
                                            <p class="text-xs font-bold text-brandNavy dark:text-slate-200">Resolve this hold</p>
                                            <p class="text-[11px] text-brandNavy/60 dark:text-slate-500 mt-0.5">Upload a scanned copy of your Form 137 or equivalent document directly to the Registrar.</p>
                                        </div>
                                        <button onclick="openSubmitModal(false)"
                                            class="flex-shrink-0 inline-flex items-center gap-2 px-4 py-2.5 bg-brandNavy hover:bg-brandGreen text-white text-[11px] font-bold rounded-xl transition-all shadow-sm hover:shadow-brandGreen/20 hover:-translate-y-0.5 active:translate-y-0 uppercase tracking-wider whitespace-nowrap">
                                            <i class="fa-solid fa-upload"></i>Submit Documents
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

            </div>

            {{-- MY SUBMITTED DOCUMENTS --}}
            @if(isset($submissions) && $submissions->isNotEmpty())
                <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
                    <div class="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800 flex justify-between items-center text-xs">
                        <span class="font-bold text-brandNavy dark:text-slate-300"><i class="fa-solid fa-folder-open mr-2"></i>My Submitted Documents</span>
                        <span class="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">Registrar Review</span>
                    </div>
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($submissions as $doc)
                            <div class="px-5 py-3 flex flex-wrap items-center justify-between gap-2 text-xs">
                                <div class="min-w-0">
                                    <span class="font-bold text-brandNavy dark:text-slate-200 block">{{ $doc->typeLabel() }}</span>
                                    <a href="{{ route('documents.show', $doc) }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline font-mono text-[11px]">
                                        <i class="fa-solid fa-paperclip mr-1"></i>{{ $doc->original_name }}
                                    </a>
                                    <span class="text-brandNavy/50 dark:text-slate-500 ml-2">{{ $doc->created_at->format('M d, Y g:i A') }}</span>
                                    @if($doc->status === 'rejected' && $doc->remarks)
                                        <p class="text-[11px] text-red-500 mt-1"><i class="fa-solid fa-comment-dots mr-1"></i>Registrar: {{ $doc->remarks }}</p>
                                    @endif
                                </div>
                                <div class="flex-shrink-0">
                                    @if($doc->status === 'pending')
                                        <span class="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-brandGold/10 text-brandGold border border-brandGold/20 uppercase tracking-wider">Pending</span>
                                    @elseif($doc->status === 'accepted')
                                        <span class="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-brandGreen/10 text-brandGreen border border-brandGreen/20 uppercase tracking-wider">Accepted</span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-red-600/10 text-red-600 border border-red-600/20 uppercase tracking-wider">Rejected</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </main>
</div>

{{-- -----------------------------------------------
     DOCUMENT SUBMISSION MODAL
     ----------------------------------------------- --}}
<div id="submitModal"
     class="fixed inset-0 bg-brandNavy/50 dark:bg-black/75 backdrop-blur-sm z-50 hidden items-center justify-center p-4">

    <div id="submitModalBox"
         class="modal-enter bg-white dark:bg-[#0D1B2A] rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl border border-brandNavy/8 dark:border-slate-800">

        {{-- Modal header --}}
        <div class="bg-lightBg dark:bg-slate-950 px-6 py-4 border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-brandNavy/8 dark:bg-slate-800 flex items-center justify-center">
                    <i class="fa-solid fa-file-arrow-up text-brandNavy dark:text-brandGold text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-brandNavy dark:text-white">Submit Missing Requirements</h3>
                    <p class="text-[10px] text-brandNavy/50 dark:text-slate-500 mt-0.5">Office of the University Registrar</p>
                </div>
            </div>
            <button onclick="closeSubmitModal()"
                class="w-8 h-8 rounded-full bg-brandNavy/5 dark:bg-slate-800 text-brandNavy/50 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>

        {{-- Requirement context strip --}}
        <div class="mx-6 mt-5 flex items-start gap-3 p-3.5 rounded-xl bg-red-600/5 border border-red-600/10 dark:bg-red-500/5 dark:border-red-500/10 text-xs">
            <i class="fa-solid fa-triangle-exclamation text-red-500 mt-0.5 flex-shrink-0"></i>
            <div>
                <span class="font-bold text-brandNavy dark:text-slate-200">Outstanding Requirement</span>
                <p class="text-brandNavy/60 dark:text-slate-500 mt-0.5">Original Copy — Form 137 / Permanent Academic Records</p>
            </div>
        </div>

        {{-- Form body --}}
        <form id="submissionForm" action="{{ route('clearance.submitRequirement') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
            @csrf

            {{-- Drop zone --}}
            <div>
                <label class="text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider block mb-2">
                    Scanned Document <span class="text-red-500">*</span>
                </label>
                <div id="dropZone"
                     class="drop-zone rounded-xl p-6 text-center cursor-pointer bg-lightBg/50 dark:bg-slate-900/40 hover:bg-lightBg dark:hover:bg-slate-900/60 transition-colors"
                     onclick="document.getElementById('fileInput').click()"
                     ondragover="handleDragOver(event)"
                     ondragleave="handleDragLeave(event)"
                     ondrop="handleDrop(event)">
                    <input type="file" id="fileInput" name="document" accept=".pdf,.jpg,.jpeg,.png" class="hidden" onchange="handleFileSelect(this)">

                    <div id="dropZoneContent">
                        <div class="w-10 h-10 rounded-full bg-brandNavy/5 dark:bg-slate-800 flex items-center justify-center mx-auto mb-3">
                            <i class="fa-solid fa-cloud-arrow-up text-brandNavy/40 dark:text-slate-500 text-lg"></i>
                        </div>
                        <p class="text-xs font-semibold text-brandNavy/70 dark:text-slate-400">Drag & drop your file here, or <span class="text-brandGreen dark:text-brandGold font-bold">browse</span></p>
                        <p class="text-[10px] text-brandNavy/40 dark:text-slate-600 mt-1">Accepted: PDF, JPG, PNG — Max 10 MB</p>
                    </div>

                    <div id="filePreview" class="hidden">
                        <div id="fileChip" class="file-chip inline-flex items-center gap-2.5 px-4 py-2.5 bg-white dark:bg-slate-800 border border-brandNavy/10 dark:border-slate-700 rounded-xl shadow-sm">
                            <i id="fileIcon" class="fa-solid fa-file-pdf text-red-500 text-base"></i>
                            <div class="text-left">
                                <p id="fileName" class="text-xs font-bold text-brandNavy dark:text-slate-200 truncate max-w-[200px]"></p>
                                <p id="fileSize" class="text-[10px] text-brandNavy/50 dark:text-slate-500"></p>
                            </div>
                            <button type="button" onclick="clearFile(event)" class="ml-1 w-5 h-5 rounded-full bg-brandNavy/5 dark:bg-slate-700 text-brandNavy/40 dark:text-slate-500 hover:bg-red-500/10 hover:text-red-500 flex items-center justify-center transition-colors">
                                <i class="fa-solid fa-xmark text-[9px]"></i>
                            </button>
                        </div>
                        <p class="text-[10px] text-brandNavy/40 dark:text-slate-600 mt-2">Click to change file</p>
                    </div>
                </div>
            </div>

            {{-- Document type selector --}}
            <div>
                <label class="text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider block mb-2">Document Type</label>
                <select name="document_type" class="w-full bg-lightBg/50 dark:bg-slate-900/40 text-brandNavy dark:text-slate-200 text-xs font-medium px-4 py-3 rounded-xl border border-brandNavy/10 dark:border-slate-700 focus:outline-none focus:border-brandGreen dark:focus:border-brandGold/50 transition-colors">
                    <option value="form137">Form 137 — Permanent Record / Senior HS Report Card</option>
                    <option value="form138">Form 138 — Report Card</option>
                    <option value="birth_cert">PSA Birth Certificate</option>
                    <option value="good_moral">Certificate of Good Moral Character</option>
                    <option value="other">Other Supporting Document</option>
                </select>
            </div>

            {{-- Notes --}}
            <div>
                <label class="text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider block mb-2">Notes to Registrar <span class="font-normal normal-case">(optional)</span></label>
                <textarea name="notes" rows="3" placeholder="e.g. Attached is the certified true copy issued by my previous school. Original is being mailed separately."
                    class="w-full bg-lightBg/50 dark:bg-slate-900/40 text-brandNavy dark:text-slate-200 text-xs px-4 py-3 rounded-xl border border-brandNavy/10 dark:border-slate-700 focus:outline-none focus:border-brandGreen dark:focus:border-brandGold/50 transition-colors resize-none placeholder-brandNavy/30 dark:placeholder-slate-600"></textarea>
            </div>

            {{-- Info notice --}}
            <div class="flex items-start gap-2.5 p-3 rounded-xl bg-blue-600/5 border border-blue-600/10 dark:bg-blue-500/5 dark:border-blue-500/10 text-[11px] text-brandNavy/60 dark:text-slate-500">
                <i class="fa-solid fa-circle-info text-blue-500 mt-0.5 flex-shrink-0"></i>
                <p>Your submission will be forwarded directly to the Registrar's Office. You will be notified once your document has been reviewed, typically within <strong class="text-brandNavy dark:text-slate-300">1–3 business days</strong>. Submitting does not guarantee immediate clearance.</p>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3 pt-1">
                <button type="button" onclick="closeSubmitModal()"
                    class="flex-1 py-3 rounded-xl text-xs font-bold text-brandNavy/60 dark:text-slate-400 hover:bg-brandNavy/5 dark:hover:bg-slate-800 border border-brandNavy/10 dark:border-slate-700 transition-colors">
                    Cancel
                </button>
                <button type="button" onclick="handleSubmit()"
                    id="submitBtn"
                    class="flex-1 py-3 rounded-xl text-xs font-bold bg-brandNavy hover:bg-brandGreen text-white transition-all shadow-md hover:shadow-brandGreen/20 hover:-translate-y-0.5 active:translate-y-0 uppercase tracking-wider disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                    <span id="submitBtnText"><i class="fa-solid fa-paper-plane mr-1.5"></i>Submit to Registrar</span>
                </button>
            </div>
        </form>

    </div>
</div>

<script>
    // -- Document submission modal ----------------------------
    function openSubmitModal(isResubmit = false) {
        const modal = document.getElementById('submitModal');
        const box   = document.getElementById('submitModalBox');
        const form  = document.getElementById('submissionForm');

        // reset
        form.classList.remove('hidden');
        if (isResubmit) clearFile();

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        // re-trigger animation
        box.classList.remove('modal-enter');
        void box.offsetWidth;
        box.classList.add('modal-enter');
    }

    function closeSubmitModal() {
        const modal = document.getElementById('submitModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Close on backdrop click
    document.getElementById('submitModal').addEventListener('click', function(e) {
        if (e.target === this) closeSubmitModal();
    });

    // -- File handling ----------------------------------------
    function handleFileSelect(input) {
        if (input.files && input.files[0]) showFile(input.files[0]);
    }

    function handleDragOver(e) {
        e.preventDefault();
        document.getElementById('dropZone').classList.add('dragover');
    }

    function handleDragLeave(e) {
        document.getElementById('dropZone').classList.remove('dragover');
    }

    function handleDrop(e) {
        e.preventDefault();
        document.getElementById('dropZone').classList.remove('dragover');
        const file = e.dataTransfer.files[0];
        if (file) {
            const dt = new DataTransfer();
            dt.items.add(file);
            document.getElementById('fileInput').files = dt.files;
            showFile(file);
        }
    }

    function showFile(file) {
        document.getElementById('dropZoneContent').classList.add('hidden');
        document.getElementById('filePreview').classList.remove('hidden');

        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileSize').textContent = formatBytes(file.size);

        const icon = document.getElementById('fileIcon');
        if (file.type === 'application/pdf') {
            icon.className = 'fa-solid fa-file-pdf text-red-500 text-base';
        } else if (file.type.startsWith('image/')) {
            icon.className = 'fa-solid fa-file-image text-blue-500 text-base';
        } else {
            icon.className = 'fa-solid fa-file text-slate-400 text-base';
        }
    }

    function clearFile(e) {
        if (e) { e.stopPropagation(); }
        document.getElementById('fileInput').value = '';
        document.getElementById('dropZoneContent').classList.remove('hidden');
        document.getElementById('filePreview').classList.add('hidden');
    }

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    // -- Submission -------------------------------------------
    function handleSubmit() {
        const fileInput = document.getElementById('fileInput');
        if (!fileInput.files || !fileInput.files[0]) {
            fileInput.closest('.drop-zone').style.borderColor = '#ef4444';
            setTimeout(() => fileInput.closest('.drop-zone').style.borderColor = '', 1500);
            return;
        }

        const btn     = document.getElementById('submitBtn');
        const btnText = document.getElementById('submitBtnText');
        btn.disabled  = true;
        btnText.innerHTML = '<i class="fa-solid fa-spinner animate-spin mr-1.5"></i>Uploading...';

        document.getElementById('submissionForm').submit();
    }
</script>

@include('partials.notif-script')
</body>
</html>