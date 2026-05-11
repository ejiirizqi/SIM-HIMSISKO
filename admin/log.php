<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/helpers/csrf.php';
require_once dirname(__DIR__) . '/config/auth.php';

require_role('admin');

$pageTitle = 'Log aktivitas';
$activeNav = 'logs';

$logs = fetch_activity_logs(250);
$csrf = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');

require dirname(__DIR__) . '/includes/admin_header.php';
?>
<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900">Log aktivitas</h1>
    <p class="mt-1 text-slate-600">Riwayat aksi yang dilakukan admin dan mahasiswa. Ini membantu audit dan pelacakan aktivitas pengguna.</p>
</div>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
            <tr class="bg-slate-50 text-left text-slate-600">
                <th class="px-4 py-3 font-medium">Waktu</th>
                <th class="px-4 py-3 font-medium">Peran</th>
                <th class="px-4 py-3 font-medium">Pengguna</th>
                <th class="px-4 py-3 font-medium">Aksi</th>
                <th class="px-4 py-3 font-medium">Detail</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php if (count($logs) === 0): ?>
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-slate-500">Belum ada log aktivitas.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr class="hover:bg-slate-50/80 align-top">
                        <td class="px-4 py-3 text-slate-700 whitespace-nowrap"><?= htmlspecialchars((string)$log['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3 text-slate-700 capitalize"><?= htmlspecialchars((string)$log['user_role'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars((string)$log['username'] ?: 'Guest', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3 text-slate-700"><?= htmlspecialchars((string)$log['action'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)$log['details'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require dirname(__DIR__) . '/includes/admin_footer.php'; ?>
