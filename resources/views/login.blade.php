<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITSA Portal | Login</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')

    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; }

        /* ── Floating label ── */
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

        /* ── Shimmer button ── */
        .btn-login { position: relative; overflow: hidden; }
        .btn-login::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.18) 50%, transparent 100%);
            transform: translateX(-100%);
        }
        .btn-login:hover::after { animation: shimmer 0.75s ease forwards; }
        @keyframes shimmer {
            from { transform: translateX(-100%); }
            to   { transform: translateX(150%); }
        }

        /* ── Floating geo shapes ── */
        .geo {
            position: absolute;
            border: 1.5px solid rgba(226,167,0,0.35);
            transform: rotate(45deg);
        }
        @keyframes floatA {
            0%,100% { transform: rotate(45deg) translateY(0);    opacity: .40; }
            50%      { transform: rotate(45deg) translateY(-14px); opacity: .70; }
        }
        @keyframes floatB {
            0%,100% { transform: rotate(45deg) translateY(0);    opacity: .22; }
            50%      { transform: rotate(45deg) translateY(10px); opacity: .48; }
        }
        .geo-a { animation: floatA 7s  ease-in-out infinite; }
        .geo-b { animation: floatB 9s  ease-in-out infinite 1.5s; }
        .geo-c { animation: floatA 12s ease-in-out infinite 3s; }
        .geo-d { animation: floatB 10s ease-in-out infinite 0.8s; border-color: rgba(29,122,70,.28); }

        /* ── Dot grid ── */
        .dot-grid {
            background-image: radial-gradient(circle, rgba(11,60,93,.04) 1px, transparent 1px);
            background-size: 22px 22px;
        }
        .dark .dot-grid {
            background-image: radial-gradient(circle, rgba(226,167,0,.045) 1px, transparent 1px);
        }

        /* ── AITSA Logo crest ── */
        .logo-crest {
            position: relative;
            width: 72px; height: 72px;
            flex-shrink: 0;
        }
        .logo-crest-ring {
            position: absolute; inset: 0;
            border-radius: 50%;
            border: none;
            animation: ringPulse 3s ease-in-out infinite;
        }
        .logo-crest-ring2 {
            position: absolute; inset: 6px;
            border-radius: 50%;
            border: none;
        }
        @keyframes ringPulse {
            0%,100% { opacity: .5; transform: scale(1); }
            50%      { opacity: 1;  transform: scale(1.04); }
        }
        .logo-img-wrap {
            position: absolute; inset: 8px;
            border-radius: 50%;
            overflow: hidden;
            background: white;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 0 2px rgba(11,60,93,0.12);
        }
        .dark .logo-img-wrap { background: #0D1B2A; box-shadow: 0 0 0 2px rgba(226,167,0,0.15); }

        /* ── Online dot ── */
        .online-dot {
            position: absolute; bottom: 4px; right: 4px;
            width: 12px; height: 12px;
            background: #1D7A46;
            border-radius: 50%;
            border: 2px solid white;
        }
        .dark .online-dot { border-color: #07101C; }

        /* ── Animation delays ── */
        .d-100 { animation-delay: 100ms; }
        .d-200 { animation-delay: 200ms; }
        .d-300 { animation-delay: 300ms; }
        .d-500 { animation-delay: 500ms; }

        /* ── Eye button ── */
        .eye-btn {
            position: absolute; right: .875rem; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: #64748b; font-size: .875rem; padding: 0;
        }
        .dark .eye-btn { color: #94a3b8; }
        .eye-btn:hover { color: #1D7A46; }
        .dark .eye-btn:hover { color: #E2A700; }

        /* ── Modal ── */
        .modal-backdrop { backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); }
        ::-webkit-scrollbar { width: 3px; }
        ::-webkit-scrollbar-thumb { background: #1D7A46; border-radius: 2px; }
    </style>
</head>

<body class="bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-200 font-body min-h-screen flex antialiased overflow-hidden">

<div class="w-full min-h-screen grid grid-cols-1 lg:grid-cols-12">

    {{-- LEFT PANEL --}}
    <div class="hidden lg:flex lg:col-span-7 relative h-screen bg-brandNavy flex-col overflow-hidden">

        <img src="{{ asset('assets/bg_aitsa.jpg') }}"
             alt="AITSA Campus"
             class="absolute inset-0 w-full h-full object-cover opacity-55 scale-105 hover:scale-100 transition-transform duration-[2500ms]">

        <div class="absolute inset-0 bg-gradient-to-br from-[#0B3C5D]/90 via-[#0B3C5D]/55 to-transparent pointer-events-none"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#050e18]/92 via-transparent to-transparent pointer-events-none"></div>

        <div class="geo geo-a w-[68px] h-[68px] top-[17%] right-[14%]"></div>
        <div class="geo geo-b w-[36px] h-[36px] top-[41%] right-[28%]"></div>
        <div class="geo geo-c w-[16px] h-[16px] top-[66%] right-[17%]"></div>
        <div class="geo geo-d w-[130px] h-[130px] top-[8%] left-[6%]"></div>
        <div class="relative z-10 flex-1"></div>
    </div>

    {{-- RIGHT PANEL --}}
    <div class="col-span-12 lg:col-span-5 bg-white dark:bg-darkBg dot-grid flex flex-col h-screen overflow-y-auto relative z-10
                shadow-[-24px_0_40px_-15px_rgba(0,0,0,0.04)] dark:shadow-none">

        <div class="px-8 lg:px-12 pt-10 pb-2 flex flex-col items-center animate-fade-in opacity-0 d-100">
            <div class="logo-crest mx-auto mb-4">
                <div class="logo-crest-ring"></div>
                <div class="logo-crest-ring2"></div>
                <div class="logo-img-wrap">
                    <img src="{{ asset('assets/bg_aitsa.jpg') }}"
                         alt="AITSA Logo"
                         class="w-full h-full object-contain p-1">
                </div>
            </div>
            <div class="text-center">
                <h1 class="font-display text-2xl font-semibold tracking-[.18em] text-brandNavy dark:text-white leading-none">
                    AITSA
                </h1>
                <p class="text-[9px] font-medium uppercase tracking-[.22em] text-brandNavy/40 dark:text-slate-500 mt-1">
                    Asian Institute of Technology, Science &amp; Arts
                </p>
            </div>
        </div>

        <div class="flex-1 flex flex-col justify-center px-8 lg:px-12 py-6">
            <div class="max-w-sm w-full mx-auto space-y-7 animate-fade-in-up opacity-0 d-200">

                <div>
                    <h2 class="text-[1.35rem] font-bold text-brandNavy dark:text-white tracking-tight leading-snug">
                        Sign in to your account
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-light">
                        Use your student ID or registered email address
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

                <form action="{{ route('login.submit') }}" method="POST" class="space-y-4">
                    @csrf

                    <div class="fl-wrap">
                        <div class="relative bg-lightBg/50 dark:bg-surfaceDark rounded-xl border border-brandNavy/10 dark:border-slate-700/30
                                    focus-within:border-brandGreen dark:focus-within:border-brandGold/50 transition-colors duration-200 overflow-hidden">
                            <input type="text" name="login_id" id="login_id"
                                   value="{{ old('login_id') }}"
                                   placeholder=" " required
                                   class="w-full px-4 text-sm text-brandNavy dark:text-slate-100 bg-transparent focus:outline-none">
                            <label for="login_id">Student ID / Email</label>
                            <div class="fl-bar"></div>
                        </div>
                    </div>

                    <div class="fl-wrap">
                        <div class="relative bg-lightBg/50 dark:bg-surfaceDark rounded-xl border border-brandNavy/10 dark:border-slate-700/30
                                    focus-within:border-brandGreen dark:focus-within:border-brandGold/50 transition-colors duration-200 overflow-hidden">
                            <input type="password" name="password" id="password"
                                   placeholder=" " required
                                   class="w-full px-4 pr-11 text-sm text-brandNavy dark:text-slate-100 bg-transparent focus:outline-none">
                            <label for="password">Password</label>
                            <button type="button" class="eye-btn" onclick="togglePassword()">
                                <i class="fa-regular fa-eye" id="eyeIcon"></i>
                            </button>
                            <div class="fl-bar"></div>
                        </div>
                    </div>

                    <div class="flex justify-end -mt-1">
                        <a href="#" class="text-[11px] font-medium text-slate-500 dark:text-slate-400 hover:text-brandGreen dark:hover:text-brandGold
                                          transition-colors duration-200 hover:underline underline-offset-2">
                            Forgot Password?
                        </a>
                    </div>

                    <button type="submit"
                        class="btn-login w-full bg-brandNavy hover:bg-brandGreen text-white font-bold py-3.5 rounded
                               transition-colors duration-300 text-xs uppercase tracking-[.16em]">
                        Sign In &nbsp;<i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="px-8 lg:px-12 pb-8 space-y-5 animate-fade-in-up opacity-0 d-500">
            <div class="max-w-sm mx-auto">
                <div class="flex items-center justify-between gap-3 px-5 py-4 rounded-lg
                            bg-lightBg/50 dark:bg-surfaceDark border border-brandNavy/10 dark:border-slate-700/30">
                    <div>
                        <p class="text-xs font-bold text-brandNavy dark:text-slate-200 leading-tight">New Applicant?</p>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Begin your admission process</p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button onclick="toggleAdmissionModal()"
                            class="text-[10px] font-semibold text-brandNavy/60 dark:text-slate-400 hover:text-brandGreen dark:hover:text-white transition-colors px-2">
                            Details
                        </button>
                        <a href="{{ route('apply') }}"
                           class="text-[10px] font-bold bg-brandGreen hover:bg-emerald-600 text-white px-4 py-1.5 rounded
                                  transition-colors">
                            Apply
                        </a>
                    </div>
                </div>
            </div>
            <div class="max-w-sm mx-auto flex justify-center">
                <p class="text-[8.5px] text-slate-400 dark:text-slate-600 uppercase tracking-widest">
                    &copy; 2026 AITSA. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</div>

{{-- ADMISSION MODAL --}}
<div id="admissionModal"
     class="fixed inset-0 modal-backdrop bg-brandNavy/40 dark:bg-black/70 hidden items-center justify-center z-50 p-4 transition-opacity duration-300">
    <div id="modalBox"
         class="bg-white dark:bg-[#0D1B2A] rounded-lg max-w-md w-full overflow-hidden shadow-xl
                scale-95 opacity-0 transition-all duration-300 border border-brandNavy/5 dark:border-none">

        <div class="px-6 pt-6 pb-5 flex justify-between items-start">
            <div>
                <div class="flex items-center gap-2 mb-0.5">
                    <div class="w-7 h-7 rounded bg-brandGreen/10 flex items-center justify-center">
                        <i class="fa-solid fa-file-lines text-brandGreen text-xs"></i>
                    </div>
                    <h3 class="text-sm font-bold text-brandNavy dark:text-white">Admission Procedures</h3>
                </div>
                <p class="text-[10.5px] text-slate-500 dark:text-slate-400 ml-9">Follow these steps to begin enrollment</p>
            </div>
            <button onclick="toggleAdmissionModal()"
                class="w-8 h-8 rounded bg-slate-100 dark:bg-slate-800 text-slate-400
                       hover:text-brandNavy dark:hover:text-white flex items-center justify-center transition-colors -mt-0.5">
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>

        <div class="mx-6 h-px mb-5" style="background: linear-gradient(to right, rgba(226,167,0,.5), rgba(226,167,0,.15), transparent);"></div>

        <div class="px-6 pb-5 space-y-5 max-h-[48vh] overflow-y-auto">
            @foreach([
                ['Online Application', 'Complete your biometrics and upload clear digital scans of prerequisite credentials through the portal.'],
                ['Document Verification', 'The Admissions Office evaluates your submitted files. Milestone updates will be sent to your registered email.'],
                ['Clearance &amp; Enrollment', 'Once vetted, proceed to the transaction ledger to finalize your enrollment and generate your COR.'],
            ] as $i => $step)
            <div class="flex gap-4">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-brandNavy dark:bg-brandNavy/70 border border-brandGold/30
                            flex items-center justify-center text-brandGold text-xs font-bold">{{ $i + 1 }}</div>
                <div class="pt-0.5">
                    <h4 class="text-sm font-bold text-brandNavy dark:text-slate-100">{!! $step[0] !!}</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">{!! $step[1] !!}</p>
                </div>
            </div>
            @endforeach
        </div>

        <div class="px-6 py-4 bg-lightBg/50 dark:bg-black/20 border-t border-brandNavy/5 dark:border-slate-800/50
                    flex justify-between items-center gap-3">
            <p class="text-[10px] text-slate-500 dark:text-slate-400">Questions? Contact the Registrar's Office.</p>
            <div class="flex gap-2 flex-shrink-0">
                <button onclick="toggleAdmissionModal()"
                    class="px-3 py-1.5 rounded text-xs font-semibold text-slate-500 dark:text-slate-400
                           hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors">
                    Dismiss
                </button>
                <a href="{{ route('apply') }}"
                   class="px-4 py-1.5 rounded text-xs font-bold bg-brandGreen hover:bg-emerald-600 text-white
                          transition-colors">
                    Apply Now →
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleAdmissionModal() {
        const modal = document.getElementById('admissionModal');
        const box   = document.getElementById('modalBox');
        if (modal.classList.contains('hidden')) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            requestAnimationFrame(() => requestAnimationFrame(() => {
                modal.style.opacity = '1';
                box.classList.remove('scale-95', 'opacity-0');
                box.classList.add('scale-100', 'opacity-100');
            }));
        } else {
            box.classList.remove('scale-100', 'opacity-100');
            box.classList.add('scale-95', 'opacity-0');
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
                modal.style.opacity = '';
            }, 300);
        }
    }

    function togglePassword() {
        const input = document.getElementById('password');
        const icon  = document.getElementById('eyeIcon');
        input.type = input.type === 'password' ? 'text' : 'password';
        icon.className = input.type === 'password' ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
    }
</script>
</body>
</html>
