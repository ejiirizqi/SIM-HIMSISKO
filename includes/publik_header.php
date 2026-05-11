<?php
declare(strict_types=1);
/** @var string $pageTitle */
if (!isset($pageTitle)) {
    $pageTitle = 'SIM HIMSISKO';
}
$u = session_status() === PHP_SESSION_ACTIVE && function_exists('current_user') ? current_user() : null;
$role = $u['role'] ?? '';

// Jika user sudah login sebagai mahasiswa/admin, halaman publik diarahkan ke dashboard masing-masing.
// (Rule: nomor 2)
if ($u && ($role === 'mahasiswa' || $role === 'admin')) {
    $target = $role === 'mahasiswa' ? url('mhs/dashboard.php') : url('admin/index.php');
    header('Location: ' . $target);
    exit;
}
$logo = htmlspecialchars(brand_logo_url(), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> — SIM HIMSISKO</title>
    <link rel="icon" type="image/png" href="<?= $logo ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>#publik-user-menu summary::-webkit-details-marker, #publik-user-menu-d summary::-webkit-details-marker { display: none; }</style>
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-slate-800">
<header class="sticky top-0 z-50 border-b bg-white shadow-sm">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-2 px-3 py-2.5 sm:px-4">
        <a href="<?= htmlspecialchars(url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="flex min-w-0 items-center gap-2 sm:gap-3">
            <img src="<?= $logo ?>" alt="HIMSISKO" class="h-9 w-9 shrink-0 rounded-lg object-contain ring-1 ring-slate-200 sm:h-10 sm:w-10">
            <span class="truncate text-sm font-bold text-slate-900 sm:text-base">SIM HIMSISKO</span>
        </a>

        <button type="button" id="publik-nav-toggle" class="inline-flex rounded-lg border border-slate-200 p-2 text-slate-700 hover:bg-slate-50 lg:hidden" aria-controls="publik-mobile-drawer" aria-expanded="false" aria-label="Menu">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <nav class="hidden flex-wrap items-center gap-3 text-sm font-medium lg:flex" aria-label="Menu">
            <a href="<?= htmlspecialchars(url('publik/kegiatan.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-slate-600 hover:text-slate-900">Kegiatan</a>
            <a href="<?= htmlspecialchars(url('publik/laporan_keuangan.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-slate-600 hover:text-slate-900">Transparansi kas</a>
            <a href="<?= htmlspecialchars(url('publik/pengumuman.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-slate-600 hover:text-slate-900">Pengumuman</a>
            <?php if ($u && $role === 'mahasiswa'): ?>
                <a href="<?= htmlspecialchars(url('mhs/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-indigo-600 hover:text-indigo-800">Dashboard mahasiswa</a>
                <details id="publik-user-menu-d" class="relative">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-2 py-1.5 hover:bg-slate-100">
                        <span class="text-xs font-semibold text-slate-800"><?= htmlspecialchars((string)($u['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="absolute right-0 z-[100] mt-2 w-52 rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                        <a href="<?= htmlspecialchars(url('mhs/profil.php'), ENT_QUOTES, 'UTF-8') ?>" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Atur profil</a>
                        <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="block px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50">Logout</a>
                    </div>
                </details>
            <?php elseif ($u && $role === 'admin'): ?>
                <a href="<?= htmlspecialchars(url('admin/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-indigo-600 hover:text-indigo-800">Admin</a>
                <details id="publik-user-menu-d" class="relative">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-2 py-1.5 hover:bg-slate-100">
                        <span class="text-xs font-semibold text-slate-800"><?= htmlspecialchars((string)($u['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="absolute right-0 z-[100] mt-2 w-52 rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                        <a href="<?= htmlspecialchars(url('admin/profil.php'), ENT_QUOTES, 'UTF-8') ?>" class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Atur profil</a>
                        <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="block px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50">Logout</a>
                    </div>
                </details>
            <?php else: ?>
                <a href="<?= htmlspecialchars(url('register.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-slate-600 hover:text-slate-900">Daftar</a>
                <a href="<?= htmlspecialchars(url('login.php'), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800">Login</a>
            <?php endif; ?>
        </nav>
    </div>

    <div id="publik-mobile-drawer" class="hidden border-t border-slate-100 bg-white lg:hidden">
        <nav class="flex flex-col gap-1 px-3 py-3 text-sm font-medium" aria-label="Menu mobile">
            <a href="<?= htmlspecialchars(url('publik/kegiatan.php'), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg px-3 py-2.5 text-slate-700 hover:bg-slate-50">Kegiatan</a>
            <a href="<?= htmlspecialchars(url('publik/laporan_keuangan.php'), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg px-3 py-2.5 text-slate-700 hover:bg-slate-50">Transparansi kas</a>
            <a href="<?= htmlspecialchars(url('publik/pengumuman.php'), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg px-3 py-2.5 text-slate-700 hover:bg-slate-50">Pengumuman</a>
            <?php if ($u && $role === 'mahasiswa'): ?>
                <a href="<?= htmlspecialchars(url('mhs/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg px-3 py-2.5 text-indigo-700 hover:bg-indigo-50">Dashboard mahasiswa</a>
                <a href="<?= htmlspecialchars(url('mhs/profil.php'), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg px-3 py-2.5 text-slate-700 hover:bg-slate-50">Atur profil</a>
                <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg px-3 py-2.5 font-semibold text-rose-600 hover:bg-rose-50">Logout</a>
            <?php elseif ($u && $role === 'admin'): ?>
                <a href="<?= htmlspecialchars(url('admin/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg px-3 py-2.5 text-indigo-700 hover:bg-indigo-50">Dashboard admin</a>
                <a href="<?= htmlspecialchars(url('admin/profil.php'), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg px-3 py-2.5 text-slate-700 hover:bg-slate-50">Atur profil</a>
                <a href="<?= htmlspecialchars(url('logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg px-3 py-2.5 font-semibold text-rose-600 hover:bg-rose-50">Logout</a>
            <?php else: ?>
                <a href="<?= htmlspecialchars(url('register.php'), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg px-3 py-2.5 text-slate-700 hover:bg-slate-50">Daftar mahasiswa</a>
                <a href="<?= htmlspecialchars(url('login.php'), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg px-3 py-2.5 font-semibold text-slate-900 hover:bg-slate-100">Login mahasiswa / admin</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="mx-auto w-full max-w-6xl flex-1 px-4 py-6 sm:px-6 sm:py-8 lg:py-10">
