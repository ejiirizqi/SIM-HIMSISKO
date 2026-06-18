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

    // Statistik status kegiatan
    $stmt = $pdo->query("SELECT status, COUNT(*) AS c FROM kegiatan GROUP BY status");
    $statusCounts = [];
    foreach ($stmt->fetchAll() as $r) {
        $statusCounts[(string)$r['status']] = (int)$r['c'];
    }

    // Kegiatan mendatang (rencana atau berlangsung), urut naik berdasarkan tanggal
    $stmt = $pdo->query("SELECT id, judul, tanggal, lokasi, status FROM kegiatan WHERE status IN ('rencana','berlangsung') ORDER BY tanggal ASC, id ASC LIMIT 8");
    $upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

/**
 * Small inline icon set so the dashboard doesn't depend on an external
 * icon font/library.
 */
function dash_icon(string $name, string $class = 'h-5 w-5'): string
{
    $paths = match ($name) {
        'calendar' => '<rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M16 2.5v4M8 2.5v4M3 9.5h18"/>',
        'megaphone' => '<path d="M3 10v4a1 1 0 0 0 1 1h2l4.5 3.2a1 1 0 0 0 1.5-.86V6.66a1 1 0 0 0-1.5-.86L6 9H4a1 1 0 0 0-1 1Z"/><path d="M16 8.5a4 4 0 0 1 0 7"/><path d="M19 6a7 7 0 0 1 0 12"/>',
        'wallet' => '<path d="M3 8a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v3"/><path d="M3 8v9a2 2 0 0 0 2 2h14a1 1 0 0 0 1-1v-6a1 1 0 0 0-1-1h-4.5a2.5 2.5 0 0 0 0 5H21"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7.25v5l3.5 2"/>',
        'play' => '<path d="M7.5 5.5v13L18.5 12Z"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="M8.25 12.5 11 15.25l4.75-6"/>',
        'map-pin' => '<path d="M12 21s7-6.42 7-11.5A7 7 0 0 0 5 9.5C5 14.58 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.25"/>',
        'alert-triangle' => '<path d="M12 4 3 19h18Z"/><path d="M12 9.5v4M12 16.25h.01"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        default => '',
    };

    return sprintf(
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="%s">%s</svg>',
        htmlspecialchars($class, ENT_QUOTES, 'UTF-8'),
        $paths
    );
}

require dirname(__DIR__) . '/includes/mhs_header.php';
?>

<div class="space-y-8 max-w-7xl mx-auto px-4 py-2 sm:px-6 lg:px-8">

    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-indigo-900 px-6 py-10 text-white shadow-xl sm:px-12 sm:py-12 border border-slate-800">
        <div class="pointer-events-none absolute -right-10 -top-10 h-72 w-72 rounded-full bg-indigo-500/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-20 left-1/4 h-80 w-80 rounded-full bg-violet-600/15 blur-3xl"></div>

        <div class="relative flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div class="space-y-2">
                <span class="inline-flex items-center rounded-full bg-indigo-500/10 px-3 py-1 text-xs font-semibold text-indigo-300 ring-1 ring-inset ring-indigo-500/20 backdrop-blur-md">
                    Portal Mahasiswa
                </span>
                <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl bg-gradient-to-r from-white via-slate-100 to-indigo-200 bg-clip-text text-transparent">
                    Hai, <?= htmlspecialchars($displayName) ?>!
                </h1>
                <p class="max-w-xl text-base text-slate-300/90 leading-relaxed">
                    Pantau ringkasan kegiatan terbaru, info pengumuman penting, dan transparansi arus kas organisasi Anda di sini.
                </p>
            </div>

            <div class="flex shrink-0 items-center gap-4 rounded-2xl bg-white/[0.04] p-5 ring-1 ring-white/10 backdrop-blur-md shadow-inner md:w-64">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-500/20 text-indigo-300 shadow-sm border border-indigo-500/30">
                    <?= dash_icon('calendar', 'h-6 w-6') ?>
                </span>
                <div>
                    <div class="text-3xl font-black tracking-tight text-white"><?= $totalKegiatan ?></div>
                    <div class="text-xs font-medium text-slate-400 mt-0.5">Total Kegiatan Aktif</div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($dbError): ?>
        <div class="flex items-start gap-3 rounded-2xl border border-red-200/60 bg-red-50/50 p-4 text-sm text-red-800 shadow-sm backdrop-blur-sm">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600 border border-red-200">
                <?= dash_icon('alert-triangle', 'h-5 w-5') ?>
            </span>
            <div>
                <div class="font-bold text-red-900">Gagal Memuat Data Terkini</div>
                <div class="mt-0.5 text-red-700/90 font-mono text-xs"><?= htmlspecialchars($dbError) ?></div>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm border border-slate-100 transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-indigo-100">
            <div class="flex items-center justify-between">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 transition-colors group-hover:bg-indigo-100">
                    <?= dash_icon('calendar', 'h-6 w-6') ?>
                </span>
                <span class="text-xs font-semibold text-slate-400 group-hover:text-indigo-500 transition-colors">Program Kerja</span>
            </div>
            <div class="mt-6">
                <div class="text-sm font-medium text-slate-500">Total Agenda Kegiatan</div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-4xl font-extrabold tracking-tight text-slate-900"><?= $totalKegiatan ?></span>
                    <span class="text-xs text-slate-400 font-medium">terdaftar</span>
                </div>
            </div>
        </div>

        <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm border border-slate-100 transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-amber-100">
            <div class="flex items-center justify-between">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600 transition-colors group-hover:bg-amber-100">
                    <?= dash_icon('megaphone', 'h-6 w-6') ?>
                </span>
                <span class="text-xs font-semibold text-slate-400 group-hover:text-amber-500 transition-colors">Informasi</span>
            </div>
            <div class="mt-6">
                <div class="text-sm font-medium text-slate-500">Rilis Pengumuman</div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-4xl font-extrabold tracking-tight text-slate-900"><?= $totalPengumuman ?></span>
                    <span class="text-xs text-slate-400 font-medium">broadcast</span>
                </div>
            </div>
        </div>

        <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm border border-slate-100 transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-emerald-100 sm:col-span-2 lg:col-span-1">
            <div class="flex items-center justify-between">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 transition-colors group-hover:bg-emerald-100">
                    <?= dash_icon('wallet', 'h-6 w-6') ?>
                </span>
                <span class="text-xs font-semibold text-slate-400 group-hover:text-emerald-500 transition-colors">Keuangan</span>
            </div>
            <div class="mt-6">
                <div class="text-sm font-medium text-slate-500">Total Kas Masuk</div>
                <div class="mt-2">
                    <span class="text-3xl font-black tracking-tight text-emerald-600 group-hover:text-emerald-700 transition-colors">
                        Rp <?= number_format($totalKasMasuk, 0, ',', '.') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
            <h3 class="text-sm font-bold text-slate-700 tracking-wide uppercase">Metrik Progres Kegiatan</h3>
        </div>
        <div class="grid grid-cols-1 divide-y divide-slate-100 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            <div class="p-6 flex items-center gap-4 transition-colors hover:bg-slate-50/50">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600 border border-sky-100">
                    <?= dash_icon('clock', 'h-5 w-5') ?>
                </span>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Tahap Perencanaan</p>
                    <p class="mt-1 text-2xl font-bold text-slate-800"><?= htmlspecialchars((string)($statusCounts['rencana'] ?? 0), ENT_QUOTES, 'UTF-8') ?> <span class="text-xs font-normal text-slate-500">Event</span></p>
                </div>
            </div>
            <div class="p-6 flex items-center gap-4 transition-colors hover:bg-slate-50/50">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 border border-amber-100 animate-pulse">
                    <?= dash_icon('play', 'h-5 w-5') ?>
                </span>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Sedang Berlangsung</p>
                    <p class="mt-1 text-2xl font-bold text-slate-800"><?= htmlspecialchars((string)($statusCounts['berlangsung'] ?? 0), ENT_QUOTES, 'UTF-8') ?> <span class="text-xs font-normal text-slate-500">Event</span></p>
                </div>
            </div>
            <div class="p-6 flex items-center gap-4 transition-colors hover:bg-slate-50/50">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100">
                    <?= dash_icon('check-circle', 'h-5 w-5') ?>
                </span>
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Selesai Dilaksanakan</p>
                    <p class="mt-1 text-2xl font-bold text-slate-800"><?= htmlspecialchars((string)($statusCounts['selesai'] ?? 0), ENT_QUOTES, 'UTF-8') ?> <span class="text-xs font-normal text-slate-500">Event</span></p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-5">
        
        <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-100 lg:col-span-2 flex flex-col justify-between">
            <div>
                <div class="mb-6 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 bg-amber-50 text-amber-600 rounded-lg"><?= dash_icon('megaphone', 'h-4 w-4') ?></span>
                        <h2 class="text-lg font-bold text-slate-900">Pengumuman</h2>
                    </div>
                    <a href="<?= htmlspecialchars(url('publik/pengumuman.php'), ENT_QUOTES, 'UTF-8') ?>"
                       class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 transition-all hover:text-indigo-700 hover:gap-1.5">
                        Semua
                        <?= dash_icon('arrow-right', 'h-3.5 w-3.5') ?>
                    </a>
                </div>

                <?php if (empty($listPengumuman)): ?>
                    <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-100 py-12 px-4 text-center text-slate-400">
                        <span class="mb-2 p-2 bg-slate-50 rounded-full text-slate-300"><?= dash_icon('megaphone', 'h-6 w-6') ?></span>
                        <p class="text-xs">Belum ada pengumuman baru saat ini.</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($listPengumuman as $item): ?>
                            <div class="group/item py-3.5 first:pt-0 last:pb-0 transition-all">
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 min-w-[4px] h-4 bg-amber-400 rounded-full opacity-40 group-hover/item:opacity-100 transition-opacity"></div>
                                    <div>
                                        <h4 class="font-semibold text-sm text-slate-800 line-clamp-2 group-hover/item:text-indigo-600 transition-colors leading-snug">
                                            <?= htmlspecialchars($item['judul']) ?>
                                        </h4>
                                        <div class="mt-1 flex items-center gap-1.5 text-[11px] text-slate-400 font-medium">
                                            <span><?= date('d M Y', strtotime($item['created_at'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-100 lg:col-span-3">
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-indigo-50 text-indigo-600 rounded-lg"><?= dash_icon('calendar', 'h-4 w-4') ?></span>
                    <h2 class="text-lg font-bold text-slate-900">Kegiatan Mendatang</h2>
                </div>
                <a href="<?= htmlspecialchars(url('publik/kegiatan.php'), ENT_QUOTES, 'UTF-8') ?>"
                   class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 transition-all hover:text-indigo-700 hover:gap-1.5">
                    Lihat semua
                    <?= dash_icon('arrow-right', 'h-3.5 w-3.5') ?>
                </a>
            </div>

            <?php if (empty($upcoming)): ?>
                <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-100 py-16 px-4 text-center text-slate-400">
                    <span class="mb-2 p-2 bg-slate-50 rounded-full text-slate-300"><?= dash_icon('calendar', 'h-6 w-6') ?></span>
                    <p class="text-xs">Tidak ada agenda kegiatan terdekat dalam waktu dekat.</p>
                </div>
            <?php else: ?>
                <div class="space-y-3.5 max-h-[400px] overflow-y-auto pr-1 subtle-scrollbar">
                    <?php foreach ($upcoming as $keg): 
                        $statusBadge = match ($keg['status']) {
                            'berlangsung' => ['Berlangsung', 'bg-amber-50 text-amber-700 border-amber-100'],
                            'rencana' => ['Terencana', 'bg-sky-50 text-sky-700 border-sky-100'],
                            default => [htmlspecialchars((string)$keg['status'], ENT_QUOTES, 'UTF-8'), 'bg-slate-50 text-slate-600 border-slate-100'],
                        };
                    ?>
                        <div class="group flex flex-col gap-4 rounded-xl border border-slate-100 p-4 transition-all duration-200 hover:border-indigo-100 hover:bg-indigo-50/[0.15] sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-slate-500 border border-slate-200 group-hover:bg-white group-hover:text-indigo-600 group-hover:border-indigo-100 transition-all shadow-sm">
                                    <?= dash_icon('calendar', 'h-4 w-4') ?>
                                </span>
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-bold text-sm text-slate-800 group-hover:text-slate-900 transition-colors"><?= htmlspecialchars($keg['judul'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold tracking-wide <?= $statusBadge[1] ?>"><?= $statusBadge[0] ?></span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                                        <span class="inline-flex items-center gap-1 font-medium">
                                            <?= dash_icon('calendar', 'h-3.5 w-3.5 text-slate-400') ?>
                                            <?= htmlspecialchars($keg['tanggal'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <span class="inline-flex items-center gap-1 font-medium">
                                            <?= dash_icon('map-pin', 'h-3.5 w-3.5 text-slate-400') ?>
                                            <?= htmlspecialchars($keg['lokasi'] ?: '-', ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <a href="<?= htmlspecialchars(url('publik/kegiatan_detail.php?id=' . (int)$keg['id']), ENT_QUOTES, 'UTF-8') ?>"
                               class="inline-flex shrink-0 items-center justify-center gap-1 rounded-lg bg-white border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 shadow-sm transition-all hover:border-indigo-200 hover:bg-indigo-600 hover:text-white self-end sm:self-center">
                                Detail
                                <?= dash_icon('arrow-right', 'h-3.5 w-3.5') ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require dirname(__DIR__) . '/includes/mhs_footer.php'; ?>