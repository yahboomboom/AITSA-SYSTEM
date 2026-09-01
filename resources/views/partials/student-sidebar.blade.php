{{-- partials/student-sidebar.blade.php --}}
{{-- Shared "Main Menu" sidebar for all student-facing pages (dashboard, documents, clearance, enrollment, ledger, schedule).
     Slides over on mobile/tablet (toggled via #app-sidebar-toggle / window.toggleMobileSidebar) and
     collapses to icon-only on desktop (window.toggleSidebarCollapse, remembered via localStorage). --}}
@php
    $studentNavLinks = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-gauge'],
        ['route' => 'documents', 'label' => 'Documents', 'icon' => 'fa-folder-open'],
        ['route' => 'clearance', 'label' => 'Clearance Routing', 'icon' => 'fa-route'],
        ['route' => 'enrollment', 'label' => 'Enrollment', 'icon' => 'fa-user-graduate'],
        ['route' => 'grades', 'label' => 'Grades', 'icon' => 'fa-chart-line'],
        ['route' => 'ledger', 'label' => 'Payments', 'icon' => 'fa-wallet'],
        ['route' => 'cor', 'label' => 'Schedule', 'icon' => 'fa-calendar-days'],
    ];
@endphp
<div id="sidebar-backdrop" class="hidden fixed inset-0 bg-black/40 z-30 lg:hidden" onclick="closeMobileSidebar()"></div>
<aside id="app-sidebar" class="fixed inset-y-0 left-0 z-40 flex flex-col w-64 -translate-x-full bg-white dark:bg-panelDark border-r border-brandNavy/10 dark:border-slate-800 transition-all duration-300 lg:translate-x-0 lg:static lg:z-auto">
    <div class="h-20 flex items-center justify-between px-6 border-b border-brandNavy/10 dark:border-slate-800">
        <div class="flex items-center min-w-0">
            <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-8 h-8 rounded-lg object-cover mr-3 flex-shrink-0">
            <h1 class="sidebar-label text-xl font-black tracking-tight text-brandNavy dark:text-white truncate">AITSA</h1>
        </div>
        <button onclick="toggleSidebarCollapse()" class="hidden lg:flex w-7 h-7 flex-shrink-0 items-center justify-center rounded text-brandNavy/40 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white transition-colors" title="Collapse sidebar">
            <i id="sidebar-collapse-icon" class="fa-solid fa-angles-left text-xs"></i>
        </button>
        <button onclick="closeMobileSidebar()" class="lg:hidden w-7 h-7 flex-shrink-0 flex items-center justify-center rounded text-brandNavy/40 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white" title="Close menu">
            <i class="fa-solid fa-xmark text-sm"></i>
        </button>
    </div>
    <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-2">
        <p class="sidebar-label px-4 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest mb-2">Main Menu</p>
        @foreach ($studentNavLinks as $link)
            <a href="{{ route($link['route']) }}" title="{{ $link['label'] }}"
               class="flex items-center space-x-3 px-4 py-3 rounded-xl text-sm transition-colors duration-300 {{ Route::is($link['route']) ? 'bg-brandGreen/10 text-brandGreen dark:bg-brandGreen/20 dark:text-emerald-400 font-bold' : 'text-brandNavy/60 hover:bg-brandNavy/5 hover:text-brandNavy dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white font-medium' }}">
                <i class="fa-solid {{ $link['icon'] }} w-4 text-center flex-shrink-0"></i>
                <span class="sidebar-label">{{ $link['label'] }}</span>
            </a>
        @endforeach
    </nav>
</aside>
@include('partials.sidebar-toggle-script')
