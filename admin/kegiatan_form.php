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

<div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 pb-24">
    <div class="mb-8">
        <a href="<?= url('admin/kegiatan.php') ?>" class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400 hover:text-indigo-600 transition-colors group">
            <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Kembali ke Daftar
        </a>
        <h1 class="text-3xl font-black text-slate-900 mt-3 tracking-tight sm:text-4xl">
            <?= $edit ? 'Edit Rincian <span class="text-indigo-600">Kegiatan</span>' : 'Buat Agenda <span class="text-indigo-600">Baru</span>' ?>
        </h1>
        <p class="mt-1 text-sm text-slate-500">Kelola informasi publikasi, linimasa waktu, lokasi, dan dokumentasi foto album kegiatan di sini.</p>
    </div>

    <?php if ($flash !== ''): ?>
        <div class="mb-8 p-4 rounded-2xl bg-rose-50/80 border border-rose-200/60 text-rose-800 font-medium text-sm flex items-start gap-3 shadow-sm backdrop-blur-sm">
            <svg class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <div>
                <span class="font-bold text-rose-900">Periksa Kembali:</span>
                <p class="mt-0.5 text-rose-700/90"><?= htmlspecialchars($flash) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        <input type="hidden" name="_csrf" value="<?= $csrf ?>">

        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm space-y-6">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Judul Utama Kegiatan</label>
                    <input type="text" name="judul" required value="<?= htmlspecialchars((string)$row['judul']) ?>"
                           class="w-full bg-slate-50/70 border border-slate-200 rounded-xl px-5 py-3.5 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all font-semibold text-slate-800 placeholder:text-slate-400 text-base" 
                           placeholder="Contoh: Rapat Kerja Internal Pengurus">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Deskripsi Lengkap Kegiatan</label>
                    <textarea name="deskripsi" rows="8" 
                              class="w-full bg-slate-50/70 border border-slate-200 rounded-xl px-5 py-3.5 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all text-slate-700 placeholder:text-slate-400 leading-relaxed text-sm" 
                              placeholder="Tuliskan detail agenda, susunan kepanitiaan, atau poin pembahasan acara secara komprehensif..."><?= htmlspecialchars((string)$row['deskripsi']) ?></textarea>
                </div>
            </div>

            <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500">Galeri Dokumentasi Foto</label>
                    <p class="text-xs text-slate-400 mt-0.5">Format yang didukung: JPG, PNG, WEBP (Maks. 8MB per file).</p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4" id="preview-grid">
                    <?php foreach ($existingPhotos as $p): ?>
                        <div class="aspect-square rounded-2xl overflow-hidden border border-slate-100 shadow-sm relative group bg-slate-50">
                            <img src="<?= url($p['file_path']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            <div class="absolute inset-0 bg-slate-900/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-[1px]">
                                <span class="text-[10px] text-white font-bold uppercase tracking-widest bg-emerald-600/90 px-2 py-1 rounded-md shadow-sm">Tersimpan</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <label class="aspect-square rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50/50 flex flex-col items-center justify-center cursor-pointer hover:bg-indigo-50/50 hover:border-indigo-400 transition-all group relative">
                        <div class="flex flex-col items-center justify-center p-4 text-center">
                            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-slate-400 group-hover:text-indigo-600 group-hover:scale-110 shadow-sm border border-slate-100 transition-all mb-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <span class="text-xs font-bold text-slate-500 group-hover:text-indigo-600 transition-colors">Tambah Foto</span>
                        </div>
                        <input type="file" name="foto_kegiatan[]" multiple accept="image/*" class="hidden" id="photo-input">
                    </label>
                </div>
            </div>
        </div>

        <div class="space-y-6 lg:sticky lg:top-6">
            <div class="bg-slate-900 rounded-3xl p-6 sm:p-8 shadow-xl border border-slate-800">
                <h3 class="text-white font-extrabold text-lg mb-6 tracking-tight flex items-center gap-2 border-b border-white/10 pb-4">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Pengaturan Batasan
                </h3>
                
                <div class="space-y-5">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-widest text-indigo-300/80 mb-2">Tanggal Pelaksanaan</label>
                        <input type="date" name="tanggal" required value="<?= htmlspecialchars((string)$row['tanggal']) ?>"
                               class="w-full bg-white/[0.07] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium text-sm color-scheme-dark">
                    </div>
                    
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-widest text-indigo-300/80 mb-2">Lokasi / Tempat</label>
                        <input type="text" name="lokasi" value="<?= htmlspecialchars((string)$row['lokasi']) ?>"
                               class="w-full bg-white/[0.07] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium text-sm placeholder:text-slate-500" 
                               placeholder="Misal: Aula Lantai 3 / Zoom">
                    </div>
                    
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-widest text-indigo-300/80 mb-2">Status Publikasi</label>
                        <div class="relative">
                            <select name="status" class="w-full bg-white/[0.07] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium text-sm appearance-none cursor-pointer">
                                <?php foreach (['rencana' => '📆 Rencana', 'berlangsung' => '🔥 Berlangsung', 'selesai' => '✅ Selesai', 'dibatalkan' => '❌ Dibatalkan'] as $v => $l): ?>
                                    <option value="<?= $v ?>" <?= $row['status'] === $v ? 'selected' : '' ?> class="text-slate-900 font-medium"><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-white/50">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pt-4 border-t border-white/10 space-y-3">
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg shadow-indigo-600/20 transition-all active:scale-[0.98] text-sm tracking-wide uppercase">
                            Simpan Perubahan
                        </button>
                        <a href="<?= url('admin/kegiatan.php') ?>" class="block text-center text-slate-400 hover:text-white text-xs font-bold py-2 transition-colors uppercase tracking-wider">
                            Batalkan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
  .color-scheme-dark { color-scheme: dark; }
</style>

<script>
// Logic Preview Gambar Instan dengan Animasi Lembut
document.getElementById('photo-input').addEventListener('change', function(e) {
    const grid = document.getElementById('preview-grid');
    const files = e.target.files;

    for (let file of files) {
        if (!file.type.startsWith('image/')) continue;
        const reader = new FileReader();
        reader.onload = function(event) {
            const wrapper = document.createElement('div');
            wrapper.className = "aspect-square rounded-2xl overflow-hidden border-2 border-indigo-300 shadow-md transform scale-95 opacity-0 transition-all duration-300 relative bg-slate-50";
            wrapper.innerHTML = `
                <img src="${event.target.result}" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-indigo-900/20 flex items-center justify-center">
                    <span class="text-[9px] text-white font-extrabold uppercase tracking-widest bg-indigo-600 px-1.5 py-0.5 rounded shadow-sm">Baru</span>
                </div>
            `;
            grid.insertBefore(wrapper, grid.lastElementChild);
            
            // Trigger visual enter animation
            requestAnimationFrame(() => {
                wrapper.classList.remove('scale-95', 'opacity-0');
                wrapper.classList.add('scale-100', 'opacity-100');
            });
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php require dirname(__DIR__) . '/includes/admin_footer.php'; ?>