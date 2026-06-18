<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/config/auth.php';

require_role('admin');

$stmt = db()->query("SELECT username, nama_lengkap, email, approval_status, created_at FROM users WHERE role='mahasiswa' ORDER BY created_at DESC");
$rows = $stmt->fetchAll();

$tgl = date('d-m-Y H:i');

ob_start();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body{font-family:DejaVu Sans,Arial,sans-serif;font-size:12px;color:#1e293b}
        h1{font-size:16px;margin:0 0 8px}
        table{width:100%;border-collapse:collapse;margin-top:8px}
        th,td{border:1px solid #94a3b8;padding:6px;text-align:left}
        th{background:#0f172a;color:#fff;font-size:10px}
        .meta{font-size:9px;color:#64748b;margin-bottom:8px}
    </style>
</head>
<body>
<h1>Laporan data mahasiswa SIM HIMSISKO</h1>
<div class="meta"><?= htmlspecialchars($tgl, ENT_QUOTES, 'UTF-8') ?></div>
<table>
    <thead>
    <tr><th>No</th><th>Username</th><th>Nama</th><th>Email</th><th>Status</th><th>Terdaftar</th></tr>
    </thead>
    <tbody>
    <?php $i=0; foreach ($rows as $r): $i++; ?>
        <tr>
            <td><?= $i ?></td>
            <td><?= htmlspecialchars((string)$r['username'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($r['nama_lengkap'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($r['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars(ucfirst((string)$r['approval_status']), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= !empty($r['created_at']) ? date('d-m-Y', strtotime((string)$r['created_at'])) : '-' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
<?php
$html = ob_get_clean();

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->setChroot(dirname(__DIR__));

$pdf = new Dompdf($options);
$pdf->loadHtml($html, 'UTF-8');
$pdf->setPaper('A4', 'portrait');
$pdf->render();
$pdf->stream('laporan-mahasiswa.pdf', ['Attachment' => true]);
exit;

