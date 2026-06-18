<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/helpers/keuangan_ringkasan.php';

require_role('admin');

$pageTitle = 'Laporan Keuangan';
$activeNav = 'keuangan';

$flash = '';
$flashType = 'ok';

$editRow = null;
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

if ($editId > 0) {
    $st = db()->prepare('SELECT id, tipe, nominal, keterangan, tanggal FROM keuangan WHERE id = ? LIMIT 1');
    $st->execute([$editId]);
    $editRow = $st->fetch() ?: null;
    if (!$editRow) {
        $editId = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate($_POST['_csrf'] ?? null);
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'tambah') {
        $tipe = (string)($_POST['tipe'] ?? '');
        $nominalRaw = preg_replace('/[^\d]/', '', (string)($_POST['nominal'] ?? '')) ?? '';
        $nominal = (int)$nominalRaw;
        $keterangan = trim((string)($_POST['keterangan'] ?? ''));
        $tanggal = (string)($_POST['tanggal'] ?? '');

        if (!in_array($tipe, ['masuk', 'keluar'], true)) {
            $flash = 'Tipe tidak valid.';
            $flashType = 'err';
        } elseif ($nominal <= 0) {
            $flash = 'Nominal harus lebih dari 0.';
            $flashType = 'err';
        } elseif ($tanggal === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            $flash = 'Tanggal tidak valid.';
            $flashType = 'err';
        } else {
            $stmt = db()->prepare('INSERT INTO keuangan (tipe, nominal, keterangan, tanggal) VALUES (?, ?, ?, ?)');
            $stmt->execute([$tipe, $nominal, $keterangan === '' ? null : $keterangan, $tanggal]);
            log_activity((int)current_user()['id'], 'admin', (string)(current_user()['username'] ?? ''), 'Tambah transaksi keuangan', sprintf('Tipe: %s Nominal: %s', $tipe, number_format($nominal, 0, ',', '.')));
            $flash = 'Transaksi berhasil dicatat.';
            header('Location: ' . url('admin/keuangan.php'));
            exit;
        }
    } elseif ($action === 'ubah') {
        $tid = (int)($_POST['id'] ?? 0);
        $tipe = (string)($_POST['tipe'] ?? '');
        $nominalRaw = preg_replace('/[^\d]/', '', (string)($_POST['nominal_edit'] ?? '')) ?? '';
        $nominal = (int)$nominalRaw;
        $keterangan = trim((string)($_POST['keterangan_edit'] ?? ''));
        $tanggal = (string)($_POST['tanggal_edit'] ?? '');

        if ($tid <= 0) {
            $flash = 'Data tidak valid.';
            $flashType = 'err';
        } elseif (!in_array($tipe, ['masuk', 'keluar'], true)) {
            $flash = 'Tipe tidak valid.';
            $flashType = 'err';
        } elseif ($nominal <= 0) {
            $flash = 'Nominal harus lebih dari 0.';
            $flashType = 'err';
        } elseif ($tanggal === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            $flash = 'Tanggal tidak valid.';
            $flashType = 'err';
        } else {
            $stmt = db()->prepare('UPDATE keuangan SET tipe = ?, nominal = ?, keterangan = ?, tanggal = ? WHERE id = ? LIMIT 1');
            $stmt->execute([$tipe, $nominal, $keterangan === '' ? null : $keterangan, $tanggal, $tid]);
            log_activity((int)current_user()['id'], 'admin', (string)(current_user()['username'] ?? ''), 'Ubah transaksi keuangan', sprintf('ID: %d Tipe: %s', $tid, $tipe));
            $flash = 'Transaksi berhasil diperbarui.';
            header('Location: ' . url('admin/keuangan.php'));
            exit;
        }
    } elseif ($action === 'hapus') {
        $hid = (int)($_POST['id'] ?? 0);
        if ($hid > 0) {
            $stmt = db()->prepare('DELETE FROM keuangan WHERE id = ? LIMIT 1');
            $stmt->execute([$hid]);
            log_activity((int)current_user()['id'], 'admin', (string)(current_user()['username'] ?? ''), 'Hapus transaksi keuangan', 'ID: ' . $hid);
            $flash = 'Data keuangan dihapus.';
            header('Location: ' . url('admin/keuangan.php'));
            exit;
        }
    }
}

$kas = keuangan_ringkasan(db());
$stmt = db()->query('SELECT id, tipe, nominal, keterangan, tanggal, created_at FROM keuangan ORDER BY tanggal DESC, id DESC');
$rows = $stmt->fetchAll();

$csrf = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
$today = date('Y-m-d');
$prefillNom = $editRow ? (string)(int)$editRow['nominal'] : '';

require dirname(__DIR__) . '/includes/admin_header.php';
?>
<div class="mb-8 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">CRUD laporan keuangan</h1>
        <p class="text-slate-600 mt-1">Pemasukan, pengeluaran, tanggal, keterangan — total saldo dihitung otomatis.</p>
    </div>
    <div class="flex flex-wrap gap-3">
        <a href="<?= htmlspecialchars(url('publik/pdf_laporan_keuangan.php'), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 shadow-sm">Ekspor PDF</a>
        <button type="button" onclick="window.print()" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Cetak</button>
    </div>
</div>

<?php if ($flash !== ''): ?>
    <div class="mb-6 rounded-lg border px-4 py-3 text-sm <?= $flashType === 'ok' ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900' ?>">
        <?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="grid gap-6 lg:grid-cols-4 mb-10">
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
        <div class="text-sm font-medium text-emerald-800">Total pemasukan</div>
        <div class="mt-1 text-xl font-bold text-emerald-900"><?= htmlspecialchars(format_rupiah($kas['pemasukan']), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="rounded-xl border border-rose-200 bg-rose-50 p-5">
        <div class="text-sm font-medium text-rose-800">Total pengeluaran</div>
        <div class="mt-1 text-xl font-bold text-rose-900"><?= htmlspecialchars(format_rupiah($kas['pengeluaran']), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
        <div class="text-sm font-medium text-slate-500">Total saldo (kas)</div>
        <div class="mt-1 text-2xl font-bold <?= $kas['saldo'] >= 0 ? 'text-slate-900' : 'text-rose-600' ?>">
            <?= htmlspecialchars(format_rupiah($kas['saldo']), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <p class="text-xs text-slate-500 mt-2">Σ pemasukan − Σ pengeluaran</p>
    </div>
</div>

<div class="grid gap-8 lg:grid-cols-5">
    <div class="lg:col-span-2 space-y-8">
        <?php if ($editRow): ?>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Edit transaksi #<?= (int)$editRow['id'] ?></h2>
                <form method="post" action="" class="mt-4 space-y-4">
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="ubah">
                    <input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Tipe transaksi</label>
                        <select name="tipe" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="masuk" <?= ($editRow['tipe'] === 'masuk') ? 'selected' : '' ?>>Pemasukan</option>
                            <option value="keluar" <?= ($editRow['tipe'] === 'keluar') ? 'selected' : '' ?>>Pengeluaran</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Nominal (Rp)</label>
                        <input type="text" name="nominal_edit" inputmode="numeric" required value="<?= htmlspecialchars($prefillNom, ENT_QUOTES, 'UTF-8') ?>"
                               class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Tanggal transaksi</label>
                        <input type="date" name="tanggal_edit" required value="<?= htmlspecialchars((string)$editRow['tanggal'], ENT_QUOTES, 'UTF-8') ?>"
                               class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Keterangan</label>
                        <textarea name="keterangan_edit" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"><?= htmlspecialchars((string)($editRow['keterangan'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="rounded-lg bg-slate-900 text-white font-semibold px-5 py-2.5 hover:bg-slate-800">Perbarui</button>
                        <a href="<?= htmlspecialchars(url('admin/keuangan.php'), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg border border-slate-300 px-5 py-2.5 font-medium text-slate-700 hover:bg-white">Batal edit</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Tambah transaksi</h2>
                <form method="post" class="mt-4 space-y-4">
                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="tambah">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Tipe</label>
                        <select name="tipe" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="masuk">Pemasukan</option>
                            <option value="keluar">Pengeluaran</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Nominal (Rp)</label>
                        <input type="text" name="nominal" inputmode="numeric" required placeholder="Contoh: 500000"
                               class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Tanggal transaksi</label>
                        <input type="date" name="tanggal" required value="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8') ?>"
                               class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Keterangan</label>
                        <textarea name="keterangan" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-slate-900 text-white font-semibold py-2.5 hover:bg-slate-800">Simpan</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="lg:col-span-3 rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Riwayat transaksi</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                <tr class="bg-slate-50 text-left text-slate-600">
                    <th class="px-4 py-3 font-medium">Tanggal</th>
                    <th class="px-4 py-3 font-medium">Tipe</th>
                    <th class="px-4 py-3 font-medium">Nominal</th>
                    <th class="px-4 py-3 font-medium">Keterangan</th>
                    <th class="px-4 py-3 font-medium text-right">Aksi</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php if (count($rows) === 0): ?>
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada transaksi.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-4 py-3 text-slate-800 whitespace-nowrap"><?= htmlspecialchars((string)$row['tanggal'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3">
                                <?php if ($row['tipe'] === 'masuk'): ?>
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">Masuk</span>
                                <?php else: ?>
                                    <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-800">Keluar</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-900 whitespace-nowrap"><?= htmlspecialchars(format_rupiah((int)$row['nominal']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($row['keterangan'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="<?= htmlspecialchars(url('admin/keuangan.php?edit=' . (int)$row['id']), ENT_QUOTES, 'UTF-8') ?>" class="text-indigo-600 font-semibold hover:underline">Edit</a>
                                <form method="post" class="inline ml-3" onsubmit="return confirm('Hapus transaksi ini?');">
                                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="hapus">
                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                    <button type="submit" class="text-rose-600 hover:text-rose-800 font-semibold">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
require dirname(__DIR__) . '/includes/admin_footer.php';
