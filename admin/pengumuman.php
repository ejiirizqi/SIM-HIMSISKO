<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';
require_once dirname(__DIR__) . '/config/auth.php';

require_role('admin');

$pageTitle = 'Pengumuman';
$activeNav = 'pengumuman';

$flash = '';

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$row = ['judul' => '', 'isi' => '', 'tanggal_publish' => date('Y-m-d')];

if ($editId > 0) {
    $st = db()->prepare('SELECT id, judul, isi, tanggal_publish FROM pengumuman WHERE id=? LIMIT 1');
    $st->execute([$editId]);
    $rw = $st->fetch();
    if ($rw) {
        $row = $rw;
    } else {
        $editId = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate($_POST['_csrf'] ?? null);
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'simpan') {
        $judul = trim((string)($_POST['judul'] ?? ''));
        $isi = trim((string)($_POST['isi'] ?? ''));
        $tgl = (string)($_POST['tanggal_publish'] ?? '');
        $tid = (int)($_POST['edit_id_hidden'] ?? 0);

        if ($judul === '' || $isi === '') {
            $flash = 'Judul dan isi wajib diisi.';
        } elseif (!$tgl || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) {
            $flash = 'Tanggal tidak valid.';
        } elseif ($tid > 0) {
            $stmt = db()->prepare('UPDATE pengumuman SET judul=?, isi=?, tanggal_publish=? WHERE id=? LIMIT 1');
            $stmt->execute([$judul, $isi, $tgl, $tid]);
            log_activity((int)current_user()['id'], 'admin', (string)(current_user()['username'] ?? ''), 'Ubah pengumuman', 'ID: ' . $tid . ' Judul: ' . $judul);
            header('Location: ' . url('admin/pengumuman.php'));
            exit;
        } else {
            $stmt = db()->prepare('INSERT INTO pengumuman (judul, isi, tanggal_publish) VALUES (?,?,?)');
            $stmt->execute([$judul, $isi, $tgl]);
            log_activity((int)current_user()['id'], 'admin', (string)(current_user()['username'] ?? ''), 'Tambah pengumuman', 'Judul: ' . $judul);
            header('Location: ' . url('admin/pengumuman.php'));
            exit;
        }
    }

    if ($action === 'hapus') {
        $hid = (int)($_POST['id_hapus'] ?? 0);
        if ($hid > 0) {
            db()->prepare('DELETE FROM pengumuman WHERE id=? LIMIT 1')->execute([$hid]);
            log_activity((int)current_user()['id'], 'admin', (string)(current_user()['username'] ?? ''), 'Hapus pengumuman', 'ID: ' . $hid);
            header('Location: ' . url('admin/pengumuman.php'));
            exit;
        }
    }
}

$list = db()->query('SELECT id, judul, tanggal_publish, created_at FROM pengumuman ORDER BY tanggal_publish DESC, id DESC')->fetchAll();
$csrf = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');

require dirname(__DIR__) . '/includes/admin_header.php';
?>
<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900">Kelola pengumuman</h1>
    <p class="text-slate-600 mt-1">buat, ubah, dan hapus pengumuman untuk mahasiswa</p>
</div>

<?php if ($flash !== ''): ?>
    <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="grid gap-8 lg:grid-cols-5">
    <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-6 shadow-sm h-fit sticky top-6">
        <h2 class="text-lg font-semibold"><?= $editId ? 'Edit pengumuman' : 'Pengumuman baru' ?></h2>
        <form method="post" class="mt-4 space-y-4">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="simpan">
            <input type="hidden" name="edit_id_hidden" value="<?= $editId ?>">
            <div>
                <label class="block text-sm font-medium text-slate-700">Judul</label>
                <input name="judul" required value="<?= htmlspecialchars((string)$row['judul'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-lg border px-3 py-2 border-slate-300">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Isi</label>
                <textarea name="isi" rows="6" required class="mt-1 w-full rounded-lg border px-3 py-2 border-slate-300"><?= htmlspecialchars((string)$row['isi'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Tanggal tayang</label>
                <input type="date" name="tanggal_publish" required value="<?= htmlspecialchars((string)$row['tanggal_publish'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-lg border px-3 py-2 border-slate-300">
            </div>
            <div class="flex gap-2">
                <button class="rounded-lg bg-slate-900 text-white font-semibold px-5 py-2.5">Simpan</button>
                <?php if ($editId): ?>
                    <a href="<?= htmlspecialchars(url('admin/pengumuman.php'), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg border px-5 py-2.5 border-slate-300">Batal</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <div class="lg:col-span-3 space-y-3">
        <?php foreach ($list as $p): ?>
            <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-slate-900"><?= htmlspecialchars((string)$p['judul'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string)$p['tanggal_publish'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="text-right whitespace-nowrap">
                        <a href="<?= htmlspecialchars(url('admin/pengumuman.php?edit=' . (int)$p['id']), ENT_QUOTES, 'UTF-8') ?>" class="text-indigo-600 text-sm font-semibold">Edit</a>
                        <form method="post" class="inline ml-3" onsubmit="return confirm('Hapus?');">
                            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="hapus">
                            <input type="hidden" name="id_hapus" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="text-rose-600 text-sm font-semibold">Hapus</button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (count($list) === 0): ?>
            <p class="text-slate-500 text-sm px-2">Belum ada pengumuman.</p>
        <?php endif; ?>
    </div>
</div>
<?php
require dirname(__DIR__) . '/includes/admin_footer.php';
