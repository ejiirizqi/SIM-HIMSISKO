<?php
declare(strict_types=1);

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/helpers/csrf.php';
require_once __DIR__ . '/config/auth.php';

$error = '';

if (current_user()) {
    $role = current_user()['role'] ?? '';
    if ($role === 'admin') {
        header('Location: ' . url('admin/index.php'));
        exit;
    }
    header('Location: ' . url('mhs/dashboard.php'));
        exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate($_POST['_csrf'] ?? null);
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $block = null;

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } elseif (!login_user($username, $password, $block)) {
        if ($block === 'pending') {
            $error = 'Akun Anda belum disetujui administrator. Silakan tunggu atau hubungi pengurus.';
        } elseif ($block === 'rejected') {
            $error = 'Pendaftaran Anda ditolak. Hubungi administrator jika ini kesalahan.';
        } else {
            $error = 'Login gagal. Periksa username dan password Anda.';
        }
    } else {
        $role = current_user()['role'] ?? '';
        if ($role === 'admin') {
            header('Location: ' . url('admin/index.php'));
        } else {
            header('Location: ' . url('mhs/dashboard.php'));
        }
        exit;
    }
}

$csrf = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — SIM HIMSISKO</title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars(brand_logo_url(), ENT_QUOTES, 'UTF-8') ?>">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-900 p-4 sm:p-6">
<div class="w-full max-w-md">
    <div class="rounded-2xl bg-white p-6 shadow-xl sm:p-8">
        <div class="flex flex-col items-center text-center">
            <img src="<?= htmlspecialchars(brand_logo_url(), ENT_QUOTES, 'UTF-8') ?>" alt="Logo HIMSISKO IBI-K57" class="h-24 w-24 object-contain sm:h-28 sm:w-28">
            <h1 class="mt-4 text-xl font-bold text-slate-900 sm:text-2xl">SIM HIMSISKO</h1>
            <p class="mt-1 text-xs text-slate-500 sm:text-sm">HIMSISKO IBI-K57</p>
        </div>
        <p class="mt-4 text-center text-slate-600 text-sm">Masuk sebagai admin atau mahasiswa</p>

        <?php if ($error !== ''): ?>
            <div class="mt-6 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-sm px-4 py-3" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post" action="" class="mt-6 space-y-4">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <div>
                <label for="username" class="block text-sm font-medium text-slate-700">Username</label>
                <input type="text" name="username" id="username" required autocomplete="username"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 focus:ring-2 focus:ring-slate-900 focus:border-slate-900 outline-none transition"
                       value="<?= htmlspecialchars(trim((string)($_POST['username'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                <input type="password" name="password" id="password" required autocomplete="current-password"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 focus:ring-2 focus:ring-slate-900 focus:border-slate-900 outline-none transition">
            </div>
            <button type="submit" class="w-full rounded-lg bg-slate-900 text-white font-semibold py-2.5 hover:bg-slate-800 transition">
                Masuk
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600">
            Belum punya akun? <a href="<?= htmlspecialchars(url('register.php'), ENT_QUOTES, 'UTF-8') ?>" class="font-semibold text-indigo-600 hover:underline">Daftar mahasiswa</a>
        </p>
        <p class="mt-4 text-center text-xs text-slate-500">
            <a href="<?= htmlspecialchars(url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-slate-700 underline underline-offset-2 hover:text-slate-900">Beranda publik</a>
        </p>
    </div>
</div>
</body>
</html>
