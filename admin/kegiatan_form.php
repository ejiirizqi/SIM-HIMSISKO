<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';
require_once dirname(__DIR__) . '/helpers/secure_upload.php';
require_once dirname(__DIR__) . '/helpers/upload_storage.php';
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

    // Upload foto dokumentasi (opsional saat edit, wajib saat tambah)
    // Minimal 1 foto tiap kegiatan.
    $fotoField = $_FILES['foto_kegiatan'] ?? null;
    $fotoCount = 0;
    if ($fotoField && is_array($fotoField['name'] ?? null)) {
        $fotoCount = count((array)($fotoField['name'] ?? []));
    } elseif ($fotoField && isset($fotoField['name']) && $fotoField['name'] !== '') {
        $fotoCount = 1;
    }


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

        // Simpan foto dokumentasi minimal 1 saat tambah.
        $needPhoto = !$edit;
        if ($needPhoto && $fotoCount < 1) {
            $flash = 'Minimal 1 foto kegiatan wajib diunggah.';
        } else {
            // normalisasi array upload (support multiple)
            $photoFiles = [];
            if ($fotoField && is_array($fotoField['name'] ?? null)) {
                $names = (array)$fotoField['name'];
                foreach ($names as $idx => $n) {
                    $n = (string)$n;
                    if (trim($n) === '') continue;
                    $photoFiles[] = [
                        'name' => (string)($fotoField['name'][$idx] ?? ''),
                        'type' => (string)($fotoField['type'][$idx] ?? ''),
                        'tmp_name' => (string)($fotoField['tmp_name'][$idx] ?? ''),
                        'error' => (int)($fotoField['error'][$idx] ?? UPLOAD_ERR_NO_FILE),
                        'size' => (int)($fotoField['size'][$idx] ?? 0),
                    ];
                }
            }

            // Kalau input tunggal, jadikan array 1 item
            if ($photoFiles === [] && $fotoField && isset($fotoField['name']) && (string)$fotoField['name'] !== '') {
                $photoFiles[] = $fotoField;
            }

            $photoExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $maxPhoto = 8 * 1024 * 1024;

            foreach ($photoFiles as $f) {
                // skip no-file
                if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;

                $res = save_upload_public($f, 'dok_kegiatan', $photoExt, $maxPhoto);
                if (!($res['ok'] ?? false)) {
                    $flash = $res['error'] ?? 'Gagal upload foto dokumentasi.';
                    $flashType = 'err';
                    break;
                }

                $rel = (string)($res['relative'] ?? '');
                $stmtD = db()->prepare(
                    'INSERT INTO dokumentasi (kegiatan_id, jenis, file_path, deskripsi) VALUES (?,?,?,?)'
                );
                $stmtD->execute([
                    $id,
                    'foto',
                    $rel,
                    $deskripsi === '' ? null : $deskripsi,
                ]);
            }
        }

        if ($flash === '') {
            header('Location: ' . url('admin/kegiatan.php'));
            exit;
        }

        // fallback row utk render ulang saat ada error upload
        $row = [
            'judul' => $judul,
            'deskripsi' => $deskripsi,
            'tanggal' => $tanggal,
            'lokasi' => $lokasi,
            'status' => in_array($status, $allowed, true) ? $status : 'rencana',
        ];
        // fallback row utk render ulang saat ada error upload/validasi
        $row = [
            'judul' => $judul,
            'deskripsi' => $deskripsi,
            'tanggal' => $tanggal,
            'lokasi' => $lokasi,
            'status' => in_array($status, $allowed, true) ? $status : 'rencana',
        ];
    }

    // Render ulang form saat ada error
    // (row sudah diisi)
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
    <form method="post" class="space-y-4" enctype="multipart/form-data">
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

        <div>
            <label class="block text-sm font-medium text-slate-700">Upload foto dokumentasi (opsional saat edit, minimal 1 saat tambah)</label>
            <input type="file" name="foto_kegiatan[]" accept="image/*" multiple class="mt-1 w-full text-sm">
            <p class="text-xs text-slate-500 mt-1">Format: JPG/JPEG/PNG/WEBP/GIF (max ~8MB per file)</p>
        </div>

        <div class="pt-2 flex gap-3">
            <button type="submit" class="rounded-lg bg-slate-900 text-white font-semibold px-5 py-2.5 hover:bg-slate-800">Simpan</button>
            <a href="<?= htmlspecialchars(url('admin/kegiatan.php'), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg border border-slate-300 px-5 py-2.5 text-slate-700 font-medium hover:bg-slate-50">Batal</a>
        </div>
    </form>
</div>
<?php
require dirname(__DIR__) . '/includes/admin_footer.php';
