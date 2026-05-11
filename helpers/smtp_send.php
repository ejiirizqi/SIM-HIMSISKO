<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/mail.php';

/**
 * @return array{ok:bool, error?:string}
 */
function smtp_send_html(string $toEmail, string $subject, string $htmlBody): array
{
    if (MAIL_SMTP_HOST === '' || MAIL_FROM_ADDRESS === '') {
        return ['ok' => false, 'error' => 'MAIL_SMTP_HOST atau MAIL_FROM_ADDRESS kosong di config/mail.php'];
    }

    $secure = strtolower(MAIL_SMTP_SECURE);
    $host = MAIL_SMTP_HOST;
    $port = (int)MAIL_SMTP_PORT;
    $user = MAIL_SMTP_USER;
    $pass = MAIL_SMTP_PASS;
    $from = MAIL_FROM_ADDRESS;
    $fromName = MAIL_FROM_NAME;

    $remote = ($secure === 'ssl')
        ? 'ssl://' . $host . ':' . $port
        : 'tcp://' . $host . ':' . $port;

    $ctx = stream_context_create([
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
        ],
    ]);

    $fp = @stream_socket_client($remote, $errno, $errstr, 25, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        return ['ok' => false, 'error' => 'Koneksi SMTP gagal: ' . $errstr];
    }
    stream_set_timeout($fp, 20);

    $read = static function () use ($fp): string {
        $buf = '';
        while (($line = fgets($fp, 8192)) !== false) {
            $buf .= $line;
            if (strlen($line) < 4) {
                break;
            }
            if ($line[3] === ' ') {
                break;
            }
        }
        return $buf;
    };

    $expect = static function (string $resp, array $codes) use ($read): bool {
        $first = substr(trim($resp), 0, 3);
        return in_array($first, $codes, true);
    };

    $write = static function (string $cmd) use ($fp): void {
        fwrite($fp, $cmd . "\r\n");
    };

    $greet = $read();
    if (!$expect($greet, ['220'])) {
        fclose($fp);
        return ['ok' => false, 'error' => 'SMTP greeting tidak valid: ' . trim($greet)];
    }

    $ehloHost = preg_replace('/[^a-zA-Z0-9.-]/', '', (string)($_SERVER['SERVER_NAME'] ?? 'localhost')) ?: 'localhost';
    $write('EHLO ' . $ehloHost);
    $ehlo = $read();
    if (!$expect($ehlo, ['250'])) {
        fclose($fp);
        return ['ok' => false, 'error' => 'EHLO gagal: ' . trim($ehlo)];
    }

    if ($secure === 'tls') {
        $write('STARTTLS');
        $st = $read();
        if (!$expect($st, ['220'])) {
            fclose($fp);
            return ['ok' => false, 'error' => 'STARTTLS ditolak: ' . trim($st)];
        }
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($fp);
            return ['ok' => false, 'error' => 'Enkripsi TLS gagal.'];
        }
        $write('EHLO ' . $ehloHost);
        $ehlo2 = $read();
        if (!$expect($ehlo2, ['250'])) {
            fclose($fp);
            return ['ok' => false, 'error' => 'EHLO setelah TLS gagal: ' . trim($ehlo2)];
        }
    }

    if ($user !== '' && $pass !== '') {
        $write('AUTH LOGIN');
        $a1 = $read();
        if (!$expect($a1, ['334'])) {
            fclose($fp);
            return ['ok' => false, 'error' => 'AUTH LOGIN ditolak: ' . trim($a1)];
        }
        $write(base64_encode($user));
        $a2 = $read();
        if (!$expect($a2, ['334'])) {
            fclose($fp);
            return ['ok' => false, 'error' => 'Username SMTP ditolak: ' . trim($a2)];
        }
        $write(base64_encode($pass));
        $a3 = $read();
        if (!$expect($a3, ['235'])) {
            fclose($fp);
            return ['ok' => false, 'error' => 'Autentikasi SMTP gagal: ' . trim($a3)];
        }
    }

    $write('MAIL FROM:<' . $from . '>');
    $m1 = $read();
    if (!$expect($m1, ['250'])) {
        fclose($fp);
        return ['ok' => false, 'error' => 'MAIL FROM ditolak: ' . trim($m1)];
    }

    $write('RCPT TO:<' . $toEmail . '>');
    $r1 = $read();
    if (!$expect($r1, ['250', '251'])) {
        fclose($fp);
        return ['ok' => false, 'error' => 'RCPT TO ditolak: ' . trim($r1)];
    }

    $write('DATA');
    $d1 = $read();
    if (!$expect($d1, ['354'])) {
        fclose($fp);
        return ['ok' => false, 'error' => 'DATA ditolak: ' . trim($d1)];
    }

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $fromNameEnc = function_exists('mb_encode_mimeheader')
        ? mb_encode_mimeheader($fromName, 'UTF-8')
        : ('=?UTF-8?B?' . base64_encode($fromName) . '?=');
    $headers = [
        'From: ' . $fromNameEnc . ' <' . $from . '>',
        'To: <' . $toEmail . '>',
        'Subject: ' . $encodedSubject,
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];
    $body = $htmlBody;
    $payload = implode("\r\n", $headers) . "\r\n\r\n" . str_replace(["\r\n", "\r"], "\n", $body);
    $payload = str_replace("\n", "\r\n", $payload);
    $payload .= "\r\n.\r\n";
    fwrite($fp, $payload);

    $d2 = $read();
    if (!$expect($d2, ['250'])) {
        fclose($fp);
        return ['ok' => false, 'error' => 'Pengiriman isi pesan gagal: ' . trim($d2)];
    }

    $write('QUIT');
    fclose($fp);
    return ['ok' => true];
}
