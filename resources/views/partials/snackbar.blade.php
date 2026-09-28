{{-- Floating success/error toast — auto-dismisses after 5s, or via the × button.
     Reads the same session('success')/session('error') flash keys every page
     already sets; just include this instead of a static inline banner. --}}
@if(session('success') || session('error'))
    @php
        $snackbarType = session('error') ? 'error' : 'success';
        $snackbarMessage = session('error') ?: session('success');
    @endphp
    <div id="snackbar" data-type="{{ $snackbarType }}"
         class="fixed bottom-6 right-6 z-[200] max-w-sm w-[calc(100%-3rem)] sm:w-auto rounded-xl shadow-2xl px-5 py-4 flex items-start gap-3 text-sm font-semibold text-white translate-y-24 opacity-0 transition-all duration-300 {{ $snackbarType === 'error' ? 'bg-red-600' : 'bg-brandGreen' }}">
        <i class="fa-solid {{ $snackbarType === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-check' }} mt-0.5"></i>
        <span class="flex-1">{{ $snackbarMessage }}</span>
        <button type="button" onclick="dismissSnackbar()" aria-label="Dismiss notification" class="text-white/70 hover:text-white transition-colors">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <script>
        (function () {
            const el = document.getElementById('snackbar');
            window.dismissSnackbar = function () {
                el.classList.add('translate-y-24', 'opacity-0');
                setTimeout(() => el.remove(), 300);
            };
            requestAnimationFrame(() => el.classList.remove('translate-y-24', 'opacity-0'));
            setTimeout(window.dismissSnackbar, 5000);
        })();
    </script>
@endif
