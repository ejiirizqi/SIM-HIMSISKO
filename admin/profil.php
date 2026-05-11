<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';
require_once dirname(__DIR__) . '/helpers/secure_upload.php';
require_once dirname(__DIR__) . '/helpers/upload_storage.php';
require_once dirname(__DIR__) . '/config/auth.php';

require_role('admin');

$pageTitle = 'Profil admin';
$activeNav = 'profil';

$uid = (int)(current_user()['id'] ?? 0);
$error = '';

$stmt = db()->prepare('SELECT id, username, nama_lengkap, email, foto_profil FROM users WHERE id=? AND role=\'admin\' LIMIT 1');
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

            log_activity($uid, 'admin', (string)(current_user()['username'] ?? ''), 'Perbarui profil admin', 'Profil admin diperbarui oleh admin');
            refresh_session_user($uid);
            header('Location: ' . url('admin/profil.php'));
            exit;
        } catch (PDOException $e) {
            if ((int)$e->errorInfo[1] === 1062) {
                $error = 'Username tersebut sudah dipakai.';
            } else {
                $error = 'Gagal memperbarui profil.';
            }
        }
    }

    $stmt->execute([$uid]);
    $me = $stmt->fetch();
}

$csrf = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');

require dirname(__DIR__) . '/includes/admin_header.php';
?>

<div class="max-w-xl">
    <h1 class="text-2xl font-bold text-slate-900 mb-2">Profil administrator</h1>
    <p class="text-slate-600 text-sm mb-6">Ubah data login, nama, email, password, dan foto profil.</p>

    <?php if ($error !== ''): ?>
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="mb-8 flex items-center gap-4">
        <div class="h-20 w-20 rounded-2xl overflow-hidden bg-slate-200 border border-slate-300 shrink-0">
            <?php if (!empty($me['foto_profil'])): ?>
                <img src="<?= htmlspecialchars(url_upload((string)$me['foto_profil']), ENT_QUOTES, 'UTF-8') ?>" alt="" class="object-cover h-full w-full">
            <?php else: ?>
                <div class="h-full w-full flex items-center justify-center text-xs text-slate-500">Tanpa foto</div>
            <?php endif; ?>
        </div>
        <div class="text-sm text-slate-600">Foto opsional, maks 2 MB (JPG/PNG/WebP).</div>
    </div>

    <form method="post" enctype="multipart/form-data" class="space-y-4 bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm">
        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Username</label>
            <input name="username" required value="<?= htmlspecialchars($f['username'], ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nama lengkap</label>
            <input name="nama_lengkap" value="<?= htmlspecialchars($f['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($f['email'], ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Foto profil</label>
            <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" class="text-sm w-full">
        </div>
        <div class="grid sm:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Password baru</label>
                <input type="password" name="password" autocomplete="new-password" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Kosongkan jika tidak diubah">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Konfirmasi</label>
                <input type="password" name="password2" autocomplete="new-password" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
        </div>
        <button type="submit" class="mt-4 rounded-lg bg-slate-900 text-white font-semibold px-6 py-2.5 hover:bg-slate-800">Simpan</button>
    </form>
</div>

<?php require dirname(__DIR__) . '/includes/admin_footer.php'; ?>
