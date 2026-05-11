<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/helpers/format.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pageTitle = 'Detail kegiatan';

if ($id <= 0) {
    header('Location: ' . url('publik/kegiatan.php'));
    exit;
}

$stmt = db()->prepare('SELECT * FROM kegiatan WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$k = $stmt->fetch();

if (!$k) {
    http_response_code(404);
    echo 'Kegiatan tidak ditemukan.';
    exit;
}

$stmt = db()->prepare('SELECT * FROM dokumentasi WHERE kegiatan_id = ? ORDER BY uploaded_at DESC');
$stmt->execute([$id]);
$docs = $stmt->fetchAll();

$st = status_kegiatan_label((string)$k['status']);

require dirname(__DIR__) . '/includes/publik_header.php';
?>
<nav class="text-sm text-slate-500 mb-6">
    <a href="<?= htmlspecialchars(url('publik/kegiatan.php'), ENT_QUOTES, 'UTF-8') ?>" class="hover:text-indigo-600">&larr; Daftar kegiatan</a>
</nav>

<div class="flex flex-wrap items-start justify-between gap-4">
    <h1 class="text-3xl font-bold text-slate-900 max-w-3xl"><?= htmlspecialchars((string)$k['judul'], ENT_QUOTES, 'UTF-8') ?></h1>
    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold <?= $st['badge'] ?>"><?= htmlspecialchars($st['teks'], ENT_QUOTES, 'UTF-8') ?></span>
</div>

<dl class="mt-4 flex flex-wrap gap-6 text-sm text-slate-600">
    <div><dt class="font-semibold text-slate-900">Tanggal</dt><dd><?= htmlspecialchars((string)$k['tanggal'], ENT_QUOTES, 'UTF-8') ?></dd></div>
    <div><dt class="font-semibold text-slate-900">Lokasi</dt><dd><?= htmlspecialchars((string)($k['lokasi'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></dd></div>
</dl>

<div class="mt-8 prose prose-slate max-w-none">
    <h2 class="text-lg font-bold text-slate-900 mb-3">Deskripsi</h2>
    <div class="rounded-2xl border border-slate-200 bg-white p-6 text-slate-700 whitespace-pre-line"><?= htmlspecialchars((string)($k['deskripsi'] ?: 'Belum ada deskripsi detail.'), ENT_QUOTES, 'UTF-8') ?></div>
</div>

<section class="mt-12">
    <h2 class="text-xl font-bold text-slate-900 mb-4">Dokumentasi kegiatan</h2>
    <?php if (count($docs) === 0): ?>
        <p class="text-slate-500 text-sm">Belum ada foto atau video dokumentasi untuk kegiatan ini.</p>
    <?php else: ?>
        <div class="grid gap-8 sm:grid-cols-2">
            <?php foreach ($docs as $d): ?>
                <figure class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <?php if ($d['jenis'] === 'foto'): ?>
                        <a href="<?= htmlspecialchars(url_upload((string)$d['file_path']), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                            <img src="<?= htmlspecialchars(url_upload((string)$d['file_path']), ENT_QUOTES, 'UTF-8') ?>" alt="" class="w-full rounded-xl object-cover max-h-72 bg-slate-100">
                        </a>
                    <?php else: ?>
                        <video controls class="w-full rounded-xl max-h-80 bg-black">
                            <source src="<?= htmlspecialchars(url_upload((string)$d['file_path']), ENT_QUOTES, 'UTF-8') ?>">
                        </video>
                    <?php endif; ?>
                    <figcaption class="mt-3 text-sm text-slate-600">
                        <?= htmlspecialchars((string)($d['deskripsi'] ?: '(Tidak ada deskripsi dokumentasi.)'), ENT_QUOTES, 'UTF-8') ?>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require dirname(__DIR__) . '/includes/publik_footer.php'; ?>
