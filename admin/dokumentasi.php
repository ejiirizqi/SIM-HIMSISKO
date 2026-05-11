<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';
require_once dirname(__DIR__) . '/helpers/secure_upload.php';
require_once dirname(__DIR__) . '/helpers/upload_storage.php';
require_once dirname(__DIR__) . '/config/auth.php';

require_role('admin');

$kegiatanId = isset($_GET['kegiatan_id']) ? (int)$_GET['kegiatan_id'] : 0;

if ($kegiatanId <= 0) {
    header('Location: ' . url('admin/kegiatan.php'));
    exit;
}

$stmt = db()->prepare('SELECT id, judul FROM kegiatan WHERE id = ? LIMIT 1');
$stmt->execute([$kegiatanId]);
$keg = $stmt->fetch();

if (!$keg) {
    http_response_code(404);
    echo 'Kegiatan tidak ditemukan.';
    exit;
}

$pageTitle = 'Dokumentasi: ' . (string)$keg['judul'];
$activeNav = 'kegiatan';

$flash = '';
$flashType = 'ok';

$maxPhoto = 8 * 1024 * 1024;
$maxVideo = 50 * 1024 * 1024;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate($_POST['_csrf'] ?? null);
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'upload') {
        $jenis = (string)($_POST['jenis'] ?? '');
        $deskripsi = trim((string)($_POST['deskripsi'] ?? ''));
        if (!in_array($jenis, ['foto', 'video'], true)) {
            $flash = 'Pilih foto atau video.';
            $flashType = 'err';
        } elseif (!isset($_FILES['berkas']) || (int)$_FILES['berkas']['error'] === UPLOAD_ERR_NO_FILE) {
            $flash = 'Pilih berkas dokumentasi.';
            $flashType = 'err';
        } else {
            $photoExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $videoExt = ['mp4', 'webm', 'mov'];
            $ext = $jenis === 'foto' ? $photoExt : $videoExt;
            $maxB = $jenis === 'foto' ? $maxPhoto : $maxVideo;
            $res = save_upload_public($_FILES['berkas'], 'dok_kegiatan', $ext, $maxB);
            if (!$res['ok']) {
                $flash = $res['error'] ?? 'Gagal upload.';
                $flashType = 'err';
            } else {
                $rel = $res['relative'] ?? '';
                $stmt = db()->prepare(
                    'INSERT INTO dokumentasi (kegiatan_id, jenis, file_path, deskripsi) VALUES (?,?,?,?)'
                );
                $stmt->execute([
                    $kegiatanId,
                    $jenis,
                    $rel,
                    $deskripsi === '' ? null : $deskripsi,
                ]);
                $flash = 'Dokumentasi berhasil ditambahkan.';
            }
        }
    } elseif ($action === 'hapus_dok') {
        $did = (int)($_POST['dok_id'] ?? 0);
        if ($did > 0) {
            $st = db()->prepare('SELECT file_path FROM dokumentasi WHERE id = ? AND kegiatan_id = ? LIMIT 1');
            $st->execute([$did, $kegiatanId]);
            $d = $st->fetch();
            if ($d) {
                unlink_upload($d['file_path']);
                db()->prepare('DELETE FROM dokumentasi WHERE id = ?')->execute([$did]);
                $flash = 'Dokumentasi dihapus.';
            }
        }
    }
}

$listStmt = db()->prepare(
    'SELECT id, jenis, file_path, deskripsi, uploaded_at FROM dokumentasi WHERE kegiatan_id = ? ORDER BY uploaded_at DESC'
);
$listStmt->execute([$kegiatanId]);
$docs = $listStmt->fetchAll();

$csrf = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');

require dirname(__DIR__) . '/includes/admin_header.php';
$urlKeg = url('admin/dokumentasi.php?kegiatan_id=' . $kegiatanId);
?>
<div class="mb-8">
    <a href="<?= htmlspecialchars(url('admin/kegiatan.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-sm text-slate-600 hover:text-slate-900">&larr; Kembali ke daftar</a>
    <h1 class="text-2xl font-bold text-slate-900 mt-3">Dokumentasi kegiatan</h1>
    <p class="text-slate-600 mt-1"><?= htmlspecialchars((string)$keg['judul'], ENT_QUOTES, 'UTF-8') ?></p>
</div>

<?php if ($flash !== ''): ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= ($flashType === 'ok')
        ? 'bg-emerald-50 border-emerald-200 text-emerald-900'
        : 'bg-rose-50 border-rose-200 text-rose-900' ?>"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="grid gap-8 lg:grid-cols-5">
    <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Unggah dokumentasi</h2>
        <p class="text-xs text-slate-500 mt-1 mb-4">Foto: JPG/PNG/GIF/WebP (max 8MB). Video: MP4/WebM/MOV (max 50MB).</p>
        <form method="post" action="<?= htmlspecialchars($urlKeg, ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="upload">
            <div>
                <label class="block text-sm font-medium text-slate-700">Jenis media</label>
                <select name="jenis" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="foto">Foto kegiatan</option>
                    <option value="video">Video kegiatan</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">File</label>
                <input type="file" name="berkas" required class="mt-1 w-full text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Deskripsi dokumentasi</label>
                <textarea name="deskripsi" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Keterangan singkat"></textarea>
            </div>
            <button type="submit" class="w-full rounded-lg bg-slate-900 text-white font-semibold py-2.5 hover:bg-slate-800">Unggah</button>
        </form>
    </div>

    <div class="lg:col-span-3 rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Galeri</h2>
        </div>
        <div class="p-6">
            <?php if (count($docs) === 0): ?>
                <p class="text-slate-500 text-sm">Belum ada dokumentasi.</p>
            <?php else: ?>
                <div class="grid gap-6 sm:grid-cols-2">
                    <?php foreach ($docs as $d): ?>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                            <?php if ($d['jenis'] === 'foto'): ?>
                                <a href="<?= htmlspecialchars(url_upload((string)$d['file_path']), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                                    <img src="<?= htmlspecialchars(url_upload((string)$d['file_path']), ENT_QUOTES, 'UTF-8') ?>"
                                         alt="" class="rounded-lg max-h-48 w-full object-cover bg-white">
                                </a>
                            <?php else: ?>
                                <video controls class="w-full rounded-lg max-h-48 bg-black">
                                    <source src="<?= htmlspecialchars(url_upload((string)$d['file_path']), ENT_QUOTES, 'UTF-8') ?>">
                                </video>
                            <?php endif; ?>
                            <p class="text-xs mt-2 text-slate-500"><?= htmlspecialchars((string)$d['deskripsi'] ?: '(tanpa deskripsi)', ENT_QUOTES, 'UTF-8') ?></p>
                            <form method="post" class="mt-2" onsubmit="return confirm('Hapus item ini?');">
                                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="hapus_dok">
                                <input type="hidden" name="dok_id" value="<?= (int)$d['id'] ?>">
                                <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-800">Hapus</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
require dirname(__DIR__) . '/includes/admin_footer.php';
