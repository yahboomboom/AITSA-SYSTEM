<script>
    (function () {
        var theme = localStorage.getItem('theme') || 'light';
        document.documentElement.classList.toggle('dark', theme === 'dark');
    })();

    // Belt-and-suspenders for the back-button/session-cache issue: the
    // Cache-Control: no-store header (see PreventBackHistoryCache) stops the
    // browser from restoring this page from its back/forward cache going
    // forward, but can't retroactively evict a copy the browser already
    // cached before that header existed. If this page is ever shown from
    // that cache anyway, force a real reload so it re-checks who's logged in.
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
</script>
