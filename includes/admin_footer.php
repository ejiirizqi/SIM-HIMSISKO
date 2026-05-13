</div><!-- /.mx-auto max-w-6xl -->
        </main>
    </div><!-- /#adm-content -->
</div><!-- /#adm-shell -->

<script>
(function () {
    'use strict';

    const body        = document.body;
    const overlay     = document.getElementById('adm-overlay');
    const openBtn     = document.getElementById('adm-open-btn');
    const collapseBtn = document.getElementById('adm-collapse-btn');
    const expandBtn   = document.getElementById('adm-expand-btn');
    const collapseIcon  = document.getElementById('adm-collapse-icon');
    const collapseLabel = document.getElementById('adm-collapse-label');
    const userMenu      = document.getElementById('adm-user-menu');
    const groupBtn      = document.getElementById('adm-group-anggota-btn');
    const groupChildren = document.getElementById('adm-group-anggota-children');

    let desktopCollapsed = false;

    /* ── Mobile sidebar ─────────────────────── */
    function openMobile() {
        body.classList.add('adm-open');
        document.documentElement.style.overflow = 'hidden';
    }

    function closeMobile() {
        body.classList.remove('adm-open');
        document.documentElement.style.overflow = '';
    }

    if (openBtn)  openBtn.addEventListener('click', openMobile);
    if (overlay)  overlay.addEventListener('click', closeMobile);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeMobile(); closeUserMenu(); }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 1024) closeMobile();
    });

    /* ── Desktop collapse ───────────────────── */
    function setCollapsed(state) {
        desktopCollapsed = state;
        body.classList.toggle('adm-collapsed', state);
        if (collapseIcon) {
            collapseIcon.innerHTML = state
                ? '<path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7"/>'
                : '<path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7"/>';
        }
        if (collapseLabel) collapseLabel.textContent = state ? 'Buka sidebar' : 'Tutup sidebar';
    }

    if (collapseBtn) collapseBtn.addEventListener('click', () => setCollapsed(!desktopCollapsed));
    if (expandBtn)   expandBtn.addEventListener('click',   () => setCollapsed(false));

    /* ── Group dropdown (Manajemen Anggota) ─── */
    if (groupBtn && groupChildren) {
        groupBtn.addEventListener('click', function () {
            const isOpen = groupChildren.classList.toggle('open');
            groupBtn.classList.toggle('open', isOpen);
            groupBtn.setAttribute('aria-expanded', String(isOpen));
        });
    }

    /* ── User dropdown ──────────────────────── */
    function openUserMenu() {
        if (!userMenu) return;
        userMenu.classList.add('open');
        userMenu.querySelector('.adm-topbar-user-btn')?.setAttribute('aria-expanded', 'true');
    }

    function closeUserMenu() {
        if (!userMenu) return;
        userMenu.classList.remove('open');
        userMenu.querySelector('.adm-topbar-user-btn')?.setAttribute('aria-expanded', 'false');
    }

    userMenu?.querySelector('.adm-topbar-user-btn')?.addEventListener('click', function (e) {
        e.stopPropagation();
        userMenu.classList.contains('open') ? closeUserMenu() : openUserMenu();
    });

    document.addEventListener('click', function (e) {
        if (userMenu && !userMenu.contains(e.target)) closeUserMenu();
    });
})();
</script>
</body>
</html>