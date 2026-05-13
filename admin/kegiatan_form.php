<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';
require_once dirname(__DIR__) . '/helpers/secure_upload.php';
require_once dirname(__DIR__) . '/helpers/upload_storage.php';
require_once dirname(__DIR__) . '/config/auth.php';

require_role('admin');

$pageTitle = 'Form Kegiatan';
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

$existingPhotos = [];
$flash = '';

// 1. AMBIL DATA JIKA MODE EDIT
if ($edit) {
    $stmt = db()->prepare('SELECT * FROM kegiatan WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $dbRow = $stmt->fetch();
    if (!$dbRow) {
        header('Location: ' . url('admin/kegiatan.php'));
        exit;
    }
    $row = array_merge($row, $dbRow);

    $stmtP = db()->prepare('SELECT file_path FROM dokumentasi WHERE kegiatan_id = ? AND jenis = "foto"');
    $stmtP->execute([$id]);
    $existingPhotos = $stmtP->fetchAll(PDO::FETCH_ASSOC);
}

// 2. LOGIKA SIMPAN DATA (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate($_POST['_csrf'] ?? null);
    
    $judul = trim((string)($_POST['judul'] ?? ''));
    $deskripsi = trim((string)($_POST['deskripsi'] ?? ''));
    $tanggal = (string)($_POST['tanggal'] ?? '');
    $lokasi = trim((string)($_POST['lokasi'] ?? ''));
    $status = (string)($_POST['status'] ?? '');
    
    // Cek input file
    $fotoField = $_FILES['foto_kegiatan'] ?? null;
    $hasNewFiles = ($fotoField && isset($fotoField['name'][0]) && $fotoField['name'][0] !== '');

    if ($judul === '') {
        $flash = 'Judul kegiatan tidak boleh kosong.';
    } elseif (!$edit && !$hasNewFiles) {
        $flash = 'Wajib mengunggah minimal 1 foto untuk kegiatan baru.';
    } else {
        try {
            $database = db();
            $database->beginTransaction(); // Mulai transaksi agar data konsisten

            if ($edit) {
                $stmt = $database->prepare('UPDATE kegiatan SET judul=?, deskripsi=?, tanggal=?, lokasi=?, status=? WHERE id=?');
                $stmt->execute([$judul, $deskripsi, $tanggal, $lokasi, $status, $id]);
                log_activity((int)current_user()['id'], 'admin', current_user()['username'], 'Ubah kegiatan', 'ID: ' . $id . ' - ' . $judul);
            } else {
                $stmt = $database->prepare('INSERT INTO kegiatan (judul, deskripsi, tanggal, lokasi, status) VALUES (?,?,?,?,?)');
                $stmt->execute([$judul, $deskripsi, $tanggal, $lokasi, $status]);
                $id = (int)$database->lastInsertId();
                log_activity((int)current_user()['id'], 'admin', current_user()['username'], 'Tambah kegiatan', 'Judul: ' . $judul);
            }

            // PROSES UPLOAD FOTO JIKA ADA
            if ($hasNewFiles) {
                // Normalisasi array upload
                $photoFiles = [];
                if (is_array($fotoField['name'])) {
                    foreach ($fotoField['name'] as $idx => $name) {
                        if ($fotoField['error'][$idx] === UPLOAD_ERR_OK) {
                            $photoFiles[] = [
                                'name' => $fotoField['name'][$idx],
                                'type' => $fotoField['type'][$idx],
                                'tmp_name' => $fotoField['tmp_name'][$idx],
                                'error' => $fotoField['error'][$idx],
                                'size' => $fotoField['size'][$idx]
                            ];
                        }
                    }
                }

                foreach ($photoFiles as $f) {
                    $res = save_upload_public($f, 'dok_kegiatan', ['jpg', 'jpeg', 'png', 'webp'], 8 * 1024 * 1024);
                    if ($res['ok']) {
                        $stmtD = $database->prepare('INSERT INTO dokumentasi (kegiatan_id, jenis, file_path) VALUES (?, "foto", ?)');
                        $stmtD->execute([$id, $res['relative']]);
                    }
                }
            }

            $database->commit(); // Simpan permanen
            header('Location: ' . url('admin/kegiatan.php?success=1'));
            exit;

        } catch (Exception $e) {
            if (isset($database)) $database->rollBack();
            $flash = 'Gagal menyimpan data: ' . $e->getMessage();
        }
    }
    
    // Fallback row jika gagal
    $row = ['judul' => $judul, 'deskripsi' => $deskripsi, 'tanggal' => $tanggal, 'lokasi' => $lokasi, 'status' => $status];
}

$csrf = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
require dirname(__DIR__) . '/includes/admin_header.php';
?>

<div class="max-w-6xl mx-auto pb-20">
    <div class="mb-8">
        <a href="<?= url('admin/kegiatan.php') ?>" class="text-sm font-bold text-slate-400 hover:text-indigo-600 transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            KEMBALI
        </a>
        <h1 class="text-4xl font-black text-slate-900 mt-2 tracking-tight">
            <?= $edit ? 'Edit <span class="text-indigo-600">Kegiatan</span>' : 'Kegiatan <span class="text-indigo-600">Baru</span>' ?>
        </h1>
    </div>

    <?php if ($flash !== ''): ?>
        <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-100 text-rose-700 font-bold text-sm flex items-center gap-3 shadow-sm">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/></svg>
            <?= htmlspecialchars($flash) ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <input type="hidden" name="_csrf" value="<?= $csrf ?>">

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-[2.5rem] border border-slate-200 p-8 shadow-sm">
                <div class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Judul Utama</label>
                        <input type="text" name="judul" required value="<?= htmlspecialchars((string)$row['judul']) ?>"
                               class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-indigo-500/10 transition-all font-bold text-slate-800" placeholder="Contoh: Rapat Kerja Organisasi">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Deskripsi Kegiatan</label>
                        <textarea name="deskripsi" rows="8" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 focus:ring-4 focus:ring-indigo-500/10 transition-all text-slate-600 leading-relaxed" placeholder="Tuliskan detail acara..."><?= htmlspecialchars((string)$row['deskripsi']) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] border border-slate-200 p-8 shadow-sm">
                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">Dokumentasi Foto</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4" id="preview-grid">
                    <?php foreach ($existingPhotos as $p): ?>
                        <div class="aspect-square rounded-2xl overflow-hidden border border-slate-100 shadow-sm relative group">
                            <img src="<?= url($p['file_path']) ?>" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <span class="text-[10px] text-white font-bold uppercase tracking-widest">Saved</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <label class="aspect-square rounded-2xl border-4 border-dashed border-slate-100 bg-slate-50 flex flex-col items-center justify-center cursor-pointer hover:bg-indigo-50 hover:border-indigo-200 transition-all group">
                        <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-slate-400 group-hover:text-indigo-600 shadow-sm transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <input type="file" name="foto_kegiatan[]" multiple accept="image/*" class="hidden" id="photo-input">
                    </label>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-slate-900 rounded-[2.5rem] p-8 shadow-2xl sticky top-8">
                <div class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-indigo-400 mb-2">Tanggal</label>
                        <input type="date" name="tanggal" required value="<?= htmlspecialchars((string)$row['tanggal']) ?>"
                               class="w-full bg-white/10 border-none rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-indigo-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-indigo-400 mb-2">Lokasi</label>
                        <input type="text" name="lokasi" value="<?= htmlspecialchars((string)$row['lokasi']) ?>"
                               class="w-full bg-white/10 border-none rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-indigo-500 transition-all" placeholder="Nama Ruangan/Tempat">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-indigo-400 mb-2">Status</label>
                        <select name="status" class="w-full bg-white/10 border-none rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-indigo-500 transition-all appearance-none cursor-pointer">
                            <?php foreach (['rencana' => 'Rencana', 'berlangsung' => 'Berlangsung', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= $row['status'] === $v ? 'selected' : '' ?> class="text-slate-900"><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <hr class="border-white/10 my-4">
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-black py-4 rounded-2xl shadow-lg shadow-indigo-600/30 transition-all transform active:scale-95">
                        SIMPAN DATA
                    </button>
                    <a href="<?= url('admin/kegiatan.php') ?>" class="block text-center text-slate-500 hover:text-white text-xs font-bold transition-colors">BATALKAN</a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Logic Preview Gambar Instan
document.getElementById('photo-input').addEventListener('change', function(e) {
    const grid = document.getElementById('preview-grid');
    const files = e.target.files;

    for (let file of files) {
        if (!file.type.startsWith('image/')) continue;
        const reader = new FileReader();
        reader.onload = function(event) {
            const wrapper = document.createElement('div');
            wrapper.className = "aspect-square rounded-2xl overflow-hidden border border-indigo-200 shadow-sm animate-pulse";
            wrapper.innerHTML = `<img src="${event.target.result}" class="w-full h-full object-cover">`;
            grid.insertBefore(wrapper, grid.lastElementChild);
            setTimeout(() => wrapper.classList.remove('animate-pulse'), 1000);
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php require dirname(__DIR__) . '/includes/admin_footer.php'; ?>