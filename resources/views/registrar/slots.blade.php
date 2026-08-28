<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Staff | Admission Slots</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
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

                <a href="{{ route('registrar.slots') }}" class="flex items-center px-3 py-2.5 border-l-2 {{ Route::is('registrar.slots') ? 'border-brandGreen text-brandGreen dark:text-emerald-400 font-bold' : 'border-transparent text-brandNavy/60 hover:text-brandNavy dark:text-slate-400 dark:hover:text-white font-medium' }} text-sm transition-colors">
                    <span>Admission Slots</span>
                </a>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden relative">

            <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 z-10 transition-colors duration-300">
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-bold text-brandNavy dark:text-slate-200">Admission Slots</span>
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

                {{-- Success / error banners --}}
                @if(session('success'))
                    <div class="p-4 rounded-lg bg-brandGreen/10 border border-brandGreen/20 text-brandGreen font-bold text-xs">
                        <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
                    </div>
                @endif
                @if($errors->any())
                    <div class="p-4 rounded-lg bg-red-500/10 border border-red-500/20 text-red-600 font-bold text-xs space-y-1">
                        @foreach($errors->all() as $error)
                            <p><i class="fa-solid fa-triangle-exclamation mr-2"></i>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="space-y-1">
                    <h1 class="text-2xl font-extrabold tracking-tight text-brandNavy dark:text-white">Admission Slots</h1>
                    <p class="text-sm text-brandNavy/60 dark:text-slate-400">
                        Set how many total slots each curriculum has for <span class="font-bold">{{ $schoolYear }}</span>, split evenly across sections.
                        Slots are consumed as applicants get marked "Reserved" from the Applicants list.
                    </p>
                </div>

                                <div class="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-xl overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-lightBg dark:bg-slate-900/40 text-[11px] uppercase tracking-wider text-brandNavy/50 dark:text-slate-400">
                            <tr>
                                <th class="text-left px-5 py-3 font-bold">Curriculum</th>
                                <th class="text-left px-5 py-3 font-bold">Level</th>
                                <th class="text-left px-5 py-3 font-bold">Total Slots</th>
                                <th class="text-left px-5 py-3 font-bold">Sections</th>
                                <th class="text-left px-5 py-3 font-bold">Per Section</th>
                                <th class="text-left px-5 py-3 font-bold">Taken / Left</th>
                                <th class="text-left px-5 py-3 font-bold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brandNavy/5 dark:divide-slate-800">
                            @foreach($curricula as $c)
                            {{--
                                IMPORTANT: a <form> can NOT legally wrap <td> elements as a direct
                                child of <tr> — the browser's HTML parser "foster-parents" it out of
                                the table when it renders, silently detaching the form from its own
                                inputs. That's why Save looked fine but never actually saved anything.

                                Fix: keep the <td>/<input> markup exactly where the table needs it,
                                but point each input (and the submit button) at a real <form> that
                                lives OUTSIDE the table, using the HTML5 form="..." attribute.
                            --}}
                            <tr>
                                <td class="px-5 py-3 font-bold text-brandNavy dark:text-white">{{ $c['programName'] }}</td>
                                <td class="px-5 py-3 text-xs text-brandNavy/60 dark:text-slate-400">{{ $c['level'] }}</td>
                                <td class="px-5 py-3">
                                    <input type="number" name="total_slots" min="1" value="{{ $c['totalSlots'] }}"
                                           form="slot-form-{{ $c['id'] }}"
                                           class="w-24 bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-700 rounded-lg px-2 py-1 text-xs">
                                </td>
                                <td class="px-5 py-3">
                                    <input type="number" name="sections" min="1" value="{{ $c['sections'] }}"
                                           form="slot-form-{{ $c['id'] }}"
                                           class="w-16 bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-700 rounded-lg px-2 py-1 text-xs">
                                </td>
                                <td class="px-5 py-3 text-xs text-brandNavy/60 dark:text-slate-400">{{ $c['perSection'] }} seats each</td>
                                <td class="px-5 py-3 text-xs">
                                    <span class="font-bold {{ $c['slotsLeft'] <= 0 ? 'text-red-500' : 'text-brandGreen' }}">
                                        {{ $c['taken'] }} taken · {{ $c['slotsLeft'] }} left
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <button type="submit" form="slot-form-{{ $c['id'] }}" class="text-[11px] font-bold text-white bg-brandNavy hover:bg-brandNavy/90 px-3 py-1.5 rounded-lg transition-colors">
                                        Save
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- One real <form> per curriculum row, kept outside the table for valid HTML.
                     Each input/button above points here via its form="slot-form-{id}" attribute. --}}
                @foreach($curricula as $c)
                    <form id="slot-form-{{ $c['id'] }}" action="{{ route('registrar.slots.update', $c['id']) }}" method="POST" class="hidden">
                        @csrf
                    </form>
                @endforeach

            </div>
        </main>
    </div>

</body>
</html>