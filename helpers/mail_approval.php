<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/helpers/smtp_send.php';

/**
 * Kirim email bahwa akun mahasiswa telah disetujui.
 *
 * @return array{ok:bool, error?:string}
 */
function mail_notify_account_approved(string $toEmail, string $namaDisplay, string $username): array
{
    $nama = htmlspecialchars($namaDisplay !== '' ? $namaDisplay : $username, ENT_QUOTES, 'UTF-8');
    $userEsc = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $loginUrl = htmlspecialchars(url('login.php'), ENT_QUOTES, 'UTF-8');

    $html = <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:system-ui,sans-serif;line-height:1.5;color:#1e293b;">
<p>Halo {$nama},</p>
<p>Akun mahasiswa Anda di <strong>SIM HIMSISKO</strong> telah <strong>disetujui</strong> oleh administrator.</p>
<p>Username: <strong>{$userEsc}</strong></p>
<p>Silakan masuk melalui tautan berikut:</p>
<p><a href="{$loginUrl}">{$loginUrl}</a></p>
<p style="color:#64748b;font-size:12px;">Email otomatis — mohon tidak membalas langsung ke alamat ini.</p>
</body></html>
HTML;

    return smtp_send_html($toEmail, 'Akun SIM HIMSISKO Anda telah disetujui', $html);
}
