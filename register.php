<?php
declare(strict_types=1);

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/helpers/csrf.php';
require_once __DIR__ . '/helpers/mail_approval.php';
require_once __DIR__ . '/config/auth.php';

if (current_user()) {
    $role = current_user()['role'] ?? '';
    header('Location: ' . url($role === 'admin' ? 'admin/index.php' : 'mhs/dashboard.php'));
    exit;
}

$error = '';
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate($_POST['_csrf'] ?? null);
    $nama = trim((string)($_POST['nama_lengkap'] ?? ''));
    $username = trim((string)($_POST['username'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $pw1 = (string)($_POST['password'] ?? '');
    $pw2 = (string)($_POST['password2'] ?? '');

    if ($username === '' || $pw1 === '') {
        $error = 'Username dan password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email wajib diisi dengan format yang valid.';
    } elseif (strlen($pw1) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($pw1 !== $pw2) {
        $error = 'Konfirmasi password tidak sama.';
    } elseif (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) {
        $error = 'Username 3–50 karakter: huruf, angka, titik, underscore, atau strip.';
    } else {
        $emailNorm = function_exists('mb_strtolower') ? mb_strtolower($email) : strtolower($email);
        $chk = db()->prepare('SELECT id FROM users WHERE LOWER(TRIM(email)) = ? LIMIT 1');
        $chk->execute([$emailNorm]);
        if ($chk->fetch()) {
            $error = 'Email sudah terdaftar. Gunakan email lain atau hubungi admin.';
        } else {
            $chk2 = db()->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $chk2->execute([$username]);
            if ($chk2->fetch()) {
                $error = 'Username sudah dipakai.';
            } else {
                try {
                    $stmt = db()->prepare(
                        'INSERT INTO users (username, password, role, nama_lengkap, email, approval_status) VALUES (?,?,?,?,?,?)'
                    );
                    $stmt->execute([
                        $username,
                        password_hash($pw1, PASSWORD_DEFAULT),
                        'mahasiswa',
                        $nama === '' ? null : $nama,
                        $emailNorm,
                        'pending',
                    ]);
                    $newUserId = (int)db()->lastInsertId();
                    log_activity($newUserId, 'mahasiswa', $username, 'Daftar akun mahasiswa', 'Status: pending');
                    $mailRes = mail_notify_admin_new_registration($username, $emailNorm, $nama);
                    if (!$mailRes['ok']) {
                        log_activity($newUserId, 'system', 'system', 'Gagal kirim notifikasi admin', $mailRes['error']);
                    }
                    $ok = true;
                } catch (PDOException $e) {
                    if ((int)$e->errorInfo[1] === 1062) {
                        $error = 'Username atau email sudah terdaftar.';
                    } else {
                        $error = 'Pendaftaran gagal. Pastikan database sudah diperbarui (kolom approval_status).';
                    }
                }
            }
        }
    }
}

$csrf = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
$ico = htmlspecialchars(brand_logo_url(), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrasi mahasiswa — SIM HIMSISKO</title>
    <link rel="icon" type="image/png" href="<?= $ico ?>">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-900 p-4 sm:p-6">
<div class="mx-auto w-full max-w-md">
    <div class="rounded-2xl bg-white p-6 shadow-xl sm:p-8">
        <div class="flex flex-col items-center text-center">
            <img src="<?= $ico ?>" alt="" class="h-20 w-20 object-contain sm:h-24 sm:w-24">
            <h1 class="mt-3 text-xl font-bold text-slate-900 sm:text-2xl">Registrasi mahasiswa</h1>
            <p class="mt-1 text-xs text-slate-500 sm:text-sm">Akun menunggu persetujuan admin. Anda akan menerima email setelah disetujui (jika SMTP sudah dikonfigurasi).</p>
        </div>

        <?php if ($ok): ?>
            <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
                Pendaftaran berhasil. Silakan tunggu persetujuan administrator sebelum dapat masuk.
            </div>
            <p class="mt-6 text-center">
                <a href="<?= htmlspecialchars(url('login.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-sm font-semibold text-indigo-600 hover:underline">Kembali ke login</a>
            </p>
        <?php else: ?>
            <?php if ($error !== ''): ?>
                <div class="mt-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="post" class="mt-6 space-y-4">
                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Nama lengkap</label>
                    <input type="text" name="nama_lengkap" value="<?= htmlspecialchars(trim((string)($_POST['nama_lengkap'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Username <span class="text-rose-600">*</span></label>
                    <input type="text" name="username" required autocomplete="username" pattern="[a-zA-Z0-9._-]{3,50}"
                           value="<?= htmlspecialchars(trim((string)($_POST['username'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Email <span class="text-rose-600">*</span></label>
                    <input type="email" name="email" required autocomplete="email"
                           value="<?= htmlspecialchars(trim((string)($_POST['email'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="nama@email.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Password <span class="text-rose-600">*</span></label>
                    <input type="password" name="password" required autocomplete="new-password" minlength="6" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Ulangi password <span class="text-rose-600">*</span></label>
                    <input type="password" name="password2" required autocomplete="new-password" minlength="6" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <button type="submit" class="w-full rounded-lg bg-slate-900 py-2.5 font-semibold text-white hover:bg-slate-800">Kirim pendaftaran</button>
            </form>
            <p class="mt-6 text-center text-xs text-slate-500">
                Sudah punya akun? <a href="<?= htmlspecialchars(url('login.php'), ENT_QUOTES, 'UTF-8') ?>" class="font-semibold text-indigo-600 hover:underline">Login</a>
                · <a href="<?= htmlspecialchars(url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-slate-600 hover:underline">Beranda</a>
            </p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
