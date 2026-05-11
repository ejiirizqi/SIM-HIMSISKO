            </div>
        </main>
    </div>
</div>
<script>
(function () {
    var side = document.getElementById('admin-sidebar');
    var overlay = document.getElementById('admin-sidebar-overlay');
    var openBtn = document.getElementById('admin-sidebar-open');
    var closeBtn = document.getElementById('admin-sidebar-close');
    var toggleBtn = document.getElementById('admin-sidebar-toggle');
    var expandBtn = document.getElementById('admin-sidebar-expand-btn');
    var desktopCollapsed = false;
    if (!side || !overlay) return;

    function isMobile() {
        return window.matchMedia('(max-width: 1023px)').matches;
    }

    function closeMobile() {
        side.classList.add('-translate-x-full');
        overlay.classList.add('opacity-0', 'pointer-events-none');
        overlay.classList.remove('opacity-100');
        document.body.classList.remove('overflow-hidden');
        if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
    }

    function openMobile() {
        side.classList.remove('-translate-x-full');
        overlay.classList.remove('opacity-0', 'pointer-events-none');
        overlay.classList.add('opacity-100');
        document.body.classList.add('overflow-hidden');
        if (openBtn) openBtn.setAttribute('aria-expanded', 'true');
    }

    function updateToggleLabel() {
        if (!toggleBtn) return;
        var label = toggleBtn.querySelector('span');
        if (label) {
            label.textContent = desktopCollapsed ? 'Buka sidebar' : 'Tutup sidebar';
        }
    }

    function setDesktopState() {
        if (desktopCollapsed) {
            document.body.classList.add('admin-sidebar-collapsed');
            if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
        } else {
            document.body.classList.remove('admin-sidebar-collapsed');
            if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
        }
        side.classList.remove('-translate-x-full');
        overlay.classList.add('opacity-0', 'pointer-events-none');
        overlay.classList.remove('opacity-100');
        document.body.classList.remove('overflow-hidden');
        updateToggleLabel();
    }

    function setResponsiveState() {
        if (isMobile()) {
            desktopCollapsed = false;
            document.body.classList.remove('admin-sidebar-collapsed');
            closeMobile();
        } else {
            setDesktopState();
        }
    }

    openBtn && openBtn.addEventListener('click', function () {
        if (side.classList.contains('-translate-x-full')) {
            openMobile();
        } else {
            closeMobile();
        }
    });

    toggleBtn && toggleBtn.addEventListener('click', function () {
        desktopCollapsed = !desktopCollapsed;
        setDesktopState();
    });

    expandBtn && expandBtn.addEventListener('click', function () {
        desktopCollapsed = false;
        setDesktopState();
    });

    closeBtn && closeBtn.addEventListener('click', closeMobile);
    overlay.addEventListener('click', closeMobile);

    side.querySelectorAll('a').forEach(function (a) {
        a.addEventListener('click', function () {
            if (isMobile()) closeMobile();
        });
    });

    window.addEventListener('resize', setResponsiveState);
    setResponsiveState();
})();
</script>
</body>
</html>
