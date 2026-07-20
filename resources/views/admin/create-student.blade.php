<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Admin | Create Student Account</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

<div class="flex h-screen overflow-hidden">

    {{-- SIDEBAR --}}
    <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-panelDark border-r border-brandNavy/10 dark:border-slate-800 transition-colors duration-300">
        <div class="h-20 flex items-center px-8 border-b border-brandNavy/10 dark:border-slate-800">
            <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-8 h-8 rounded-lg object-cover mr-3">
            <h1 class="text-xl font-black tracking-tight text-brandNavy dark:text-white">AITSA HQ</h1>
        </div>
        <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-2">
            <p class="px-4 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest mb-2">Core Control</p>
            <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3 px-4 py-3 text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white rounded-xl font-medium text-sm transition-colors">
                <span>System Overview</span>
            </a>
            <a href="{{ route('admin.students.create') }}" class="flex items-center space-x-3 px-4 py-3 bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 rounded-xl font-bold text-sm">
                <span>Create Student Account</span>
            </a>
            <a href="{{ route('admin.curriculum') }}" class="flex items-center space-x-3 px-4 py-3 text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white rounded-xl font-medium text-sm transition-colors">
                <span>Curriculum</span>
            </a>
            <a href="{{ route('admin.departments') }}" class="flex items-center space-x-3 px-4 py-3 text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white rounded-xl font-medium text-sm transition-colors">
                <span>Departments</span>
            </a>
            <a href="{{ route('admin.audit') }}" class="flex items-center space-x-3 px-4 py-3 text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white rounded-xl font-medium text-sm transition-colors">
                <span>Audit Trail</span>
            </a>
            <a href="{{ route('admin.reports') }}" class="flex items-center space-x-3 px-4 py-3 text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white rounded-xl font-medium text-sm transition-colors">
                <span>Reports</span>
            </a>
        </nav>
    </aside>

    {{-- MAIN --}}
    <main class="flex-1 flex flex-col overflow-hidden">

        <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
            <div class="flex items-center gap-3">
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

            <div class="max-w-3xl mx-auto space-y-6">

                <div>
                    <h1 class="text-2xl font-black text-brandNavy dark:text-white">
                        {{ $applicant ? 'Activate Student Account' : 'New Student Account' }}
                    </h1>
                    <p class="text-xs text-brandNavy/50 dark:text-slate-400 mt-1">
                        {{ $applicant ? 'Assign login credentials and academic details for the verified applicant below.' : 'Manually register a student and initialize their clearance record.' }}
                    </p>
                </div>

                {{-- Applicant banner (only when coming from admission queue) --}}
                @if($applicant)
                <div class="flex items-center gap-4 p-4 bg-brandGreen/8 dark:bg-brandGreen/10 border border-brandGreen/25 rounded-2xl">
                    <div class="w-10 h-10 rounded-full bg-brandGreen/15 flex items-center justify-center text-brandGreen font-black text-sm flex-shrink-0">
                        {{ strtoupper(substr($applicant->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[10px] font-bold text-brandGreen uppercase tracking-wider">From Admission Queue</p>
                        <p class="font-black text-brandNavy dark:text-white text-sm">{{ $applicant->name }}</p>
                        <p class="text-xs text-brandNavy/50 dark:text-slate-400">{{ $applicant->email }} &nbsp;·&nbsp; {{ $applicant->major ?? '—' }}</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-[10px] font-black bg-brandGold/10 text-amber-600 border border-brandGold/20 uppercase tracking-wider flex-shrink-0">Verified</span>
                </div>
                @endif

                <form action="{{ route('admin.students.store') }}" method="POST" class="space-y-6">
                    @csrf
                    @if($applicant)
                    <input type="hidden" name="applicant_id" value="{{ $applicant->id }}">
                    @endif

                    {{-- Account Credentials --}}
                    <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                        <div class="px-6 py-4 border-b border-brandNavy/8 dark:border-slate-800 bg-lightBg dark:bg-slate-900/40">
                            <h3 class="text-xs font-black text-brandNavy dark:text-white uppercase tracking-wider flex items-center gap-2">
                                <i class="fa-solid fa-key text-brandGreen"></i>Login Credentials
                            </h3>
                        </div>
                        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Student ID <span class="text-red-500">*</span></label>
                                <input type="text" name="login_id" value="{{ old('login_id') }}" required placeholder="e.g. 2026-10001"
                                    class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 focus:outline-none focus:border-brandGreen transition-colors font-mono">
                                <p class="text-[10px] text-brandNavy/40 dark:text-slate-500 mt-1">Used as login username.</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Password <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="text" name="password" id="passwordInput" value="{{ old('password') }}" required placeholder="Min. 8 characters"
                                        class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 focus:outline-none focus:border-brandGreen transition-colors font-mono pr-20">
                                    <button type="button" onclick="generatePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-black text-brandGreen hover:text-emerald-700 transition-colors">Generate</button>
                                </div>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Confirm Password <span class="text-red-500">*</span></label>
                                <input type="text" name="password_confirmation" id="passwordConfirmInput" required placeholder="Re-enter password"
                                    class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 focus:outline-none focus:border-brandGreen transition-colors font-mono">
                            </div>
                        </div>
                    </div>

                    {{-- Academic Info --}}
                    <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                        <div class="px-6 py-4 border-b border-brandNavy/8 dark:border-slate-800 bg-lightBg dark:bg-slate-900/40">
                            <h3 class="text-xs font-black text-brandNavy dark:text-white uppercase tracking-wider flex items-center gap-2">
                                <i class="fa-solid fa-graduation-cap text-brandGreen"></i>Academic Information
                            </h3>
                        </div>
                        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Program / Course <span class="text-red-500">*</span></label>
                                @php $selMajor = old('major', $applicant->major ?? ''); @endphp
                                <select name="major" required onchange="updateSection()" id="programSelect"
                                    class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 focus:outline-none focus:border-brandGreen transition-colors">
                                    <option value="" disabled {{ $selMajor ? '' : 'selected' }}>Select program</option>
                                    @foreach ($programs->groupBy('level') as $level => $levelPrograms)
                                        <optgroup label="{{ ucfirst($level) }}">
                                            @foreach ($levelPrograms as $program)
                                                <option value="{{ $program->code }}" {{ $selMajor == $program->code ? 'selected' : '' }}>
                                                    {{ $program->code }} — {{ $program->name }}{{ $program->is_enrollable ? '' : ' (manual enrollment)' }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <input type="hidden" name="program_level" id="programLevelInput" value="{{ old('program_level', $applicant->program_level ?? '') }}">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Year Level <span class="text-red-500">*</span></label>
                                <select name="year_level" required id="yearLevelSelect"
                                    class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 focus:outline-none focus:border-brandGreen transition-colors">
                                    <option value="" disabled {{ old('year_level') ? '' : 'selected' }}>Select year level</option>
                                    <option value="1st Year" {{ old('year_level') == '1st Year' ? 'selected' : '' }}>1st Year</option>
                                    <option value="2nd Year" {{ old('year_level') == '2nd Year' ? 'selected' : '' }}>2nd Year</option>
                                    <option value="3rd Year" {{ old('year_level') == '3rd Year' ? 'selected' : '' }}>3rd Year</option>
                                    <option value="4th Year" {{ old('year_level') == '4th Year' ? 'selected' : '' }}>4th Year</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Section <span class="text-brandNavy/30 dark:text-slate-600 font-normal normal-case">(Optional)</span></label>
                                <input type="text" name="section" value="{{ old('section') }}" placeholder="e.g. BSOA-1A"
                                    class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 focus:outline-none focus:border-brandGreen transition-colors">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Applicant Type</label>
                                @php $selType = old('applicant_type', $applicant->applicant_type ?? ''); @endphp
                                <select name="applicant_type"
                                    class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 focus:outline-none focus:border-brandGreen transition-colors">
                                    <option value="">Not specified</option>
                                    <option value="NEW"        {{ $selType == 'NEW'        ? 'selected' : '' }}>New Student</option>
                                    <option value="TRANSFEREE" {{ $selType == 'TRANSFEREE' ? 'selected' : '' }}>Transferee</option>
                                    <option value="RETURNEE"   {{ $selType == 'RETURNEE'   ? 'selected' : '' }}>Returnee</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Personal Info --}}
                    <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                        <div class="px-6 py-4 border-b border-brandNavy/8 dark:border-slate-800 bg-lightBg dark:bg-slate-900/40">
                            <h3 class="text-xs font-black text-brandNavy dark:text-white uppercase tracking-wider flex items-center gap-2">
                                <i class="fa-solid fa-user text-brandGreen"></i>Personal Information
                            </h3>
                        </div>
                        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div class="sm:col-span-2">
                                <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Full Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $applicant->name ?? '') }}" required placeholder="Last Name, First Name Middle Name"
                                    {{ $applicant ? 'readonly' : '' }}
                                    class="w-full border border-brandNavy/15 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 focus:outline-none focus:border-brandGreen transition-colors {{ $applicant ? 'bg-lightBg dark:bg-slate-800/50 cursor-not-allowed' : 'bg-white dark:bg-slate-900/60' }}">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Email Address <span class="text-red-500">*</span></label>
                                <input type="email" name="email" value="{{ old('email', $applicant->email ?? '') }}" required placeholder="student@email.com"
                                    {{ $applicant ? 'readonly' : '' }}
                                    class="w-full border border-brandNavy/15 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 focus:outline-none focus:border-brandGreen transition-colors {{ $applicant ? 'bg-lightBg dark:bg-slate-800/50 cursor-not-allowed' : 'bg-white dark:bg-slate-900/60' }}">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Contact Number</label>
                                <input type="text" name="contact_number" value="{{ old('contact_number', $applicant->contact_number ?? '') }}" placeholder="09XX-XXX-XXXX"
                                    class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 focus:outline-none focus:border-brandGreen transition-colors">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Date of Birth</label>
                                <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $applicant->date_of_birth ?? '') }}"
                                    class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 focus:outline-none focus:border-brandGreen transition-colors">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Sex</label>
                                <select name="sex" class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 focus:outline-none focus:border-brandGreen transition-colors">
                                    <option value="">Not specified</option>
                                    <option value="Male" {{ old('sex', $applicant->sex ?? '') == 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ old('sex', $applicant->sex ?? '') == 'Female' ? 'selected' : '' }}>Female</option>
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Home Address</label>
                                <input type="text" name="address" value="{{ old('address', $applicant->address ?? '') }}" placeholder="Street, Barangay, City/Municipality, Province"
                                    class="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 focus:outline-none focus:border-brandGreen transition-colors">
                            </div>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <div class="flex items-center justify-between gap-4 pb-6">
                        <p class="text-[10px] text-brandNavy/40 dark:text-slate-500">Fields marked <span class="text-red-500">*</span> are required. A clearance record will be automatically created for this student.</p>
                        <button type="submit"
                            class="flex-shrink-0 flex items-center gap-2 px-8 py-3.5 bg-brandGreen hover:bg-emerald-700 text-white text-sm font-black rounded-xl transition-all shadow-md hover:-translate-y-0.5 active:translate-y-0">
                            <i class="fa-solid fa-user-plus"></i>Create Student Account
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </main>
</div>

<script>
const PROGRAM_LEVELS = @json($programs->pluck('level', 'code')->map(fn ($level) => strtoupper($level)));

function updateSection() {
    const prog = document.getElementById('programSelect').value;
    document.getElementById('programLevelInput').value = PROGRAM_LEVELS[prog] || '';
}
document.addEventListener('DOMContentLoaded', updateSection);

function generatePassword() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789@#!';
    let pw = 'Aitsa@';
    for (let i = 0; i < 6; i++) pw += chars[Math.floor(Math.random() * chars.length)];
    document.getElementById('passwordInput').value = pw;
    document.getElementById('passwordConfirmInput').value = pw;
}
</script>

@include('partials.notif-script')
</body>
</html>
