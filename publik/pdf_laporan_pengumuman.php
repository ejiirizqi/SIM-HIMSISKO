<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/koneksi.php';

$stmt = db()->query('SELECT id, judul, isi, tanggal_publish, created_at FROM pengumuman ORDER BY tanggal_publish DESC, id DESC');
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
        .meta{font-size:9px;color:#64748b;margin-bottom:8px}
        .ann{margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid #e2e8f0}
        .title{font-weight:700;margin-bottom:4px}
        .date{font-size:11px;color:#64748b;margin-bottom:6px}
        .content{white-space:pre-wrap}
    </style>
</head>
<body>
<h1>Laporan Pengumuman SIM HIMSISKO</h1>
<div class="meta">Dicetak: <?= htmlspecialchars($tgl, ENT_QUOTES, 'UTF-8') ?></div>

<?php if (count($rows) === 0): ?>
    <p style="color:#64748b">Belum ada pengumuman.</p>
<?php else: ?>
    <?php foreach ($rows as $r): ?>
        <div class="ann">
            <div class="title"><?= htmlspecialchars((string)$r['judul'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="date"><?= htmlspecialchars((string)$r['tanggal_publish'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="content"><?= htmlspecialchars((string)$r['isi'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
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
$pdf->stream('laporan-pengumuman.pdf', ['Attachment' => true]);
exit;
