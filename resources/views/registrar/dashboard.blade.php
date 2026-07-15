<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Staff | Registrar Portal</title>
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
            var icon = document.getElementById('theme-icon');
            if (icon) icon.className = document.documentElement.classList.contains('dark') ? 'fa-solid fa-sun text-sm' : 'fa-solid fa-moon text-sm';
        }
        function initializeTheme() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.classList.toggle('dark', theme === 'dark');
        }
        document.addEventListener('DOMContentLoaded', updateThemeIcon);
        function toggleTheme() {
            const html = document.documentElement;
            const isDark = html.classList.toggle('dark');
            updateThemeIcon(); localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }
        initializeTheme();
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

    <div class="flex h-screen overflow-hidden">

        <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-panelDark border-r border-brandNavy/10 dark:border-slate-800 transition-colors duration-300">
            <div class="h-16 flex items-center px-6 border-b border-brandNavy/10 dark:border-slate-800">
                <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-7 h-7 rounded object-cover mr-3">
                <h1 class="text-base font-black tracking-tight text-brandNavy dark:text-white">AITSA Staff</h1>
            </div>

            <nav class="flex-1 overflow-y-auto py-5 px-3 space-y-0.5">
                <p class="px-3 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest mb-3">Management</p>

                <a href="{{ route('registrar.dashboard') }}" class="flex items-center px-3 py-2.5 border-l-2 {{ Route::is('registrar.dashboard') ? 'border-brandGreen text-brandGreen dark:text-emerald-400 font-bold' : 'border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium' }} text-sm transition-colors">
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('registrar.students') }}" class="flex items-center px-3 py-2.5 border-l-2 {{ Route::is('registrar.students') ? 'border-brandGreen text-brandGreen dark:text-emerald-400 font-bold' : 'border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium' }} text-sm transition-colors">
                    <span>Student Records</span>
                </a>

                <a href="{{ route('registrar.reports') }}" class="flex items-center px-3 py-2.5 border-l-2 {{ Route::is('registrar.reports') ? 'border-brandGreen text-brandGreen dark:text-emerald-400 font-bold' : 'border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium' }} text-sm transition-colors">
                    <span>Reports</span>
                </a>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden relative">

            <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
                <div class="flex items-center space-x-2">
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

                <div class="space-y-1">
                    <h1 class="text-2xl font-extrabold tracking-tight text-brandNavy dark:text-white">Registrar Portal</h1>
                </div>

                @if(isset($applicants) && $applicants->count() > 0)
                <div class="bg-white dark:bg-panelDark/40 border border-brandGold/30 dark:border-amber-500/20 rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-brandGold/20 dark:border-amber-500/20 bg-brandGold/5 dark:bg-amber-500/5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            <h3 class="text-sm font-bold text-brandNavy dark:text-white">Pending Admission Applications</h3>
                            <span class="px-2 py-0.5 text-[9px] font-black bg-amber-500 text-white rounded-full">{{ $applicants->count() }} new</span>
                        </div>
                        <p class="text-[10px] text-brandNavy/50 dark:text-slate-400">Review each application before forwarding to Admin for account creation.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-brandNavy/10 dark:border-slate-800 bg-lightBg dark:bg-slate-900/40 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">
                                    <th class="py-3.5 px-6">Applicant Name</th>
                                    <th class="py-3.5 px-6">Contact</th>
                                    <th class="py-3.5 px-6">Program Applied</th>
                                    <th class="py-3.5 px-6">Type</th>
                                    <th class="py-3.5 px-6">Last School</th>
                                    <th class="py-3.5 px-6 text-center">Date Applied</th>
                                    <th class="py-3.5 px-6 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800/40 text-xs">
                                @foreach($applicants as $applicant)
                                <tr class="hover:bg-amber-50/50 dark:hover:bg-amber-500/5 transition-colors">
                                    <td class="py-4 px-6">
                                        <p class="font-bold text-brandNavy dark:text-white">{{ $applicant->name }}</p>
                                        <p class="text-brandNavy/50 dark:text-slate-500 text-[11px]">{{ $applicant->email }}</p>
                                        @if($applicant->date_of_birth)
                                        <p class="text-brandNavy/40 dark:text-slate-600 text-[10px]">{{ $applicant->sex ?? '' }} · {{ \Carbon\Carbon::parse($applicant->date_of_birth)->format('M d, Y') }}</p>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-brandNavy/60 dark:text-slate-400">
                                        <p>{{ $applicant->contact_number ?? '—' }}</p>
                                        <p class="text-[10px] text-brandNavy/40 dark:text-slate-600 mt-0.5 max-w-[140px] truncate">{{ $applicant->address ?? '' }}</p>
                                    </td>
                                    <td class="py-4 px-6">
                                        <p class="font-semibold text-brandNavy dark:text-slate-200">{{ $applicant->major ?? '—' }}</p>
                                        <p class="text-[10px] text-brandNavy/40 dark:text-slate-600">{{ $applicant->program_level ?? '' }}</p>
                                    </td>
                                    <td class="py-4 px-6">
                                        @php $typeColors = ['NEW'=>'bg-brandGreen/10 text-brandGreen','TRANSFEREE'=>'bg-blue-500/10 text-blue-600','RETURNEE'=>'bg-amber-500/10 text-amber-600']; @endphp
                                        <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wide {{ $typeColors[$applicant->applicant_type ?? ''] ?? 'bg-slate-100 dark:bg-slate-800 text-brandNavy/50' }}">
                                            {{ $applicant->applicant_type ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-brandNavy/60 dark:text-slate-400">
                                        <p>{{ $applicant->last_school ?? '—' }}</p>
                                        <p class="text-[10px] text-brandNavy/40 dark:text-slate-600">Grad: {{ $applicant->year_graduated ?? '—' }}</p>
                                    </td>
                                    <td class="py-4 px-6 text-center text-brandNavy/50 dark:text-slate-500">{{ $applicant->created_at->format('M d, Y') }}</td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <form action="{{ route('registrar.verify-applicant', $applicant->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit"
                                                    class="px-3 py-1.5 text-[11px] font-black text-white bg-brandGreen hover:bg-emerald-700 rounded transition-colors"
                                                    onclick="return confirm('Verify application for {{ $applicant->name }} and forward to Admin?')">
                                                    <i class="fa-solid fa-circle-check mr-1"></i>Verify
                                                </button>
                                            </form>
                                            <form action="{{ route('registrar.decline-applicant', $applicant->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit"
                                                    class="px-3 py-1.5 text-[11px] font-black text-red-500 border border-red-500/30 hover:bg-red-500/10 rounded transition-colors"
                                                    onclick="return confirm('Decline and remove the application for {{ $applicant->name }}?')">
                                                    <i class="fa-solid fa-xmark mr-1"></i>Decline
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="bg-white dark:bg-panelDark/40 p-6 border border-brandNavy/10 dark:border-slate-800/80 rounded-lg">
                        <p class="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">Total Applications</p>
                        <h3 class="text-3xl font-black text-blue-600 dark:text-blue-400 mt-2">{{ count($clearances ?? []) }}</h3>
                        <p class="text-[11px] text-brandNavy/50 dark:text-slate-500 mt-1">Registration Verification.</p>
                    </div>

                    <div class="bg-white dark:bg-panelDark/40 p-6 border border-brandNavy/10 dark:border-slate-800/80 rounded-lg">
                        <p class="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">Approved / Cleared Today</p>
                        <h3 class="text-3xl font-black text-brandGreen dark:text-emerald-400 mt-2">
                            {{ isset($clearances) ? $clearances->where('registrar_status', 'Approved')->count() : 0 }}
                        </h3>
                        <p class="text-[11px] text-brandNavy/50 dark:text-slate-500 mt-1">Students cleared.</p>
                    </div>

                    <div class="bg-white dark:bg-panelDark/40 p-6 border border-brandNavy/10 dark:border-slate-800/80 rounded-lg">
                        <p class="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">Pending Decisions</p>
                        <h3 class="text-3xl font-black text-brandGold dark:text-amber-400 mt-2">
                            {{ isset($clearances) ? $clearances->where('registrar_status', '!=', 'Approved')->count() : 0 }}
                        </h3>
                        <p class="text-[11px] text-brandNavy/50 dark:text-slate-500 mt-1">Pending Requests.</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-panelDark/40 border border-brandNavy/10 dark:border-slate-800/80 rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-brandNavy/10 dark:border-slate-800 bg-lightBg dark:bg-slate-900/20 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <h3 class="text-sm font-bold text-brandNavy dark:text-white tracking-wide">Admission & Clearance Processing Queue</h3>
                        <div class="relative w-full sm:w-72">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-brandNavy/40 dark:text-slate-500 text-xs"></i>
                            <input type="text" placeholder="Search student name or ID..." class="w-full bg-white dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-xs text-brandNavy dark:text-slate-200 placeholder-brandNavy/40 dark:placeholder-slate-500 pl-9 pr-4 py-2 rounded focus:outline-none focus:border-brandGreen dark:focus:border-emerald-500/50 transition-colors">
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-brandNavy/10 dark:border-slate-800 bg-lightBg dark:bg-slate-900/40 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">
                                    <th class="py-4 px-6">Student Info</th>
                                    <th class="py-4 px-6">Student ID</th>
                                    <th class="py-4 px-6">Program / Track</th>
                                    <th class="py-4 px-6 text-center">Admission Status</th>
                                    <th class="py-4 px-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800/40 text-xs">
                                @forelse($clearances as $row)
                                <tr class="hover:bg-lightBg dark:hover:bg-slate-800/20 transition-colors">
                                    <td class="py-5 px-6 font-bold text-brandNavy dark:text-white tracking-wide">
                                        {{ $row->user->name ?? '—' }}
                                    </td>
                                    <td class="py-5 px-6 font-mono text-brandNavy/60 dark:text-slate-400 font-medium">
                                        {{ $row->user->login_id ?? '—' }}
                                    </td>
                                    <td class="py-5 px-6 text-brandNavy/70 dark:text-slate-300 font-medium tracking-wide">
                                        {{ $row->user->major ?? '—' }}
                                    </td>
                                    <td class="py-5 px-6 text-center">
                                        @if(($row->registrar_status ?? 'Pending') === 'Approved')
                                            <span class="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-brandGreen/10 text-brandGreen border border-brandGreen/20 uppercase tracking-wider">
                                                <i class="fa-solid fa-circle-check mr-1.5"></i>Cleared
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-brandGold/10 text-brandGold border border-brandGold/20 uppercase tracking-wider">
                                                <i class="fa-solid fa-clock mr-1.5"></i>Pending Review
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-5 px-6 text-right">
                                        @if(($row->registrar_status ?? 'Pending') === 'Approved')
                                            <button disabled class="px-4 py-2 bg-lightBg dark:bg-slate-800 text-brandNavy/40 dark:text-slate-500 text-[11px] font-bold rounded cursor-not-allowed border border-brandNavy/10 dark:border-slate-700">
                                                <i class="fa-solid fa-check-double mr-1.5"></i>Signed Off
                                            </button>
                                        @else
                                            <div class="flex items-center justify-end gap-2">
                                                <form action="{{ route('registrar.sign', isset($row->id) ? $row->id : 1) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    <button type="submit" class="px-4 py-2 bg-brandNavy hover:bg-brandGreen text-white text-[11px] font-black rounded transition-colors tracking-wide">
                                                        Sign Clearance
                                                    </button>
                                                </form>
                                                <form action="{{ route('registrar.hold', isset($row->id) ? $row->id : 1) }}" method="POST" class="flex items-center gap-2">
                                                    @csrf
                                                    <input type="text" name="remarks" required maxlength="500" placeholder="Reason for hold" class="w-36 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-[11px] text-brandNavy dark:text-slate-200 outline-none">
                                                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-[11px] font-black rounded transition-colors tracking-wide">
                                                        Hold
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-brandNavy/40 dark:text-slate-500 font-medium">
                                        <div class="flex flex-col items-center justify-center space-y-2">
                                            <i class="fa-solid fa-box-open text-2xl text-brandNavy/20 dark:text-slate-600"></i>
                                            <span>No students pending admission review at this time.</span>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- STUDENT DOCUMENT SUBMISSIONS --}}
                <div class="bg-white dark:bg-panelDark/40 border border-brandNavy/10 dark:border-slate-800/80 rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-brandNavy/10 dark:border-slate-800 bg-lightBg dark:bg-slate-900/20 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-brandNavy dark:text-white tracking-wide">Student Document Submissions</h3>
                        @php $pendingDocCount = isset($documentSubmissions) ? $documentSubmissions->where('status', 'pending')->count() : 0; @endphp
                        @if($pendingDocCount > 0)
                            <span class="px-2 py-0.5 text-[9px] font-black bg-brandGold text-white rounded-full">{{ $pendingDocCount }} pending</span>
                        @endif
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-brandNavy/10 dark:border-slate-800 bg-lightBg dark:bg-slate-900/40 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">
                                    <th class="py-4 px-6">Student</th>
                                    <th class="py-4 px-6">Document</th>
                                    <th class="py-4 px-6">Submitted</th>
                                    <th class="py-4 px-6 text-center">Status</th>
                                    <th class="py-4 px-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800/40 text-xs">
                                @forelse($documentSubmissions ?? [] as $doc)
                                <tr class="hover:bg-lightBg dark:hover:bg-slate-800/20 transition-colors align-top">
                                    <td class="py-5 px-6">
                                        <span class="font-bold text-brandNavy dark:text-white block">{{ $doc->user->name ?? '—' }}</span>
                                        <span class="font-mono text-brandNavy/60 dark:text-slate-400">{{ $doc->user->login_id ?? '—' }}</span>
                                    </td>
                                    <td class="py-5 px-6">
                                        <span class="font-semibold text-brandNavy dark:text-slate-200 block">{{ $doc->typeLabel() }}</span>
                                        <a href="{{ route('documents.show', $doc) }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline font-mono text-[11px]">
                                            <i class="fa-solid fa-paperclip mr-1"></i>{{ $doc->original_name }} ({{ number_format($doc->size / 1024, 0) }} KB)
                                        </a>
                                        @if($doc->notes)
                                            <p class="text-[11px] text-brandNavy/50 dark:text-slate-500 mt-1 italic">"{{ $doc->notes }}"</p>
                                        @endif
                                    </td>
                                    <td class="py-5 px-6 text-brandNavy/60 dark:text-slate-400">{{ $doc->created_at->format('M d, Y g:i A') }}</td>
                                    <td class="py-5 px-6 text-center">
                                        @if($doc->status === 'pending')
                                            <span class="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-brandGold/10 text-brandGold border border-brandGold/20 uppercase tracking-wider">Pending</span>
                                        @elseif($doc->status === 'accepted')
                                            <span class="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-brandGreen/10 text-brandGreen border border-brandGreen/20 uppercase tracking-wider">Accepted</span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-red-600/10 text-red-600 border border-red-600/20 uppercase tracking-wider">Rejected</span>
                                            @if($doc->remarks)
                                                <p class="text-[10px] text-red-500/80 mt-1 max-w-40 mx-auto">{{ $doc->remarks }}</p>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="py-5 px-6 text-right">
                                        @if($doc->status === 'pending')
                                            <div class="flex flex-col items-end gap-2">
                                                <form action="{{ route('registrar.documents.accept', $doc) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="px-4 py-2 bg-brandGreen hover:bg-emerald-600 text-white text-[11px] font-black rounded transition-colors tracking-wide">
                                                        <i class="fa-solid fa-check mr-1"></i>Accept
                                                    </button>
                                                </form>
                                                <form action="{{ route('registrar.documents.reject', $doc) }}" method="POST" class="flex items-center gap-2">
                                                    @csrf
                                                    <input type="text" name="remarks" required maxlength="500" placeholder="Reason for rejection"
                                                        class="w-44 bg-white dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-[11px] text-brandNavy dark:text-slate-200 placeholder-brandNavy/40 dark:placeholder-slate-500 px-3 py-2 rounded focus:outline-none focus:border-red-400">
                                                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-[11px] font-black rounded transition-colors tracking-wide">
                                                        Reject
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-[10px] text-brandNavy/40 dark:text-slate-500 uppercase tracking-wider">Reviewed</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-brandNavy/40 dark:text-slate-500 font-medium">
                                        <div class="flex flex-col items-center justify-center space-y-2">
                                            <i class="fa-solid fa-folder-open text-2xl text-brandNavy/20 dark:text-slate-600"></i>
                                            <span>No document submissions yet.</span>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

@include('partials.notif-script')
</body>
</html>
