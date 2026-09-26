{{-- partials/profile-menu.blade.php --}}
{{-- Props: $roleLabel, $avatarClass (opt), $avatarInitial (opt) --}}
<div class="relative" id="profileMenuWrap">
   {{-- Button to toggle the profile menu dropdown, displaying the user's name, role, and avatar --}}
    <button type="button" onclick="toggleProfileMenu()" aria-label="Open profile menu"
        class="flex items-center gap-3 cursor-pointer select-none rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-brandGreen focus-visible:ring-offset-2 dark:focus-visible:ring-offset-panelDark">
        <div class="hidden md:block text-right">
            <p class="text-sm font-bold text-brandNavy dark:text-slate-200">
                {{ Auth::user()->name ?? 'User' }}
            </p>
            <p class="text-[10px] font-medium {{ $roleClass ?? 'text-brandNavy/60 dark:text-slate-400' }}">
                {{ $roleLabel ?? (Auth::user()->major ?? 'Staff') }}
            </p>
        </div>
        <div class="w-10 h-10 rounded-full {{ $avatarClass ?? 'bg-gradient-to-tr from-brandNavy to-brandGreen text-white' }} flex items-center justify-center font-bold text-sm relative">
            {{ $avatarInitial ?? strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
            <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-white dark:bg-panelDark rounded-full border border-brandNavy/10 dark:border-slate-700 flex items-center justify-center">
                <i class="fa-solid fa-chevron-down text-brandNavy/40 dark:text-slate-500 text-[5px]"></i>
            </span>
        </div>
    </button>
    {{-- Profile menu dropdown, initially hidden, containing links to the signature edit page and a logout button --}}
    <div id="profileMenuDropdown"
        class="hidden absolute right-0 top-14 w-60 bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 rounded-lg shadow-xl z-[200] overflow-hidden">
        <div class="px-4 py-3 bg-lightBg dark:bg-slate-800/60 border-b border-brandNavy/8 dark:border-slate-700">
            <p class="text-xs font-extrabold text-brandNavy dark:text-slate-200 truncate">
                {{ Auth::user()->name ?? 'User' }}
            </p>
            <p class="text-[10px] text-brandNavy/50 dark:text-slate-500 mt-0.5 truncate">
                {{ $roleLabel ?? (Auth::user()->major ?? 'Staff') }}
            </p>
        </div> {{-- Container for the profile menu links and logout button --}}
        <div class="p-2">
            <a href="{{ route('profile.edit') }}"
                class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-brandNavy/70 dark:text-slate-300 hover:bg-brandNavy/5 dark:hover:bg-slate-800 rounded transition-colors text-left">
                <i class="fa-solid fa-user-pen w-4"></i>
                <span>My Profile</span>
            </a>
		<a href="{{ route('signature.edit') }}"
                class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-brandNavy/70 dark:text-slate-300 hover:bg-brandNavy/5 dark:hover:bg-slate-800 rounded transition-colors text-left">
                <i class="fa-solid fa-signature w-4"></i>
                <span>My Signature</span>
            </a>
            <button type="button" onclick="openResetPasswordModal()"
                class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-brandNavy/70 dark:text-slate-300 hover:bg-brandNavy/5 dark:hover:bg-slate-800 rounded transition-colors text-left">
                <i class="fa-solid fa-key w-4"></i>
                <span>Reset Password</span>
            </button>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit"
                    class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-red-500 hover:bg-red-500/10 rounded transition-colors text-left">
                    <i class="fa-solid fa-right-from-bracket w-4"></i>
                    <span>Sign Out</span>
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Reset-password confirmation card: hidden by default, opened from the dropdown above.
     Positioned over just the page's <main> content area (not the sidebar) via JS,
     matching the step-up re-auth modal's treatment. --}}
<div id="resetPasswordModal" class="hidden fixed bg-black/40 backdrop-blur-sm flex items-center justify-center p-4 z-[300]">
    <div class="bg-white dark:bg-panelDark rounded-2xl shadow-2xl max-w-sm w-full p-8 animate-fade-in">
        <div id="resetPasswordModalBody">
            <div class="w-14 h-14 rounded-full bg-brandGold/10 text-brandGold flex items-center justify-center mx-auto mb-5 text-xl">
                <i class="fa-solid fa-key"></i>
            </div>
            <h2 class="text-lg font-extrabold text-brandNavy dark:text-white mb-1 text-center">Reset Your Password</h2>
            <p class="text-sm text-brandNavy/60 dark:text-slate-400 mb-6 text-center">
                We'll email a password reset link to your registered email address.
            </p>
            <p id="resetPasswordModalError" class="hidden mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-red-600 text-xs font-semibold text-center">
                Something went wrong. Please try again.
            </p>
            <div class="flex gap-3">
                <button type="button" onclick="closeResetPasswordModal()"
                    class="flex-1 py-3 rounded-xl bg-transparent border border-brandNavy/20 dark:border-slate-600 text-brandNavy/60 dark:text-slate-400 font-bold text-sm hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                    Cancel
                </button>
                <button type="button" id="resetPasswordModalSendBtn" onclick="submitResetPasswordRequest()"
                    class="flex-1 py-3 rounded-xl bg-brandNavy dark:bg-brandGreen text-white font-bold text-sm hover:opacity-90 transition-opacity">
                    Send Reset Link
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    window.toggleProfileMenu = function () {
        document.getElementById('profileMenuDropdown').classList.toggle('hidden');
    };
    document.addEventListener('click', function (e) {
        var wrap = document.getElementById('profileMenuWrap');
        var dd   = document.getElementById('profileMenuDropdown');
        if (wrap && dd && !wrap.contains(e.target)) dd.classList.add('hidden');
    });

    window.openResetPasswordModal = function () {
        document.getElementById('profileMenuDropdown').classList.add('hidden');

        var modal = document.getElementById('resetPasswordModal');
        var main = document.querySelector('main');
        if (main) {
            var rect = main.getBoundingClientRect();
            modal.style.top = rect.top + 'px';
            modal.style.left = rect.left + 'px';
            modal.style.width = rect.width + 'px';
            modal.style.height = rect.height + 'px';
        } else {
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100vw';
            modal.style.height = '100vh';
        }
        modal.classList.remove('hidden');
    };

    window.closeResetPasswordModal = function () {
        document.getElementById('resetPasswordModal').classList.add('hidden');
    };

    document.getElementById('resetPasswordModal').addEventListener('click', function (e) {
        if (e.target.id === 'resetPasswordModal') closeResetPasswordModal();
    });

    window.submitResetPasswordRequest = function () {
        var btn = document.getElementById('resetPasswordModalSendBtn');
        var errorEl = document.getElementById('resetPasswordModalError');
        var token = document.querySelector('#profileMenuWrap form input[name="_token"]').value;

        errorEl.classList.add('hidden');
        btn.disabled = true;
        btn.textContent = 'Sending…';

        fetch('{{ route('profile.password.reset-link') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        }).then(function (res) {
            if (!res.ok) throw new Error('request failed');
            document.getElementById('resetPasswordModalBody').innerHTML =
                '<div class="w-14 h-14 rounded-full bg-brandGreen/10 text-brandGreen flex items-center justify-center mx-auto mb-5 text-xl">' +
                '<i class="fa-solid fa-circle-check"></i></div>' +
                '<h2 class="text-lg font-extrabold text-brandNavy dark:text-white mb-1 text-center">Check Your Email</h2>' +
                '<p class="text-sm text-brandNavy/60 dark:text-slate-400 mb-6 text-center">A password reset link has been sent to your registered email address.</p>' +
                '<button type="button" onclick="closeResetPasswordModal()" ' +
                'class="w-full py-3 rounded-xl bg-brandNavy dark:bg-brandGreen text-white font-bold text-sm hover:opacity-90 transition-opacity">Done</button>';
        }).catch(function () {
            errorEl.classList.remove('hidden');
            btn.disabled = false;
            btn.textContent = 'Send Reset Link';
        });
    };
}());
</script>