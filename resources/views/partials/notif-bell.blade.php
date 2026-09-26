{{-- ── Notification Bell + Dropdown ─────────────────────────────────────────── --}}
<div class="relative" id="notif-container">
    <button id="notif-btn" onclick="toggleNotifs(event)" aria-label="Notifications"
        class="w-8 h-8 rounded text-brandNavy/50 dark:text-brandGold flex items-center justify-center hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors relative">
        <i class="fa-solid fa-bell text-sm"></i>
        <span id="notif-badge"
            class="hidden absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-0.5 bg-brandGold text-brandNavy text-[9px] font-black rounded-full flex items-center justify-center leading-none">
        </span>
    </button>

    {{-- Dropdown panel --}}
    <div id="notif-dropdown"
        class="hidden absolute right-0 top-11 w-80 bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 rounded-lg shadow-xl z-50 overflow-hidden">

        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-brandNavy/8 dark:border-slate-700 bg-lightBg dark:bg-slate-800/60">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-bell text-brandNavy dark:text-slate-300 text-xs"></i>
                <span class="text-[11px] font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">Notifications</span>
                <span id="notif-count-label"
                    class="hidden text-[9px] font-black px-1.5 py-0.5 bg-brandGold text-brandNavy rounded-full">
                </span>
            </div>
            <button onclick="markAllRead()"
                class="text-[10px] font-bold text-brandGreen hover:text-brandNavy dark:hover:text-white transition-colors">
                Mark all read
            </button>
        </div>

        {{-- List --}}
        <div id="notif-list" class="max-h-72 overflow-y-auto divide-y divide-brandNavy/5 dark:divide-slate-800">
            {{-- Populated by JS --}}
        </div>

        {{-- Footer --}}
        <div id="notif-footer" class="px-4 py-2.5 border-t border-brandNavy/8 dark:border-slate-700 text-center bg-lightBg/50 dark:bg-slate-800/30">
            <span id="notif-footer-text" class="text-[10px] text-brandNavy/40 dark:text-slate-500">You're all caught up</span>
        </div>
    </div>
</div>
