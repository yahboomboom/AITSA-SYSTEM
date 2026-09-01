{{-- partials/student-sidebar.blade.php --}}
{{-- Shared "Main Menu" sidebar for all student-facing pages (dashboard, documents, clearance, enrollment, ledger, schedule). --}}
@php
    $studentNavLinks = [
        ['route' => 'dashboard', 'label' => 'Dashboard'],
        ['route' => 'documents', 'label' => 'Documents'],
        ['route' => 'clearance', 'label' => 'Clearance Routing'],
        ['route' => 'enrollment', 'label' => 'Enrollment'],
        ['route' => 'grades', 'label' => 'Grades'],
        ['route' => 'ledger', 'label' => 'Payments'],
        ['route' => 'cor', 'label' => 'Schedule'],
    ];
@endphp
<aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-panelDark border-r border-brandNavy/10 dark:border-slate-800 transition-colors duration-300">
    <div class="h-20 flex items-center px-8 border-b border-brandNavy/10 dark:border-slate-800">
        <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-8 h-8 rounded-lg object-cover mr-3">
        <h1 class="text-xl font-black tracking-tight text-brandNavy dark:text-white">AITSA</h1>
    </div>
    <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-2">
        <p class="px-4 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest mb-2">Main Menu</p>
        @foreach ($studentNavLinks as $link)
            <a href="{{ route($link['route']) }}"
               class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm transition-colors duration-300 {{ Route::is($link['route']) ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }}">
                <span>{{ $link['label'] }}</span>
            </a>
        @endforeach
    </nav>
</aside>
