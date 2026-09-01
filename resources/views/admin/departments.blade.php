<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | Departments</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

    <div class="flex h-screen overflow-hidden">

        @include('partials.admin-sidebar')

        <main class="flex-1 flex flex-col overflow-hidden relative">

            <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
                <div class="flex items-center">
                    <button onclick="toggleMobileSidebar()" class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white mr-4">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                    <h2 class="text-sm font-bold text-brandNavy dark:text-slate-100">Departments</h2>
                </div>
                <div class="flex items-center space-x-3 border-l border-brandNavy/10 dark:border-slate-700 pl-4">
                    @include('partials.notif-bell')
                    <button onclick="toggleTheme()" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', [
                        'roleLabel'   => 'Root Access Mode',
                        'roleClass'   => 'text-red-500 uppercase tracking-wider',
                        'avatarClass' => 'bg-red-500/10 dark:bg-red-500/20 text-red-500',
                    ])
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6 lg:p-10 space-y-6">

                @if(session('success'))
                    <div class="p-4 rounded-xl bg-brandGreen/10 border border-brandGreen/20 text-brandGreen font-bold text-xs">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="p-4 rounded-xl bg-red-600/10 border border-red-600/20 text-red-600 font-bold text-xs">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="p-4 rounded-xl bg-red-600/10 border border-red-600/20 text-red-600 font-bold text-xs">{{ $errors->first() }}</div>
                @endif

                <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl p-6">
                    <h3 class="text-sm font-bold text-brandNavy dark:text-white mb-4">Add Department</h3>
                    <form action="{{ route('admin.departments.store') }}" method="POST" class="flex gap-3">
                        @csrf
                        <input type="text" name="name" required maxlength="100" placeholder="e.g. Library"
                               class="flex-1 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 outline-none focus:border-brandGreen/40">
                        <button type="submit" class="px-5 py-2.5 bg-brandGreen hover:bg-emerald-600 text-white font-bold text-xs rounded uppercase tracking-wider">Add</button>
                    </form>
                </div>

                <div class="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="bg-lightBg dark:bg-slate-800/40 border-b border-brandNavy/8 dark:border-slate-800 text-brandNavy/40 dark:text-slate-500 font-bold uppercase tracking-wider">
                                    <th class="p-4">Department</th>
                                    <th class="p-4">Officers</th>
                                    <th class="p-4">Status</th>
                                    <th class="p-4">Add Officer</th>
                                    <th class="p-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800/60">
                                @forelse($departments as $department)
                                    <tr>
                                        <td class="p-4 font-bold text-brandNavy dark:text-white">{{ $department->name }}</td>
                                        <td class="p-4 text-brandNavy/60 dark:text-slate-400">{{ $department->officers_count }}</td>
                                        <td class="p-4">
                                            @if($department->is_active)
                                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-brandGreen/10 text-brandGreen border border-brandGreen/20 rounded">Active</span>
                                            @else
                                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-brandNavy/10 text-brandNavy/50 dark:text-slate-500 border border-brandNavy/10 rounded">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="p-4">
                                            <form action="{{ route('admin.departments.officers.store', $department) }}" method="POST" class="flex gap-2">
                                                @csrf
                                                <input type="text" name="name" required maxlength="100" placeholder="Officer name" class="w-32 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-[11px] text-brandNavy dark:text-slate-200 outline-none">
                                                <input type="text" name="login_id" required maxlength="50" placeholder="Login ID" class="w-24 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-2.5 py-1.5 text-[11px] text-brandNavy dark:text-slate-200 outline-none">
                                                <button type="submit" class="px-3 py-1.5 bg-brandNavy hover:bg-brandGreen text-white text-[11px] font-bold rounded">Create</button>
                                            </form>
                                        </td>
                                        <td class="p-4 text-right">
                                            <form action="{{ route('admin.departments.toggle', $department) }}" method="POST"
                                                onsubmit="return confirmDepartmentToggle(this, {{ Illuminate\Support\Js::from((bool) $department->is_active) }}, {{ Illuminate\Support\Js::from($department->name) }})">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 bg-lightBg dark:bg-slate-800 hover:bg-brandNavy/10 text-brandNavy dark:text-slate-300 text-[11px] font-bold rounded border border-brandNavy/10 dark:border-slate-700">
                                                    {{ $department->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="p-8 text-center text-brandNavy/40 dark:text-slate-500 text-sm">No departments yet. Add one above.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
<script>
    function confirmDepartmentToggle(form, isActive, name) {
        var msg = isActive
            ? 'Deactivate "' + name + '"? New clearance routing will no longer include this department until it is reactivated.'
            : 'Activate "' + name + '" again?';
        if (!confirm(msg)) return false;
        var btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Please wait…';
        return true;
    }
</script>
@include('partials.notif-script')
</body>
</html>
