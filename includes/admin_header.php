<?php
declare(strict_types=1);
/** @var string $pageTitle */
/** @var string $activeNav dashboard|kegiatan|keuangan|mahasiswa|pendaftar|pengumuman|profil|logs */
if (!isset($pageTitle)) {
    $pageTitle = 'Admin';
}
if (!isset($activeNav)) {
    $activeNav = '';
}
$d = $activeNav === 'dashboard';
$kg = $activeNav === 'kegiatan';
$ku = $activeNav === 'keuangan';
$ms = $activeNav === 'mahasiswa';
$pd = $activeNav === 'pendaftar';
$pg = $activeNav === 'pengumuman';
$pl = $activeNav === 'logs';
$pr = $activeNav === 'profil';

$pendingReg = 0;
try {
    $pendingReg = (int)(db()->query(
        "SELECT COUNT(*) AS c FROM users WHERE role = 'mahasiswa' AND approval_status = 'pending'"
    )->fetch()['c'] ?? 0);
} catch (Throwable $e) {
    $pendingReg = 0;
}

$u = current_user();
$dispName = trim((string)($u['nama_lengkap'] ?? '')) !== '' ? trim((string)$u['nama_lengkap']) : (string)($u['username'] ?? '');
$logo = htmlspecialchars(brand_logo_url(), ENT_QUOTES, 'UTF-8');
$navClass = function (bool $on): string {
    return $on
        ? 'bg-sky-700 text-white'
        : 'text-slate-300 hover:bg-slate-800 hover:text-white';
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
        #admin-user-menu summary::-webkit-details-marker { display: none; }
        #admin-sidebar {
            transition: transform .24s ease, width .24s ease, padding .24s ease, opacity .24s ease;
        }
        body.admin-sidebar-collapsed #admin-sidebar {
            width: 0 !important;
            max-width: 0 !important;
            min-width: 0 !important;
            padding: 0 !important;
            opacity: 0.98;
            overflow: hidden;
        }
        body.admin-sidebar-collapsed #admin-sidebar .admin-sidebar-inner {
            opacity: 0;
            visibility: hidden;
        }
        body.admin-sidebar-collapsed #admin-sidebar-expand-btn {
            display: inline-flex !important;
        }
        @media (max-width: 1023px) {
            body.admin-sidebar-collapsed #admin-sidebar {
                width: min(18rem, 88vw) !important;
                opacity: 1;
                visibility: visible;
            }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">
<div class="flex min-h-screen flex-col lg:flex-row">
    <!-- Overlay mobile -->
    <div id="admin-sidebar-overlay" class="fixed inset-0 z-40 bg-slate-900/60 opacity-0 pointer-events-none transition-opacity duration-200 lg:hidden" aria-hidden="true"></div>

    <!-- Sidebar -->
    <aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-50 flex w-[min(18rem,88vw)] max-w-sm -translate-x-full flex-col bg-slate-950 text-white shadow-xl transition-transform duration-200 ease-out lg:static lg:z-0 lg:w-64 lg:max-w-none lg:translate-x-0 lg:shadow-none" aria-label="Menu admin">
        <div class="admin-sidebar-inner flex min-h-full flex-col">
            <div class="flex items-center justify-between gap-2 border-b border-slate-700 p-4">
            <a href="<?= htmlspecialchars(url('admin/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="flex min-w-0 flex-1 items-center gap-3">
                <img src="<?= $logo ?>" alt="HIMSISKO" class="h-11 w-11 shrink-0 rounded-lg bg-white/10 object-contain p-0.5">
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold tracking-tight">SIM HIMSISKO</div>
                    <div class="truncate text-xs text-slate-400">HIMSISKO IBI-K57</div>
                </div>
            </a>
            <button type="button" id="admin-sidebar-close" class="rounded-lg p-2 text-slate-300 hover:bg-slate-800 lg:hidden" aria-label="Tutup menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <nav class="flex-1 space-y-1 overflow-y-auto p-3">
            <a href="<?= htmlspecialchars(url('admin/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium <?= $navClass($d) ?>">Dashboard</a>
            <a href="<?= htmlspecialchars(url('admin/kegiatan.php'), ENT_QUOTES, 'UTF-8') ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium <?= $navClass($kg) ?>">Data kegiatan</a>
            <a href="<?= htmlspecialchars(url('admin/keuangan.php'), ENT_QUOTES, 'UTF-8') ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium <?= $navClass($ku) ?>">Laporan keuangan</a>
            <a href="<?= htmlspecialchars(url('admin/mahasiswa.php'), ENT_QUOTES, 'UTF-8') ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium <?= $navClass($ms) ?>">Data mahasiswa</a>
            <a href="<?= htmlspecialchars(url('admin/pendaftar.php'), ENT_QUOTES, 'UTF-8') ?>" class="flex items-center justify-between gap-2 rounded-lg px-3 py-2.5 text-sm font-medium <?= $navClass($pd) ?>">
                <span>Pendaftar</span>
                <?php if ($pendingReg > 0): ?>
                    <span class="min-w-[1.25rem] rounded-full bg-amber-400 px-1.5 py-0.5 text-center text-[10px] font-bold text-slate-900"><?= $pendingReg ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= htmlspecialchars(url('admin/pengumuman.php'), ENT_QUOTES, 'UTF-8') ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium <?= $navClass($pg) ?>">Pengumuman</a>
            <a href="<?= htmlspecialchars(url('admin/log.php'), ENT_QUOTES, 'UTF-8') ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium <?= $navClass($pl) ?>">Log aktivitas</a>
            <a href="<?= htmlspecialchars(url('admin/profil.php'), ENT_QUOTES, 'UTF-8') ?>" class="block rounded-lg px-3 py-2.5 text-sm font-medium <?= $navClass($pr) ?>">Profil saya</a>
        </nav>
        <div class="border-t border-slate-700 p-4 hidden lg:flex justify-center">
            <button type="button" id="admin-sidebar-toggle" class="inline-flex items-center gap-2 rounded-full border border-slate-500 bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition" aria-expanded="true" aria-label="Toggle sidebar">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4l8 8-8 8"/></svg>
                <span>Tutup sidebar</span>
            </button>
        </div>
        <div class="border-t border-slate-700 p-4 text-sm lg:hidden">
            <div class="truncate text-slate-400"><?= htmlspecialchars((string)($u['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="mt-2 inline-block font-medium text-rose-400 hover:text-rose-300">Keluar</a>
        </div>
        </div>
    </aside>

    <!-- Kolom utama: navbar + konten -->
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex shrink-0 items-center gap-3 border-b border-slate-200 bg-white px-3 py-2.5 shadow-sm sm:px-4">
            <button type="button" id="admin-sidebar-open" class="inline-flex rounded-lg border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50 lg:hidden" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <button type="button" id="admin-sidebar-expand-btn" class="hidden rounded-lg border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50 lg:inline-flex" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka sidebar">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <a href="<?= htmlspecialchars(url('admin/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="hidden items-center gap-2 sm:flex lg:flex">
                <img src="<?= $logo ?>" alt="" class="h-9 w-9 rounded-lg object-contain ring-1 ring-slate-200">
                <span class="hidden font-semibold text-slate-900 sm:inline text-sm">Panel admin</span>
            </a>
            <div class="ml-auto flex items-center gap-2">
                <details id="admin-user-menu" class="relative">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 py-1.5 pl-1.5 pr-2 hover:bg-slate-100 sm:pr-3">
                        <span class="relative h-9 w-9 shrink-0 overflow-hidden rounded-lg bg-slate-200">
                            <?php if (!empty($u['foto_profil'])): ?>
                                <img src="<?= htmlspecialchars(url_upload((string)$u['foto_profil']), ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                            <?php else: ?>
                                <span class="flex h-full w-full items-center justify-center text-xs font-bold text-slate-600"><?= htmlspecialchars(mb_strtoupper(mb_substr((string)($u['username'] ?? '?'), 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="hidden max-w-[10rem] truncate text-left text-xs font-medium text-slate-800 sm:block"><?= htmlspecialchars($dispName, ENT_QUOTES, 'UTF-8') ?></span>
                        <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="absolute right-0 z-[100] mt-2 w-52 rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                        <div class="border-b border-slate-100 px-3 py-2 text-xs text-slate-500 sm:hidden"><?= htmlspecialchars($dispName, ENT_QUOTES, 'UTF-8') ?></div>
                        <a href="<?= htmlspecialchars(url('admin/profil.php'), ENT_QUOTES, 'UTF-8') ?>" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Atur profil</a>
                        <a href="<?= htmlspecialchars(url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Beranda publik</a>
                        <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="block px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50">Logout</a>
                    </div>
                </details>
            </div>
        </header>

        <main class="min-h-0 flex-1 overflow-auto">
            <div class="mx-auto max-w-6xl px-4 py-5 sm:px-6 sm:py-6 lg:px-10 lg:py-10">
