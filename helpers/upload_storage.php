<?php
declare(strict_types=1);

function unlink_upload(?string $relative): void
{
    if ($relative === null || $relative === '') {
        return;
    }
    $relative = ltrim(str_replace(['..', '\\'], '', $relative), '/');
    $path = project_root() . '/uploads/' . $relative;
    if (is_file($path)) {
        @unlink($path);
    }
}
