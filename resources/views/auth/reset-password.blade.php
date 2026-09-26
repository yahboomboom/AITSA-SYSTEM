<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | Reset Password</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')

    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; }

        .fl-wrap { position: relative; }
        .fl-wrap input {
            padding: 1.5rem 1rem 0.625rem;
            height: 3.625rem;
        }
        .fl-wrap label {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.8125rem;
            color: #64748b;
            pointer-events: none;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            transform-origin: left top;
        }
        .dark .fl-wrap label { color: #94a3b8; }
        .fl-wrap input:focus ~ label,
        .fl-wrap input:not(:placeholder-shown) ~ label {
            top: 0.7rem;
            transform: translateY(0) scale(0.72);
            font-weight: 600;
            letter-spacing: 0.02em;
            color: #1D7A46;
        }
        .dark .fl-wrap input:focus ~ label,
        .dark .fl-wrap input:not(:placeholder-shown) ~ label { color: #E2A700; }

        .fl-bar {
            position: absolute;
            bottom: 0; left: 0;
            width: 0; height: 2px;
            background: #1D7A46;
            transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 0 0 2px 2px;
        }
        .dark .fl-bar { background: #E2A700; }
        .fl-wrap input:focus ~ .fl-bar { width: 100%; }

        .btn-primary { position: relative; overflow: hidden; }
        .btn-primary::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.18) 50%, transparent 100%);
            transform: translateX(-100%);
        }
        .btn-primary:hover::after { animation: shimmer 0.75s ease forwards; }
        @keyframes shimmer {
            from { transform: translateX(-100%); }
            to   { transform: translateX(150%); }
        }

        .dot-grid {
            background-image: radial-gradient(circle, rgba(11,60,93,.04) 1px, transparent 1px);
            background-size: 22px 22px;
        }
        .dark .dot-grid {
            background-image: radial-gradient(circle, rgba(226,167,0,.045) 1px, transparent 1px);
        }

        .logo-crest { position: relative; width: 64px; height: 64px; flex-shrink: 0; }
        .logo-img-wrap {
            position: absolute; inset: 0;
            border-radius: 50%;
            overflow: hidden;
            background: white;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 0 2px rgba(11,60,93,0.12);
        }
        .dark .logo-img-wrap { background: #0D1B2A; box-shadow: 0 0 0 2px rgba(226,167,0,0.15); }

        .eye-btn {
            position: absolute; right: .875rem; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: #64748b; font-size: .875rem; padding: 0;
        }
        .dark .eye-btn { color: #94a3b8; }
        .eye-btn:hover { color: #1D7A46; }
        .dark .eye-btn:hover { color: #E2A700; }
    </style>
</head>

<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-body min-h-screen flex items-center justify-center antialiased dot-grid px-4">

    <div class="max-w-sm w-full space-y-7 py-10">

        <div class="flex flex-col items-center">
            <div class="logo-crest mb-4">
                <div class="logo-img-wrap">
                    <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA Logo" class="w-full h-full object-contain p-1">
                </div>
            </div>
            <div class="text-center">
                <h1 class="font-display text-xl font-semibold tracking-[.18em] text-brandNavy dark:text-white leading-none">AITSA</h1>
                <p class="text-[9px] font-medium uppercase tracking-[.22em] text-brandNavy/40 dark:text-slate-500 mt-1">
                    Asian Institute of Technology, Science &amp; Arts
                </p>
            </div>
        </div>

        <div>
            <h2 class="text-[1.2rem] font-bold text-brandNavy dark:text-white tracking-tight leading-snug">
                Choose a new password
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-light">
                Must be at least 8 characters.
            </p>
        </div>

        @if ($errors->any())
        <div class="rounded-lg border border-red-200/60 bg-red-50 dark:bg-red-950/20 dark:border-red-900/30 px-4 py-3 flex gap-2.5 items-start">
            <i class="fa-solid fa-circle-exclamation text-red-500 mt-0.5 text-sm flex-shrink-0"></i>
            <ul class="list-none space-y-0.5 text-xs font-medium text-red-600 dark:text-red-400">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('password.update') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="fl-wrap">
                <div class="relative bg-lightBg/50 dark:bg-surfaceDark rounded-xl border border-brandNavy/10 dark:border-slate-700/30
                            focus-within:border-brandGreen dark:focus-within:border-brandGold/50 transition-colors duration-200 overflow-hidden">
                    <input type="email" name="email" id="email"
                           value="{{ old('email', $email) }}"
                           placeholder=" " required
                           class="w-full px-4 text-sm text-brandNavy dark:text-slate-100 bg-transparent focus:outline-none">
                    <label for="email">Email</label>
                    <div class="fl-bar"></div>
                </div>
            </div>

            <div class="fl-wrap">
                <div class="relative bg-lightBg/50 dark:bg-surfaceDark rounded-xl border border-brandNavy/10 dark:border-slate-700/30
                            focus-within:border-brandGreen dark:focus-within:border-brandGold/50 transition-colors duration-200 overflow-hidden">
                    <input type="password" name="password" id="password"
                           placeholder=" " required minlength="8"
                           class="w-full px-4 pr-11 text-sm text-brandNavy dark:text-slate-100 bg-transparent focus:outline-none">
                    <label for="password">New Password</label>
                    <button type="button" class="eye-btn" id="eyeBtn1" onclick="togglePassword('password','eyeIcon1','eyeBtn1')" aria-label="Show password">
                        <i class="fa-regular fa-eye" id="eyeIcon1"></i>
                    </button>
                    <div class="fl-bar"></div>
                </div>
            </div>

            <div class="fl-wrap">
                <div class="relative bg-lightBg/50 dark:bg-surfaceDark rounded-xl border border-brandNavy/10 dark:border-slate-700/30
                            focus-within:border-brandGreen dark:focus-within:border-brandGold/50 transition-colors duration-200 overflow-hidden">
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           placeholder=" " required minlength="8"
                           class="w-full px-4 pr-11 text-sm text-brandNavy dark:text-slate-100 bg-transparent focus:outline-none">
                    <label for="password_confirmation">Confirm Password</label>
                    <button type="button" class="eye-btn" id="eyeBtn2" onclick="togglePassword('password_confirmation','eyeIcon2','eyeBtn2')" aria-label="Show password">
                        <i class="fa-regular fa-eye" id="eyeIcon2"></i>
                    </button>
                    <div class="fl-bar"></div>
                </div>
            </div>

            <button type="submit"
                class="btn-primary w-full bg-brandNavy hover:bg-brandGreen text-white font-bold py-3.5 rounded
                       transition-colors duration-300 text-xs uppercase tracking-[.16em]">
                Reset Password &nbsp;<i class="fa-solid fa-check text-[10px]"></i>
            </button>
        </form>

        <div class="text-center">
            <a href="{{ route('login') }}" class="text-[11px] font-medium text-slate-500 dark:text-slate-400 hover:text-brandGreen dark:hover:text-brandGold
                              transition-colors duration-200 hover:underline underline-offset-2">
                <i class="fa-solid fa-arrow-left text-[9px]"></i> Back to Sign In
            </a>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId, btnId) {
            const input = document.getElementById(inputId);
            const icon  = document.getElementById(iconId);
            const btn   = document.getElementById(btnId);
            input.type = input.type === 'password' ? 'text' : 'password';
            icon.className = input.type === 'password' ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
            btn.setAttribute('aria-label', input.type === 'password' ? 'Show password' : 'Hide password');
        }
    </script>
</body>
</html>
