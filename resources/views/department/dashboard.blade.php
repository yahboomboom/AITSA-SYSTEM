<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Staff | Department Clearance Queue</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: {
                brandNavy: '#0B3C5D', brandGreen: '#1D7A46', brandGold: '#E2A700',
                darkBg: '#121212', lightBg: '#EFF3F7', panelDark: '#1E1E1E',
            } } }
        }
    </script>
    <script>
        if (localStorage.getItem('theme') === 'dark') { document.documentElement.classList.add('dark'); }
        function updateThemeIcon() {
            const icon = document.getElementById('theme-icon');
            if (icon) icon.className = document.documentElement.classList.contains('dark') ? 'fa-solid fa-sun text-sm' : 'fa-solid fa-moon text-sm';
        }
        function toggleTheme() {
            const html = document.documentElement;
            html.classList.toggle('dark');
            updateThemeIcon(); localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
        }
        document.addEventListener('DOMContentLoaded', updateThemeIcon);
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

    <div class="flex h-screen overflow-hidden">
        <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-panelDark border-r border-brandNavy/10 dark:border-slate-800 transition-colors duration-300">
            <div class="h-16 flex items-center px-6 border-b border-brandNavy/10 dark:border-slate-800">
                <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-7 h-7 rounded object-cover mr-3">
                <h1 class="text-base font-black tracking-tight text-brandNavy dark:text-white">AITSA</h1>
            </div>
            <nav class="flex-1 overflow-y-auto py-5 px-3 space-y-0.5">
                <p class="px-3 text-[10px] font-bold text-brandNavy/40 dark:text-slate-500 uppercase tracking-widest mb-3">{{ Auth::user()->department->name ?? 'Department' }}</p>
                <a href="{{ route('department.dashboard') }}" class="flex items-center px-3 py-2.5 border-l-2 border-brandGreen text-brandGreen dark:text-emerald-400 font-bold text-sm">
                    <span>Clearance Queue</span>
                </a>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden relative">
            <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
                <h2 class="text-sm font-bold text-brandNavy dark:text-slate-100">{{ Auth::user()->department->name ?? 'Department' }} Clearance Queue</h2>
                <div class="flex items-center space-x-3 border-l border-brandNavy/10 dark:border-slate-700 pl-4">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', ['roleLabel' => 'Department Officer'])
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6 lg:p-10 space-y-6">
                @if(session('success'))
                    <div class="p-4 rounded-xl bg-brandGreen/10 border border-brandGreen/20 text-brandGreen font-bold text-xs">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="p-4 rounded-xl bg-red-600/10 border border-red-600/20 text-red-600 font-bold text-xs">{{ $errors->first() }}</div>
                @endif

                <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="bg-lightBg dark:bg-slate-800/40 border-b border-brandNavy/8 dark:border-slate-800 text-brandNavy/40 dark:text-slate-500 font-bold uppercase tracking-wider">
                                    <th class="p-4">Student</th>
                                    <th class="p-4">Student No.</th>
                                    <th class="p-4">Status</th>
                                    <th class="p-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800/60">
                                @forelse($items as $item)
                                    <tr>
                                        <td class="p-4 font-bold text-brandNavy dark:text-white">{{ $item->clearance->user->name ?? 'Unknown' }}</td>
                                        <td class="p-4 font-mono text-brandNavy/60 dark:text-slate-400">{{ $item->clearance->user->login_id ?? 'N/A' }}</td>
                                        <td class="p-4">
                                            @if($item->status === 'Approved')
                                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-brandGreen/10 text-brandGreen border border-brandGreen/20 rounded">Approved</span>
                                            @elseif($item->status === 'Hold')
                                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-red-500/10 text-red-600 border border-red-500/20 rounded">Hold: {{ $item->remarks }}</span>
                                            @else
                                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-brandGold/10 text-brandGold border border-brandGold/20 rounded">Pending</span>
                                            @endif
                                        </td>
                                        <td class="p-4 text-right">
                                            @if($item->status === 'Approved')
                                                <button disabled class="px-3 py-1.5 bg-lightBg dark:bg-slate-800 text-brandNavy/30 dark:text-slate-600 rounded text-[11px] font-bold border border-brandNavy/8 dark:border-slate-700 cursor-not-allowed">Approved</button>
                                            @else
                                                <div class="flex items-center justify-end gap-2">
                                                    <form action="{{ route('department.items.approve', $item) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="px-3 py-1.5 bg-brandGreen hover:bg-emerald-600 text-white text-[11px] font-bold rounded">Approve</button>
                                                    </form>
                                                    <form action="{{ route('department.items.hold', $item) }}" method="POST" class="flex items-center gap-2">
                                                        @csrf
                                                        <input type="text" name="remarks" required maxlength="500" placeholder="Reason for hold" class="w-40 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-[11px] text-brandNavy dark:text-slate-200 outline-none">
                                                        <button type="submit" class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-[11px] font-bold rounded">Hold</button>
                                                    </form>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="p-8 text-center text-brandNavy/40 dark:text-slate-500 text-sm">No students in your queue.</td>
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
