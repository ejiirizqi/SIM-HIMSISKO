</main>
<script>
(function () {
    // Get elements
    const shell = document.getElementById('mhs-shell');
    const sidebar = document.getElementById('mhs-sidebar');
    const overlay = document.getElementById('mhs-overlay');
    const openBtn = document.getElementById('mhs-open-btn');
    const expandBtn = document.getElementById('mhs-expand-btn');
    const collapseBtn = document.getElementById('mhs-collapse-btn');
    const userMenuBtn = document.querySelector('#mhs-user-menu .mhs-topbar-user-btn');
    const userMenu = document.getElementById('mhs-user-menu');

    // Wait for DOM to be fully loaded
    const target = shell || document.body;

    // MOBILE: Toggle sidebar with open button
    if (openBtn) {
        openBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            target.classList.toggle('mhs-open');
        });
    }

    // MOBILE: Close sidebar when clicking overlay
    if (overlay) {
        overlay.addEventListener('click', function (e) {
            e.preventDefault();
            target.classList.remove('mhs-open');
        });
    }

    // DESKTOP: Collapse sidebar button
    if (collapseBtn) {
        collapseBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            target.classList.toggle('mhs-collapsed');
        });
    }

    // DESKTOP: Expand sidebar button
    if (expandBtn) {
        expandBtn.addEventListener('click', function (e) {
            e.preventDefault();
            target.classList.remove('mhs-collapsed');
        });
    }

    // MOBILE: Close sidebar when clicking nav links
    if (sidebar) {
        sidebar.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                // Only close on mobile (viewport < 1024px)
                if (window.innerWidth < 1024) {
                    target.classList.remove('mhs-open');
                }
            });
        });
    }

    // USER DROPDOWN: Toggle menu
    if (userMenuBtn && userMenu) {
        userMenuBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const isOpen = userMenu.classList.contains('open');
            userMenu.classList.toggle('open', !isOpen);
            userMenuBtn.setAttribute('aria-expanded', !isOpen ? 'true' : 'false');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!userMenu.contains(e.target)) {
                userMenu.classList.remove('open');
                userMenuBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Close mhs-open on window resize to large screen
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 1024) {
            target.classList.remove('mhs-open');
        }
    });
})();
</script>
</body>
</html>

