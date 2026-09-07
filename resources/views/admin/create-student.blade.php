<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Admin | Create Student Account</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    @include('partials.theme-fonts')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

<div class="flex h-screen overflow-hidden">

    @include('partials.admin-sidebar')

    {{-- MAIN --}}
    <main class="flex-1 flex flex-col overflow-hidden">

        <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
            <div class="flex items-center gap-3">
                <button onclick="toggleMobileSidebar()" class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <a href="{{ route('admin.dashboard') }}" class="text-brandNavy/50 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white transition-colors text-sm">
                    <i class="fa-solid fa-chevron-left mr-1"></i>Dashboard
                </a>
                <span class="text-brandNavy/20 dark:text-slate-700">/</span>
                <h2 class="text-sm font-bold text-brandNavy dark:text-slate-100">Create Student Account</h2>
            </div>
            <div class="flex items-center gap-4">
                @include('partials.notif-bell')
                <button onclick="toggleTheme()" class="w-9 h-9 rounded-full bg-lightBg dark:bg-darkBg text-brandNavy dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/10 dark:hover:bg-slate-800 transition-colors">
                    <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                </button>
                @include('partials.profile-menu', ['roleLabel' => 'Administrator'])
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 lg:p-10">

            @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-brandGreen/10 border border-brandGreen/20 text-brandGreen font-bold text-sm flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-lg"></i>
                <div>
                    <p class="font-black">Account Created Successfully</p>
                    <p class="font-normal text-xs mt-0.5">{{ session('success') }}</p>
                </div>
            </div>
            @endif

            @if($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-600 text-xs font-semibold">
                <p class="font-black flex items-center gap-2 mb-1"><i class="fa-solid fa-triangle-exclamation"></i>Please fix the following:</p>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            @endif

            @php
                $context = [
                    'applicant' => $applicant ? [
                        'id' => $applicant->id,
                        'name' => $applicant->name,
                        'email' => $applicant->email,
                        'major' => $applicant->major,
                        'programLevel' => $applicant->program_level,
                        'applicantType' => $applicant->applicant_type,
                        'contactNumber' => $applicant->contact_number,
                        'dateOfBirth' => $applicant->date_of_birth,
                        'sex' => $applicant->sex,
                        'address' => $applicant->address,
                    ] : null,
                    'programs' => $programs->groupBy('level')->map(fn ($levelPrograms) => $levelPrograms->map(fn ($program) => [
                        'code' => $program->code,
                        'name' => $program->name,
                        'level' => $program->level,
                        'isEnrollable' => (bool) $program->is_enrollable,
                    ])->values())->map(fn ($programs, $level) => ['level' => $level, 'programs' => $programs])->values(),
                    'old' => $errors->any() ? old() : [],
                ];
            @endphp

            <div
                id="admin-create-student-root"
                data-context="{{ json_encode($context) }}"
                data-csrf-token="{{ csrf_token() }}"
                data-store-url="{{ route('admin.students.store') }}"
            >
                <p class="text-sm text-slate-500">Loading…</p>
            </div>

        </div>
    </main>
</div>

@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/admin-create-student-app.jsx')
</body>
</html>
