{{-- partials/admin-sidebar.blade.php --}}
{{-- Shared sidebar for all Super Administrator pages. --}}
@php
    $adminNavLinks = [
        ['route' => 'admin.dashboard', 'label' => 'System Overview', 'icon' => 'fa-gauge'],
        ['route' => 'admin.students.create', 'label' => 'Create Student Account', 'icon' => 'fa-user-plus'],
        ['route' => 'admin.students.index', 'label' => 'Student Registry', 'icon' => 'fa-users'],
        ['route' => 'admin.departments', 'label' => 'Departments', 'icon' => 'fa-building'],
        ['route' => 'admin.audit', 'label' => 'Audit Trail', 'icon' => 'fa-clipboard-list'],
        ['route' => 'admin.reports', 'label' => 'Reports', 'icon' => 'fa-chart-pie'],
    ];
@endphp
<div id="sidebar-backdrop" class="hidden fixed inset-0 bg-black/40 z-30 lg:hidden" onclick="closeMobileSidebar()"></div>
<aside id="app-sidebar" class="fixed inset-y-0 left-0 z-40 flex flex-col w-64 -translate-x-full bg-white dark:bg-panelDark border-r border-brandNavy/10 dark:border-slate-800 transition-all duration-300 lg:translate-x-0 lg:static lg:z-auto">
    <div class="h-16 flex items-center justify-between px-6 border-b border-brandNavy/10 dark:border-slate-800">
        <div class="flex items-center min-w-0">
            <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-7 h-7 rounded object-cover mr-3 flex-shrink-0">
            <h1 class="sidebar-label text-base font-black tracking-tight text-brandNavy dark:text-white truncate">AITSA HQ</h1>
        </div>
        <button onclick="toggleSidebarCollapse()" class="hidden lg:flex w-7 h-7 flex-shrink-0 items-center justify-center rounded text-brandNavy/40 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white transition-colors" title="Collapse sidebar">
            <i id="sidebar-collapse-icon" class="fa-solid fa-angles-left text-xs"></i>
        </button>
        <button onclick="closeMobileSidebar()" class="lg:hidden w-7 h-7 flex-shrink-0 flex items-center justify-center rounded text-brandNavy/40 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white" title="Close menu">
            <i class="fa-solid fa-xmark text-sm"></i>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto py-5 px-3 space-y-0.5">
        <p class="sidebar-label px-3 text-[10px] font-bold text-brandNavy/40 dark:text-slate-500 uppercase tracking-widest mb-3">Core Control</p>
        @foreach ($adminNavLinks as $link)
            <a href="{{ route($link['route']) }}" title="{{ $link['label'] }}"
               class="flex items-center space-x-3 px-3 py-2.5 border-l-2 text-sm transition-colors {{ Route::is($link['route']) ? 'border-brandGreen text-brandGreen dark:text-emerald-400 font-bold' : 'border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium' }}">
                <i class="fa-solid {{ $link['icon'] }} w-4 text-center flex-shrink-0"></i>
                <span class="sidebar-label">{{ $link['label'] }}</span>
            </a>
        @endforeach
    </nav>
</aside>
@include('partials.sidebar-toggle-script')
