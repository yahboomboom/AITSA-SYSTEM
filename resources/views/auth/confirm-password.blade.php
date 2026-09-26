<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Password | AITSA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">

    <div class="flex h-screen overflow-hidden">

        @if ($sidebarPartial)
            @include($sidebarPartial)
        @endif

        <main class="flex-1 flex flex-col overflow-hidden relative">
            <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10 flex-shrink-0 z-10 transition-colors duration-300">
                <div class="flex items-center gap-3">
                    @if ($sidebarPartial)
                        <button onclick="toggleMobileSidebar()" aria-label="Open sidebar menu" class="lg:hidden text-brandNavy/60 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white">
                            <i class="fa-solid fa-bars text-lg"></i>
                        </button>
                    @endif
                    <span class="font-heading text-2xl font-semibold leading-none text-brandNavy dark:text-slate-200">AITSA Staff</span>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="toggleTheme()" aria-label="Toggle dark mode" class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                        <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
                    </button>
                    @include('partials.profile-menu', ['roleLabel' => $roleLabel])
                </div>
            </header>

            {{-- Empty content area standing in for the real page — no sensitive
                 data is ever queried or rendered here until the password below
                 is confirmed. --}}
            <div class="flex-1 overflow-y-auto p-6 lg:p-8"></div>

            {{-- Modal overlay: a real page load underneath (so nothing sensitive
                 ever reaches the browser pre-confirmation), styled to read as a
                 popup over the app rather than a separate screen. --}}
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4 z-20">
                <div class="bg-white dark:bg-panelDark rounded-2xl shadow-2xl max-w-sm w-full p-8 animate-fade-in">
                    <div class="w-14 h-14 rounded-full bg-brandGold/10 text-brandGold flex items-center justify-center mx-auto mb-5 text-xl">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <h1 class="text-lg font-extrabold text-brandNavy dark:text-white mb-1 text-center">Confirm Your Password</h1>
                    <p class="text-sm text-brandNavy/60 dark:text-slate-400 mb-6 text-center">
                        For your security, please confirm your password before continuing.
                    </p>

                    @if ($errors->any())
                        <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-red-600 text-xs font-semibold">
                            {{ $errors->first('password') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.confirm.store') }}">
                        @csrf
                        <label for="password" class="block text-xs font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Password</label>
                        <div class="relative mb-5">
                            <input type="password" name="password" id="password" required autofocus
                                class="w-full border border-brandNavy/15 dark:border-slate-700 rounded-xl px-4 py-3 pr-11 text-sm text-brandNavy dark:text-slate-100 dark:bg-slate-900 focus:outline-none focus:border-brandGreen transition-colors">
                            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-brandNavy/40 dark:text-slate-500 hover:text-brandGreen dark:hover:text-brandGold transition-colors" onclick="togglePassword()" id="eyeBtn" aria-label="Show password">
                                <i class="fa-regular fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>

                        <button type="submit" class="w-full py-3 rounded-xl bg-brandNavy dark:bg-brandGreen text-white font-bold text-sm hover:opacity-90 transition-opacity">
                            Confirm
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <a href="{{ route('login') }}" class="text-xs font-medium text-brandNavy/50 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white transition-colors">Cancel</a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon  = document.getElementById('eyeIcon');
            const btn   = document.getElementById('eyeBtn');
            input.type = input.type === 'password' ? 'text' : 'password';
            icon.className = input.type === 'password' ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
            btn.setAttribute('aria-label', input.type === 'password' ? 'Show password' : 'Hide password');
        }
    </script>
</body>
</html>
