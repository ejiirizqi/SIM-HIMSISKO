<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/auth.php';

$pageTitle = 'Pengumuman';

$stmt = db()->query('SELECT judul, isi, tanggal_publish FROM pengumuman ORDER BY tanggal_publish DESC, id DESC');
$rows = $stmt->fetchAll();

require dirname(__DIR__) . '/includes/publik_header.php';
?>
<h1 class="text-3xl font-bold text-slate-900">Pengumuman</h1>
<div class="mt-2 flex items-center gap-3">
    <p class="text-slate-600">Informasi resmi bagi anggota HIMSISKO.</p>
    <div class="ml-auto flex gap-2">
        <a href="<?= htmlspecialchars(url('publik/pdf_laporan_pengumuman.php'), ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="inline-flex items-center rounded-xl bg-white border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">Ekspor PDF</a>
        <button type="button" onclick="window.print()" class="inline-flex items-center rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">Cetak</button>
    </div>
</div>

<div class="mt-4 space-y-5">
    <?php foreach ($rows as $row): ?>
        <article class="rounded-2xl border border-indigo-100 bg-white p-6 shadow-md shadow-indigo-50">
            <h2 class="text-xl font-black text-indigo-950"><?= htmlspecialchars((string)$row['judul'], ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="text-xs text-indigo-500 font-bold uppercase tracking-wide mt-1"><?= htmlspecialchars((string)$row['tanggal_publish'], ENT_QUOTES, 'UTF-8') ?></p>
            <div class="mt-5 text-sm text-slate-700 leading-relaxed whitespace-pre-wrap"><?= nl2br(htmlspecialchars((string)$row['isi'], ENT_QUOTES, 'UTF-8')) ?></div>
        </article>
    <?php endforeach; ?>
</div>
<?php if (count($rows) === 0): ?>
    <p class="text-slate-500 mt-6">Saat ini belum ada pengumuman aktif.</p>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/publik_footer_wrapper.php'; ?>
