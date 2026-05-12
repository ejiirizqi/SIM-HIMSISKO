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

// Maping Navigasi
$d  = $activeNav === 'dashboard';
$kg = $activeNav === 'kegiatan';
$ku = $activeNav === 'keuangan';
$ms = $activeNav === 'mahasiswa';
$pd = $activeNav === 'pendaftar';
$pg = $activeNav === 'pengumuman';
$pl = $activeNav === 'logs';
$pr = $activeNav === 'profil';

// Cek apakah group dropdown harus terbuka
$isGroupMahasiswa = in_array($activeNav, ['mahasiswa', 'pendaftar']);

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
        /* Sembunyikan marker default dropdown */
        details summary::-webkit-details-marker { display: none; }
        summary { list-style: none; }

        #admin-sidebar {
            transition: transform .24s ease, width .24s ease, opacity .24s ease;
        }
        body.admin-sidebar-collapsed #admin-sidebar {
            width: 0 !important;
            min-width: 0 !important;
            overflow: hidden;
            border: 0 !important;
        }
        body.admin-sidebar-collapsed .admin-sidebar-inner {
            display: none;
        }

        /* Nav icon micro-interaction */
        .admin-nav-icon { transition: transform .18s ease, opacity .18s ease; }
        a.admin-nav-link:hover .admin-nav-icon { transform: translateY(-1px) scale(1.03); }

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
            <!-- Header Sidebar -->
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

            <!-- Navigation -->
            <nav class="flex-1 space-y-1 overflow-y-auto p-3">
                <!-- Dashboard -->
                <a href="<?= htmlspecialchars(url('admin/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="admin-nav-link group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium <?= $navClass($d) ?>">
                    <span class="admin-nav-icon flex h-7 w-7 items-center justify-center rounded-md bg-white/5 group-hover:bg-white/10">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 7v-7h7v7h-7z"/></svg>
                    </span>
                    <span class="truncate">Dashboard</span>
                </a>
                
                <div class="my-4 border-t border-slate-800"></div>

                <!-- Dropdown: Master Data -->
                <details class="group" <?= $isGroupMahasiswa ? 'open' : '' ?>>
                    <summary class="flex cursor-pointer items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition-all">
                        <div class="flex items-center gap-3">
                            <span class="admin-nav-icon flex h-7 w-7 items-center justify-center rounded-md bg-white/5 group-hover:bg-white/10" aria-hidden="true">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 0 0-5-2.5M9 20H4v-2a3 3 0 0 1 5-2.5m1-4a4 4 0 1 0-8 0 4 4 0 0 0 8 0zm8 0a4 4 0 1 0-8 0 4 4 0 0 0 8 0z"/>
                                </svg>
                            </span>
                            <span>Manajemen Anggota</span>
                        </div>
                        <svg class="h-4 w-4 transition-transform duration-200 group-open:-rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="mt-1 space-y-1 pl-4">
                        <a href="<?= htmlspecialchars(url('admin/mahasiswa.php'), ENT_QUOTES, 'UTF-8') ?>" class="admin-nav-link group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium <?= $navClass($ms) ?>">
                            <span class="admin-nav-icon flex h-7 w-7 items-center justify-center rounded-md bg-white/5 group-hover:bg-white/10" aria-hidden="true">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="8.5" cy="7" r="4"/>
                                </svg>
                            </span>
                            <span class="truncate">Data Mahasiswa</span>
                        </a>
                        <a href="<?= htmlspecialchars(url('admin/pendaftar.php'), ENT_QUOTES, 'UTF-8') ?>" class="admin-nav-link group flex items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-sm font-medium <?= $navClass($pd) ?>">
                            <span class="flex items-center gap-3 min-w-0">
                                <span class="admin-nav-icon flex h-7 w-7 items-center justify-center rounded-md bg-white/5 group-hover:bg-white/10" aria-hidden="true">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                                    </svg>
                                </span>
                                <span class="truncate">Pendaftar</span>
                            </span>

                            <?php if ($pendingReg > 0): ?>
                                <span class="rounded-full bg-amber-400 px-1.5 py-0.5 text-[10px] font-bold text-slate-900"><?= $pendingReg ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </details>
                
                <div class="my-4 border-t border-slate-800"></div>
                <!-- Menu Utama Lainnya -->
                <a href="<?= htmlspecialchars(url('admin/kegiatan.php'), ENT_QUOTES, 'UTF-8') ?>" class="admin-nav-link group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium <?= $navClass($kg) ?>">
                    <span class="admin-nav-icon flex h-7 w-7 items-center justify-center rounded-md bg-white/5 group-hover:bg-white/10" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M5 11h14M5 11v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V11"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 11V7h10v4"/></svg>
                    </span>
                    <span class="truncate">Data Kegiatan</span>
                </a>
                
                <a href="<?= htmlspecialchars(url('admin/keuangan.php'), ENT_QUOTES, 'UTF-8') ?>" class="admin-nav-link group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium <?= $navClass($ku) ?>">
                    <span class="admin-nav-icon flex h-7 w-7 items-center justify-center rounded-md bg-white/5 group-hover:bg-white/10" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </span>
                    <span class="truncate">Kas Organisasi</span>
                </a>

                <a href="<?= htmlspecialchars(url('admin/pengumuman.php'), ENT_QUOTES, 'UTF-8') ?>" class="admin-nav-link group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium <?= $navClass($pg) ?>">
                    <span class="admin-nav-icon flex h-7 w-7 items-center justify-center rounded-md bg-white/5 group-hover:bg-white/10" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V4a1 1 0 0 0-2 0v1.341C7.67 5.165 6 7.388 6 10v4.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 1 1-6 0"/></svg>
                    </span>
                    <span class="truncate">Pengumuman</span>
                </a>

                <div class="my-4 border-t border-slate-800"></div>

                <a href="<?= htmlspecialchars(url('admin/log.php'), ENT_QUOTES, 'UTF-8') ?>" class="admin-nav-link group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium <?= $navClass($pl) ?>">
                    <span class="admin-nav-icon flex h-7 w-7 items-center justify-center rounded-md bg-white/5 group-hover:bg-white/10" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                    </span>
                    <span class="truncate">Log Aktivitas</span>
                </a>
                
                
            </nav>

            <!-- Sidebar Toggle (Desktop) -->
            <div class="border-t border-slate-700 p-4 hidden lg:flex justify-center">
                <button type="button" id="admin-sidebar-toggle" class="inline-flex items-center gap-2 rounded-full border border-slate-500 bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                    <span>Tutup Sidebar</span>
                </button>
            </div>

            <!-- Footer Sidebar (Mobile) -->
            <div class="border-t border-slate-700 p-4 text-sm lg:hidden">
                <div class="truncate text-slate-400"><?= htmlspecialchars((string)($u['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="mt-2 inline-block font-medium text-rose-400 hover:text-rose-300">Keluar</a>
            </div>
        </div>
    </aside>

    <!-- Kolom Utama -->
    <div class="flex-1 flex flex-col min-w-0">
        <header class="sticky top-0 z-30 flex shrink-0 items-center gap-3 border-b border-slate-200 bg-white px-3 py-2.5 shadow-sm sm:px-4">
            <!-- Mobile Toggle -->
            <button type="button" id="admin-sidebar-open" class="inline-flex rounded-lg border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50 lg:hidden">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            
            <!-- Desktop Expand -->
            <button type="button" id="admin-sidebar-expand-btn" class="hidden rounded-lg border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50 lg:inline-flex">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            <a href="<?= htmlspecialchars(url('admin/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="hidden items-center gap-2 sm:flex">
                <img src="<?= $logo ?>" alt="" class="h-9 w-9 rounded-lg object-contain ring-1 ring-slate-200">
                <span class="font-semibold text-slate-900 text-sm">Panel Admin</span>
            </a>

            <div class="ml-auto flex items-center gap-2">
                <details id="admin-user-menu" class="relative">
                    <summary class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 py-1.5 pl-1.5 pr-2 hover:bg-slate-100 sm:pr-3">
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
                        <a href="<?= htmlspecialchars(url('admin/profil.php'), ENT_QUOTES, 'UTF-8') ?>" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Atur Profil</a>
                       
                        <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="block px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50">Logout</a>
                    </div>
                </details>
            </div>
        </header>

        <main class="min-h-0 flex-1 overflow-auto">
            <div class="mx-auto max-w-6xl px-4 py-5 sm:px-6 sm:py-6 lg:px-10 lg:py-10">
                <!-- Konten Halaman Disini -->