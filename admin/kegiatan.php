<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';
require_once dirname(__DIR__) . '/helpers/format.php';
require_once dirname(__DIR__) . '/config/auth.php';

require_role('admin');

$pageTitle = 'Data kegiatan';
$activeNav = 'kegiatan';

$flash = '';
$flashType = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate($_POST['_csrf'] ?? null);
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = db()->prepare('SELECT file_path FROM dokumentasi WHERE kegiatan_id = ?');
            $stmt->execute([$id]);
            $paths = $stmt->fetchAll();
            require_once dirname(__DIR__) . '/helpers/upload_storage.php';
            foreach ($paths as $p) {
                unlink_upload($p['file_path'] ?? null);
            }
            $del = db()->prepare('DELETE FROM kegiatan WHERE id = ? LIMIT 1');
            $del->execute([$id]);
            log_activity((int)current_user()['id'], 'admin', (string)(current_user()['username'] ?? ''), 'Hapus kegiatan', 'ID: ' . $id);
            $flash = 'Kegiatan dan dokumentasinya dihapus.';
        }
    }
}

$stmt = db()->query('SELECT id, judul, tanggal, lokasi, status, created_at FROM kegiatan ORDER BY tanggal DESC, id DESC');
$rows = $stmt->fetchAll();
$csrf = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');

require dirname(__DIR__) . '/includes/admin_header.php';
?>
<div class="mb-8 flex flex-col sm:flex-row sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Data kegiatan</h1>
        <p class="text-slate-600 mt-1">Kelola judul, deskripsi, tanggal, lokasi, dan status</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="<?= htmlspecialchars(url('admin/kegiatan_form.php'), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex rounded-lg bg-slate-900 text-white text-sm font-semibold px-4 py-2.5 hover:bg-slate-800">
            Tambah kegiatan
        </a>
        <a href="<?= htmlspecialchars(url('admin/pdf_laporan_kegiatan.php'), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex rounded-lg border border-slate-300 bg-white text-sm font-semibold px-4 py-2.5 hover:bg-slate-50" target="_blank">Ekspor PDF</a>
        <button type="button" onclick="window.print()" class="inline-flex rounded-lg bg-slate-900 text-white text-sm font-semibold px-4 py-2.5 hover:bg-slate-800">Cetak</button>
    </div>
</div>

<?php if ($flash !== ''): ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $flashType === 'ok' ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900' ?>">
        <?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
            <tr class="bg-slate-50 text-left text-slate-600">
                <th class="px-4 py-3 font-medium">Judul</th>
                <th class="px-4 py-3 font-medium">Tanggal</th>
                <th class="px-4 py-3 font-medium">Lokasi</th>
                <th class="px-4 py-3 font-medium">Status</th>
                <th class="px-4 py-3 font-medium text-right">Aksi</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php if (count($rows) === 0): ?>
                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada data.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <?php $st = status_kegiatan_label((string)$row['status']); ?>
                    <tr class="hover:bg-slate-50/80 align-top">
                        <td class="px-4 py-3 font-medium text-slate-900"><?= htmlspecialchars((string)$row['judul'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars((string)$row['tanggal'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($row['lokasi'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold <?= htmlspecialchars($st['badge'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($st['teks'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                            <a href="<?= htmlspecialchars(url('admin/kegiatan_form.php?id=' . (int)$row['id']), ENT_QUOTES, 'UTF-8') ?>" class="text-slate-700 font-semibold hover:underline">Edit</a>
                            <a href="<?= htmlspecialchars(url('admin/dokumentasi.php?kegiatan_id=' . (int)$row['id']), ENT_QUOTES, 'UTF-8') ?>" class="text-indigo-600 font-semibold hover:underline">Dokumentasi</a>
                            <form method="post" class="inline" onsubmit="return confirm('Hapus kegiatan beserta dokumentasi?');">
                                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="hapus">
                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                <button type="submit" class="text-rose-600 font-semibold hover:underline">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
require dirname(__DIR__) . '/includes/admin_footer.php';
