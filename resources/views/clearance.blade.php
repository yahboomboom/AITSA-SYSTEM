<!DOCTYPE html>
<html lang="en">
<head>
    {{-- Character encoding and styles for the certificate of clearance --}}
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | Enrollment Clearance</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    @include('partials.theme-fonts')
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

    @include('partials.student-sidebar')

    <main class="flex-1 flex flex-col overflow-hidden relative">

        {{-- HEADER --}}
        <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
            <div class="flex items-center gap-4">
                <button onclick="toggleMobileSidebar()" class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div>
                    <h2 class="font-heading text-2xl font-semibold leading-none text-brandNavy dark:text-slate-100">Clearance</h2>
                    <p class="text-xs text-brandNavy/50 dark:text-slate-400 mt-1">Student No. {{ Auth::user()->login_id ?? '—' }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="flex items-center gap-4 border-l border-brandNavy/10 dark:border-slate-700 pl-4">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" class="w-9 h-9 rounded-full bg-lightBg dark:bg-darkBg text-brandNavy dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/10 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.student-status-badge')
                    @include('partials.profile-menu', ['roleLabel' => Auth::user()->major ?? 'BSIT'])
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 lg:p-10 space-y-6">

            @if(session('success'))
                <div class="p-4 rounded bg-brandGreen/10 border border-brandGreen/20 text-brandGreen text-sm">
                    <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 rounded bg-red-600/10 border border-red-600/20 text-red-600 text-sm">
                    <i class="fa-solid fa-circle-xmark mr-2"></i>{{ $errors->first() }}
                </div>
            @endif

            @if($agreement)
                <div class="p-4 rounded bg-brandGreen/10 border border-brandGreen/20 text-brandGreen text-sm flex items-center justify-between gap-3">
                    <span><i class="fa-solid fa-signature mr-2"></i>Enrollment Agreement Signed &mdash; {{ $agreement->signed_at->format('M d, Y') }}</span>
                    <a href="{{ route('agreement.mine') }}" target="_blank" rel="noopener noreferrer"
                       class="underline underline-offset-2 hover:text-brandGreen/80 transition-colors whitespace-nowrap">View my copy</a>
                </div>
            @endif

            {{-- Prepare the context data for the clearance React component, including clearance status, submission details, and a list of submissions with their respective information --}}
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

                $clearanceContext = [
                    'schoolYear' => $clearance->school_year,
                    'semester' => $clearance->semester,
                    'isCleared' => $isCleared,
		    'printUrl' => $isCleared ? route('clearance.print', $clearance->id) : null,
                    'cashierCleared' => $cashierCleared,
                    'registrarCleared' => $registrarCleared,
                    'chairCleared' => $chairCleared,
                    'remarks' => $clearance->remarks,
                    'breakdown' => $breakdown,
                    'items' => $clearance->items->map(fn ($item) => [
                        'departmentName' => $item->department->name,
                        'status' => $item->status,
                        'remarks' => $item->remarks,
                    ])->all(),
                    'submission' => [
                        'hasSubmission' => $hasSubmission,
                        'submissionPending' => $submissionPending,
                        'createdAt' => $hasSubmission ? (isset($submission->created_at) ? $submission->created_at->format('M d, Y g:i A') : 'recently') : null,
                        'originalName' => $hasSubmission ? ($submission->original_name ?? null) : null,
                    ],
                    'documentsUrl' => route('documents'),
                    'schoolYear' => $clearance->school_year,
                ];
            @endphp
            {{-- Render the clearance React component, passing the prepared context data, CSRF token, and submission URL as props for dynamic interaction and state management --}}
            <div id="clearance-root"
                 data-context="{{ json_encode($clearanceContext) }}"
                 data-csrf-token="{{ csrf_token() }}"
                 data-submit-url="{{ route('clearance.submitRequirement') }}">
                <p class="text-sm text-slate-500">Loading…</p>
            </div>
        </div>
    </main>
</div>

@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/clearance-app.jsx')
</body>
</html>