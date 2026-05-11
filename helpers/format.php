<?php
declare(strict_types=1);

function status_kegiatan_label(string $s): array
{
    return match ($s) {
        'rencana' => ['badge' => 'bg-amber-100 text-amber-900', 'teks' => 'Rencana'],
        'berlangsung' => ['badge' => 'bg-blue-100 text-blue-900', 'teks' => 'Berlangsung'],
        'selesai' => ['badge' => 'bg-emerald-100 text-emerald-900', 'teks' => 'Selesai'],
        'dibatalkan' => ['badge' => 'bg-slate-200 text-slate-700', 'teks' => 'Dibatalkan'],
        default => ['badge' => 'bg-slate-100 text-slate-800', 'teks' => $s],
    };
}
