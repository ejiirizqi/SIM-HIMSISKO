<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/config/auth.php';

require_role('mahasiswa');

$pageTitle = 'Dashboard Mahasiswa';
$activeNav = 'dashboard';

$u = current_user();

$displayName = trim((string)($u['nama_lengkap'] ?? '')) !== ''
    ? trim((string)$u['nama_lengkap'])
    : (string)($u['username'] ?? '');

$totalKegiatan = 0;
$totalPengumuman = 0;
$totalKasMasuk = 0;
$listPengumuman = [];
$dbError = null;

try {

    $pdo = db();

    /**
     * TOTAL KEGIATAN
     */
    $stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM kegiatan
    ");

    $totalKegiatan = (int)$stmt->fetchColumn();

    /**
     * TOTAL PENGUMUMAN
     */
    $stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM pengumuman
    ");

    $totalPengumuman = (int)$stmt->fetchColumn();

    /**
     * TOTAL KAS MASUK
     */
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(nominal),0)
        FROM keuangan
        WHERE tipe = 'masuk'
    ");

    $totalKasMasuk = (int)$stmt->fetchColumn();

    /**
     * PENGUMUMAN TERBARU
     */
    $stmt = $pdo->query("
        SELECT judul, created_at
        FROM pengumuman
        ORDER BY created_at DESC
        LIMIT 5
    ");

    $listPengumuman = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {

    $dbError = $e->getMessage();

}

require dirname(__DIR__) . '/includes/mhs_header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-900">
        Dashboard Mahasiswa
    </h1>

    <p class="mt-2 text-slate-500">
        Selamat datang,
        <span class="font-semibold text-indigo-600">
            <?= htmlspecialchars($displayName) ?>
        </span>
    </p>
</div>

<?php if ($dbError): ?>
    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        ERROR DATABASE:
        <?= htmlspecialchars($dbError) ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    <!-- KEGIATAN -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="text-sm font-medium text-slate-500">
            Total Kegiatan
        </div>

        <div class="mt-4 text-4xl font-black text-indigo-600">
            <?= $totalKegiatan ?>
        </div>
    </div>

    <!-- PENGUMUMAN -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="text-sm font-medium text-slate-500">
            Pengumuman
        </div>

        <div class="mt-4 text-4xl font-black text-amber-500">
            <?= $totalPengumuman ?>
        </div>
    </div>

    <!-- KAS -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="text-sm font-medium text-slate-500">
            Total Kas Masuk
        </div>

        <div class="mt-4 text-3xl font-black text-emerald-600">
            Rp <?= number_format($totalKasMasuk, 0, ',', '.') ?>
        </div>
    </div>

</div>

<div class="mt-8 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-slate-900">
            Pengumuman Terbaru
        </h2>

        <a href="<?= url('publik/pengumuman.php') ?>"
           class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">
            Lihat semua
        </a>
    </div>

    <?php if (empty($listPengumuman)): ?>

        <div class="text-sm text-slate-400 italic">
            Belum ada pengumuman.
        </div>

    <?php else: ?>

        <div class="space-y-4">

            <?php foreach ($listPengumuman as $item): ?>

                <div class="border-b border-slate-100 pb-4 last:border-none">

                    <div class="font-semibold text-slate-800">
                        <?= htmlspecialchars($item['judul']) ?>
                    </div>

                    <div class="text-xs text-slate-400 mt-1">
                        <?= date('d F Y', strtotime($item['created_at'])) ?>
                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

<?php require dirname(__DIR__) . '/includes/mhs_footer.php'; ?>