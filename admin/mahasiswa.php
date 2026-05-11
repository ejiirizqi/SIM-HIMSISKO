<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';
require_once dirname(__DIR__) . '/helpers/upload_storage.php';
require_once dirname(__DIR__) . '/config/auth.php';

require_role('admin');

$pageTitle = 'Data mahasiswa';
$activeNav = 'mahasiswa';

$flash = '';
$flashType = 'ok';

$cu = current_user();
$myId = (int)($cu['id'] ?? 0);

$editMode = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$form = [
    'username' => '',
    'nama_lengkap' => '',
    'email' => '',
    'password' => '',
];
if ($editMode > 0) {
    $st = db()->prepare('SELECT id, username, nama_lengkap, email FROM users WHERE id=? AND role=\'mahasiswa\' LIMIT 1');
    $st->execute([$editMode]);
    $rw = $st->fetch();
    if ($rw) {
        $form = [
            'username' => (string)$rw['username'],
            'nama_lengkap' => (string)($rw['nama_lengkap'] ?? ''),
            'email' => (string)($rw['email'] ?? ''),
            'password' => '',
        ];
    } else {
        $editMode = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate($_POST['_csrf'] ?? null);
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'tambah') {
        $username = trim((string)($_POST['username_new'] ?? ''));
        $pass = (string)($_POST['password_new'] ?? '');
        $nama = trim((string)($_POST['nama_new'] ?? ''));
        $email = trim((string)($_POST['email_new'] ?? ''));

        if ($username === '' || $pass === '') {
            $flash = 'Username dan password wajib untuk akun baru.';
            $flashType = 'err';
        } elseif (strlen($pass) < 6) {
            $flash = 'Password minimal 6 karakter.';
            $flashType = 'err';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) && $email !== '') {
            $flash = 'Format email tidak valid.';
            $flashType = 'err';
        } else {
            try {
                $stmt = db()->prepare(
                    'INSERT INTO users (username, password, role, nama_lengkap, email, approval_status) VALUES (?,?,?,?,?,?)'
                );
                $stmt->execute([
                    $username,
                    password_hash($pass, PASSWORD_DEFAULT),
                    'mahasiswa',
                    $nama === '' ? null : $nama,
                    $email === '' ? null : $email,
                    'active',
                ]);
                $newId = (int)db()->lastInsertId();
                log_activity((int)current_user()['id'], 'admin', (string)(current_user()['username'] ?? ''), 'Tambah mahasiswa', 'ID: ' . $newId . ' Username: ' . $username);
                header('Location: ' . url('admin/mahasiswa.php'));
                exit;
            } catch (PDOException $e) {
                if ((int)$e->errorInfo[1] === 1062) {
                    $flash = 'Username sudah dipakai.';
                } else {
                    $flash = 'Gagal menyimpan: ' . $e->getMessage();
                }
                $flashType = 'err';
            }
        }
    } elseif ($action === 'ubah') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $username = trim((string)($_POST['username'] ?? ''));
        $nama = trim((string)($_POST['nama_lengkap'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $passBaru = (string)($_POST['password_baru'] ?? '');

        if ($uid <= 0) {
            $flash = 'Target tidak valid.';
            $flashType = 'err';
        } elseif ($username === '') {
            $flash = 'Username tidak boleh kosong.';
            $flashType = 'err';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) && $email !== '') {
            $flash = 'Email tidak valid.';
            $flashType = 'err';
        } else {
            $cek = db()->prepare('SELECT id FROM users WHERE id=? AND role=\'mahasiswa\' LIMIT 1');
            $cek->execute([$uid]);
            if (!$cek->fetch()) {
                $flash = 'Mahasiswa tidak ditemukan.';
                $flashType = 'err';
            } else {
                try {
                    if ($passBaru !== '') {
                        if (strlen($passBaru) < 6) {
                            $flash = 'Password baru minimal 6 karakter.';
                            $flashType = 'err';
                        } else {
                            $stmt = db()->prepare(
                                'UPDATE users SET username=?, nama_lengkap=?, email=?, password=? WHERE id=? LIMIT 1'
                            );
                            $stmt->execute([
                                $username,
                                $nama === '' ? null : $nama,
                                $email === '' ? null : $email,
                                password_hash($passBaru, PASSWORD_DEFAULT),
                                $uid,
                            ]);
                            log_activity((int)current_user()['id'], 'admin', (string)(current_user()['username'] ?? ''), 'Ubah data mahasiswa', 'ID: ' . $uid . ' Username: ' . $username);
                            header('Location: ' . url('admin/mahasiswa.php'));
                            exit;
                        }
                    } else {
                        $stmt = db()->prepare('UPDATE users SET username=?, nama_lengkap=?, email=? WHERE id=? LIMIT 1');
                        $stmt->execute([
                            $username,
                            $nama === '' ? null : $nama,
                            $email === '' ? null : $email,
                            $uid,
                        ]);
                        log_activity((int)current_user()['id'], 'admin', (string)(current_user()['username'] ?? ''), 'Ubah data mahasiswa', 'ID: ' . $uid . ' Username: ' . $username);
                        header('Location: ' . url('admin/mahasiswa.php'));
                        exit;
                    }
                } catch (PDOException $e) {
                    if ((int)$e->errorInfo[1] === 1062) {
                        $flash = 'Username sudah dipakai.';
                    } else {
                        $flash = 'Gagal memperbarui.';
                    }
                    $flashType = 'err';
                }
            }
        }
    } elseif ($action === 'hapus') {
        $hid = (int)($_POST['user_id'] ?? 0);
        if ($hid > 0 && $hid !== $myId) {
            $cek = db()->prepare('SELECT id, foto_profil, username FROM users WHERE id=? AND role=\'mahasiswa\' LIMIT 1');
            $cek->execute([$hid]);
            $ur = $cek->fetch();
            if ($ur) {
                unlink_upload($ur['foto_profil'] ?? null);
                db()->prepare('DELETE FROM users WHERE id=? LIMIT 1')->execute([$hid]);
                log_activity((int)current_user()['id'], 'admin', (string)(current_user()['username'] ?? ''), 'Hapus mahasiswa', 'ID: ' . $hid . ' Username: ' . (string)($ur['username'] ?? ''));
            }
            header('Location: ' . url('admin/mahasiswa.php'));
            exit;
        }
    }
}

$list = db()->query(
    'SELECT id, username, nama_lengkap, email, approval_status, created_at FROM users WHERE role=\'mahasiswa\' ORDER BY created_at DESC'
)->fetchAll();

$csrf = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');

require dirname(__DIR__) . '/includes/admin_header.php';
?>
<div class="mb-8 flex flex-col sm:flex-row sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Kelola data mahasiswa</h1>
        <p class="text-slate-600 mt-1">Akun role mahasiswa — password disimpan hashed</p>
    </div>
</div>

<?php if ($flash !== ''): ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $flashType === 'ok'
        ? 'bg-emerald-50 border-emerald-200 text-emerald-900'
        : 'bg-rose-50 border-rose-200 text-rose-900' ?>"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($editMode): ?>
<div class="mb-10 max-w-xl rounded-xl border border-amber-200 bg-amber-50 p-6">
    <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold">Edit mahasiswa</h2>
        <a href="<?= htmlspecialchars(url('admin/mahasiswa.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-sm text-slate-600 hover:text-slate-900">Tutup</a>
    </div>
    <form method="post" class="mt-4 space-y-4">
        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="ubah">
        <input type="hidden" name="user_id" value="<?= $editMode ?>">
        <div>
            <label class="block text-sm font-medium text-slate-700">Username</label>
            <input name="username" required value="<?= htmlspecialchars($form['username'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-lg border px-3 py-2 border-slate-300">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Nama lengkap</label>
            <input name="nama_lengkap" value="<?= htmlspecialchars($form['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-lg border px-3 py-2 border-slate-300">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($form['email'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-lg border px-3 py-2 border-slate-300">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Password baru</label>
            <input type="password" name="password_baru" placeholder="Biarkan kosong jika tidak diubah" class="mt-1 w-full rounded-lg border px-3 py-2 border-slate-300">
        </div>
        <button class="rounded-lg bg-slate-900 text-white font-semibold px-5 py-2.5">Simpan perubahan</button>
    </form>
</div>
<?php else: ?>

<div class="grid gap-8 lg:grid-cols-5 mb-12">
    <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Tambah mahasiswa</h2>
        <form method="post" class="mt-4 space-y-3">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="tambah">
            <input name="username_new" required placeholder="Username" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            <input type="password" name="password_new" required placeholder="Password minimal 6 char" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            <input name="nama_new" placeholder="Nama lengkap" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            <input type="email" name="email_new" placeholder="Email (opsional)" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            <button type="submit" class="w-full rounded-lg bg-slate-900 text-white font-semibold py-2.5">Tambahkan</button>
        </form>
    </div>
</div>

<?php endif; ?>

<div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <table class="min-w-full text-sm">
        <thead><tr class="bg-slate-50 text-left text-slate-600">
            <th class="px-4 py-3">Username</th>
            <th class="px-4 py-3">Nama</th>
            <th class="px-4 py-3">Email</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3"></th>
        </tr></thead>
        <tbody class="divide-y divide-slate-100">
        <?php foreach ($list as $m): ?>
            <?php
            $ap = (string)($m['approval_status'] ?? 'active');
            $apLab = match ($ap) {
                'pending' => ['bg-amber-100 text-amber-900', 'Menunggu'],
                'rejected' => ['bg-rose-100 text-rose-900', 'Ditolak'],
                default => ['bg-emerald-100 text-emerald-900', 'Aktif'],
            };
            ?>
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-3 font-medium"><?= htmlspecialchars((string)$m['username'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars((string)($m['nama_lengkap'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars((string)($m['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3">
                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold <?= $apLab[0] ?>"><?= htmlspecialchars($apLab[1], ENT_QUOTES, 'UTF-8') ?></span>
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <a href="<?= htmlspecialchars(url('admin/mahasiswa.php?edit=' . (int)$m['id']), ENT_QUOTES, 'UTF-8') ?>" class="text-indigo-600 font-semibold">Edit</a>
                    <form method="post" class="inline ml-3" onsubmit="return confirm('Yakin menghapus akun ini?');">
                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="hapus">
                        <input type="hidden" name="user_id" value="<?= (int)$m['id'] ?>">
                        <button type="submit" class="text-rose-600 font-semibold hover:underline">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (count($list) === 0): ?>
            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada mahasiswa terdaftar.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
require dirname(__DIR__) . '/includes/admin_footer.php';
