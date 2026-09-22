{{-- partials/sidebar-toggle-script.blade.php --}}
{{-- Shared behavior for every "#app-sidebar" instance: a slide-over on
     mobile/tablet (below lg) with a tap-to-close backdrop, and a
     persistent collapse toggle on desktop (remembered per-browser via
     localStorage so it doesn't snap back open on the next page load). --}}
<script>
    (function () {
        function applyCollapse(collapsed) {
            var aside = document.getElementById('app-sidebar');
            if (!aside) return;
            aside.classList.toggle('lg:w-20', collapsed);
            aside.classList.toggle('lg:w-64', !collapsed);
            document.querySelectorAll('.sidebar-label').forEach(function (el) {
                el.classList.toggle('lg:hidden', collapsed);
            });
            var icon = document.getElementById('sidebar-collapse-icon');
            if (icon) {
                icon.classList.toggle('fa-angles-left', !collapsed);
                icon.classList.toggle('fa-angles-right', collapsed);
            }
        }

        window.toggleSidebarCollapse = function () {
            var collapsed = localStorage.getItem('aitsa-sidebar-collapsed') !== '1';
            localStorage.setItem('aitsa-sidebar-collapsed', collapsed ? '1' : '0');
            applyCollapse(collapsed);
        };

        window.openMobileSidebar = function () {
            document.getElementById('app-sidebar')?.classList.remove('-translate-x-full');
            document.getElementById('sidebar-backdrop')?.classList.remove('hidden');
        };

        window.closeMobileSidebar = function () {
            document.getElementById('app-sidebar')?.classList.add('-translate-x-full');
            document.getElementById('sidebar-backdrop')?.classList.add('hidden');
        };

        window.toggleMobileSidebar = function () {
            var aside = document.getElementById('app-sidebar');
            if (!aside) return;
            aside.classList.contains('-translate-x-full') ? window.openMobileSidebar() : window.closeMobileSidebar();
        };

        document.addEventListener('DOMContentLoaded', function () {
            applyCollapse(localStorage.getItem('aitsa-sidebar-collapsed') === '1');
        });
    })();
</script>
