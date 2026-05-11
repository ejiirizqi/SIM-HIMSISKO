<?php
declare(strict_types=1);

/**
 * Unggah file ke folder uploads/$sub dengan nama random.
 *
 * @return array{ok:bool,relative?:string,error?:string}
 */
function save_upload_public(array $file, string $subfolder, array $allowedExt, int $maxBytes): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Tidak ada file dipilih.'];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Gagal mengunggah (kode upload).'];
    }
    $tmp = $file['tmp_name'] ?? '';
    if (!$tmp || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'Upload tidak valid.'];
    }
    if (($file['size'] ?? 0) > $maxBytes) {
        return ['ok' => false, 'error' => 'Ukuran file melebihi batas yang diizinkan.'];
    }
    $base = strtolower((string)pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if (!in_array($base, $allowedExt, true)) {
        return ['ok' => false, 'error' => 'Tipe file tidak diizinkan.'];
    }
    $subfolder = trim(str_replace(['.', '\\'], '', $subfolder), '/');
    $targetDir = project_root() . '/uploads/' . $subfolder . '/';
    if (!is_dir($targetDir) && !@mkdir($targetDir, 0755, true)) {
        return ['ok' => false, 'error' => 'Gagal menyiapkan folder upload.'];
    }
    $newName = bin2hex(random_bytes(16)) . '.' . $base;
    $dest = $targetDir . $newName;
    if (!move_uploaded_file($tmp, $dest)) {
        return ['ok' => false, 'error' => 'Gagal menyimpan file.'];
    }
    return ['ok' => true, 'relative' => $subfolder . '/' . $newName];
}
