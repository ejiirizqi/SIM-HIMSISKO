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

$cu   = current_user();
$myId = (int)($cu['id'] ?? 0);

$editMode = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$form = ['username' => '', 'nama_lengkap' => '', 'email' => '', 'password' => ''];

if ($editMode > 0) {
    $st = db()->prepare('SELECT id, username, nama_lengkap, email FROM users WHERE id=? AND role=\'mahasiswa\' LIMIT 1');
    $st->execute([$editMode]);
    $rw = $st->fetch();
    if ($rw) {
        $form = [
            'username'     => (string)$rw['username'],
            'nama_lengkap' => (string)($rw['nama_lengkap'] ?? ''),
            'email'        => (string)($rw['email'] ?? ''),
            'password'     => '',
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
        $pass     = (string)($_POST['password_new'] ?? '');
        $nama     = trim((string)($_POST['nama_new'] ?? ''));
        $email    = trim((string)($_POST['email_new'] ?? ''));

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
                $flash = (int)$e->errorInfo[1] === 1062 ? 'Username sudah dipakai.' : 'Gagal menyimpan: ' . $e->getMessage();
                $flashType = 'err';
            }
        }
    } elseif ($action === 'ubah') {
        $uid      = (int)($_POST['user_id'] ?? 0);
        $username = trim((string)($_POST['username'] ?? ''));
        $nama     = trim((string)($_POST['nama_lengkap'] ?? ''));
        $email    = trim((string)($_POST['email'] ?? ''));
        $passBaru = (string)($_POST['password_baru'] ?? '');

        if ($uid <= 0) {
            $flash = 'Target tidak valid.'; $flashType = 'err';
        } elseif ($username === '') {
            $flash = 'Username tidak boleh kosong.'; $flashType = 'err';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) && $email !== '') {
            $flash = 'Email tidak valid.'; $flashType = 'err';
        } else {
            $cek = db()->prepare('SELECT id FROM users WHERE id=? AND role=\'mahasiswa\' LIMIT 1');
            $cek->execute([$uid]);
            if (!$cek->fetch()) {
                $flash = 'Mahasiswa tidak ditemukan.'; $flashType = 'err';
            } else {
                try {
                    if ($passBaru !== '') {
                        if (strlen($passBaru) < 6) {
                            $flash = 'Password baru minimal 6 karakter.'; $flashType = 'err';
                        } else {
                            $stmt = db()->prepare('UPDATE users SET username=?, nama_lengkap=?, email=?, password=? WHERE id=? LIMIT 1');
                            $stmt->execute([$username, $nama === '' ? null : $nama, $email === '' ? null : $email, password_hash($passBaru, PASSWORD_DEFAULT), $uid]);
                            log_activity((int)current_user()['id'], 'admin', (string)(current_user()['username'] ?? ''), 'Ubah data mahasiswa', 'ID: ' . $uid . ' Username: ' . $username);
                            header('Location: ' . url('admin/mahasiswa.php')); exit;
                        }
                    } else {
                        $stmt = db()->prepare('UPDATE users SET username=?, nama_lengkap=?, email=? WHERE id=? LIMIT 1');
                        $stmt->execute([$username, $nama === '' ? null : $nama, $email === '' ? null : $email, $uid]);
                        log_activity((int)current_user()['id'], 'admin', (string)(current_user()['username'] ?? ''), 'Ubah data mahasiswa', 'ID: ' . $uid . ' Username: ' . $username);
                        header('Location: ' . url('admin/mahasiswa.php')); exit;
                    }
                } catch (PDOException $e) {
                    $flash = (int)$e->errorInfo[1] === 1062 ? 'Username sudah dipakai.' : 'Gagal memperbarui.';
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
            header('Location: ' . url('admin/mahasiswa.php')); exit;
        }
    }
}

$list = db()->query(
    'SELECT id, username, nama_lengkap, email, approval_status, created_at FROM users WHERE role=\'mahasiswa\' ORDER BY created_at DESC'
)->fetchAll();

$csrf = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');

require dirname(__DIR__) . '/includes/admin_header.php';
?>

<style>
    /* ── Page-level tokens ─────────────────────── */
    .mhs-page { --clr-ink: #0f172a; --clr-muted: #64748b; --clr-border: #e2e8f0; --clr-surface: #fff; --clr-bg: #f8fafc; --clr-accent: #2563eb; --clr-accent-light: #eff6ff; --clr-danger: #dc2626; --clr-danger-light: #fef2f2; --clr-ok: #059669; --clr-ok-light: #ecfdf5; --radius: .75rem; }

    /* ── Page header ───────────────────────────── */
    .page-header { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 2rem; }
    .page-header-left h1 { font-size: 1.375rem; font-weight: 800; color: var(--clr-ink); letter-spacing: -.025em; line-height: 1.2; }
    .page-header-left p  { font-size: .8125rem; color: var(--clr-muted); margin-top: .25rem; }

    /* ── Flash ─────────────────────────────────── */
    .flash { display: flex; align-items: flex-start; gap: .75rem; padding: .875rem 1rem; border-radius: var(--radius); font-size: .8125rem; font-weight: 500; margin-bottom: 1.5rem; border: 1px solid; }
    .flash--ok  { background: var(--clr-ok-light);     border-color: #a7f3d0; color: #064e3b; }
    .flash--err { background: var(--clr-danger-light); border-color: #fecaca; color: #7f1d1d; }
    .flash svg  { flex-shrink: 0; width: 1rem; height: 1rem; margin-top: .0625rem; }

    /* ── Stats bar ─────────────────────────────── */
    .stats-bar { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; margin-bottom: 2rem; }
    @media (max-width: 639px) { .stats-bar { grid-template-columns: repeat(3, 1fr); gap: .5rem; } }
    .stat-card { background: var(--clr-surface); border: 1px solid var(--clr-border); border-radius: var(--radius); padding: .875rem 1rem; }
    .stat-card-value { font-size: 1.5rem; font-weight: 800; color: var(--clr-ink); line-height: 1; }
    .stat-card-label { font-size: .6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--clr-muted); margin-top: .25rem; }
    @media (max-width: 479px) { .stat-card-value { font-size: 1.25rem; } .stat-card-label { font-size: .5625rem; } }

    /* ── Layout grid ───────────────────────────── */
    .content-grid { display: grid; gap: 1.5rem; margin-bottom: 2rem; }
    @media (min-width: 1024px) { .content-grid { grid-template-columns: 20rem 1fr; } }

    /* ── Card ──────────────────────────────────── */
    .card { background: var(--clr-surface); border: 1px solid var(--clr-border); border-radius: var(--radius); overflow: hidden; }
    .card-header { display: flex; align-items: center; gap: .75rem; padding: 1.125rem 1.25rem; border-bottom: 1px solid var(--clr-border); }
    .card-header-icon { width: 2rem; height: 2rem; border-radius: .5rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .card-header-icon--blue { background: var(--clr-accent-light); color: var(--clr-accent); }
    .card-header-icon--amber { background: #fffbeb; color: #d97706; }
    .card-header-icon svg { width: 1rem; height: 1rem; }
    .card-header-title { font-size: .9375rem; font-weight: 700; color: var(--clr-ink); flex: 1; min-width: 0; }
    .card-header-action { font-size: .75rem; font-weight: 600; color: var(--clr-muted); text-decoration: none; padding: .25rem .625rem; border-radius: .375rem; transition: background .12s, color .12s; }
    .card-header-action:hover { background: var(--clr-bg); color: var(--clr-ink); }
    .card-body { padding: 1.25rem; }

    /* ── Form elements ─────────────────────────── */
    .form-group { display: flex; flex-direction: column; gap: .375rem; margin-bottom: .875rem; }
    .form-group:last-of-type { margin-bottom: 0; }
    .form-label { font-size: .75rem; font-weight: 600; color: var(--clr-ink); }
    .form-label span { color: var(--clr-muted); font-weight: 400; }
    .form-input {
        width: 100%;
        padding: .5625rem .875rem;
        border: 1px solid var(--clr-border);
        border-radius: .5rem;
        font-size: .8125rem;
        font-family: inherit;
        color: var(--clr-ink);
        background: var(--clr-bg);
        transition: border-color .14s, box-shadow .14s;
        outline: none;
    }
    .form-input:focus { border-color: var(--clr-accent); box-shadow: 0 0 0 3px rgba(37,99,235,.1); background: #fff; }
    .form-input::placeholder { color: #94a3b8; }

    /* ── Buttons ───────────────────────────────── */
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; padding: .5625rem 1.125rem; border-radius: .5rem; font-size: .8125rem; font-weight: 600; font-family: inherit; cursor: pointer; border: 1px solid transparent; transition: background .14s, border-color .14s, box-shadow .14s; text-decoration: none; }
    .btn svg { width: .875rem; height: .875rem; flex-shrink: 0; }
    .btn--primary { background: #0f172a; color: #fff; }
    .btn--primary:hover { background: #1e293b; }
    .btn--primary-w { width: 100%; }
    .btn--ghost { background: transparent; color: var(--clr-muted); border-color: var(--clr-border); }
    .btn--ghost:hover { background: var(--clr-bg); color: var(--clr-ink); }
    .btn--danger-ghost { background: transparent; color: var(--clr-danger); border: none; padding: 0; font-size: .75rem; font-weight: 600; font-family: inherit; cursor: pointer; }
    .btn--danger-ghost:hover { text-decoration: underline; }
    .btn--edit-ghost { background: transparent; color: var(--clr-accent); border: none; padding: 0; font-size: .75rem; font-weight: 600; font-family: inherit; cursor: pointer; text-decoration: none; }
    .btn--edit-ghost:hover { text-decoration: underline; }

    /* ── Table wrapper ─────────────────────────── */
    .table-card { background: var(--clr-surface); border: 1px solid var(--clr-border); border-radius: var(--radius); overflow: hidden; }

    .table-top { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--clr-border); }
    .table-top-title { font-size: .9375rem; font-weight: 700; color: var(--clr-ink); }
    .table-count { font-size: .75rem; font-weight: 600; color: var(--clr-muted); background: var(--clr-bg); border: 1px solid var(--clr-border); border-radius: 999px; padding: .1875rem .625rem; }

    /* Search */
    .table-search-wrap { position: relative; }
    .table-search-wrap svg { position: absolute; left: .625rem; top: 50%; transform: translateY(-50%); width: .875rem; height: .875rem; color: #94a3b8; pointer-events: none; }
    .table-search { padding: .4375rem .75rem .4375rem 2rem; border: 1px solid var(--clr-border); border-radius: .5rem; font-size: .8125rem; font-family: inherit; color: var(--clr-ink); background: var(--clr-bg); outline: none; width: 14rem; transition: border-color .14s, box-shadow .14s; }
    .table-search:focus { border-color: var(--clr-accent); box-shadow: 0 0 0 3px rgba(37,99,235,.1); background: #fff; width: 16rem; }
    @media (max-width: 479px) { .table-search { width: 100%; } .table-search:focus { width: 100%; } }

    /* Desktop table */
    .data-table { width: 100%; border-collapse: collapse; font-size: .8125rem; }
    .data-table thead tr { background: #f8fafc; }
    .data-table th { padding: .75rem 1.25rem; text-align: left; font-size: .625rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--clr-muted); white-space: nowrap; border-bottom: 1px solid var(--clr-border); }
    .data-table td { padding: .875rem 1.25rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; color: var(--clr-ink); }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .data-table tbody tr:hover td { background: #fafbfc; }

    /* Avatar cell */
    .user-cell { display: flex; align-items: center; gap: .75rem; }
    .user-avatar { width: 2rem; height: 2rem; border-radius: .5rem; background: linear-gradient(135deg, #dbeafe, #eff6ff); display: flex; align-items: center; justify-content: center; font-size: .6875rem; font-weight: 700; color: #1d4ed8; flex-shrink: 0; border: 1px solid #bfdbfe; overflow: hidden; }
    .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .user-name { font-weight: 600; color: var(--clr-ink); line-height: 1.2; }
    .user-meta { font-size: .6875rem; color: var(--clr-muted); margin-top: .125rem; }

    /* Status badge */
    .status-badge { display: inline-flex; align-items: center; gap: .3125rem; padding: .25rem .625rem; border-radius: 999px; font-size: .625rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; white-space: nowrap; }
    .status-badge::before { content: ''; width: .375rem; height: .375rem; border-radius: 50%; flex-shrink: 0; }
    .status-badge--active  { background: #dcfce7; color: #14532d; } .status-badge--active::before  { background: #16a34a; }
    .status-badge--pending { background: #fef9c3; color: #713f12; } .status-badge--pending::before { background: #ca8a04; }
    .status-badge--rejected{ background: #fee2e2; color: #7f1d1d; } .status-badge--rejected::before{ background: #dc2626; }

    /* Actions cell */
    .actions-cell { display: flex; align-items: center; justify-content: flex-end; gap: .875rem; }

    /* Mobile card list */
    .mobile-list { display: none; }
    @media (max-width: 767px) {
        .desktop-table { display: none; }
        .mobile-list { display: flex; flex-direction: column; }
    }

    .mhs-card { padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; transition: background .12s; }
    .mhs-card:last-child { border-bottom: none; }
    .mhs-card:hover { background: #fafbfc; }
    .mhs-card-top { display: flex; align-items: center; gap: .875rem; }
    .mhs-card-info { flex: 1; min-width: 0; }
    .mhs-card-name { font-size: .875rem; font-weight: 700; color: var(--clr-ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mhs-card-username { font-size: .75rem; color: var(--clr-muted); margin-top: .0625rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mhs-card-footer { display: flex; align-items: center; justify-content: space-between; margin-top: .75rem; padding-top: .625rem; border-top: 1px solid #f1f5f9; }
    .mhs-card-email { font-size: .6875rem; color: var(--clr-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; min-width: 0; margin-right: .75rem; }

    /* Empty state */
    .empty-state { padding: 3.5rem 1.25rem; text-align: center; }
    .empty-state-icon { width: 3rem; height: 3rem; border-radius: 50%; background: var(--clr-bg); border: 1px solid var(--clr-border); display: flex; align-items: center; justify-content: center; margin: 0 auto .875rem; }
    .empty-state-icon svg { width: 1.25rem; height: 1.25rem; color: #94a3b8; }
    .empty-state-title { font-size: .9375rem; font-weight: 700; color: var(--clr-ink); }
    .empty-state-desc  { font-size: .8125rem; color: var(--clr-muted); margin-top: .25rem; }
</style>

<div class="mhs-page">

    <!-- Page header -->
    <div class="page-header">
        <div class="page-header-left">
            <h1>Data Mahasiswa</h1>
            <p>Kelola akun mahasiswa — password disimpan dalam bentuk hash</p>
        </div>
    </div>

    <?php if ($flash !== ''): ?>
    <div class="flash flash--<?= $flashType === 'ok' ? 'ok' : 'err' ?>">
        <?php if ($flashType === 'ok'): ?>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0"/></svg>
        <?php else: ?>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?php endif; ?>
        <?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <?php
    /* ── Stats ──────────────────────────────── */
    $total    = count($list);
    $aktif    = count(array_filter($list, fn($r) => ($r['approval_status'] ?? '') === 'active'));
    $pending  = count(array_filter($list, fn($r) => ($r['approval_status'] ?? '') === 'pending'));
    ?>
    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-card-value"><?= $total ?></div>
            <div class="stat-card-label">Total</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-value" style="color:#059669"><?= $aktif ?></div>
            <div class="stat-card-label">Aktif</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-value" style="color:#d97706"><?= $pending ?></div>
            <div class="stat-card-label">Pending</div>
        </div>
    </div>

    <!-- Content grid: form + table -->
    <div class="content-grid">

        <!-- ── Form panel ───────────────────── -->
        <?php if ($editMode): ?>
        <div class="card" style="align-self: start;">
            <div class="card-header">
                <div class="card-header-icon card-header-icon--amber">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <span class="card-header-title">Edit Mahasiswa</span>
                <a href="<?= htmlspecialchars(url('admin/mahasiswa.php'), ENT_QUOTES, 'UTF-8') ?>" class="card-header-action">Tutup ✕</a>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="ubah">
                    <input type="hidden" name="user_id" value="<?= $editMode ?>">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input name="username" required class="form-input" value="<?= htmlspecialchars($form['username'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap <span>(opsional)</span></label>
                        <input name="nama_lengkap" class="form-input" value="<?= htmlspecialchars($form['nama_lengkap'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span>(opsional)</span></label>
                        <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($form['email'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:1.25rem">
                        <label class="form-label">Password Baru <span>(kosongkan jika tidak diubah)</span></label>
                        <input type="password" name="password_baru" class="form-input" placeholder="min. 6 karakter">
                    </div>
                    <button type="submit" class="btn btn--primary btn--primary-w">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>

        <?php else: ?>

        <div class="card" style="align-self: start;">
            <div class="card-header">
                <div class="card-header-icon card-header-icon--blue">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="17" y1="11" x2="23" y2="11"/></svg>
                </div>
                <span class="card-header-title">Tambah Mahasiswa</span>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="tambah">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input name="username_new" required class="form-input" placeholder="Contoh: budi2024">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password_new" required class="form-input" placeholder="Minimal 6 karakter">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap <span>(opsional)</span></label>
                        <input name="nama_new" class="form-input" placeholder="Nama lengkap mahasiswa">
                    </div>
                    <div class="form-group" style="margin-bottom:1.25rem">
                        <label class="form-label">Email <span>(opsional)</span></label>
                        <input type="email" name="email_new" class="form-input" placeholder="email@example.com">
                    </div>
                    <button type="submit" class="btn btn--primary btn--primary-w">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Tambahkan
                    </button>
                </form>
            </div>
        </div>

        <?php endif; ?>

        <!-- ── Table panel ───────────────────── -->
        <div class="table-card">
            <div class="table-top">
                <div style="display:flex;align-items:center;gap:.625rem;">
                    <span class="table-top-title">Daftar Mahasiswa</span>
                    <span class="table-count"><?= $total ?> akun</span>
                </div>
                <div class="table-search-wrap">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <input type="search" class="table-search" placeholder="Cari mahasiswa..." id="mhs-search">
                </div>
            </div>

            <!-- Desktop table -->
            <div class="desktop-table">
                <table class="data-table" id="mhs-table">
                    <thead>
                        <tr>
                            <th>Mahasiswa</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Bergabung</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($list as $m):
                        $ap    = (string)($m['approval_status'] ?? 'active');
                        $apMap = ['pending' => ['pending', 'Pending'], 'rejected' => ['rejected', 'Ditolak'], 'active' => ['active', 'Aktif']];
                        [$apCls, $apLab] = $apMap[$ap] ?? ['active', 'Aktif'];
                        $inisial = mb_strtoupper(mb_substr((string)($m['username'] ?? '?'), 0, 1));
                        $tgl = !empty($m['created_at']) ? date('d M Y', strtotime((string)$m['created_at'])) : '-';
                    ?>
                    <tr data-search="<?= htmlspecialchars(strtolower(($m['username'] ?? '') . ' ' . ($m['nama_lengkap'] ?? '') . ' ' . ($m['email'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                        <td>
                            <div class="user-cell">
                                <div class="user-avatar"><?= htmlspecialchars($inisial, ENT_QUOTES, 'UTF-8') ?></div>
                                <div>
                                    <div class="user-name"><?= htmlspecialchars((string)($m['nama_lengkap'] ?: $m['username']), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="user-meta">@<?= htmlspecialchars((string)$m['username'], ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--clr-muted)"><?= htmlspecialchars((string)($m['email'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="status-badge status-badge--<?= $apCls ?>"><?= htmlspecialchars($apLab, ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td style="color:var(--clr-muted);white-space:nowrap;font-size:.75rem"><?= $tgl ?></td>
                        <td>
                            <div class="actions-cell">
                                <a href="<?= htmlspecialchars(url('admin/mahasiswa.php?edit=' . (int)$m['id']), ENT_QUOTES, 'UTF-8') ?>" class="btn--edit-ghost">Edit</a>
                                <form method="post" onsubmit="return confirm('Yakin menghapus akun ini?');" style="display:inline">
                                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="hapus">
                                    <input type="hidden" name="user_id" value="<?= (int)$m['id'] ?>">
                                    <button type="submit" class="btn--danger-ghost">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (count($list) === 0): ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <div class="empty-state-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
                                <div class="empty-state-title">Belum ada mahasiswa</div>
                                <div class="empty-state-desc">Tambahkan akun mahasiswa menggunakan form di samping.</div>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile card list -->
            <div class="mobile-list" id="mhs-mobile-list">
                <?php foreach ($list as $m):
                    $ap    = (string)($m['approval_status'] ?? 'active');
                    $apMap = ['pending' => ['pending', 'Pending'], 'rejected' => ['rejected', 'Ditolak'], 'active' => ['active', 'Aktif']];
                    [$apCls, $apLab] = $apMap[$ap] ?? ['active', 'Aktif'];
                    $inisial = mb_strtoupper(mb_substr((string)($m['username'] ?? '?'), 0, 1));
                ?>
                <div class="mhs-card" data-search="<?= htmlspecialchars(strtolower(($m['username'] ?? '') . ' ' . ($m['nama_lengkap'] ?? '') . ' ' . ($m['email'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mhs-card-top">
                        <div class="user-avatar" style="width:2.5rem;height:2.5rem;font-size:.8125rem;flex-shrink:0"><?= htmlspecialchars($inisial, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="mhs-card-info">
                            <div class="mhs-card-name"><?= htmlspecialchars((string)($m['nama_lengkap'] ?: $m['username']), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="mhs-card-username">@<?= htmlspecialchars((string)$m['username'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <span class="status-badge status-badge--<?= $apCls ?>"><?= htmlspecialchars($apLab, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="mhs-card-footer">
                        <div class="mhs-card-email"><?= htmlspecialchars((string)($m['email'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></div>
                        <div style="display:flex;align-items:center;gap:.75rem;flex-shrink:0">
                            <a href="<?= htmlspecialchars(url('admin/mahasiswa.php?edit=' . (int)$m['id']), ENT_QUOTES, 'UTF-8') ?>" class="btn--edit-ghost">Edit</a>
                            <form method="post" onsubmit="return confirm('Yakin menghapus akun ini?');" style="display:inline">
                                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="hapus">
                                <input type="hidden" name="user_id" value="<?= (int)$m['id'] ?>">
                                <button type="submit" class="btn--danger-ghost">Hapus</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (count($list) === 0): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
                    <div class="empty-state-title">Belum ada mahasiswa</div>
                    <div class="empty-state-desc">Tambahkan akun mahasiswa menggunakan form di atas.</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /.content-grid -->

</div><!-- /.mhs-page -->

<script>
(function () {
    const input      = document.getElementById('mhs-search');
    const tableRows  = document.querySelectorAll('#mhs-table tbody tr[data-search]');
    const mobileCards = document.querySelectorAll('#mhs-mobile-list .mhs-card[data-search]');

    if (!input) return;

    input.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();

        tableRows.forEach(function (tr) {
            tr.style.display = (!q || tr.dataset.search.includes(q)) ? '' : 'none';
        });

        mobileCards.forEach(function (card) {
            card.style.display = (!q || card.dataset.search.includes(q)) ? '' : 'none';
        });
    });
})();
</script>

<?php
require dirname(__DIR__) . '/includes/admin_footer.php';