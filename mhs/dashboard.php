<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/config/auth.php';

require_role('mahasiswa');

$pageTitle = 'Dashboard mahasiswa';
$activeNav = 'dashboard';

$u = current_user();

require dirname(__DIR__) . '/includes/mhs_header.php';
?>

<div class="rounded-3xl bg-gradient-to-br from-indigo-600 to-purple-900 text-white p-8 shadow-xl">
    <h1 class="text-2xl font-black tracking-tight">Selamat datang kembali</h1>
    <p class="text-indigo-100 mt-2 max-w-xl text-sm opacity-95">
        Monitor kegiatan, transparansi keuangan, dan pengumuman organisasi. Profil Anda dapat diperbarui kapan saja.
    </p>
</div>

<div class="mt-10 grid sm:grid-cols-3 gap-4">
    <a href="<?= htmlspecialchars(url('publik/kegiatan.php'), ENT_QUOTES, 'UTF-8') ?>" class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:border-indigo-200 hover:shadow-md transition flex flex-col">
        <div class="text-xs font-black text-indigo-600 uppercase">Katalog</div>
        <h2 class="text-lg font-bold text-slate-900 mt-1 group-hover:text-indigo-700 transition">Daftar kegiatan</h2>
        <p class="text-sm text-slate-600 mt-2 flex-1">Lihat aktivitas bersama dokumentasi resmi organisasi.</p>
        <span class="mt-6 text-xs font-black text-indigo-600 underline decoration-2 underline-offset-4 decoration-indigo-200">Buka</span>
    </a>

    <a href="<?= htmlspecialchars(url('publik/laporan_keuangan.php'), ENT_QUOTES, 'UTF-8') ?>" class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:border-indigo-200 hover:shadow-md transition flex flex-col">
        <div class="text-xs font-black text-indigo-600 uppercase">Transparansi</div>
        <h2 class="text-lg font-bold text-slate-900 mt-1 group-hover:text-indigo-700 transition">Laporan keuangan organisasi</h2>
        <p class="text-sm text-slate-600 mt-2 flex-1">Lihat saldo secara real-time dan unduh rekapitulasi PDF legal.</p>
        <span class="mt-6 text-xs font-black text-indigo-600 underline decoration-2 underline-offset-4 decoration-indigo-200">Buka</span>
    </a>

    <a href="<?= htmlspecialchars(url('publik/pengumuman.php'), ENT_QUOTES, 'UTF-8') ?>" class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:border-indigo-200 hover:shadow-md transition flex flex-col">
        <div class="text-xs font-black text-indigo-600 uppercase">Informasi</div>
        <h2 class="text-lg font-bold text-slate-900 mt-1 group-hover:text-indigo-700 transition">Pengumuman resmi</h2>
        <p class="text-sm text-slate-600 mt-2 flex-1">Jangan sampai tertinggal announcment rutin kepengurusan.</p>
        <span class="mt-6 text-xs font-black text-indigo-600 underline decoration-2 underline-offset-4 decoration-indigo-200">Buka</span>
    </a>
</div>

<div class="mt-12 p-8 rounded-[2rem] bg-slate-900 text-white border border-white/10">
    <h3 class="text-sm font-medium text-slate-400 uppercase tracking-[0.2em] mb-6">Konfigurasi akun Anda</h3>
    <div class="flex flex-wrap gap-12 items-center justify-between">
        <div class="flex items-center gap-5">
            <div class="h-14 w-14 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center overflow-hidden shrink-0">
                <?php if (!empty($u['foto_profil'])): ?>
                    <img src="<?= htmlspecialchars(url_upload((string)$u['foto_profil']), ENT_QUOTES, 'UTF-8') ?>" alt="" class="h-full w-full object-cover">
                <?php else: ?>
                    <span class="text-xl font-black text-indigo-300"><?php
                        $uname = (string)($u['username'] ?? 'M');
                        echo strtoupper($uname !== '' ? mb_substr($uname, 0, 1) : '?');
                        ?></span>
                <?php endif; ?>
            </div>
            <div>
                <div class="text-xl font-bold"><?= htmlspecialchars((string)(trim((string)($u['nama_lengkap'] ?? '')) ?: $u['username']), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-indigo-200 text-sm"><?= htmlspecialchars((string)($u['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <?php if (!empty($u['email'])): ?>
                    <div class="text-slate-400 text-xs mt-1"><?= htmlspecialchars((string)$u['email'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="flex gap-4">
            <a href="<?= htmlspecialchars(url('mhs/profil.php'), ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl bg-white text-slate-900 px-8 py-3 font-bold text-sm hover:bg-indigo-50 transition">
                Kelola Profil Mahasiswa
            </a>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/mhs_footer.php'; ?>
