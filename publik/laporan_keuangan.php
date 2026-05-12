<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/helpers/keuangan_ringkasan.php';

$pageTitle = 'Transparansi keuangan';
$kas = keuangan_ringkasan(db());
$stmt = db()->query(
    'SELECT id, tanggal, tipe, nominal, keterangan FROM keuangan ORDER BY tanggal DESC, id DESC'
);
$rows = $stmt->fetchAll();

require dirname(__DIR__) . '/includes/publik_header.php';
?>
<h1 class="text-3xl font-bold text-slate-900">Transparansi keuangan organisasi</h1>
<p class="text-slate-600 mt-2">Ringkasan arus kas (pemasukan, pengeluaran, dan sisa saldo).</p>

<div class="grid sm:grid-cols-3 gap-4 mt-4">
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4">
        <div class="text-xs font-semibold text-emerald-800 uppercase">Pemasukan</div>
        <div class="text-xl font-bold text-emerald-900 mt-1"><?= htmlspecialchars(format_rupiah($kas['pemasukan']), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4">
        <div class="text-xs font-semibold text-rose-800 uppercase">Pengeluaran</div>
        <div class="text-xl font-bold text-rose-900 mt-1"><?= htmlspecialchars(format_rupiah($kas['pengeluaran']), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="rounded-2xl border border-slate-900 bg-slate-900 px-5 py-4">
        <div class="text-xs font-semibold text-slate-300 uppercase">Total saldo</div>
        <div class="text-xl font-bold text-white mt-1"><?= htmlspecialchars(format_rupiah($kas['saldo']), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
</div>

<p class="mt-6 mb-10">
    <a href="<?= htmlspecialchars(url('publik/pdf_laporan_keuangan.php'), ENT_QUOTES, 'UTF-8') ?>"
       class="inline-flex items-center rounded-xl bg-white border border-slate-300 px-4 py-2 text-sm font-bold text-slate-900 hover:bg-slate-50 shadow-sm">
        Unduh laporan PDF
    </a>
</p>

<div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
            <tr class="bg-slate-900 text-white text-left text-xs uppercase tracking-wide">
                <th class="px-4 py-3">Tanggal</th>
                <th class="px-4 py-3">Jenis</th>
                <th class="px-4 py-3">Nominal</th>
                <th class="px-4 py-3">Keterangan</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php if (count($rows) === 0): ?>
                <tr><td colspan="4" class="text-center px-4 py-8 text-slate-400">Belum ada data transaksi.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr class="odd:bg-white even:bg-slate-50 hover:bg-blue-50/50">
                        <td class="px-4 py-3 whitespace-nowrap text-slate-800"><?= htmlspecialchars((string)$row['tanggal'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3">
                            <?= $row['tipe'] === 'masuk'
                                ? '<span class="rounded-full px-2 py-1 text-[10px] font-bold uppercase bg-emerald-100 text-emerald-900">Masuk</span>'
                                : '<span class="rounded-full px-2 py-1 text-[10px] font-bold uppercase bg-rose-100 text-rose-900">Keluar</span>' ?>
                        </td>
                        <td class="px-4 py-3 font-semibold <?= $row['tipe'] === 'masuk' ? 'text-emerald-900' : 'text-rose-900' ?>">
                            <?= htmlspecialchars(format_rupiah((int)$row['nominal']), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars((string)($row['keterangan'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/publik_footer_wrapper.php'; ?>
