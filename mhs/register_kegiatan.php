<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/auth.php';

require_role('mahasiswa');

// Endpoint pendaftaran dinonaktifkan. Redirect ke dashboard mahasiswa.
header('Location: ' . url('mhs/dashboard.php'));
exit;
