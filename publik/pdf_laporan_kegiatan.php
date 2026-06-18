<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/koneksi.php';

$stmt = db()->query('SELECT id, judul, tanggal, lokasi, status, created_at FROM kegiatan ORDER BY tanggal DESC, id DESC');
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
<h1>Laporan data kegiatan SIM HIMSISKO</h1>
<div class="meta">Dicetak: <?= htmlspecialchars($tgl, ENT_QUOTES, 'UTF-8') ?></div>
<table>
    <thead>
    <tr><th>No</th><th>Judul</th><th>Tanggal</th><th>Lokasi</th><th>Status</th></tr>
    </thead>
    <tbody>
    <?php $i=0; foreach ($rows as $r): $i++;
        $status = (string)($r['status'] ?? '');
        $stText = match ($status) {
            'rencana' => 'Rencana',
            'berlangsung' => 'Berlangsung',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
            default => ucfirst($status),
        };
    ?>
        <tr>
            <td><?= $i ?></td>
            <td><?= htmlspecialchars((string)$r['judul'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)$r['tanggal'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($r['lokasi'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($stText, ENT_QUOTES, 'UTF-8') ?></td>
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
$pdf->stream('laporan-kegiatan.pdf', ['Attachment' => true]);
exit;
