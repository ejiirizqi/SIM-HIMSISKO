<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/helpers/format.php';

$pageTitle = 'Daftar kegiatan';

$stmt = db()->query(
    'SELECT id, judul, tanggal, lokasi, status, LEFT(deskripsi,160) AS ringkas FROM kegiatan ORDER BY tanggal DESC, id DESC'
);
$rows = $stmt->fetchAll();

require dirname(__DIR__) . '/includes/publik_header.php';
?>
<h1 class="text-3xl font-bold text-slate-900">Daftar kegiatan</h1>
<p class="text-slate-600 mt-2">Lihat kegiatan organisasi bersama dokumentasi di halaman detail.</p>

<div class="mt-4 grid gap-4 sm:grid-cols-2">
    <?php foreach ($rows as $r): ?>
        <?php $st = status_kegiatan_label((string)$r['status']); ?>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow transition">
            <div class="flex items-start justify-between gap-3">
                <h2 class="font-semibold text-slate-900 leading-snug"><?= htmlspecialchars((string)$r['judul'], ENT_QUOTES, 'UTF-8') ?></h2>
                <span class="shrink-0 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold <?= $st['badge'] ?>"><?= htmlspecialchars($st['teks'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <dl class="mt-3 grid gap-1 text-xs text-slate-600">
                <div><dt class="inline font-medium text-slate-500">Tanggal:&nbsp;</dt><dd class="inline"><?= htmlspecialchars((string)$r['tanggal'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                <div><dt class="inline font-medium text-slate-500">Lokasi:&nbsp;</dt><dd class="inline"><?= htmlspecialchars((string)($r['lokasi'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></dd></div>
            </dl>
            <p class="text-sm text-slate-600 mt-3"><?php
                $snippet = trim((string)($r['ringkas'] ?? ''));
                if (mb_strlen($snippet) > 180) {
                    $snippet = mb_substr($snippet, 0, 177) . '…';
                }
                echo htmlspecialchars($snippet ?: '—', ENT_QUOTES, 'UTF-8');
                ?></p>
            <div class="mt-4 flex justify-between items-center">
                <a href="<?= htmlspecialchars(url('publik/kegiatan_detail.php?id=' . (int)$r['id']), ENT_QUOTES, 'UTF-8') ?>" class="text-sm font-bold text-indigo-600 hover:underline">
                    Detail & dokumentasi →
                </a>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?php if (count($rows) === 0): ?>
    <p class="text-slate-500 mt-6">Belum ada kegiatan yang dipublikasi.</p>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/publik_footer_wrapper.php'; ?>
