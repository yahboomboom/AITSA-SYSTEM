{{-- partials/profile-menu.blade.php --}}
{{-- Props: $roleLabel, $avatarClass (opt), $avatarInitial (opt) --}}
<div class="relative" id="profileMenuWrap">

    <button type="button" onclick="toggleProfileMenu()"
        class="flex items-center gap-3 cursor-pointer focus:outline-none select-none">
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

    <div id="profileMenuDropdown"
        class="hidden absolute right-0 top-14 w-60 bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 rounded-lg shadow-xl z-[200] overflow-hidden">
        <div class="px-4 py-3 bg-lightBg dark:bg-slate-800/60 border-b border-brandNavy/8 dark:border-slate-700">
            <p class="text-xs font-extrabold text-brandNavy dark:text-slate-200 truncate">
                {{ Auth::user()->name ?? 'User' }}
            </p>
            <p class="text-[10px] text-brandNavy/50 dark:text-slate-500 mt-0.5 truncate">
                {{ $roleLabel ?? (Auth::user()->major ?? 'Staff') }}
            </p>
        </div>
        <div class="p-2">
		<a href="{{ route('signature.edit') }}"
                class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-semibold text-brandNavy/70 dark:text-slate-300 hover:bg-brandNavy/5 dark:hover:bg-slate-800 rounded transition-colors text-left">
                <i class="fa-solid fa-signature w-4"></i>
                <span>My Signature</span>
            </a>
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
}());
</script>
