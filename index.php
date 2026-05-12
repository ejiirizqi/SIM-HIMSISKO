<?php declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

$u = current_user();
$pageTitle = 'Beranda SIM HIMSISKO';
$ico = htmlspecialchars(brand_logo_url(), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="icon" type="image/png" href="<?= $ico ?>">

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-[#f8fafc] overflow-x-hidden">

<div class="relative min-h-screen overflow-hidden">

    <!-- Background Blur -->
    <div class="absolute top-[-150px] right-[-120px] w-[350px] h-[350px] bg-purple-500/20 rounded-full blur-3xl"></div>
    <div class="absolute bottom-[-120px] left-[-100px] w-[300px] h-[300px] bg-indigo-500/20 rounded-full blur-3xl"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-5 sm:px-8 py-10 lg:py-20">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-14 items-center min-h-[85vh]">

            <!-- LEFT CONTENT -->
            <div class="text-center lg:text-left">

                <div class="flex flex-col items-center lg:items-start gap-4">

                    <img src="<?= $ico ?>"
                         alt="Logo HIMSISKO"
                         class="h-20 w-20 sm:h-24 sm:w-24 object-contain">

                    <span class="text-[11px] sm:text-xs font-black uppercase tracking-[0.25em] text-indigo-600">
                        Sistem Informasi HIMSISKO
                    </span>

                </div>

                <h1 class="mt-7 text-4xl sm:text-5xl lg:text-7xl font-black text-slate-900 leading-[1] tracking-tight">

                    Informasi yang
                    <span class="text-indigo-600">transparan.</span>

                    <br class="hidden sm:block">

                    Organisasi yang
                    <span class="text-purple-600">modern.</span>

                </h1>

                <p class="mt-6 text-sm sm:text-base lg:text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto lg:mx-0">

                    Platform digital untuk pengelolaan kegiatan mahasiswa,
                    dokumentasi organisasi, dan transparansi laporan keuangan
                    HIMSISKO secara modern, cepat, dan terintegrasi.

                </p>

                <!-- BUTTON -->
                <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">

                    <a href="<?= htmlspecialchars(url('publik/kegiatan.php'), ENT_QUOTES, 'UTF-8') ?>"
                       class="inline-flex justify-center items-center rounded-2xl bg-slate-900 text-white px-7 py-4 font-bold hover:bg-indigo-600 transition duration-300 shadow-lg">

                        Lihat Aktivitas

                    </a>

                    <?php if ($u && ($u['role'] ?? '') === 'mahasiswa'): ?>

                        <a href="<?= htmlspecialchars(url('mhs/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>"
                           class="inline-flex justify-center items-center rounded-2xl bg-white border border-slate-200 px-7 py-4 font-bold text-indigo-700 hover:bg-indigo-50 transition duration-300 shadow-sm">

                            Dashboard Mahasiswa →

                        </a>

                    <?php elseif ($u && ($u['role'] ?? '') === 'admin'): ?>

                        <a href="<?= htmlspecialchars(url('admin/index.php'), ENT_QUOTES, 'UTF-8') ?>"
                           class="inline-flex justify-center items-center rounded-2xl bg-white border border-slate-200 px-7 py-4 font-bold text-purple-700 hover:bg-purple-50 transition duration-300 shadow-sm">

                            Dashboard Admin →

                        </a>

                    <?php else: ?>

                        <a href="<?= htmlspecialchars(url('register.php'), ENT_QUOTES, 'UTF-8') ?>"
                           class="inline-flex justify-center items-center rounded-2xl bg-indigo-50 border border-indigo-200 px-7 py-4 font-bold text-indigo-700 hover:bg-indigo-100 transition duration-300">

                            Daftar Mahasiswa

                        </a>

                        <a href="<?= htmlspecialchars(url('login.php'), ENT_QUOTES, 'UTF-8') ?>"
                           class="inline-flex justify-center items-center rounded-2xl border border-slate-900 bg-white px-7 py-4 font-bold text-slate-900 hover:bg-slate-100 transition duration-300">

                            Login

                        </a>

                    <?php endif; ?>

                </div>

                <!-- FEATURES -->
                <div class="mt-14 grid grid-cols-1 sm:grid-cols-3 gap-4">

                    <?php
                    $features = [
                        ['Kegiatan', 'Dokumentasi aktivitas organisasi mahasiswa.', 'publik/kegiatan.php'],
                        ['Keuangan', 'Laporan kas dan saldo organisasi realtime.', 'publik/laporan_keuangan.php'],
                        ['Pengumuman', 'Informasi terbaru dari kepengurusan.', 'publik/pengumuman.php']
                    ];

                    foreach ($features as $feat):
                    ?>

                    <a href="<?= htmlspecialchars(url($feat[2]), ENT_QUOTES, 'UTF-8') ?>"
                       class="rounded-2xl bg-white border border-slate-200 p-5 text-left hover:shadow-lg hover:-translate-y-1 transition duration-300">

                        <div class="text-xs font-black uppercase tracking-wide text-indigo-600">
                            <?= htmlspecialchars($feat[0], ENT_QUOTES, 'UTF-8') ?>
                        </div>

                        <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                            <?= htmlspecialchars($feat[1], ENT_QUOTES, 'UTF-8') ?>
                        </p>

                    </a>

                    <?php endforeach; ?>

                </div>

            </div>

            <!-- RIGHT SIDE CARD -->
            <div class="hidden lg:flex justify-center">

                <div class="relative w-full max-w-md">

                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 to-purple-700 rounded-[40px] blur-3xl opacity-30 scale-95"></div>

                    <div class="relative bg-white rounded-[40px] p-10 border border-slate-200 shadow-2xl overflow-hidden">

                        <div class="flex justify-between items-center mb-10">

                            <div class="flex items-center gap-3">

                                <div class="h-14 w-14 rounded-2xl bg-indigo-600"></div>

                                <div>
                                    <div class="text-xs text-slate-400 font-bold uppercase">
                                        SIM HIMSISKO
                                    </div>

                                    <div class="text-lg font-black text-slate-900">
                                        Dashboard
                                    </div>
                                </div>

                            </div>

                            <div class="h-3 w-3 rounded-full bg-emerald-500 animate-pulse"></div>

                        </div>

                        <div class="space-y-5">

                            <?php foreach ([90, 75, 85] as $w): ?>

                                <div class="bg-slate-50 rounded-2xl p-5 shadow-inner">

                                    <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-indigo-500 rounded-full"
                                             style="width: <?= $w ?>%"></div>
                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                        <div class="mt-10 grid grid-cols-2 gap-4">

                            <div class="rounded-2xl bg-indigo-50 p-5">
                                <div class="text-xs uppercase font-bold text-indigo-500">
                                    Transparansi
                                </div>

                                <div class="mt-2 text-3xl font-black text-indigo-900">
                                    100%
                                </div>
                            </div>

                            <div class="rounded-2xl bg-purple-50 p-5">
                                <div class="text-xs uppercase font-bold text-purple-500">
                                    Sistem
                                </div>

                                <div class="mt-2 text-3xl font-black text-purple-900">
                                    Online
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>
```
