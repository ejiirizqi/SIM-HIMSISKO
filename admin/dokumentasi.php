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
$maxDocument = 20 * 1024 * 1024;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate($_POST['_csrf'] ?? null);
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'upload') {
        $jenis = (string)($_POST['jenis'] ?? '');
        $deskripsi = trim((string)($_POST['deskripsi'] ?? ''));
        if (!in_array($jenis, ['foto', 'video', 'proposal', 'laporan'], true)) {
            $flash = 'Pilih jenis dokumentasi yang benar.';
            $flashType = 'err';
        } elseif (!isset($_FILES['berkas']) || (int)$_FILES['berkas']['error'] === UPLOAD_ERR_NO_FILE) {
            $flash = 'Pilih berkas dokumentasi.';
            $flashType = 'err';
        } else {
            $photoExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $videoExt = ['mp4', 'webm', 'mov'];
            $docExt = ['jpg', 'jpeg', 'pdf', 'docx'];
            if ($jenis === 'foto') {
                $ext = $photoExt;
                $maxB = $maxPhoto;
            } elseif ($jenis === 'video') {
                $ext = $videoExt;
                $maxB = $maxVideo;
            } else {
                $ext = $docExt;
                $maxB = $maxDocument;
            }
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

<div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 pb-24">
    <div class="mb-8">
        <a href="<?= htmlspecialchars(url('admin/kegiatan.php'), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400 hover:text-indigo-600 transition-colors group">
            <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path d="M15 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Kembali ke Kegiatan
        </a>
        <h1 class="text-3xl font-black text-slate-900 mt-3 tracking-tight sm:text-4xl">
            Arsip & <span class="text-indigo-600">Dokumentasi</span>
        </h1>
        <p class="mt-1 text-sm text-slate-500 font-medium flex items-center gap-1.5">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
            <?= htmlspecialchars((string)$keg['judul'], ENT_QUOTES, 'UTF-8') ?>
        </p>
    </div>

    <?php if ($flash !== ''): ?>
        <div class="mb-8 p-4 rounded-2xl border text-sm flex items-start gap-3 shadow-sm backdrop-blur-sm <?= ($flashType === 'ok') ? 'bg-emerald-50/80 border-emerald-200 text-emerald-900' : 'bg-rose-50/80 border-rose-200 text-rose-900' ?>">
            <?php if ($flashType === 'ok'): ?>
                <svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <?php else: ?>
                <svg class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <?php endif; ?>
            <div>
                <span class="font-bold"><?= $flashType === 'ok' ? 'Berhasil:' : 'Periksa Kembali:' ?></span>
                <p class="mt-0.5 opacity-90"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid gap-8 lg:grid-cols-5 items-start">
        
        <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <h2 class="text-lg font-extrabold text-slate-900 tracking-tight flex items-center gap-2 border-b border-slate-100 pb-3">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z"/></svg>
                Unggah Berkas Baru
            </h2>
            
            <form method="post" action="<?= htmlspecialchars($urlKeg, ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data" class="space-y-5 mt-5">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="upload">
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Jenis Media / Dokumen</label>
                    <div class="relative">
                        <select name="jenis" id="jenis-media" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all font-semibold text-sm appearance-none cursor-pointer">
                            <option value="foto">📸 Foto Kegiatan</option>
                            <option value="video">🎥 Video Kegiatan</option>
                            <option value="proposal">📄 Proposal Kegiatan</option>
                            <option value="laporan">📊 Laporan Kegiatan</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Pilih File</label>
                    <div class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 transition-all hover:bg-slate-100/50">
                        <input type="file" name="berkas" required class="w-full text-sm text-slate-600 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                    </div>
                    <div class="mt-2 space-y-1 bg-slate-50/70 border border-slate-100 rounded-xl p-3 text-[11px] text-slate-500 font-medium leading-normal" id="size-info-container">
                        <p class="text-indigo-600 font-bold uppercase tracking-wide mb-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 111.063.852l-.708 2.836a.75.75 0 001.063.852l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Ketentuan Validasi Ukuran:
                        </p>
                        <p id="info-foto" class="font-semibold text-slate-700">• Foto: JPG, PNG, WEBP, GIF (Maks 8MB)</p>
                        <p id="info-video" class="opacity-60">• Video: MP4, WEBM, MOV (Maks 50MB)</p>
                        <p id="info-dokumen" class="opacity-60">• Proposal/Laporan: PDF, DOCX, JPG (Maks 20MB)</p>
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Deskripsi Singkat Dokumentasi</label>
                    <textarea name="deskripsi" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all text-sm text-slate-700 placeholder:text-slate-400 leading-relaxed" placeholder="Contoh: Sesi foto bersama narasumber utama pasca penutupan rapat..."></textarea>
                </div>
                
                <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg shadow-slate-900/10 transition-all active:scale-[0.98] text-sm tracking-wide uppercase mt-2">
                    Mulai Unggah
                </button>
            </form>
        </div>

        <div class="lg:col-span-3 bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">Koleksi Arsip Terunggah</h2>
                <span class="bg-indigo-50 text-indigo-700 font-bold text-xs px-2.5 py-1 rounded-full border border-indigo-100"><?= count($docs) ?> Item</span>
            </div>
            
            <div class="p-6 sm:p-8">
                <?php if (count($docs) === 0): ?>
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 mx-auto mb-3">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 012.008 1.24l.885 1.77a2.25 2.25 0 002.007 1.24h1.98a2.25 2.25 0 002.007-1.24l.885-1.77a2.25 2.25 0 012.007-1.24h3.86m-18 1.5V7.5A2.25 2.25 0 014.5 5.3h15a2.25 2.25 0 012.25 2.25v6m-18 1.5a2.25 2.25 0 002.25 2.25h15a2.25 2.25 0 002.25-2.25m-18 0v-2.25A2.25 2.25 0 014.5 12h15a2.25 2.25 0 012.25 2.25V15m-15 0h15"/></svg>
                        </div>
                        <p class="text-slate-400 text-sm font-semibold">Belum ada dokumentasi terunggah untuk agenda ini.</p>
                    </div>
                <?php else: ?>
                    <div class="grid gap-6 sm:grid-cols-2">
                        <?php foreach ($docs as $d): ?>
                            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm flex flex-col justify-between group hover:border-slate-300 transition-all duration-200">
                                <div>
                                    <div class="relative aspect-video w-full rounded-xl overflow-hidden bg-slate-900 border border-slate-100 flex items-center justify-center">
                                        <?php if ($d['jenis'] === 'foto'): ?>
                                            <a href="<?= htmlspecialchars(url_upload((string)$d['file_path']), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="w-full h-full">
                                                <img src="<?= htmlspecialchars(url_upload((string)$d['file_path']), ENT_QUOTES, 'UTF-8') ?>" alt="" class="w-full h-full object-cover group-hover:scale-102 transition-transform duration-300 bg-white">
                                            </a>
                                            <span class="absolute top-2 left-2 text-[10px] font-bold text-white bg-sky-600/90 backdrop-blur-sm px-2 py-0.5 rounded-md shadow-sm uppercase tracking-wider">📸 Foto</span>
                                        <?php elseif ($d['jenis'] === 'video'): ?>
                                            <video controls class="w-full h-full object-cover">
                                                <source src="<?= htmlspecialchars(url_upload((string)$d['file_path']), ENT_QUOTES, 'UTF-8') ?>">
                                            </video>
                                            <span class="absolute top-2 left-2 text-[10px] font-bold text-white bg-amber-600/90 backdrop-blur-sm px-2 py-0.5 rounded-md shadow-sm uppercase tracking-wider">🎥 Video</span>
                                        <?php else: 
                                            $filePath = htmlspecialchars(url_upload((string)$d['file_path']), ENT_QUOTES, 'UTF-8');
                                            $fileExt = strtoupper(pathinfo((string)$d['file_path'], PATHINFO_EXTENSION));
                                            $isProposal = $d['jenis'] === 'proposal';
                                            $badgeColor = $isProposal ? 'bg-purple-600/90' : 'bg-emerald-600/90';
                                            $labelStr = $isProposal ? '📄 Proposal' : '📊 Laporan';
                                        ?>
                                            <div class="absolute inset-0 bg-slate-50 flex flex-col items-center justify-center p-4 text-center">
                                                <div class="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 text-slate-600 flex items-center justify-center font-black text-xs mb-2 shadow-inner"><?= $fileExt ?></div>
                                                <div class="text-[11px] font-bold text-slate-800 line-clamp-1 px-2"><?= basename((string)$d['file_path']) ?></div>
                                                <a href="<?= $filePath ?>" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-slate-900 hover:bg-indigo-600 text-white font-bold px-3 py-1.5 text-[11px] shadow-sm transition-all">
                                                    Lihat Berkas
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                                                </a>
                                            </div>
                                            <span class="absolute top-2 left-2 text-[10px] font-bold text-white <?= $badgeColor ?> backdrop-blur-sm px-2 py-0.5 rounded-md shadow-sm uppercase tracking-wider"><?= $labelStr ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <p class="text-xs text-slate-600 font-medium leading-relaxed mt-3 break-words bg-slate-50 rounded-xl p-3 border border-slate-100">
                                        <?= htmlspecialchars((string)$d['deskripsi'] ?: '(Tidak ada deskripsi rincian)', ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>

                                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px]">
                                    <span class="text-slate-400 font-medium flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <?= date('d M Y H:i', strtotime((string)$d['uploaded_at'])) ?>
                                    </span>
                                    
                                    <form method="post" onsubmit="return confirm('Apakah Anda yakin ingin menghapus item dokumentasi ini secara permanen?');">
                                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                        <input type="hidden" name="action" value="hapus_dok">
                                        <input type="hidden" name="dok_id" value="<?= (int)$d['id'] ?>">
                                        <button type="submit" class="font-bold text-rose-600 hover:text-rose-800 transition-colors flex items-center gap-0.5 group">
                                            <svg class="w-3.5 h-3.5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
// Logic UX Interaktif untuk menyorot baris pedoman ukuran file berdasarkan tipe media pilihan
document.getElementById('jenis-media').addEventListener('change', function(e) {
    const val = e.target.value;
    const infoFoto = document.getElementById('info-foto');
    const infoVideo = document.getElementById('info-video');
    const infoDokumen = document.getElementById('info-dokumen');
    
    // Reset status opacity
    [infoFoto, infoVideo, infoDokumen].forEach(el => {
        el.classList.remove('opacity-100', 'font-semibold', 'text-slate-700', 'text-indigo-600');
        el.classList.add('opacity-60');
    });

    // Beri penekanan visual pada informasi yang sesuai dengan seleksi
    if (val === 'foto') {
        infoFoto.classList.remove('opacity-60');
        infoFoto.classList.add('opacity-100', 'font-semibold', 'text-indigo-600');
    } else if (val === 'video') {
        infoVideo.classList.remove('opacity-60');
        infoVideo.classList.add('opacity-100', 'font-semibold', 'text-indigo-600');
    } else if (val === 'proposal' || val === 'laporan') {
        infoDokumen.classList.remove('opacity-60');
        infoDokumen.classList.add('opacity-100', 'font-semibold', 'text-indigo-600');
    }
});
</script>

<?php require dirname(__DIR__) . '/includes/admin_footer.php'; ?>