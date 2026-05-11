<?php
declare(strict_types=1);

/**
 * Konfigurasi email untuk notifikasi persetujuan akun mahasiswa.
 * Isi MAIL_SMTP_HOST (dan kredensial) agar email terkirim; jika kosong, persetujuan tetap jalan tanpa email.
 *
 * Contoh Gmail: host smtp.gmail.com, port 587, secure tls, gunakan App Password.
 */
const MAIL_SMTP_HOST = '';
const MAIL_SMTP_PORT = 587;
const MAIL_SMTP_USER = '';
const MAIL_SMTP_PASS = '';
/** '' | 'tls' (STARTTLS, biasanya port 587) | 'ssl' (SMTPS, biasanya port 465) */
const MAIL_SMTP_SECURE = 'tls';
const MAIL_FROM_ADDRESS = '';
const MAIL_FROM_NAME = 'SIM HIMSISKO';
