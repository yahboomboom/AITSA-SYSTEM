<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Staff | Registrar Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    @include('partials.theme-fonts')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

    <div class="flex h-screen overflow-hidden">

        @include('partials.registrar-sidebar')

        <main class="flex-1 flex flex-col overflow-hidden relative">

            <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
                <div class="flex items-center space-x-3">
                    <button onclick="toggleMobileSidebar()" class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                    <span class="text-sm font-bold text-brandNavy dark:text-slate-200">Approvals & Admissions Management</span>
                </div>
                <div class="flex items-center space-x-3">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', [
                        'roleLabel' => 'Registrar Portal',
                        'roleClass' => 'font-mono font-bold uppercase tracking-wider text-brandGreen dark:text-emerald-400',
                    ])
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-6">

                @if(session('success'))
                    <div class="p-4 rounded-lg bg-brandGreen/10 border border-brandGreen/20 text-brandGreen font-bold text-xs">
                        <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="p-4 rounded-lg bg-red-600/10 border border-red-600/20 text-red-600 font-bold text-xs">
                        <i class="fa-solid fa-circle-xmark mr-2"></i>{{ session('error') }}
                    </div>
                @endif

                <div class="space-y-1">
                    <h1 class="text-2xl font-extrabold tracking-tight text-brandNavy dark:text-white">Registrar Portal</h1>
                </div>

                @php
                    $context = [
                        'applicants' => $applicants->map(fn ($a) => [
                            'id' => $a->id,
                            'name' => $a->name,
                            'email' => $a->email,
                            'dob' => $a->date_of_birth ? \Carbon\Carbon::parse($a->date_of_birth)->format('M d, Y') : null,
                            'sex' => $a->sex,
                            'contactNumber' => $a->contact_number,
                            'address' => $a->address,
                            'major' => $a->major,
                            'programLevel' => $a->program_level,
                            'applicantType' => $a->applicant_type,
                            'lastSchool' => $a->last_school,
                            'yearGraduated' => $a->year_graduated,
                            'createdAtFormatted' => $a->created_at->format('M d, Y'),
                            'wantsReservation' => (bool) $a->wants_reservation, // did they check the box on the application form?
                            'isReserved' => (bool) $a->is_reserved, // have they actually paid the fee?
                            'toggleReservationUrl' => route('registrar.toggle-reservation', $a->id),
                            'activateApplicantUrl' => route('registrar.activate-applicant', $a->id),
                            'archiveApplicantUrl' => route('registrar.archive-applicant', $a->id),
                            'agreementSigned' => $a->agreements->contains(fn ($agreement) => $agreement->status === 'completed'),
                            'agreementSignedAt' => optional($a->agreements->firstWhere('status', 'completed'))->signed_at?->format('M d, Y'),
                            'agreementViewUrl' => route('registrar.applicant-agreement', $a->id),
                        ])->values(),
                        'stats' => [
                            'total' => $clearances->count(),
                            'approvedToday' => $clearances->where('registrar_status', 'Approved')->count(),
                            'pending' => $clearances->where('registrar_status', '!=', 'Approved')->count(),
                        ],
                        'clearances' => $clearances->map(fn ($row) => [
                            'id' => $row->id,
                            'studentName' => $row->user->name ?? '—',
                            'studentId' => $row->user->login_id ?? '—',
                            'program' => $row->user->major ?? '—',
                            'isApproved' => ($row->registrar_status ?? 'Pending') === 'Approved',
                            'signUrl' => route('registrar.sign', $row->id),
                            'holdUrl' => route('registrar.hold', $row->id),
                        ])->values(),
                        'documents' => $documentSubmissions->map(fn ($doc) => [
                            'id' => $doc->id,
                            'studentName' => $doc->user->name ?? '—',
                            'studentId' => $doc->user->login_id ?? '—',
                            'typeLabel' => $doc->typeLabel(),
                            'documentUrl' => route('documents.show', $doc),
                            'originalName' => $doc->original_name,
                            'sizeKb' => number_format($doc->size / 1024, 0),
                            'notes' => $doc->notes,
                            'status' => $doc->status,
                            'remarks' => $doc->remarks,
                            'createdAtFormatted' => $doc->created_at->format('M d, Y g:i A'),
                            'acceptUrl' => route('registrar.documents.accept', $doc),
                            'rejectUrl' => route('registrar.documents.reject', $doc),
                        ])->values(),
                    ];
                @endphp

                <div id="registrar-dashboard-root" data-context="{{ json_encode($context) }}" data-csrf-token="{{ csrf_token() }}">
                    <p class="text-sm text-slate-500">Loading…</p>
                </div>

            </div>
        </main>
    </div>

@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/registrar-dashboard-app.jsx')
</body>
</html>
