<?php
declare(strict_types=1);
/** @var string $pageTitle */
/** @var string $activeNav dashboard|kegiatan|keuangan|pengumuman|profil */
if (!isset($pageTitle)) {
    $pageTitle = 'Mahasiswa';
}
if (!isset($activeNav)) {
    $activeNav = '';
}
$u = current_user();
$name = trim((string)($u['nama_lengkap'] ?? ''));
$greet = $name !== '' ? $name : (string)($u['username'] ?? '');
$hDash = $activeNav === 'dashboard';
$hKegiatan = $activeNav === 'kegiatan';
$hKeuangan = $activeNav === 'keuangan';
$hPengumuman = $activeNav === 'pengumuman';
$hProfil = $activeNav === 'profil';

$logo = htmlspecialchars(brand_logo_url(), ENT_QUOTES, 'UTF-8');
$navClass = function (bool $on): string {
    return $on
        ? 'bg-indigo-100 text-indigo-900'
        : 'text-slate-700 hover:bg-slate-100';
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> — SIM HIMSISKO</title>
    <link rel="icon" type="image/png" href="<?= $logo ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        #mhs-user-menu summary::-webkit-details-marker { display: none; }
        #mhs-user-menu-desktop summary::-webkit-details-marker { display: none; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">
<div class="flex min-h-screen flex-col lg:flex-row">
    <script>
        (function () {
            const openBtn = document.getElementById('mhs-sidebar-open');
            const closeBtn = document.getElementById('mhs-sidebar-close');
            const sidebar = document.getElementById('mhs-sidebar');
            const overlay = document.getElementById('mhs-sidebar-overlay');

            if (!openBtn || !sidebar) return;

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
        })();
    </script>
    <!-- Overlay mobile -->
    <div id="mhs-sidebar-overlay" class="fixed inset-0 z-40 bg-slate-900/60 opacity-0 pointer-events-none transition-opacity duration-200 lg:hidden" aria-hidden="true"></div>

    <!-- Sidebar -->
    <aside id="mhs-sidebar" class="fixed inset-y-0 left-0 z-50 flex w-[min(18rem,88vw)] max-w-sm -translate-x-full flex-col bg-white text-slate-900 shadow-xl transition-transform duration-200 ease-out lg:static lg:z-0 lg:w-64 lg:max-w-none lg:translate-x-0 lg:shadow-none" aria-label="Menu mahasiswa">
        <div class="flex min-h-full flex-col">
            <div class="flex items-center justify-between gap-2 border-b border-slate-200 p-4">
                <a href="<?= htmlspecialchars(url('mhs/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" class="flex min-w-0 flex-1 items-center gap-3">
                    <img src="<?= $logo ?>" alt="HIMSISKO" class="h-11 w-11 shrink-0 rounded-lg ring-1 ring-slate-200 object-contain p-0.5">
                    <div class="min-w-0">
                        <div class="truncate text-sm font-semibold tracking-tight text-slate-900">Mahasiswa</div>
                        <div class="truncate text-xs text-slate-500">SIM HIMSISKO</div>
                    </div>
                </a>
                <button type="button" id="mhs-sidebar-close" class="rounded-lg p-2 text-slate-700 hover:bg-slate-100 lg:hidden" aria-label="Tutup menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto p-3">
                <a href="<?= htmlspecialchars(url('mhs/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium <?= $navClass($hDash) ?>">Dashboard</a>
                <a href="<?= htmlspecialchars(url('publik/kegiatan.php'), ENT_QUOTES, 'UTF-8') ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium <?= $navClass($hKegiatan) ?>">Kegiatan</a>
                <a href="<?= htmlspecialchars(url('publik/laporan_keuangan.php'), ENT_QUOTES, 'UTF-8') ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium <?= $navClass($hKeuangan) ?>">Kas organisasi</a>
                <a href="<?= htmlspecialchars(url('publik/pengumuman.php'), ENT_QUOTES, 'UTF-8') ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium <?= $navClass($hPengumuman) ?>">Pengumuman</a>
                <a href="<?= htmlspecialchars(url('mhs/profil.php'), ENT_QUOTES, 'UTF-8') ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium <?= $navClass($hProfil) ?>">Profil</a>
            </nav>

            <div class="border-t border-slate-200 p-4">
                <div class="flex items-center gap-3">
                    <span class="relative h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-slate-200 text-xs font-bold text-slate-700 flex items-center justify-center">
                        <?php if (!empty($u['foto_profil'])): ?>
                            <img src="<?= htmlspecialchars(url_upload((string)$u['foto_profil']), ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                        <?php else: ?>
                            <?= htmlspecialchars(mb_strtoupper(mb_substr((string)($u['username'] ?? '?'), 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </span>
                    <div class="min-w-0">
                        <div class="truncate text-xs font-semibold text-slate-900"><?= htmlspecialchars($greet, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="truncate text-[11px] text-slate-500">Akun mahasiswa</div>
                    </div>
                </div>
                <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="mt-3 block rounded-lg px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50">Logout</a>
            </div>
        </div>
    </aside>

    <!-- Kolom utama: navbar + konten -->
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex shrink-0 items-center gap-3 border-b border-slate-200 bg-white px-3 py-2.5 shadow-sm sm:px-4">
            <button type="button" id="mhs-sidebar-open" class="inline-flex rounded-lg border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50 lg:hidden" aria-controls="mhs-sidebar" aria-expanded="false" aria-label="Buka menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            <a href="<?= htmlspecialchars(url('mhs/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" class="hidden items-center gap-2 sm:flex lg:flex">
                <img src="<?= $logo ?>" alt="" class="h-9 w-9 rounded-lg object-contain ring-1 ring-slate-200">
                <span class="hidden font-semibold text-slate-900 sm:inline text-sm">Panel mahasiswa</span>
            </a>

            <div class="ml-auto flex items-center gap-2">
                <details id="mhs-user-menu" class="relative lg:hidden">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 py-1 pl-1 pr-2 hover:bg-slate-100">
                        <span class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-lg bg-slate-200 text-xs font-bold text-slate-700">
                            <?php if (!empty($u['foto_profil'])): ?>
                                <img src="<?= htmlspecialchars(url_upload((string)$u['foto_profil']), ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                            <?php else: ?>
                                <?= htmlspecialchars(mb_strtoupper(mb_substr((string)($u['username'] ?? '?'), 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </span>
                        <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="absolute right-0 z-[100] mt-2 w-48 rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                        <a href="<?= htmlspecialchars(url('mhs/profil.php'), ENT_QUOTES, 'UTF-8') ?>" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Atur profil</a>
                        <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="block px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50">Logout</a>
                    </div>
                </details>

                <details id="mhs-user-menu-desktop" class="relative hidden lg:block">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 py-1 pl-1 pr-3 hover:bg-slate-100">
                        <span class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-lg bg-slate-200 text-xs font-bold text-slate-700">
                            <?php if (!empty($u['foto_profil'])): ?>
                                <img src="<?= htmlspecialchars(url_upload((string)$u['foto_profil']), ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                            <?php else: ?>
                                <?= htmlspecialchars(mb_strtoupper(mb_substr((string)($u['username'] ?? '?'), 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </span>
                        <span class="hidden max-w-[10rem] truncate text-xs font-medium text-slate-800 sm:block"><?= htmlspecialchars($greet, ENT_QUOTES, 'UTF-8') ?></span>
                        <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="absolute right-0 z-[100] mt-2 w-52 rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                        <a href="<?= htmlspecialchars(url('mhs/profil.php'), ENT_QUOTES, 'UTF-8') ?>" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Atur profil</a>
                        <a href="<?= htmlspecialchars(url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Beranda</a>
                        <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="block px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50">Logout</a>
                    </div>
                </details>
            </div>
        </header>

        <main class="min-h-0 flex-1 overflow-auto">
            <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-8">

