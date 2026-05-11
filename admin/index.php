<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/helpers/keuangan_ringkasan.php';

require_role('admin');

$pageTitle = 'Dashboard Admin';
$activeNav = 'dashboard';

$r = db()->query('SELECT COUNT(*) AS total FROM kegiatan')->fetch();
$totalKegiatan = (int)($r['total'] ?? 0);

$totalMahasiswa = (int)(db()->query('SELECT COUNT(*) AS n FROM users WHERE role=\'mahasiswa\'')->fetch()['n'] ?? 0);
$totalPengumuman = (int)(db()->query('SELECT COUNT(*) AS n FROM pengumuman')->fetch()['n'] ?? 0);

$pendingReg = 0;
try {
    $pendingReg = (int)(db()->query(
        "SELECT COUNT(*) AS c FROM users WHERE role = 'mahasiswa' AND approval_status = 'pending'"
    )->fetch()['c'] ?? 0);
} catch (Throwable $e) {
    $pendingReg = 0;
}

$kas = keuangan_ringkasan(db());

require dirname(__DIR__) . '/includes/admin_header.php';
?>
<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
    <p class="text-slate-600 mt-1">Ringkasan kegiatan dan saldo kas</p>
</div>

<?php if ($pendingReg > 0): ?>
    <div class="mb-6 flex flex-col gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 sm:flex-row sm:items-center sm:justify-between">
        <span>Ada <strong><?= $pendingReg ?></strong> pendaftar mahasiswa yang menunggu persetujuan.</span>
        <a href="<?= htmlspecialchars(url('admin/pendaftar.php'), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex shrink-0 font-semibold text-amber-900 underline decoration-2 underline-offset-2 hover:text-amber-950">Buka halaman pendaftar →</a>
    </div>
<?php endif; ?>

<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm font-medium text-slate-500">Total kegiatan</div>
        <div class="mt-2 text-3xl font-bold text-slate-900"><?= $totalKegiatan ?></div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm font-medium text-slate-500">Mahasiswa terdaftar</div>
        <div class="mt-2 text-3xl font-bold text-slate-900"><?= $totalMahasiswa ?></div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm font-medium text-slate-500">Pengumuman</div>
        <div class="mt-2 text-3xl font-bold text-slate-900"><?= $totalPengumuman ?></div>
    </div>
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
        <div class="text-sm font-medium text-emerald-800">Total pemasukan</div>
        <div class="mt-2 text-2xl font-bold text-emerald-900"><?= htmlspecialchars(format_rupiah($kas['pemasukan']), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="rounded-xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
        <div class="text-sm font-medium text-rose-800">Total pengeluaran</div>
        <div class="mt-2 text-2xl font-bold text-rose-900"><?= htmlspecialchars(format_rupiah($kas['pengeluaran']), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm font-medium text-slate-500">Sisa saldo kas</div>
        <div class="mt-2 text-2xl font-bold <?= $kas['saldo'] >= 0 ? 'text-slate-900' : 'text-rose-600' ?>">
            <?= htmlspecialchars(format_rupiah($kas['saldo']), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <p class="mt-2 text-xs text-slate-500">Pemasukan − Pengeluaran</p>
    </div>
</div>

<div class="mt-10 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-lg font-semibold text-slate-900">Langkah cepat</h2>
    <p class="text-slate-600 text-sm mt-2">Pilih modul utama yang sering digunakan.</p>
    <div class="mt-4 flex flex-wrap gap-3">
        <a href="<?= htmlspecialchars(url('admin/kegiatan.php'), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Data kegiatan</a>
        <a href="<?= htmlspecialchars(url('admin/keuangan.php'), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">Laporan keuangan</a>
        <a href="<?= htmlspecialchars(url('admin/mahasiswa.php'), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">Data mahasiswa</a>
        <a href="<?= htmlspecialchars(url('admin/pengumuman.php'), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">Pengumuman</a>
        <a href="<?= htmlspecialchars(url('admin/pendaftar.php'), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-950 hover:bg-amber-100">Pendaftar baru<?= $pendingReg > 0 ? ' (' . $pendingReg . ')' : '' ?></a>
    </div>
</div>
<?php
require dirname(__DIR__) . '/includes/admin_footer.php';
