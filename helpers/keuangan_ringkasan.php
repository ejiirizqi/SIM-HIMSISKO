<?php
declare(strict_types=1);

/**
 * Hitung ringkasan kas: pemasukan (tipe masuk), pengeluaran (tipe keluar), saldo = masuk − keluar.
 */
function keuangan_ringkasan(PDO $pdo): array
{
    $sql = <<<SQL
        SELECT
            COALESCE(SUM(CASE WHEN tipe = 'masuk' THEN nominal ELSE 0 END), 0) AS pemasukan,
            COALESCE(SUM(CASE WHEN tipe = 'keluar' THEN nominal ELSE 0 END), 0) AS pengeluaran
        FROM keuangan
SQL;
    $row = $pdo->query($sql)->fetch();
    $pemasukan = (int)($row['pemasukan'] ?? 0);
    $pengeluaran = (int)($row['pengeluaran'] ?? 0);

    return [
        'pemasukan' => $pemasukan,
        'pengeluaran' => $pengeluaran,
        'saldo' => $pemasukan - $pengeluaran,
    ];
}

function format_rupiah(int $n): string
{
    return 'Rp ' . number_format($n, 0, ',', '.');
}
