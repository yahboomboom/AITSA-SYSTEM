<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | My Profile</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-sans antialiased transition-colors duration-300">
<div class="min-h-screen flex flex-col">

    <header class="h-20 bg-white/80 dark:bg-panelDark/80 backdrop-blur-md border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between px-6 lg:px-10">
        <div class="flex items-center gap-3">
            <a href="{{ $homeUrl }}" title="Back to Home"
               class="w-9 h-9 flex items-center justify-center rounded-lg text-brandNavy/60 hover:text-brandNavy hover:bg-brandNavy/5 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition-colors">
                <i class="fa-solid fa-arrow-left text-sm"></i>
            </a>
            <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-8 h-8 rounded-lg object-cover">
            <h2 class="text-base font-bold text-brandNavy dark:text-slate-100">My Profile</h2>
        </div>
        <div class="flex items-center gap-4">
            @include('partials.notif-bell')
            <button onclick="toggleTheme()" aria-label="Toggle dark mode" class="w-9 h-9 rounded-full bg-lightBg dark:bg-darkBg text-brandNavy dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/10 dark:hover:bg-slate-800 transition-colors">
                <i id="theme-icon" class="fa-solid fa-moon text-sm"></i>
            </button>
            @include('partials.profile-menu')
        </div>
    </header>

    <main class="flex-1 p-6 lg:p-10 max-w-md w-full mx-auto space-y-6">
        @if (session('success'))
            <div class="p-3.5 rounded-lg bg-brandGreen/10 border border-brandGreen/20 text-brandGreen text-xs font-bold">
                <i class="fa-solid fa-circle-check mr-2"></i>{{ session('success') }}
            </div>
        @endif

        <div id="profile-root" data-context="{{ json_encode([
            'user' => [
                'name' => $user->name,
                'loginId' => $user->login_id,
                'email' => old('email', $user->email),
                'contactNumber' => old('contact_number', $user->contact_number),
                'address' => old('address', $user->address),
            ],
            'errors' => $errors->messages(),
            'updateUrl' => route('profile.update'),
            'passwordUpdateUrl' => route('profile.password.update'),
            'passwordResetLinkUrl' => route('profile.password.reset-link'),
            'csrfToken' => csrf_token(),
        ]) }}">
            <p class="text-sm text-slate-500">Loading…</p>
        </div>
    </main>
</div>

@include('partials.notif-script')
@viteReactRefresh
@vite('resources/js/profile-app.jsx')
</body>
</html>
