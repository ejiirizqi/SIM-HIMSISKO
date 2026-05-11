<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/helpers/mail_approval.php';

require_role('admin');

$pageTitle = 'Pendaftar mahasiswa';
$activeNav = 'pendaftar';

$flash = '';
$flashType = 'ok';

if (!empty($_SESSION['_pend_flash']) && is_array($_SESSION['_pend_flash'])) {
    $flash = (string)($_SESSION['_pend_flash'][0] ?? '');
    $flashType = (string)($_SESSION['_pend_flash'][1] ?? 'ok');
    unset($_SESSION['_pend_flash']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate($_POST['_csrf'] ?? null);
    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    $pFlash = '';
    $pType = 'ok';

    if ($id <= 0) {
        $pFlash = 'Data tidak valid.';
        $pType = 'err';
    } else {
        $st = db()->prepare(
            'SELECT id, username, nama_lengkap, email, approval_status FROM users WHERE id = ? AND role = \'mahasiswa\' LIMIT 1'
        );
        $st->execute([$id]);
        $row = $st->fetch();

        if (!$row || ($row['approval_status'] ?? '') !== 'pending') {
            $pFlash = 'Pendaftar tidak ditemukan atau sudah diproses.';
            $pType = 'err';
        } elseif ($action === 'setujui') {
            db()->prepare(
                "UPDATE users SET approval_status = 'active' WHERE id = ? AND role = 'mahasiswa' AND approval_status = 'pending' LIMIT 1"
            )->execute([$id]);

            $email = trim((string)($row['email'] ?? ''));
            $nama = trim((string)($row['nama_lengkap'] ?? ''));
            $uname = (string)$row['username'];
            log_activity((int)$row['id'], 'mahasiswa', $uname, 'Akun mahasiswa disetujui', 'Disetujui oleh admin ' . (string)(current_user()['username'] ?? '')); 

            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mailRes = mail_notify_account_approved($email, $nama, $uname);
                if (!$mailRes['ok']) {
                    $pFlash = 'Akun disetujui. Notifikasi email gagal dikirim: ' . ($mailRes['error'] ?? 'periksa config/mail.php (SMTP).');
                    $pType = 'err';
                } else {
                    $pFlash = 'Akun disetujui dan notifikasi email telah dikirim.';
                }
            } else {
                $pFlash = 'Akun disetujui (email tidak valid — notifikasi tidak dikirim).';
                $pType = 'err';
            }
        } elseif ($action === 'tolak') {
            db()->prepare(
                "UPDATE users SET approval_status = 'rejected' WHERE id = ? AND role = 'mahasiswa' AND approval_status = 'pending' LIMIT 1"
            )->execute([$id]);
            log_activity((int)$row['id'], 'mahasiswa', (string)$row['username'], 'Pendaftaran mahasiswa ditolak', 'Ditolak oleh admin ' . (string)(current_user()['username'] ?? ''));
            $pFlash = 'Pendaftaran ditolak.';
        } else {
            $pFlash = 'Aksi tidak dikenal.';
            $pType = 'err';
        }
    }

    if ($pFlash !== '') {
        $_SESSION['_pend_flash'] = [$pFlash, $pType];
    }
    header('Location: ' . url('admin/pendaftar.php'));
    exit;
}

$list = db()->query(
    "SELECT id, username, nama_lengkap, email, created_at FROM users WHERE role = 'mahasiswa' AND approval_status = 'pending' ORDER BY created_at ASC"
)->fetchAll();

$csrf = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');

require dirname(__DIR__) . '/includes/admin_header.php';
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900">Pendaftar baru</h1>
    <p class="mt-1 text-slate-600">Setujui atau tolak akun mahasiswa yang mendaftar mandiri. Jika SMTP di <code class="rounded bg-slate-100 px-1 text-xs">config/mail.php</code> sudah diisi, email pemberitahuan otomatis terkirim saat disetujui.</p>
</div>

<?php if ($flash !== ''): ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $flashType === 'ok'
        ? 'border-emerald-200 bg-emerald-50 text-emerald-900'
        : 'border-rose-200 bg-rose-50 text-rose-900' ?>"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
            <tr class="bg-slate-50 text-left text-slate-600">
                <th class="px-4 py-3 font-medium">Tanggal</th>
                <th class="px-4 py-3 font-medium">Username</th>
                <th class="px-4 py-3 font-medium">Nama</th>
                <th class="px-4 py-3 font-medium">Email</th>
                <th class="px-4 py-3 font-medium text-right">Aksi</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php if (count($list) === 0): ?>
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-slate-500">Tidak ada pendaftar yang menunggu persetujuan.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($list as $p): ?>
                    <tr class="hover:bg-slate-50/80">
                        <td class="whitespace-nowrap px-4 py-3 text-slate-700"><?= htmlspecialchars((string)$p['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3 font-medium text-slate-900"><?= htmlspecialchars((string)$p['username'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars((string)($p['nama_lengkap'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars((string)$p['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <form method="post" class="inline" onsubmit="return confirm('Setujui akun ini?');">
                                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="setujui">
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <button type="submit" class="font-semibold text-emerald-600 hover:text-emerald-800">Setujui</button>
                            </form>
                            <form method="post" class="ml-3 inline" onsubmit="return confirm('Tolak pendaftaran ini?');">
                                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="tolak">
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <button type="submit" class="font-semibold text-rose-600 hover:text-rose-800">Tolak</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/admin_footer.php'; ?>
