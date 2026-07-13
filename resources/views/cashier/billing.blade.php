<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Staff | Billing Configuration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brandNavy: '#0B3C5D', brandGreen: '#1D7A46', brandGold: '#E2A700',
                        darkBg: '#121212', lightBg: '#EFF3F7', panelDark: '#1E1E1E'
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
            const isDark = document.documentElement.classList.toggle('dark');
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
                <h1 class="text-base font-black text-brandNavy dark:text-white">AITSA <span class="text-xs text-brandGreen font-mono px-1.5 py-0.5 bg-brandGreen/10 rounded ml-1">Staff</span></h1>
            </div>

            <nav class="flex-1 overflow-y-auto py-5 px-3 space-y-0.5">
                <p class="px-3 text-[10px] font-bold text-brandNavy/40 dark:text-slate-500 uppercase tracking-widest mb-3">Management</p>

                <a href="{{ route('cashier.dashboard') }}"
                   class="flex items-center px-3 py-2.5 border-l-2 text-sm transition-colors border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium">
                    <span>Cashier Hub</span>
                </a>

                <a href="{{ route('cashier.transactions') }}"
                   class="flex items-center px-3 py-2.5 border-l-2 text-sm transition-colors border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium">
                    <span>Transactions</span>
                </a>

                <a href="{{ route('cashier.accounts') }}"
                   class="flex items-center px-3 py-2.5 border-l-2 text-sm transition-colors border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium">
                    <span>Student Accounts</span>
                </a>

                <a href="{{ route('cashier.billing') }}"
                   class="flex items-center px-3 py-2.5 border-l-2 text-sm transition-colors border-brandGreen text-brandGreen dark:text-emerald-400 font-bold">
                    <span>Billing Setup</span>
                </a>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden relative">
            <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 flex-shrink-0 z-10 transition-colors duration-300">
                <span class="text-sm font-bold text-brandNavy dark:text-slate-200">Cashier Operations</span>
                <div class="flex items-center gap-3">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', ['roleLabel' => 'Cashier Staff'])
                </div>
            </header>
            <div class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-5">

                <div>
                    <h1 class="text-xl font-bold text-brandNavy dark:text-white">Billing Configuration</h1>
                    <p class="text-xs text-brandNavy/50 dark:text-slate-400">Fee rates, discount types, and per-student discount assignment.</p>
                </div>

                @if(session('success'))
                    <div class="p-4 rounded-lg bg-brandGreen/10 border border-brandGreen/20 text-brandGreen font-bold text-xs">
                        <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="p-4 rounded-lg bg-red-600/10 border border-red-600/20 text-red-600 font-bold text-xs">
                        <i class="fa-solid fa-circle-xmark mr-2"></i>{{ $errors->first() }}
                    </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

                    {{-- FEE RATES --}}
                    <div class="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg overflow-hidden">
                        <div class="p-5 border-b border-brandNavy/8 dark:border-slate-800">
                            <h2 class="text-sm font-bold text-brandNavy dark:text-white"><i class="fa-solid fa-coins mr-2 text-brandGold"></i>Fee Rates</h2>
                        </div>
                        <form action="{{ route('cashier.billing.fees') }}" method="POST" class="p-5 space-y-4 text-xs">
                            @csrf
                            <div>
                                <label class="block font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-widest text-[10px] mb-1.5">Tuition per unit (₱)</label>
                                <input type="number" name="tuition_per_unit" min="0" required value="{{ $tuitionPerUnit }}"
                                    class="w-full bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-brandNavy dark:text-slate-200 px-3 py-2.5 rounded focus:outline-none focus:border-brandGreen">
                            </div>
                            <div>
                                <label class="block font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-widest text-[10px] mb-1.5">Miscellaneous fee per term (₱)</label>
                                <input type="number" name="misc_fee" min="0" required value="{{ $miscFee }}"
                                    class="w-full bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-brandNavy dark:text-slate-200 px-3 py-2.5 rounded focus:outline-none focus:border-brandGreen">
                            </div>
                            <button type="submit" class="px-5 py-2.5 bg-brandNavy hover:bg-brandGreen text-white font-black rounded text-[11px] uppercase tracking-wider transition-colors">
                                Save Rates
                            </button>
                        </form>
                    </div>

                    {{-- DISCOUNT TYPES --}}
                    <div class="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg overflow-hidden">
                        <div class="p-5 border-b border-brandNavy/8 dark:border-slate-800">
                            <h2 class="text-sm font-bold text-brandNavy dark:text-white"><i class="fa-solid fa-percent mr-2 text-brandGreen"></i>Discount Types <span class="font-normal text-brandNavy/40 dark:text-slate-500">(applies to tuition only)</span></h2>
                        </div>
                        <div class="p-5 space-y-4 text-xs">
                            <form action="{{ route('cashier.billing.discounts') }}" method="POST" class="flex flex-wrap items-end gap-2">
                                @csrf
                                <div class="flex-1 min-w-32">
                                    <label class="block font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-widest text-[10px] mb-1.5">Name</label>
                                    <input type="text" name="name" required maxlength="100" placeholder="e.g. Academic Scholar"
                                        class="w-full bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-brandNavy dark:text-slate-200 px-3 py-2.5 rounded focus:outline-none focus:border-brandGreen">
                                </div>
                                <div class="w-20">
                                    <label class="block font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-widest text-[10px] mb-1.5">%</label>
                                    <input type="number" name="percent" min="1" max="100" required
                                        class="w-full bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-brandNavy dark:text-slate-200 px-3 py-2.5 rounded focus:outline-none focus:border-brandGreen">
                                </div>
                                <button type="submit" class="px-4 py-2.5 bg-brandGreen hover:bg-emerald-600 text-white font-black rounded text-[11px] uppercase tracking-wider transition-colors">
                                    Add
                                </button>
                            </form>

                            <div class="divide-y divide-brandNavy/5 dark:divide-slate-800/60">
                                @forelse($discountTypes as $type)
                                    <div class="py-2.5 flex items-center justify-between gap-2">
                                        <div>
                                            <span class="font-bold text-brandNavy dark:text-slate-200">{{ $type->name }}</span>
                                            <span class="text-brandGreen font-black ml-2">{{ $type->percent }}%</span>
                                            <span class="text-brandNavy/40 dark:text-slate-500 ml-2">{{ $type->students_count }} student(s)</span>
                                        </div>
                                        <form action="{{ route('cashier.billing.discounts.delete', $type) }}" method="POST"
                                              onsubmit="return confirm('Remove {{ $type->name }}? Students with this discount will lose it.');">
                                            @csrf
                                            <button type="submit" class="w-7 h-7 rounded bg-red-600/10 text-red-600 hover:bg-red-600 hover:text-white transition-colors">
                                                <i class="fa-solid fa-trash-can text-[10px]"></i>
                                            </button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="py-3 text-brandNavy/40 dark:text-slate-500">No discount types yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STUDENT DISCOUNT ASSIGNMENT --}}
                <div class="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg overflow-hidden">
                    <div class="p-5 border-b border-brandNavy/8 dark:border-slate-800">
                        <h2 class="text-sm font-bold text-brandNavy dark:text-white"><i class="fa-solid fa-user-tag mr-2 text-brandNavy dark:text-slate-300"></i>Student Discounts</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-brandNavy/10 dark:border-slate-800 bg-lightBg dark:bg-slate-900/40 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">
                                    <th class="py-3.5 px-5">Student</th>
                                    <th class="py-3.5 px-5">Program / Year</th>
                                    <th class="py-3.5 px-5">Discount</th>
                                    <th class="py-3.5 px-5 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800/40 text-xs">
                                @forelse($students as $student)
                                    <tr class="hover:bg-lightBg dark:hover:bg-slate-800/20 transition-colors">
                                        <td class="py-3.5 px-5">
                                            <span class="font-bold text-brandNavy dark:text-white block">{{ $student->name }}</span>
                                            <span class="font-mono text-brandNavy/60 dark:text-slate-400">{{ $student->login_id ?? '—' }}</span>
                                        </td>
                                        <td class="py-3.5 px-5 text-brandNavy/60 dark:text-slate-400">{{ $student->major ?? '—' }} · {{ $student->year_level ?? '—' }}</td>
                                        <td class="py-3.5 px-5" colspan="2">
                                            <form action="{{ route('cashier.billing.assign', $student) }}" method="POST" class="flex items-center justify-between gap-2">
                                                @csrf
                                                <select name="discount_type_id"
                                                    class="bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-brandNavy dark:text-slate-200 px-3 py-2 rounded focus:outline-none focus:border-brandGreen">
                                                    <option value="">— None —</option>
                                                    @foreach($discountTypes as $type)
                                                        <option value="{{ $type->id }}" @selected($student->discount_type_id === $type->id)>{{ $type->name }} ({{ $type->percent }}%)</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="px-4 py-2 bg-brandNavy hover:bg-brandGreen text-white text-[11px] font-black rounded transition-colors tracking-wide">
                                                    Save
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-10 text-center text-brandNavy/40 dark:text-slate-500 font-medium">No student accounts found.</td>
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
