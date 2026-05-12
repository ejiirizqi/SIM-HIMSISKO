<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';
require_once dirname(__DIR__) . '/helpers/secure_upload.php';
require_once dirname(__DIR__) . '/helpers/upload_storage.php';
require_once dirname(__DIR__) . '/config/auth.php';

require_role('mahasiswa');

$pageTitle = 'Edit profil';
$activeNav = 'profil';

$uid = (int)(current_user()['id'] ?? 0);
$error = '';

$stmt = db()->prepare('SELECT id, username, nama_lengkap, email, foto_profil FROM users WHERE id=? LIMIT 1');
$stmt->execute([$uid]);
$me = $stmt->fetch();

if (!$me) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$f = [
    'username' => (string)$me['username'],
    'nama_lengkap' => (string)($me['nama_lengkap'] ?? ''),
    'email' => (string)($me['email'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate($_POST['_csrf'] ?? null);

    $f['username'] = trim((string)($_POST['username'] ?? ''));
    $f['nama_lengkap'] = trim((string)($_POST['nama_lengkap'] ?? ''));
    $f['email'] = trim((string)($_POST['email'] ?? ''));
    $pw1 = (string)($_POST['password'] ?? '');
    $pw2 = (string)($_POST['password2'] ?? '');

    $fotoBaruRelative = '';

    if ($f['username'] === '') {
        $error = 'Username tidak boleh kosong.';
    } elseif (!filter_var($f['email'], FILTER_VALIDATE_EMAIL) && $f['email'] !== '') {
        $error = 'Email tidak valid.';
    } elseif ($pw1 !== '' && $pw1 !== $pw2) {
        $error = 'Konfirmasi password tidak sama.';
    } elseif ($pw1 !== '' && strlen($pw1) < 6) {
        $error = 'Password minimal 6 karakter.';
    }

    if ($error === '' && isset($_FILES['foto']) && (int)$_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $up = save_upload_public($_FILES['foto'], 'profil', ['jpg', 'jpeg', 'png', 'webp'], 2 * 1024 * 1024);
        if (!$up['ok']) {
            $error = $up['error'] ?? 'Gagal unggah foto.';
        } else {
            $fotoBaruRelative = $up['relative'] ?? '';
        }
    }

    if ($error === '') {
        try {
            $hashBaru = $pw1 !== '' ? password_hash($pw1, PASSWORD_DEFAULT) : null;

            if ($hashBaru && $fotoBaruRelative !== '') {
                unlink_upload((string)($me['foto_profil'] ?? ''));
                $qx = db()->prepare(
                    'UPDATE users SET username=?, nama_lengkap=?, email=?, password=?, foto_profil=? WHERE id=? LIMIT 1'
                );
                $qx->execute([
                    $f['username'],
                    $f['nama_lengkap'] === '' ? null : $f['nama_lengkap'],
                    $f['email'] === '' ? null : $f['email'],
                    $hashBaru,
                    $fotoBaruRelative,
                    $uid,
                ]);
            } elseif ($hashBaru) {
                $qx = db()->prepare('UPDATE users SET username=?, nama_lengkap=?, email=?, password=? WHERE id=? LIMIT 1');
                $qx->execute([
                    $f['username'],
                    $f['nama_lengkap'] === '' ? null : $f['nama_lengkap'],
                    $f['email'] === '' ? null : $f['email'],
                    $hashBaru,
                    $uid,
                ]);
            } elseif ($fotoBaruRelative !== '') {
                unlink_upload((string)($me['foto_profil'] ?? ''));
                $qx = db()->prepare(
                    'UPDATE users SET username=?, nama_lengkap=?, email=?, foto_profil=? WHERE id=? LIMIT 1'
                );
                $qx->execute([
                    $f['username'],
                    $f['nama_lengkap'] === '' ? null : $f['nama_lengkap'],
                    $f['email'] === '' ? null : $f['email'],
                    $fotoBaruRelative,
                    $uid,
                ]);
            } else {
                $qx = db()->prepare('UPDATE users SET username=?, nama_lengkap=?, email=? WHERE id=? LIMIT 1');
                $qx->execute([
                    $f['username'],
                    $f['nama_lengkap'] === '' ? null : $f['nama_lengkap'],
                    $f['email'] === '' ? null : $f['email'],
                    $uid,
                ]);
            }

            log_activity($uid, 'mahasiswa', (string)(current_user()['username'] ?? ''), 'Perbarui profil mahasiswa', 'Profil mahasiswa diperbarui oleh pengguna');
            refresh_session_user($uid);
            header('Location: ' . url('mhs/profil.php'));
            exit;
        } catch (PDOException $e) {
            if ((int)$e->errorInfo[1] === 1062) {
                $error = 'Username tersebut sudah dipakai orang lain.';
            } else {
                $error = 'Gagal memperbarui profil.';
            }
        }
    }

    $stmt->execute([$uid]);
    $me = $stmt->fetch();
}

$csrf = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');

require dirname(__DIR__) . '/includes/mhs_header.php';
?>

<div class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-black text-slate-900 mb-2">Kelola Profil Anda</h1>
    <p class="text-slate-600 mb-8">Update informasi pribadi dan foto profil Anda kapan saja</p>

    <?php if ($error !== ''): ?>
        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-900 font-medium">
            <svg class="inline-block w-5 h-5 mr-2 -mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 7a1 1 0 100 2 1 1 0 000-2zm0 6a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/></svg>
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="grid md:grid-cols-3 gap-8 lg:gap-12">
        <!-- FOTO SECTION -->
        <div class="md:col-span-1 flex flex-col items-center md:items-start">
            <div class="sticky top-32">
                <div class="rounded-full overflow-hidden bg-gradient-to-br from-indigo-100 to-blue-100 border-2 border-slate-200 shadow-lg cursor-pointer group relative w-64 h-64 mx-auto md:mx-0"
                     id="foto-upload-trigger">
                    <div class="w-full h-full flex items-center justify-center relative overflow-hidden">
                        <?php if (!empty($me['foto_profil'])): ?>
                            <img src="<?= htmlspecialchars(url_upload((string)$me['foto_profil']), ENT_QUOTES, 'UTF-8') ?>" 
                                 alt="Profile" class="w-full h-full object-cover" id="profile-img">
                        <?php else: ?>
                            <div class="flex flex-col items-center justify-center text-slate-400">
                                <svg class="w-16 h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span class="text-sm font-medium">Belum ada foto</span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Overlay hover -->
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition flex items-center justify-center">
                            <div class="text-white opacity-0 group-hover:opacity-100 transition text-center">
                                <svg class="w-10 h-10 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span class="text-sm font-bold">Klik untuk ganti</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-2xl">
                    <p class="text-xs font-semibold text-blue-900 uppercase tracking-wide mb-1">Foto Profil</p>
                    <p class="text-sm text-blue-800">Ukuran maks <strong>2 MB</strong></p>
                    <p class="text-sm text-blue-700 mt-1">Format: JPG, PNG, WebP</p>
                </div>

                <!-- Preview section (hidden by default) -->
                <div id="preview-section" class="hidden mt-4 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl">
                    <p class="text-xs font-semibold text-emerald-900 uppercase tracking-wide mb-2">Preview Baru</p>
                    <div class="rounded-xl overflow-hidden bg-white border border-emerald-200">
                        <img id="preview-img" src="" alt="Preview" class="w-full h-auto">
                    </div>
                    <button type="button" id="confirm-upload-btn" class="mt-2 w-full bg-emerald-600 text-white font-bold text-sm py-2 rounded-lg hover:bg-emerald-700 transition">
                        Gunakan foto ini
                    </button>
                    <button type="button" id="cancel-upload-btn" class="mt-2 w-full bg-slate-300 text-slate-700 font-bold text-sm py-2 rounded-lg hover:bg-slate-400 transition">
                        Batal
                    </button>
                </div>
            </div>
        </div>

        <!-- FORM SECTION -->
        <div class="md:col-span-2">
            <form method="post" enctype="multipart/form-data" id="profile-form" class="space-y-6">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <input type="file" id="foto-input" name="foto" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="hidden">

                <!-- IDENTITAS SECTION -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                        Data Identitas
                    </h2>
                    
                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Username (untuk login)</label>
                            <input name="username" required value="<?= htmlspecialchars($f['username'], ENT_QUOTES, 'UTF-8') ?>" 
                                   class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition" 
                                   placeholder="username">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Nama Lengkap</label>
                            <input name="nama_lengkap" value="<?= htmlspecialchars($f['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>" 
                                   class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition" 
                                   placeholder="Nama lengkap Anda">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($f['email'], ENT_QUOTES, 'UTF-8') ?>" 
                                   class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition" 
                                   placeholder="email@example.com">
                        </div>
                    </div>
                </div>

                <!-- KEAMANAN SECTION -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                        Keamanan
                    </h2>
                    
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 mb-5">
                        <p class="text-sm text-slate-600">Biarkan kosong jika Anda tidak ingin mengubah password</p>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Password Baru</label>
                            <input type="password" name="password" autocomplete="new-password"
                                   class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition" 
                                   placeholder="Minimal 6 karakter">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Konfirmasi Password</label>
                            <input type="password" name="password2" autocomplete="new-password"
                                   class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition" 
                                   placeholder="Ketik ulang password">
                        </div>
                    </div>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="flex flex-col sm:flex-row gap-3 pt-4">
                    <button type="submit" class="flex-1 bg-indigo-600 text-white font-bold py-3 px-6 rounded-xl shadow-md hover:bg-indigo-700 transition transform hover:scale-105 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan Perubahan
                    </button>
                    <a href="<?= htmlspecialchars(url('mhs/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" class="flex-1 bg-slate-200 text-slate-700 font-bold py-3 px-6 rounded-xl hover:bg-slate-300 transition text-center">
                        Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const fotoInput = document.getElementById('foto-input');
    const fotoTrigger = document.getElementById('foto-upload-trigger');
    const profileImg = document.getElementById('profile-img');
    const previewSection = document.getElementById('preview-section');
    const previewImg = document.getElementById('preview-img');
    const confirmBtn = document.getElementById('confirm-upload-btn');
    const cancelBtn = document.getElementById('cancel-upload-btn');
    const profileForm = document.getElementById('profile-form');

    let tempFile = null;

    // Click on foto to upload
    fotoTrigger.addEventListener('click', function () {
        fotoInput.click();
    });

    // Handle file selection and preview
    fotoInput.addEventListener('change', function (e) {
        const file = this.files[0];
        if (!file) return;

        // Validate file size (2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('File terlalu besar! Maksimal 2 MB.');
            fotoInput.value = '';
            return;
        }

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Format file tidak didukung. Gunakan JPG, PNG, atau WebP.');
            fotoInput.value = '';
            return;
        }

        // Create preview
        const reader = new FileReader();
        reader.onload = function (event) {
            previewImg.src = event.target.result;
            previewSection.classList.remove('hidden');
            tempFile = file;

            // Scroll to preview
            previewSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        };
        reader.readAsDataURL(file);
    });

    // Confirm upload
    confirmBtn.addEventListener('click', function () {
        if (tempFile) {
            // Update profile image immediately
            const reader = new FileReader();
            reader.onload = function (e) {
                if (profileImg) {
                    profileImg.src = e.target.result;
                    profileImg.parentElement.style.display = 'block';
                }
            };
            reader.readAsDataURL(tempFile);
            previewSection.classList.add('hidden');
            // File input masih tersimpan, siap untuk submit
        }
    });

    // Cancel upload
    cancelBtn.addEventListener('click', function () {
        fotoInput.value = '';
        previewSection.classList.add('hidden');
        tempFile = null;
    });
})();
</script>

<?php require dirname(__DIR__) . '/includes/mhs_footer.php'; ?>
