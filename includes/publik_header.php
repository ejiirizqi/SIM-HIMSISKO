<?php
declare(strict_types=1);
/** @var string $pageTitle */
if (!isset($pageTitle)) {
    $pageTitle = 'SIM HIMSISKO';
}
$u = session_status() === PHP_SESSION_ACTIVE && function_exists('current_user') ? current_user() : null;
$role = $u['role'] ?? '';

// Jika user sudah login sebagai mahasiswa, gunakan template mhs dengan sidebar
if ($u && $role === 'mahasiswa') {
    $activeNav = $activeNav ?? '';
    require dirname(__FILE__) . '/mhs_header.php';
    return;
}

// Jika user sudah login sebagai admin, halaman publik seharusnya tetap tampil.
// Hanya redirect admin saat membuka halaman publik dari menu publik (bukan dari sidebar admin).
// Karena implementasi ini tidak bisa dibedakan dari server-side, kita pertahankan tampilan publik agar layout tidak bentrok.
// (Tidak redirect admin).
$logo = htmlspecialchars(brand_logo_url(), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> — SIM HIMSISKO</title>
    <link rel="icon" type="image/png" href="<?= $logo ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        :root {
            --nav-h: 3.5rem;
            --accent: #2563eb;
            --accent-light: #eff6ff;
            --accent-text: #1d4ed8;
            --radius: .625rem;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            background: #f8fafc;
            color: #1e293b;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* ════════ NAVBAR SHELL ════════ */
        #pub-header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(226,232,240,.8);
            box-shadow: 0 1px 3px rgba(0,0,0,.04);
        }

        .pub-nav-inner {
            display: flex;
            align-items: center;
            height: var(--nav-h);
            max-width: 72rem;
            margin: 0 auto;
            padding: 0 1rem;
            gap: .75rem;
        }

        @media (min-width: 640px) { .pub-nav-inner { padding: 0 1.5rem; } }

        /* ════════ BRAND ════════ */
        .pub-brand {
            display: flex;
            align-items: center;
            gap: .625rem;
            text-decoration: none;
            flex-shrink: 0;
            margin-right: auto;
        }

        .pub-brand-logo {
            width: 2.125rem;
            height: 2.125rem;
            border-radius: .5rem;
            object-fit: contain;
            border: 1px solid rgba(226,232,240,.8);
            background: #fff;
            padding: .1rem;
            flex-shrink: 0;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
        }

        .pub-brand-text {
            font-size: .9rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -.02em;
            white-space: nowrap;
        }

        .pub-brand-text span {
            color: var(--accent);
        }

        /* ════════ DESKTOP NAV LINKS ════════ */
        .pub-nav-desktop {
            display: none;
            align-items: center;
            gap: .25rem;
        }

        @media (min-width: 1024px) {
            .pub-nav-desktop { display: flex; }
        }

        .pub-nav-link {
            display: flex;
            align-items: center;
            gap: .375rem;
            padding: .4375rem .75rem;
            border-radius: var(--radius);
            font-size: .8125rem;
            font-weight: 500;
            color: #475569;
            text-decoration: none;
            transition: background .14s, color .14s;
            white-space: nowrap;
        }

        .pub-nav-link:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .pub-nav-link svg {
            width: .875rem;
            height: .875rem;
            flex-shrink: 0;
            opacity: .7;
        }

        /* separator dot between links */
        .pub-nav-sep {
            width: .25rem;
            height: .25rem;
            border-radius: 50%;
            background: #cbd5e1;
            flex-shrink: 0;
            margin: 0 .125rem;
        }

        /* ════════ DESKTOP AUTH AREA ════════ */
        .pub-nav-auth {
            display: none;
            align-items: center;
            gap: .5rem;
            margin-left: .5rem;
            padding-left: .75rem;
            border-left: 1px solid #e2e8f0;
        }

        @media (min-width: 1024px) {
            .pub-nav-auth { display: flex; }
        }

        /* Dashboard shortcut pill */
        .pub-dashboard-pill {
            display: flex;
            align-items: center;
            gap: .375rem;
            padding: .375rem .875rem;
            border-radius: 2rem;
            font-size: .75rem;
            font-weight: 600;
            color: var(--accent-text);
            background: var(--accent-light);
            text-decoration: none;
            border: 1px solid #bfdbfe;
            transition: background .14s, border-color .14s;
        }

        .pub-dashboard-pill:hover {
            background: #dbeafe;
            border-color: #93c5fd;
        }

        .pub-dashboard-pill svg {
            width: .875rem;
            height: .875rem;
            flex-shrink: 0;
        }

        /* Register link */
        .pub-nav-register {
            padding: .4375rem .75rem;
            border-radius: var(--radius);
            font-size: .8125rem;
            font-weight: 500;
            color: #475569;
            text-decoration: none;
            transition: background .14s, color .14s;
        }

        .pub-nav-register:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        /* Login button */
        .pub-nav-login {
            display: flex;
            align-items: center;
            gap: .375rem;
            padding: .4375rem 1rem;
            border-radius: var(--radius);
            font-size: .8125rem;
            font-weight: 600;
            color: #fff;
            background: #0f172a;
            text-decoration: none;
            transition: background .14s;
            border: 1px solid transparent;
        }

        .pub-nav-login:hover {
            background: #1e293b;
        }

        .pub-nav-login svg {
            width: .875rem;
            height: .875rem;
            flex-shrink: 0;
        }

        /* ════════ USER MENU (desktop) ════════ */
        .pub-user-menu {
            position: relative;
        }

        .pub-user-menu-btn {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .25rem .625rem .25rem .3125rem;
            border-radius: 2rem;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            cursor: pointer;
            font-family: inherit;
            font-size: .75rem;
            font-weight: 600;
            color: #1e293b;
            transition: background .14s, border-color .14s;
        }

        .pub-user-menu-btn:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .pub-user-avatar {
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 50%;
            background: #dbeafe;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .625rem;
            font-weight: 700;
            color: var(--accent-text);
            border: 1.5px solid #bfdbfe;
            flex-shrink: 0;
            overflow: hidden;
        }

        .pub-user-avatar img { width: 100%; height: 100%; object-fit: cover; }

        .pub-user-chevron {
            width: .8125rem;
            height: .8125rem;
            color: #94a3b8;
            transition: transform .2s;
            flex-shrink: 0;
        }

        .pub-user-menu.open .pub-user-chevron { transform: rotate(180deg); }

        .pub-user-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + .4375rem);
            width: 12.5rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: .875rem;
            box-shadow: 0 10px 30px rgba(0,0,0,.1), 0 2px 8px rgba(0,0,0,.05);
            overflow: hidden;
            z-index: 100;
        }

        .pub-user-menu.open .pub-user-dropdown { display: block; }

        .pub-dropdown-header {
            padding: .625rem .875rem .5rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .pub-dropdown-header-name {
            font-size: .75rem;
            font-weight: 700;
            color: #0f172a;
        }

        .pub-dropdown-header-role {
            font-size: .5875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #94a3b8;
            margin-top: .0625rem;
        }

        .pub-dropdown-item {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .5625rem .875rem;
            font-size: .8125rem;
            color: #334155;
            text-decoration: none;
            transition: background .12s;
        }

        .pub-dropdown-item:hover { background: #f8fafc; }

        .pub-dropdown-item svg {
            width: .875rem;
            height: .875rem;
            color: #94a3b8;
            flex-shrink: 0;
        }

        .pub-dropdown-divider { height: 1px; background: #f1f5f9; margin: .1875rem 0; }

        .pub-dropdown-item--danger { color: #dc2626; }
        .pub-dropdown-item--danger svg { color: #ef4444; }
        .pub-dropdown-item--danger:hover { background: #fef2f2; }

        /* ════════ MOBILE HAMBURGER ════════ */
        .pub-hamburger {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.125rem;
            height: 2.125rem;
            border-radius: .5rem;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #475569;
            cursor: pointer;
            transition: background .14s, border-color .14s;
            flex-shrink: 0;
        }

        .pub-hamburger:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .pub-hamburger svg { width: 1rem; height: 1rem; }

        @media (min-width: 1024px) { .pub-hamburger { display: none; } }

        /* ════════ MOBILE DRAWER ════════ */
        #pub-mobile-drawer {
            display: none;
            border-top: 1px solid #f1f5f9;
            background: #fff;
        }

        #pub-mobile-drawer.open { display: block; }

        @media (min-width: 1024px) { #pub-mobile-drawer { display: none !important; } }

        .pub-drawer-inner {
            max-width: 72rem;
            margin: 0 auto;
            padding: .75rem 1rem 1rem;
        }

        @media (min-width: 640px) { .pub-drawer-inner { padding: .75rem 1.5rem 1rem; } }

        /* Section label inside drawer */
        .pub-drawer-label {
            font-size: .5625rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: #94a3b8;
            padding: .375rem .625rem .1875rem;
            margin-top: .125rem;
        }

        .pub-drawer-link {
            display: flex;
            align-items: center;
            gap: .625rem;
            padding: .625rem .75rem;
            border-radius: .5rem;
            font-size: .875rem;
            font-weight: 500;
            color: #334155;
            text-decoration: none;
            transition: background .13s, color .13s;
        }

        .pub-drawer-link:hover {
            background: #f8fafc;
            color: #0f172a;
        }

        .pub-drawer-link svg {
            width: 1rem;
            height: 1rem;
            color: #94a3b8;
            flex-shrink: 0;
        }

        .pub-drawer-link--accent {
            color: var(--accent-text);
            background: var(--accent-light);
            border: 1px solid #bfdbfe;
            font-weight: 600;
        }

        .pub-drawer-link--accent:hover {
            background: #dbeafe;
        }

        .pub-drawer-link--accent svg { color: var(--accent); }

        .pub-drawer-link--danger { color: #dc2626; }
        .pub-drawer-link--danger:hover { background: #fef2f2; }
        .pub-drawer-link--danger svg { color: #ef4444; }

        .pub-drawer-divider { height: 1px; background: #f1f5f9; margin: .375rem 0; }

        /* Auth buttons in mobile drawer */
        .pub-drawer-auth {
            display: flex;
            gap: .5rem;
            padding: .5rem .625rem 0;
            margin-top: .25rem;
        }

        .pub-drawer-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .375rem;
            padding: .625rem 1rem;
            border-radius: .5rem;
            font-size: .8125rem;
            font-weight: 600;
            text-decoration: none;
            transition: background .14s;
        }

        .pub-drawer-btn--outline {
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #334155;
        }

        .pub-drawer-btn--outline:hover { background: #f1f5f9; border-color: #cbd5e1; }

        .pub-drawer-btn--solid {
            background: #0f172a;
            color: #fff;
            border: 1px solid transparent;
        }

        .pub-drawer-btn--solid:hover { background: #1e293b; }

        .pub-drawer-btn svg { width: .875rem; height: .875rem; flex-shrink: 0; }

    </style>
</head>

<body>

<!-- ═══════════════════════════════ HEADER ═══════════════════════════════ -->
<header id="pub-header">
    <div class="pub-nav-inner">

        <!-- Brand -->
        <a href="<?= htmlspecialchars(url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="pub-brand">
            <img src="<?= $logo ?>" alt="HIMSISKO" class="pub-brand-logo">
            <span class="pub-brand-text">SIM <span>HIMSISKO</span></span>
        </a>

        <!-- Desktop nav links -->
        <nav class="pub-nav-desktop" aria-label="Menu utama">
            <a href="<?= htmlspecialchars(url('publik/kegiatan.php'), ENT_QUOTES, 'UTF-8') ?>"
               class="pub-nav-link">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
                </svg>
                Kegiatan
            </a>

            <div class="pub-nav-sep"></div>

            <a href="<?= htmlspecialchars(url('publik/laporan_keuangan.php'), ENT_QUOTES, 'UTF-8') ?>"
               class="pub-nav-link">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path d="M4 3h16v14l-2-1.5-2 1.5-2-1.5-2 1.5-2-1.5-2 1.5V3z"/><path d="M8 8h8M8 12h5"/>
                </svg>
                Transparansi Kas
            </a>

            <div class="pub-nav-sep"></div>

            <a href="<?= htmlspecialchars(url('publik/pengumuman.php'), ENT_QUOTES, 'UTF-8') ?>"
               class="pub-nav-link">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                Pengumuman
            </a>
        </nav>

        <!-- Desktop auth area -->
        <div class="pub-nav-auth">
            <?php if ($u && $role === 'mahasiswa'): ?>

                <a href="<?= htmlspecialchars(url('mhs/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>"
                   class="pub-dashboard-pill">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                    Dashboard
                </a>

                <div class="pub-user-menu" id="publik-user-menu-d">
                    <button type="button" class="pub-user-menu-btn" aria-haspopup="true" aria-expanded="false">
                        <div class="pub-user-avatar">
                            <?= htmlspecialchars(mb_strtoupper(mb_substr((string)($u['username'] ?? '?'), 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <span><?= htmlspecialchars((string)($u['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <svg class="pub-user-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="pub-user-dropdown" role="menu">
                        <div class="pub-dropdown-header">
                            <div class="pub-dropdown-header-name"><?= htmlspecialchars((string)($u['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="pub-dropdown-header-role">Mahasiswa</div>
                        </div>
                        <a href="<?= htmlspecialchars(url('mhs/profil.php'), ENT_QUOTES, 'UTF-8') ?>"
                           class="pub-dropdown-item" role="menuitem">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                <circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/>
                            </svg>
                            Atur Profil
                        </a>
                        <div class="pub-dropdown-divider"></div>
                        <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>"
                           class="pub-dropdown-item pub-dropdown-item--danger" role="menuitem">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1"/>
                            </svg>
                            Logout
                        </a>
                    </div>
                </div>

            <?php elseif ($u && $role === 'admin'): ?>

                <a href="<?= htmlspecialchars(url('admin/index.php'), ENT_QUOTES, 'UTF-8') ?>"
                   class="pub-dashboard-pill">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                    Panel Admin
                </a>

                <div class="pub-user-menu" id="publik-user-menu-d">
                    <button type="button" class="pub-user-menu-btn" aria-haspopup="true" aria-expanded="false">
                        <div class="pub-user-avatar">
                            <?= htmlspecialchars(mb_strtoupper(mb_substr((string)($u['username'] ?? '?'), 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <span><?= htmlspecialchars((string)($u['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <svg class="pub-user-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="pub-user-dropdown" role="menu">
                        <div class="pub-dropdown-header">
                            <div class="pub-dropdown-header-name"><?= htmlspecialchars((string)($u['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="pub-dropdown-header-role">Administrator</div>
                        </div>
                        <a href="<?= htmlspecialchars(url('admin/profil.php'), ENT_QUOTES, 'UTF-8') ?>"
                           class="pub-dropdown-item" role="menuitem">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                <circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/>
                            </svg>
                            Atur Profil
                        </a>
                        <div class="pub-dropdown-divider"></div>
                        <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>"
                           class="pub-dropdown-item pub-dropdown-item--danger" role="menuitem">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1"/>
                            </svg>
                            Logout
                        </a>
                    </div>
                </div>

            <?php else: ?>

                <a href="<?= htmlspecialchars(url('register.php'), ENT_QUOTES, 'UTF-8') ?>"
                   class="pub-nav-register">Daftar</a>

                <a href="<?= htmlspecialchars(url('login.php'), ENT_QUOTES, 'UTF-8') ?>"
                   class="pub-nav-login">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>
                    </svg>
                    Login
                </a>

            <?php endif; ?>
        </div>

        <!-- Mobile hamburger -->
        <button type="button" class="pub-hamburger" id="publik-nav-toggle"
                aria-controls="pub-mobile-drawer" aria-expanded="false" aria-label="Buka menu">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" id="pub-hamburger-icon">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

    </div>

    <!-- Mobile drawer -->
    <div id="pub-mobile-drawer" aria-label="Menu mobile" role="navigation">
        <div class="pub-drawer-inner">

            <div class="pub-drawer-label">Jelajahi</div>

            <a href="<?= htmlspecialchars(url('publik/kegiatan.php'), ENT_QUOTES, 'UTF-8') ?>"
               class="pub-drawer-link">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
                </svg>
                Kegiatan
            </a>

            <a href="<?= htmlspecialchars(url('publik/laporan_keuangan.php'), ENT_QUOTES, 'UTF-8') ?>"
               class="pub-drawer-link">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path d="M4 3h16v14l-2-1.5-2 1.5-2-1.5-2 1.5-2-1.5-2 1.5V3z"/><path d="M8 8h8M8 12h5"/>
                </svg>
                Transparansi Kas
            </a>

            <a href="<?= htmlspecialchars(url('publik/pengumuman.php'), ENT_QUOTES, 'UTF-8') ?>"
               class="pub-drawer-link">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                Pengumuman
            </a>

            <div class="pub-drawer-divider"></div>

            <?php if ($u && $role === 'mahasiswa'): ?>

                <div class="pub-drawer-label">Akun</div>

                <a href="<?= htmlspecialchars(url('mhs/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>"
                   class="pub-drawer-link pub-drawer-link--accent">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                    Dashboard Mahasiswa
                </a>

                <a href="<?= htmlspecialchars(url('mhs/profil.php'), ENT_QUOTES, 'UTF-8') ?>"
                   class="pub-drawer-link">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                        <circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/>
                    </svg>
                    Atur Profil
                </a>

                <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>"
                   class="pub-drawer-link pub-drawer-link--danger">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1"/>
                    </svg>
                    Logout
                </a>

            <?php elseif ($u && $role === 'admin'): ?>

                <div class="pub-drawer-label">Akun</div>

                <a href="<?= htmlspecialchars(url('admin/index.php'), ENT_QUOTES, 'UTF-8') ?>"
                   class="pub-drawer-link pub-drawer-link--accent">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                    Dashboard Admin
                </a>

                <a href="<?= htmlspecialchars(url('admin/profil.php'), ENT_QUOTES, 'UTF-8') ?>"
                   class="pub-drawer-link">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                        <circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/>
                    </svg>
                    Atur Profil
                </a>

                <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>"
                   class="pub-drawer-link pub-drawer-link--danger">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1"/>
                    </svg>
                    Logout
                </a>

            <?php else: ?>

                <div class="pub-drawer-auth">
                    <a href="<?= htmlspecialchars(url('register.php'), ENT_QUOTES, 'UTF-8') ?>"
                       class="pub-drawer-btn pub-drawer-btn--outline">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/>
                            <line x1="20" y1="8" x2="20" y2="14"/><line x1="17" y1="11" x2="23" y2="11"/>
                        </svg>
                        Daftar
                    </a>
                    <a href="<?= htmlspecialchars(url('login.php'), ENT_QUOTES, 'UTF-8') ?>"
                       class="pub-drawer-btn pub-drawer-btn--solid">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>
                        </svg>
                        Login
                    </a>
                </div>

            <?php endif; ?>

        </div>
    </div>
</header>
<!-- ═══════════════════════════════ / HEADER ══════════════════════════════ -->

<main class="mx-auto w-full max-w-6xl flex-1 px-4 py-6 sm:px-6 sm:py-8 lg:py-10">
    <!-- ISI HALAMAN -->

<script>
(function () {
    'use strict';

    /* ── Mobile drawer toggle ───────────── */
    const toggleBtn = document.getElementById('publik-nav-toggle');
    const drawer    = document.getElementById('pub-mobile-drawer');
    const icon      = document.getElementById('pub-hamburger-icon');

    if (toggleBtn && drawer) {
        toggleBtn.addEventListener('click', function () {
            const open = drawer.classList.toggle('open');
            toggleBtn.setAttribute('aria-expanded', String(open));

            // swap icon: hamburger ↔ X
            icon.innerHTML = open
                ? '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>'
                : '<path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>';
        });
    }

    /* close drawer on resize to desktop */
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 1024 && drawer) {
            drawer.classList.remove('open');
            if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
            if (icon) icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>';
        }
    });

    /* ── Desktop user dropdown ──────────── */
    const userMenuEl = document.getElementById('publik-user-menu-d');
    if (userMenuEl) {
        const btn      = userMenuEl.querySelector('.pub-user-menu-btn');
        const chevron  = userMenuEl.querySelector('.pub-user-chevron');

        function openMenu() {
            userMenuEl.classList.add('open');
            btn?.setAttribute('aria-expanded', 'true');
        }

        function closeMenu() {
            userMenuEl.classList.remove('open');
            btn?.setAttribute('aria-expanded', 'false');
        }

        btn?.addEventListener('click', function (e) {
            e.stopPropagation();
            userMenuEl.classList.contains('open') ? closeMenu() : openMenu();
        });

        document.addEventListener('click', function (e) {
            if (!userMenuEl.contains(e.target)) closeMenu();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeMenu();
        });
    }
})();
</script>
