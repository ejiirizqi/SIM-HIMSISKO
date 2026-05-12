<?php
// This wrapper include the correct footer based on user role
$u = session_status() === PHP_SESSION_ACTIVE && function_exists('current_user') ? current_user() : null;
$role = $u['role'] ?? '';

if ($u && $role === 'mahasiswa') {
    require dirname(__FILE__) . '/mhs_footer.php';
} else {
    require dirname(__FILE__) . '/publik_footer.php';
}
?>
