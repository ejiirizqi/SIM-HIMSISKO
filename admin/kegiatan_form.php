<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';
require_once dirname(__DIR__) . '/config/auth.php';

require_role('admin');

$pageTitle = 'Form kegiatan';
$activeNav = 'kegiatan';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$edit = $id > 0;
$row = [
    'judul' => '',
    'deskripsi' => '',
    'tanggal' => date('Y-m-d'),
    'lokasi' => '',
    'status' => 'rencana',
];

if ($edit) {
    $stmt = db()->prepare('SELECT id, judul, deskripsi, tanggal, lokasi, status FROM kegiatan WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $dbRow = $stmt->fetch();
    if (!$dbRow) {
        http_response_code(404);
        echo 'Kegiatan tidak ditemukan.';
        exit;
    }
    $row = array_merge($row, $dbRow);
}

$flash = '';
$flashType = 'err';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate($_POST['_csrf'] ?? null);
    $judul = trim((string)($_POST['judul'] ?? ''));
    $deskripsi = trim((string)($_POST['deskripsi'] ?? ''));
    $tanggal = (string)($_POST['tanggal'] ?? '');
    $lokasi = trim((string)($_POST['lokasi'] ?? ''));
    $status = (string)($_POST['status'] ?? '');
    $allowed = ['rencana', 'berlangsung', 'selesai', 'dibatalkan'];

    if ($judul === '') {
        $flash = 'Judul wajib diisi.';
    } elseif ($tanggal === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        $flash = 'Tanggal tidak valid.';
    } elseif (!in_array($status, $allowed, true)) {
        $flash = 'Status tidak valid.';
    } else {
        if ($edit) {
            $stmt = db()->prepare(
                'UPDATE kegiatan SET judul=?, deskripsi=?, tanggal=?, lokasi=?, status=? WHERE id=? LIMIT 1'
            );
            $stmt->execute([
                $judul,
                $deskripsi === '' ? null : $deskripsi,
                $tanggal,
                $lokasi === '' ? null : $lokasi,
                $status,
                $id,
            ]);
            log_activity((int)current_user()['id'], 'admin', (string)(current_user()['username'] ?? ''), 'Ubah kegiatan', 'ID: ' . $id . ' Judul: ' . $judul);
        } else {
            $stmt = db()->prepare(
                'INSERT INTO kegiatan (judul, deskripsi, tanggal, lokasi, status) VALUES (?,?,?,?,?)'
            );
            $stmt->execute([
                $judul,
                $deskripsi === '' ? null : $deskripsi,
                $tanggal,
                $lokasi === '' ? null : $lokasi,
                $status,
            ]);
            $id = (int)db()->lastInsertId();
            log_activity((int)current_user()['id'], 'admin', (string)(current_user()['username'] ?? ''), 'Tambah kegiatan', 'ID: ' . $id . ' Judul: ' . $judul);
        }
        header('Location: ' . url('admin/kegiatan.php'));
        exit;
    }
    $row = [
        'judul' => $judul,
        'deskripsi' => $deskripsi,
        'tanggal' => $tanggal,
        'lokasi' => $lokasi,
        'status' => in_array($status, $allowed, true) ? $status : 'rencana',
    ];
}

$csrf = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');

require dirname(__DIR__) . '/includes/admin_header.php';
?>
<div class="mb-8">
    <a href="<?= htmlspecialchars(url('admin/kegiatan.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-sm text-slate-600 hover:text-slate-900">&larr; Kembali</a>
    <h1 class="text-2xl font-bold text-slate-900 mt-3"><?= $edit ? 'Edit kegiatan' : 'Tambah kegiatan' ?></h1>
</div>

<?php if ($flash !== ''): ?>
    <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="max-w-2xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="post" class="space-y-4">
        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
        <div>
            <label class="block text-sm font-medium text-slate-700">Judul kegiatan</label>
            <input type="text" name="judul" required value="<?= htmlspecialchars((string)$row['judul'], ENT_QUOTES, 'UTF-8') ?>"
                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Deskripsi</label>
            <textarea name="deskripsi" rows="4" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none"><?= htmlspecialchars((string)($row['deskripsi'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700">Tanggal</label>
                <input type="date" name="tanggal" required value="<?= htmlspecialchars((string)$row['tanggal'], ENT_QUOTES, 'UTF-8') ?>"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Status kegiatan</label>
                <select name="status" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none">
                    <?php foreach (['rencana' => 'Rencana', 'berlangsung' => 'Berlangsung', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'] as $val => $lab): ?>
                        <option value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>" <?= ($row['status'] ?? '') === $val ? 'selected' : '' ?>><?= htmlspecialchars($lab, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Lokasi</label>
            <input type="text" name="lokasi" value="<?= htmlspecialchars((string)($row['lokasi'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:ring-2 focus:ring-slate-900 outline-none" placeholder="Contoh: Aula kampus">
        </div>
        <div class="pt-2 flex gap-3">
            <button type="submit" class="rounded-lg bg-slate-900 text-white font-semibold px-5 py-2.5 hover:bg-slate-800">Simpan</button>
            <a href="<?= htmlspecialchars(url('admin/kegiatan.php'), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg border border-slate-300 px-5 py-2.5 text-slate-700 font-medium hover:bg-slate-50">Batal</a>
        </div>
    </form>
</div>
<?php
require dirname(__DIR__) . '/includes/admin_footer.php';
