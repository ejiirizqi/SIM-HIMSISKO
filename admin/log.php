<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';
require_once dirname(__DIR__) . '/config/auth.php';

require_role('admin');

$pageTitle = 'Log Aktivitas';
$activeNav = 'logs';

// FILTER
$role = isset($_GET['role']) && $_GET['role'] !== 'all'
    ? trim((string)$_GET['role'])
    : null;

$username = isset($_GET['username'])
    ? trim((string)$_GET['username'])
    : null;

$action_filter = isset($_GET['action'])
    ? trim((string)$_GET['action'])
    : null;

$fromDate = isset($_GET['from'])
    ? trim((string)$_GET['from'])
    : null;

$toDate = isset($_GET['to'])
    ? trim((string)$_GET['to'])
    : null;

$isAnyFilterActive =
    ($role !== null) ||
    ($username !== null && $username !== '') ||
    ($action_filter !== null && $action_filter !== '') ||
    ($fromDate !== null && $fromDate !== '') ||
    ($toDate !== null && $toDate !== '');

// PAGINATION
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

try {
    $pdo = db();

    $where = [];
    $params = [];

    if ($role !== null) {
        $where[] = 'user_role = ?';
        $params[] = $role;
    }

    if (!empty($username)) {
        $where[] = 'username LIKE ?';
        $params[] = '%' . $username . '%';
    }

    if (!empty($action_filter)) {
        $where[] = 'action LIKE ?';
        $params[] = '%' . $action_filter . '%';
    }

    if (!empty($fromDate)) {
        $where[] = 'DATE(created_at) >= ?';
        $params[] = $fromDate;
    }

    if (!empty($toDate)) {
        $where[] = 'DATE(created_at) <= ?';
        $params[] = $toDate;
    }

    $whereSql = $where
        ? 'WHERE ' . implode(' AND ', $where)
        : '';

    // TOTAL DATA
    $countStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM activity_logs
        $whereSql
    ");

    $countStmt->execute($params);

    $totalLogs = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalLogs / $perPage));

    // DATA LOG
    $sql = "
        SELECT *
        FROM activity_logs
        $whereSql
        ORDER BY created_at DESC
        LIMIT $perPage OFFSET $offset
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $logs = [];
    $totalPages = 1;

    error_log('[admin/log.php] ' . $e->getMessage());
}

$csrf = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');

require dirname(__DIR__) . '/includes/admin_header.php';
?>

<div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-black text-slate-900 tracking-tight">
            Log Aktivitas
        </h1>

        <p class="mt-1 text-slate-500">
            Memantau riwayat aksi admin dan mahasiswa untuk audit sistem.
        </p>
    </div>

    <div class="flex gap-3">
        <div class="bg-white border border-slate-200 px-4 py-2 rounded-2xl shadow-sm">
            <span class="block text-[10px] uppercase font-bold text-slate-400 tracking-wider">
                Total Log
            </span>

            <span class="text-lg font-bold text-slate-900">
                <?= $totalLogs ?>
            </span>
        </div>
    </div>
</div>

<?php
$roleOptions = [
    'all' => 'Semua',
    'admin' => 'Admin',
    'mahasiswa' => 'Mahasiswa',
];
?>

<div class="mb-6 bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">

<form method="GET" class="px-6 py-5" id="log-filter-form">

    <div class="flex flex-col lg:flex-row lg:items-end gap-4 lg:gap-5">

        <div class="flex-1">
            <label class="block text-xs font-bold uppercase tracking-widest text-slate-500">
                Role
            </label>

            <select
                name="role"
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800"
            >
                <?php foreach ($roleOptions as $val => $label): ?>
                    <option
                        value="<?= htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') ?>"
                        <?= ((isset($_GET['role']) ? (string)$_GET['role'] : 'all') === (string)$val) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex-1">
            <label class="block text-xs font-bold uppercase tracking-widest text-slate-500">
                Username
            </label>

            <input
                type="text"
                name="username"
                value="<?= htmlspecialchars((string)($_GET['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                placeholder="cth: budi"
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800"
            />
        </div>

        <div class="flex-1">
            <label class="block text-xs font-bold uppercase tracking-widest text-slate-500">
                Aksi
            </label>

            <input
                type="text"
                name="action"
                value="<?= htmlspecialchars((string)($_GET['action'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                placeholder="cth: login"
                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800"
            />
        </div>

        <div class="flex items-end gap-3">

            <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-slate-500">
                    Dari
                </label>

                <input
                    type="date"
                    name="from"
                    value="<?= htmlspecialchars((string)($_GET['from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    class="mt-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800"
                />
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-widest text-slate-500">
                    Sampai
                </label>

                <input
                    type="date"
                    name="to"
                    value="<?= htmlspecialchars((string)($_GET['to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    class="mt-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800"
                />
            </div>

        </div>

        <div class="flex gap-2">
            <a
                href="<?= htmlspecialchars(url('admin/log.php'), ENT_QUOTES, 'UTF-8') ?>"
                class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-800 hover:bg-slate-50 transition"
            >
                Reset
            </a>
        </div>

    </div>

</form>

</div>

<div class="bg-white rounded-[2rem] border border-slate-200 shadow-sm overflow-hidden">

    <div class="overflow-x-auto">

        <table class="w-full text-left border-collapse">

            <thead>
                <tr class="bg-slate-50/50 border-b border-slate-100">
                    <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">
                        Waktu
                    </th>

                    <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">
                        Pengguna
                    </th>

                    <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500 text-center">
                        Peran
                    </th>

                    <th class="px-6 py-4 text-xs font-bold uppercase tracking-widest text-slate-500">
                        Aksi & Detail
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-50">

                <?php if (empty($logs)): ?>

                    <tr>
                        <td colspan="4" class="px-6 py-20 text-center">
                            <p class="text-slate-500 font-medium">
                                Belum ada aktivitas yang tercatat.
                            </p>
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($logs as $log):

                        $roleColor = ($log['user_role'] === 'admin')
                            ? 'bg-rose-50 text-rose-600 border-rose-100'
                            : 'bg-indigo-50 text-indigo-600 border-indigo-100';

                    ?>

                    <tr class="hover:bg-slate-50/50 transition-colors">

                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-slate-700">
                                <?= date('H:i', strtotime((string)$log['created_at'])) ?>
                            </div>

                            <div class="text-[11px] text-slate-400 font-medium uppercase tracking-tighter">
                                <?= date('d M Y', strtotime((string)$log['created_at'])) ?>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">

                                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-500">
                                    <?= strtoupper(substr((string)$log['username'] ?: 'G', 0, 1)) ?>
                                </div>

                                <span class="text-sm font-bold text-slate-800">
                                    <?= htmlspecialchars((string)$log['username'] ?: 'Guest', ENT_QUOTES, 'UTF-8') ?>
                                </span>

                            </div>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border <?= $roleColor ?>">
                                <?= htmlspecialchars((string)$log['user_role'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>

                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-slate-700">
                                <?= htmlspecialchars((string)$log['action'], ENT_QUOTES, 'UTF-8') ?>
                            </div>

                            <?php if (!empty($log['details'])): ?>
                                <p class="mt-1 text-xs text-slate-500 italic">
                                    "<?= htmlspecialchars((string)$log['details'], ENT_QUOTES, 'UTF-8') ?>"
                                </p>
                            <?php endif; ?>
                        </td>

                    </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <div class="bg-slate-50/50 px-6 py-4 border-t border-slate-100">
        <p class="text-[11px] text-slate-400 font-medium">
            Menampilkan halaman <?= $page ?> dari <?= $totalPages ?>.
        </p>
    </div>

</div>

<!-- PAGINATION -->
<?php if ($totalPages > 1): ?>

<div class="mt-6 flex items-center justify-center gap-2 flex-wrap">

    <?php $queryParams = $_GET; ?>

    <?php if ($page > 1): ?>
        <?php $queryParams['page'] = $page - 1; ?>

        <a
            href="?<?= http_build_query($queryParams) ?>"
            class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold hover:bg-slate-50"
        >
            ← Prev
        </a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPages; $i++): ?>

        <?php $queryParams['page'] = $i; ?>

        <a
            href="?<?= http_build_query($queryParams) ?>"
            class="px-4 py-2 rounded-xl text-sm font-bold transition
            <?= $i === $page
                ? 'bg-indigo-600 text-white'
                : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' ?>"
        >
            <?= $i ?>
        </a>

    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>

        <?php $queryParams['page'] = $page + 1; ?>

        <a
            href="?<?= http_build_query($queryParams) ?>"
            class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold hover:bg-slate-50"
        >
            Next →
        </a>

    <?php endif; ?>

</div>

<?php endif; ?>

<script>
(function(){
    const form = document.getElementById('log-filter-form');

    if(!form) return;

    const inputs = form.querySelectorAll('input, select');

    inputs.forEach(el => {

        el.addEventListener('change', () => {
            form.submit();
        });

        el.addEventListener('input', () => {

            if(el.tagName === 'INPUT' && el.type === 'text'){

                clearTimeout(el.__logDebounce);

                el.__logDebounce = setTimeout(() => {
                    form.submit();
                }, 400);

            }

        });

    });

})();
</script>

<?php require dirname(__DIR__) . '/includes/admin_footer.php'; ?>