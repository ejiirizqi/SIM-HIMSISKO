<?php
declare(strict_types=1);
/** @var string $pageTitle */
/** @var string $activeNav dashboard|kegiatan|keuangan|pengumuman|profil */
if (!isset($pageTitle)) $pageTitle = 'Mahasiswa';
if (!isset($activeNav)) $activeNav = '';

$u    = current_user();
$name = trim((string)($u['nama_lengkap'] ?? ''));
$greet = $name !== '' ? $name : (string)($u['username'] ?? '');

$hDash       = $activeNav === 'dashboard';
$hKegiatan   = $activeNav === 'kegiatan';
$hKeuangan   = $activeNav === 'keuangan';
$hPengumuman = $activeNav === 'pengumuman';
$hProfil     = $activeNav === 'profil';

$logo      = htmlspecialchars(brand_logo_url(), ENT_QUOTES, 'UTF-8');
$initials  = mb_strtoupper(mb_substr((string)($u['username'] ?? '?'), 0, 1));
$avatarUrl = !empty($u['foto_profil'])
    ? htmlspecialchars(url_upload((string)$u['foto_profil']), ENT_QUOTES, 'UTF-8')
    : '';

$navClass = fn(bool $on): string => $on
    ? 'mhs-nav-link mhs-nav-link--active'
    : 'mhs-nav-link';

$navItems = [
    ['href' => url('mhs/dashboard.php'),          'label' => 'Dashboard',      'icon' => 'dashboard',      'active' => $hDash],
    ['href' => url('publik/kegiatan.php'),         'label' => 'Data Kegiatan',  'icon' => 'calendar-event', 'active' => $hKegiatan],
    ['href' => url('publik/laporan_keuangan.php'), 'label' => 'Kas Organisasi', 'icon' => 'receipt',        'active' => $hKeuangan],
    ['href' => url('publik/pengumuman.php'),       'label' => 'Pengumuman',     'icon' => 'bell',           'active' => $hPengumuman],
    ['href' => url('mhs/profil.php'),              'label' => 'Profil Saya',    'icon' => 'user-circle',    'active' => $hProfil],
];

$navIcons = [
    'dashboard'      => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
    'calendar-event' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><circle cx="12" cy="16" r="1.25" fill="currentColor" stroke="none"/>',
    'receipt'        => '<path d="M4 3h16v14l-2-1.5-2 1.5-2-1.5-2 1.5-2-1.5-2 1.5V3z"/><path d="M8 8h8M8 12h5"/>',
    'bell'           => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
    'user-circle'    => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="10" r="3"/><path d="M6.17 19a6 6 0 0 1 11.66 0"/>',
];
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
            --sb-w: 16rem;
            --sb-bg: #080d18;
            --sb-border: rgba(255,255,255,.06);
            --sb-text: #7c8fa6;
            --sb-text-hover: #e2e8f0;
            --sb-active-bg: rgba(99,179,237,.13);
            --sb-active-fg: #63b3ed;
            --sb-active-bar: #63b3ed;
            --accent: #63b3ed;
            --accent-glow: rgba(99,179,237,.18);
            --topbar-h: 3.375rem;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { min-height: 100dvh; background: #f1f5f9; color: #1e293b; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
        #mhs-shell { display: flex; min-height: 100dvh; }

        /* SIDEBAR */
        #mhs-sidebar { width: var(--sb-w); background: var(--sb-bg); display: flex; flex-direction: column; flex-shrink: 0; transition: width .24s cubic-bezier(.4,0,.2,1), opacity .24s, transform .24s cubic-bezier(.4,0,.2,1); }
        @media (min-width: 1024px) {
            #mhs-sidebar { position: sticky; top: 0; height: 100dvh; overflow: hidden; }
            #mhs-shell.mhs-collapsed #mhs-sidebar { width: 0; min-width: 0; opacity: 0; pointer-events: none; }
        }
        @media (max-width: 1023px) {
            #mhs-sidebar { position: fixed; inset-block: 0; left: 0; z-index: 50; width: min(var(--sb-w), 88vw); transform: translateX(-100%); box-shadow: 8px 0 40px rgba(0,0,0,.55); }
            #mhs-shell.mhs-open #mhs-sidebar { transform: translateX(0); }
        }

        .mhs-sidebar-inner { display: flex; flex-direction: column; height: 100%; overflow: visible; }

        /* BRAND */
        .mhs-brand { display: flex; align-items: center; gap: .875rem; padding: 1.25rem 1.125rem 1.125rem; text-decoration: none; flex-shrink: 0; border-bottom: 1px solid var(--sb-border); position: relative; }
        .mhs-brand::after { content: ''; position: absolute; bottom: -1px; left: 1.125rem; right: 1.125rem; height: 1px; background: linear-gradient(90deg, var(--accent) 0%, transparent 100%); opacity: .35; }
        .mhs-brand-logo-wrap { position: relative; flex-shrink: 0; }
        .mhs-brand-logo { width: 2.375rem; height: 2.375rem; border-radius: .625rem; object-fit: contain; padding: .2rem; background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1); display: block; }
        .mhs-brand-logo-wrap::after { content: ''; position: absolute; bottom: -.1875rem; right: -.1875rem; width: .5rem; height: .5rem; background: var(--accent); border-radius: 50%; border: 2px solid var(--sb-bg); }
        .mhs-brand-title { font-size: .8125rem; font-weight: 700; color: #e8edf2; letter-spacing: -.015em; line-height: 1.25; }
        .mhs-brand-sub { font-size: .625rem; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: var(--sb-text); margin-top: .0625rem; }

        /* NAV */
        .mhs-nav { flex: 1; overflow-y: auto; padding: 1rem .75rem; display: flex; flex-direction: column; gap: .125rem; scrollbar-width: thin; scrollbar-color: rgba(255,255,255,.08) transparent; }
        .mhs-nav-label { font-size: .5625rem; font-weight: 700; text-transform: uppercase; letter-spacing: .14em; color: rgba(124,143,166,.45); padding: .625rem .75rem .3125rem; }
        .mhs-nav-link { display: flex; align-items: center; gap: .75rem; padding: .5625rem .875rem; border-radius: .625rem; font-size: .8125rem; font-weight: 500; color: var(--sb-text); text-decoration: none; transition: background .16s, color .16s; border-left: 2px solid transparent; }
        .mhs-nav-link:hover { background: rgba(255,255,255,.055); color: var(--sb-text-hover); }
        .mhs-nav-link--active { background: var(--sb-active-bg); color: var(--sb-active-fg); border-left-color: var(--sb-active-bar); font-weight: 600; }
        .mhs-nav-link--active:hover { background: var(--sb-active-bg); color: var(--sb-active-fg); }
        .mhs-nav-icon-wrap { width: 1.625rem; height: 1.625rem; border-radius: .375rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: background .16s; }
        .mhs-nav-link:hover .mhs-nav-icon-wrap { background: rgba(255,255,255,.06); }
        .mhs-nav-link--active .mhs-nav-icon-wrap { background: var(--accent-glow); }
        .mhs-nav-icon { width: 1rem; height: 1rem; flex-shrink: 0; opacity: .6; transition: opacity .16s; }
        .mhs-nav-link:hover .mhs-nav-icon { opacity: .9; }
        .mhs-nav-link--active .mhs-nav-icon { opacity: 1; }
        .mhs-nav-link-label { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* FOOTER */
        .mhs-sidebar-footer { border-top: 1px solid var(--sb-border); padding: .875rem .75rem; flex-shrink: 0; background: rgba(0,0,0,.15); }
        .mhs-user-row { display: flex; align-items: center; gap: .75rem; padding: .5rem .75rem; border-radius: .625rem; background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.05); margin-bottom: .625rem; }
        .mhs-avatar { width: 2.125rem; height: 2.125rem; border-radius: .375rem; overflow: hidden; background: linear-gradient(135deg, rgba(99,179,237,.25), rgba(99,179,237,.1)); display: flex; align-items: center; justify-content: center; font-size: .6875rem; font-weight: 700; color: var(--accent); flex-shrink: 0; border: 1px solid rgba(99,179,237,.22); }
        .mhs-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .mhs-user-name { font-size: .75rem; font-weight: 600; color: #dde5ef; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; flex: 1; min-width: 0; }
        .mhs-user-role { font-size: .5875rem; color: rgba(124,143,166,.65); letter-spacing: .04em; text-transform: uppercase; font-weight: 600; margin-top: .0625rem; }
        .mhs-logout-btn { display: flex; align-items: center; gap: .625rem; width: 100%; padding: .5rem .875rem; border-radius: .625rem; font-size: .7875rem; font-weight: 600; color: #f87171; text-decoration: none; transition: background .15s, color .15s; }
        .mhs-logout-btn:hover { background: rgba(248,113,113,.1); color: #fca5a5; }
        .mhs-logout-btn svg { width: .875rem; height: .875rem; flex-shrink: 0; opacity: .8; }

        /* COLLAPSE BTN */
        .mhs-collapse-btn { display: none; }
        @media (min-width: 1024px) {
            .mhs-collapse-btn { display: flex; align-items: center; justify-content: center; gap: .5rem; border-top: 1px solid var(--sb-border); padding: .6875rem; cursor: pointer; background: none; border-left: none; border-right: none; border-bottom: none; color: rgba(124,143,166,.45); font-size: .625rem; font-weight: 600; font-family: inherit; letter-spacing: .07em; text-transform: uppercase; transition: color .15s, background .15s; flex-shrink: 0; }
            .mhs-collapse-btn:hover { background: rgba(255,255,255,.03); color: var(--sb-text-hover); }
            .mhs-collapse-btn svg { width: .8125rem; height: .8125rem; flex-shrink: 0; }
        }

        /* RESPONSIVE HELPERS */
        .lg\:hidden { display: none !important; }
        .lg\:inline-flex { display: none !important; }
        @media (min-width: 1024px) {
            .lg\:hidden { display: none !important; }
            .lg\:inline-flex { display: inline-flex !important; }
        }
        .hidden { display: none !important; }
        
        /* MOBILE OPEN BTN always visible on mobile */
        @media (max-width: 1023px) {
            #mhs-open-btn { display: flex !important; }
        }

        /* OVERLAY */
        #mhs-overlay { position: fixed; inset: 0; z-index: 40; background: rgba(4,8,20,.65); backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px); opacity: 0; pointer-events: none; transition: opacity .24s; }
        #mhs-shell.mhs-open #mhs-overlay { opacity: 1; pointer-events: auto; }
        @media (min-width: 1024px) { #mhs-overlay { display: none !important; } }

        /* CONTENT */
        #mhs-content { flex: 1; display: flex; flex-direction: column; min-width: 0; background: #f1f5f9; }

        /* TOPBAR */
        #mhs-topbar { position: sticky; top: 0; z-index: 30; height: var(--topbar-h); display: flex; align-items: center; gap: .75rem; padding: 0 1.125rem; background: rgba(255,255,255,.92); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border-bottom: 1px solid rgba(203,213,225,.6); box-shadow: 0 1px 3px rgba(0,0,0,.04); }
        @media (min-width: 640px) { #mhs-topbar { padding: 0 1.5rem; } }
        .mhs-topbar-icon-btn { display: flex; align-items: center; justify-content: center; width: 2.125rem; height: 2.125rem; border-radius: .4375rem; border: 1px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; transition: background .14s, border-color .14s, color .14s; flex-shrink: 0; }
        .mhs-topbar-icon-btn:hover { background: #f8fafc; border-color: #cbd5e1; color: #1e293b; }
        .mhs-topbar-icon-btn svg { width: 1rem; height: 1rem; }
        .mhs-topbar-brand { display: none; align-items: center; gap: .625rem; }
        #mhs-shell.mhs-collapsed .mhs-topbar-brand { display: flex; }
        .mhs-topbar-brand-logo { width: 1.75rem; height: 1.75rem; border-radius: .375rem; object-fit: contain; }
        .mhs-topbar-brand-text { font-size: .8125rem; font-weight: 700; color: #1e293b; letter-spacing: -.015em; }
        .mhs-topbar-title { flex: 1; min-width: 0; }
        .mhs-topbar-title-text { font-size: .875rem; font-weight: 600; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        /* USER DROPDOWN */
        .mhs-topbar-user { position: relative; margin-left: auto; }
        .mhs-topbar-user-btn { display: flex; align-items: center; gap: .5rem; padding: .25rem .75rem .25rem .3125rem; border-radius: 2rem; border: 1px solid #e2e8f0; background: #fff; cursor: pointer; transition: background .14s, border-color .14s; }
        .mhs-topbar-user-btn:hover { background: #f8fafc; border-color: #cbd5e1; }
        .mhs-topbar-avatar { width: 2rem; height: 2rem; border-radius: 50%; overflow: hidden; background: linear-gradient(135deg, rgba(99,179,237,.2), rgba(99,179,237,.08)); display: flex; align-items: center; justify-content: center; font-size: .6875rem; font-weight: 700; color: #0369a1; flex-shrink: 0; border: 1.5px solid rgba(99,179,237,.3); }
        .mhs-topbar-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .mhs-topbar-username { font-size: .75rem; font-weight: 600; color: #1e293b; max-width: 8rem; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
        @media (max-width: 479px) { .mhs-topbar-username { display: none; } }
        .mhs-topbar-chevron { width: .8125rem; height: .8125rem; color: #94a3b8; transition: transform .2s; flex-shrink: 0; }
        .mhs-topbar-user.open .mhs-topbar-chevron { transform: rotate(180deg); }
        .mhs-user-dropdown { display: none; position: absolute; right: 0; top: calc(100% + .4375rem); width: 13rem; background: #fff; border: 1px solid #e2e8f0; border-radius: .875rem; box-shadow: 0 10px 30px rgba(0,0,0,.1), 0 2px 8px rgba(0,0,0,.06); overflow: hidden; z-index: 100; }
        .mhs-topbar-user.open .mhs-user-dropdown { display: block; }
        .mhs-dropdown-header { padding: .625rem .875rem .5rem; border-bottom: 1px solid #f1f5f9; }
        .mhs-dropdown-header-name { font-size: .75rem; font-weight: 700; color: #1e293b; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
        .mhs-dropdown-header-role { font-size: .625rem; color: #94a3b8; font-weight: 500; letter-spacing: .04em; text-transform: uppercase; margin-top: .0625rem; }
        .mhs-dropdown-item { display: flex; align-items: center; gap: .625rem; padding: .5625rem .875rem; font-size: .8125rem; color: #334155; text-decoration: none; transition: background .12s; }
        .mhs-dropdown-item:hover { background: #f8fafc; }
        .mhs-dropdown-item svg { width: .875rem; height: .875rem; color: #94a3b8; flex-shrink: 0; }
        .mhs-dropdown-divider { height: 1px; background: #f1f5f9; margin: .1875rem 0; }
        .mhs-dropdown-item--danger { color: #dc2626; }
        .mhs-dropdown-item--danger svg { color: #ef4444; }
        .mhs-dropdown-item--danger:hover { background: #fef2f2; }

        /* MAIN */
        #mhs-main { flex: 1; overflow: auto; }
        
        /* EXPAND BTN - only show on desktop when collapsed */
        .mhs-topbar-expand-btn { display: none; }
        @media (min-width: 1024px) {
            #mhs-shell.mhs-collapsed .mhs-topbar-expand-btn { display: flex; }
        }
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(148,163,184,.35); border-radius: 999px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(148,163,184,.6); }
    </style>
</head>
<body>

<div id="mhs-overlay" aria-hidden="true"></div>

<div id="mhs-shell">

    <aside id="mhs-sidebar" aria-label="Menu mahasiswa">
        <div class="mhs-sidebar-inner">
            <a href="<?= htmlspecialchars(url('mhs/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" class="mhs-brand">
                <div class="mhs-brand-logo-wrap">
                    <img src="<?= $logo ?>" alt="HIMSISKO" class="mhs-brand-logo">
                </div>
                <div>
                    <div class="mhs-brand-title">Panel Mahasiswa</div>
                    <div class="mhs-brand-sub">SIM HIMSISKO</div>
                </div>
            </a>

            <nav class="mhs-nav" aria-label="Navigasi mahasiswa">
                <div class="mhs-nav-label">Menu</div>
                <?php foreach ($navItems as $item): ?>
                <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"
                   class="<?= $navClass($item['active']) ?>"
                   <?= $item['active'] ? 'aria-current="page"' : '' ?>>
                    <div class="mhs-nav-icon-wrap">
                        <svg class="mhs-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                            <?= $navIcons[$item['icon']] ?? '<circle cx="12" cy="12" r="5"/>' ?>
                        </svg>
                    </div>
                    <span class="mhs-nav-link-label"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
                <?php endforeach; ?>
            </nav>

            <div class="mhs-sidebar-footer">
                <div class="mhs-user-row">
                    <div class="mhs-avatar">
                        <?php if ($avatarUrl): ?>
                            <img src="<?= $avatarUrl ?>" alt="">
                        <?php else: ?>
                            <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </div>
                    <div style="min-width:0; flex:1;">
                        <div class="mhs-user-name"><?= htmlspecialchars($greet, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="mhs-user-role">Mahasiswa Aktif</div>
                    </div>
                </div>
                <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="mhs-logout-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1"/>
                    </svg>
                    Keluar
                </a>
            </div>

            <button type="button" class="mhs-collapse-btn" id="mhs-collapse-btn">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" id="mhs-collapse-icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7"/>
                </svg>
                <span id="mhs-collapse-label">Tutup sidebar</span>
            </button>
        </div>
    </aside>

    <div id="mhs-content">
        <header id="mhs-topbar">
            <button type="button" class="mhs-topbar-icon-btn lg:hidden" id="mhs-open-btn" aria-label="Buka menu">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <button type="button" class="mhs-topbar-icon-btn mhs-topbar-expand-btn" id="mhs-expand-btn" aria-label="Buka sidebar">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="mhs-topbar-brand">
                <img src="<?= $logo ?>" alt="" class="mhs-topbar-brand-logo">
                <span class="mhs-topbar-brand-text">SIM HIMSISKO</span>
            </div>

            <div class="mhs-topbar-title">
                <div class="mhs-topbar-title-text"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></div>
            </div>

            <div class="mhs-topbar-user" id="mhs-user-menu">
                <button type="button" class="mhs-topbar-user-btn" aria-haspopup="true" aria-expanded="false">
                    <div class="mhs-topbar-avatar">
                        <?php if ($avatarUrl): ?>
                            <img src="<?= $avatarUrl ?>" alt="">
                        <?php else: ?>
                            <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </div>
                    <span class="mhs-topbar-username"><?= htmlspecialchars($greet, ENT_QUOTES, 'UTF-8') ?></span>
                    <svg class="mhs-topbar-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div class="mhs-user-dropdown" role="menu">
                    <div class="mhs-dropdown-header">
                        <div class="mhs-dropdown-header-name"><?= htmlspecialchars($greet, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="mhs-dropdown-header-role">Mahasiswa Aktif</div>
                    </div>
                    <a href="<?= htmlspecialchars(url('mhs/profil.php'), ENT_QUOTES, 'UTF-8') ?>" class="mhs-dropdown-item" role="menuitem">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/></svg>
                        Profil Saya
                    </a>
                    <div class="mhs-dropdown-divider"></div>
                    <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="mhs-dropdown-item mhs-dropdown-item--danger" role="menuitem">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1"/></svg>
                        Logout
                    </a>
                </div>
            </div>
        </header>

        <main id="mhs-main">
            <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-8">
