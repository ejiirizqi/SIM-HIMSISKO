<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/koneksi.php';
require_once dirname(__DIR__) . '/helpers/keuangan_ringkasan.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$kas = keuangan_ringkasan(db());
$stmt = db()->query('SELECT tanggal, tipe, nominal, keterangan FROM keuangan ORDER BY tanggal DESC, id DESC');
$rows = $stmt->fetchAll();

$tglBuat = date('d-m-Y H:i');

ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style>
*{box-sizing:border-box;}
body{font-family:DejaVu Sans,Arial,sans-serif;font-size:11px;color:#1e293b;}
h1{font-size:16px;margin:0 0 4px;color:#020617;}
.summary{display:flex;gap:10px;margin:16px 0;}
.box{border:1px solid #cbd5e1;padding:10px;border-radius:4px;background:#f8fafc;width:33%;}
.label{font-weight:bold;font-size:9px;color:#64748b;text-transform:uppercase;}
.val{font-weight:bold;margin-top:4px;color:#020617;font-size:12px;}
table{width:100%;border-collapse:collapse;margin-top:8px;}
th,td{border:1px solid #94a3b8;padding:5px;text-align:left;}
th{background:#0f172a;color:#ffffff;font-size:9px;text-transform:uppercase;}
.meta{font-size:9px;color:#64748b;margin-bottom:14px;}
.tipe{font-weight:bold;}
.m{border-radius:999px;font-size:8px;font-weight:bold;padding:2px 6px;display:inline;}
.masuk{background:#d1fae5;color:#065f46;}
.keluar{background:#fee2e2;color:#9f1239;}
.num{text-align:right;white-space:nowrap;}
.footer{margin-top:20px;font-size:8px;color:#94a3b8;text-align:center;}
</style>
</head>
<body>
<h1>Laporan transparansi keuangan SIM HIMSISKO</h1>
<div class="meta">Dicetak: <?= htmlspecialchars($tglBuat, ENT_QUOTES, 'UTF-8') ?></div>

<div class="summary">
    <div class="box">
        <div class="label">Total pemasukan</div>
        <div class="val"><?= htmlspecialchars(format_rupiah($kas['pemasukan']), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="box">
        <div class="label">Total pengeluaran</div>
        <div class="val"><?= htmlspecialchars(format_rupiah($kas['pengeluaran']), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="box" style="background:#020617;color:#fff;border:none;">
        <div class="label" style="color:#94a3b8;">Saldo akhir</div>
        <div class="val" style="color:#fff;"><?= htmlspecialchars(format_rupiah($kas['saldo']), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
</div>

<table>
<thead><tr><th>Tanggal</th><th>Tipe transaksi</th><th>Nominal (Rp)</th><th>Keterangan</th></tr></thead>
<tbody>
<?php if (count($rows) === 0): ?>
    <tr><td colspan="4" style="text-align:center;color:#64748b;">Belum ada data transaksi.</td></tr>
<?php endif; ?>
<?php foreach ($rows as $row): ?>
    <?php $isMasuk = ($row['tipe'] ?? '') === 'masuk'; ?>
    <tr>
        <td><?= htmlspecialchars((string)$row['tanggal'], ENT_QUOTES, 'UTF-8') ?></td>
        <td>
            <span class="tipe <?= $isMasuk ? 'masuk m' : 'keluar m' ?>">
                <?= $isMasuk ? 'Pemasukan' : 'Pengeluaran' ?>
            </span>
        </td>
        <td class="num"><?= htmlspecialchars(format_rupiah((int)$row['nominal']), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string)($row['keterangan'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
<?php endforeach; ?>
</tbody>
</table>

<p class="footer">Sumber data: aplikasi SIM HIMSISKO &mdash; arsip internal organisasi.</p>
</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->setChroot(dirname(__DIR__));

$pdf = new Dompdf($options);
$pdf->loadHtml($html, 'UTF-8');
$pdf->setPaper('A4', 'portrait');
$pdf->render();

$pdf->stream('laporan-keuangan-himsisko.pdf', ['Attachment' => true]);
exit;
