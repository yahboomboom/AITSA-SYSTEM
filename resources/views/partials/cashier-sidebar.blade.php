{{-- partials/cashier-sidebar.blade.php --}}
{{-- Shared sidebar for all Cashier pages. --}}
@php
    $cashierNavLinks = [
        ['route' => 'cashier.dashboard', 'label' => 'Cashier Hub', 'icon' => 'fa-gauge'],
        ['route' => 'cashier.transactions', 'label' => 'Transactions', 'icon' => 'fa-receipt'],
        ['route' => 'cashier.accounts', 'label' => 'Student Accounts', 'icon' => 'fa-user-group'],
        ['route' => 'cashier.billing', 'label' => 'Billing Setup', 'icon' => 'fa-sliders'],
    ];
@endphp
<div id="sidebar-backdrop" class="hidden fixed inset-0 bg-black/40 z-30 lg:hidden" onclick="closeMobileSidebar()"></div>
<aside id="app-sidebar" class="group fixed inset-y-0 left-0 z-40 flex flex-col w-64 -translate-x-full ui-sidebar transition-all duration-300 lg:translate-x-0 lg:static lg:z-auto">
    <div class="h-20 flex items-center justify-between px-6 border-b ui-sidebar-divider lg:group-[.sidebar-collapsed]:justify-center lg:group-[.sidebar-collapsed]:px-0">
        <div class="flex items-center min-w-0 lg:group-[.sidebar-collapsed]:hidden">
            <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-8 h-8 rounded-full object-cover ring-2 ring-brandGreen/50 mr-3 flex-shrink-0">
            <h1 class="sidebar-label font-heading text-lg font-semibold tracking-tight ui-sidebar-title truncate">AITSA Staff</h1>
        </div>
        <button onclick="toggleSidebarCollapse()" class="hidden lg:flex w-7 h-7 flex-shrink-0 items-center justify-center rounded ui-sidebar-icon transition-colors" title="Collapse sidebar" aria-label="Collapse sidebar">
            <i id="sidebar-collapse-icon" class="fa-solid fa-angles-left text-xs"></i>
        </button>
        <button onclick="closeMobileSidebar()" class="lg:hidden w-7 h-7 flex-shrink-0 flex items-center justify-center rounded ui-sidebar-icon" title="Close menu" aria-label="Close menu">
            <i class="fa-solid fa-xmark text-sm"></i>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto py-5 px-3 space-y-0.5">
        <p class="sidebar-label px-3 text-[11px] font-semibold ui-sidebar-muted mb-3">Management</p>
        @foreach ($cashierNavLinks as $link)
            <a href="{{ route($link['route']) }}" title="{{ $link['label'] }}"
               class="flex items-center space-x-3 px-3 py-2.5 border-l-2 text-sm transition-colors {{ Route::is($link['route']) ? 'ui-nav-active font-semibold' : 'ui-nav-link font-medium' }}">
                <i class="fa-solid {{ $link['icon'] }} w-4 text-center flex-shrink-0"></i>
                <span class="sidebar-label">{{ $link['label'] }}</span>
            </a>
        @endforeach
    </nav>
</aside>
@include('partials.sidebar-toggle-script')
