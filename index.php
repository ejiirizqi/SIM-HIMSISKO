<?php
declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

$u = current_user();
$pageTitle = 'Beranda SIM HIMSISKO';
$ico = htmlspecialchars(brand_logo_url(), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/png" href="<?= $ico ?>">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#fdfbff]">
    <div class="relative overflow-hidden min-h-screen flex flex-col items-center px-6 py-12 lg:py-24 text-center lg:text-left lg:grid lg:grid-cols-2 lg:gap-24 max-w-[1200px] mx-auto">

        <!-- Background decoration -->
        <div class="absolute top-[-10%] right-[-5%] w-96 h-96 bg-purple-600/10 rounded-full blur-3xl -z-10"></div>

        <div class="flex flex-col justify-center z-10">
            <div class="flex flex-col items-center gap-4 lg:items-start mb-6">
                <img src="<?= $ico ?>" alt="Logo HIMSISKO" class="h-20 w-20 object-contain sm:h-24 sm:w-24">
                <span class="text-indigo-600 font-black tracking-[0.2em] uppercase text-[10px] text-center lg:text-left">Komunitas HIMSISKO</span>
            </div>
            <h1 class="text-5xl lg:text-[4.25rem] font-black text-slate-900 tracking-tighter leading-[0.95]">
                Informasi itu <span class="text-indigo-600">transparan.</span><br>Pengurus itu <span class="text-purple-600">bertanggung&nbsp;jawab.</span>
            </h1>

            <p class="mt-8 text-lg text-slate-600 leading-relaxed max-w-xl font-medium mx-auto lg:mx-0 opacity-90">
                Satu aplikasi untuk manajemen agenda, dokumentasi foto &amp; video, serta transparansi keuangan yang dapat Anda unduh secara resmi sebagai PDF laporan&nbsp;audit.
            </p>

            <div class="mt-12 flex flex-col sm:flex-row gap-5 justify-center lg:justify-start items-center lg:items-stretch">
                <a href="<?= htmlspecialchars(url('publik/kegiatan.php'), ENT_QUOTES, 'UTF-8') ?>"
                   class="w-full sm:w-auto rounded-2xl bg-slate-900 text-white font-bold px-10 py-5 shadow-xl shadow-slate-200 hover:-translate-y-1 transition hover:bg-indigo-600">
                    Lihat aktivitas mahasiswa
                </a>
                <?php if ($u && ($u['role'] ?? '') === 'mahasiswa'): ?>
                    <a href="<?= htmlspecialchars(url('mhs/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>"
                       class="w-full sm:w-auto rounded-2xl border-4 border-white bg-white px-10 py-[18px] font-bold shadow-lg text-indigo-600 hover:bg-indigo-50 transition hover:-translate-y-1 ring-8 ring-purple-600/10">
                        Lanjut ke dashboard &rarr;
                    </a>
                <?php elseif ($u && ($u['role'] ?? '') === 'admin'): ?>
                    <a href="<?= htmlspecialchars(url('admin/index.php'), ENT_QUOTES, 'UTF-8') ?>"
                       class="w-full sm:w-auto rounded-2xl border-4 border-white bg-white px-10 py-[18px] font-bold shadow-lg text-purple-900 hover:bg-purple-50 transition hover:-translate-y-1">
                        Dashboard admin panel
                    </a>
                <?php else: ?>
                    <a href="<?= htmlspecialchars(url('register.php'), ENT_QUOTES, 'UTF-8') ?>"
                       class="w-full sm:w-auto rounded-2xl border-2 border-indigo-200 bg-indigo-50 px-8 py-5 text-center text-sm font-bold text-indigo-900 hover:bg-indigo-100 transition hover:-translate-y-1">
                        Daftar mahasiswa
                    </a>
                    <a href="<?= htmlspecialchars(url('login.php'), ENT_QUOTES, 'UTF-8') ?>"
                       class="w-full sm:w-auto rounded-2xl bg-white px-10 py-5 border-2 border-slate-900 font-black text-[11px] uppercase tracking-[0.15em] hover:bg-indigo-50 transition hover:border-indigo-600">
                        Login
                    </a>
                <?php endif; ?>
            </div>

            <!-- Quick Footer Links Grid -->
            <div class="mt-20 grid grid-cols-3 gap-x-12 gap-y-8 border-y border-indigo-100 py-12 w-full lg:max-w-2xl">
                <?php foreach ([['Kegiatan', 'Semua dokumentasi foto & video resmi.', 'publik/kegiatan.php'], ['Laporan kas', 'Pemasukan, pengeluaran, saldo realtime.', 'publik/laporan_keuangan.php'], ['Informasi dinamis', 'Pengumuman resmi pusat komunikasi.', 'publik/pengumuman.php']] as $feat): ?>
                    <div class="text-left px-4 border-l-2 border-indigo-600 first:border-0 lg:first:border-l-2 first:pl-0">
                        <a href="<?= htmlspecialchars(url($feat[2]), ENT_QUOTES, 'UTF-8') ?>" class="block group">
                            <div class="text-[11px] font-black text-indigo-600 uppercase"><?= htmlspecialchars($feat[0], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-[13px] text-slate-500 mt-1 font-medium opacity-75 group-hover:opacity-100 transition"><?= htmlspecialchars($feat[1], ENT_QUOTES, 'UTF-8') ?></div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="hidden lg:flex items-center justify-center relative">
             <div class="absolute inset-0 bg-gradient-to-br from-purple-600 to-blue-900 rounded-[3rem] blur-3xl opacity-40 scale-90"></div>
             <!-- Abstract Graphic Element -->
             <div class="relative z-10 w-full aspect-square max-w-md bg-white rounded-[2.5rem] shadow-2xl border border-slate-100 p-12 flex flex-col justify-between transition duration-500 hover:-translate-y-2">
                <div class="flex justify-between items-start">
                    <span class="h-14 w-14 bg-purple-600 rounded-xl flex shadow-lg shadow-purple-900/40"></span>
                    <div class="h-3 w-3 rounded-full bg-emerald-500 animate-pulse"></div>
                </div>
                <div class="space-y-8">
                     <div class="h-2 w-24 bg-indigo-100 rounded-full mb-12"></div>
                     <div class="space-y-3">
                        <?php foreach([80,95,72] as $w): ?>
                            <div class="h-10 bg-slate-50 rounded-xl w-full overflow-hidden shadow-inner px-4 flex items-center">
                                <div class="h-1.5 bg-indigo-200 rounded-full" style="width: <?= $w ?>%"></div>
                            </div>
                        <?php endforeach; ?>
                     </div>
                </div>
                <div class="flex items-center gap-6">
                    <div class="rounded-2xl bg-indigo-50 p-4 flex-1">
                        <div class="text-[9px] font-black uppercase text-indigo-400 tracking-wider">Integrity</div>
                        <div class="text-xl font-black text-indigo-900 tracking-tighter">100%</div>
                    </div>
                     <div class="rounded-full h-14 w-14 border-8 border-purple-900 border-t-transparent animate-spin opacity-45"></div>
                </div>
             </div>
        </div>
    </div>
</body>
</html>
