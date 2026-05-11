</main>
<script>
(function () {
    const sidebar = document.getElementById('mhs-sidebar');
    const openBtn = document.getElementById('mhs-sidebar-open');
    const closeBtn = document.getElementById('mhs-sidebar-close');
    const overlay = document.getElementById('mhs-sidebar-overlay');

    if (!sidebar || !openBtn) return;

    function open() {
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        if (overlay) {
            overlay.classList.remove('opacity-0', 'pointer-events-none');
            overlay.classList.add('opacity-100', 'pointer-events-auto');
        }
        openBtn.setAttribute('aria-expanded', 'true');
    }

    function close() {
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.add('-translate-x-full');
        if (overlay) {
            overlay.classList.add('opacity-0', 'pointer-events-none');
            overlay.classList.remove('opacity-100', 'pointer-events-auto');
        }
        openBtn.setAttribute('aria-expanded', 'false');
    }

    openBtn.addEventListener('click', open);
    if (closeBtn) closeBtn.addEventListener('click', close);
    if (overlay) overlay.addEventListener('click', close);

    // Tutup otomatis saat klik link navigasi (mobile)
    sidebar.querySelectorAll('a').forEach(function (a) {
        a.addEventListener('click', function () {
            // hanya tutup di mode mobile
            if (window.innerWidth < 1024) close();
        });
    });
})();
</script>
</body>
</html>

