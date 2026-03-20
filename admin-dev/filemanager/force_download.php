<?php

declare (strict_types=1);
include 'config/config.php';
$directory = normalize_path(Tools::get_value('path', ''));
$filename = Tools::get_value('name', '');
if (strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
    die('wrong path');
}
$full_path = rtrim(FILE_MANAGER_BASE_DIR . $directory, '/') . '/' . $filename;
// check extension
$info = pathinfo($filename);
$file_extension = fix_strtolower($info['extension'] ?? '');
if (!in_array($file_extension, get_file_extensions())) {
    die('wrong extension');
}
// check that file exists
if (!file_exists($full_path)) {
    die('file does not exists');
}
if (!is_file($full_path)) {
    die('not a file');
}
// check mime type
$mime_type = mime_content_type($full_path);
if (!can_upload_file($mime_type, $file_extension)) {
    die('unsupported mime type');
}
header('Pragma: private');
header('Cache-control: private, must-revalidate');
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($full_path));
header('Content-Disposition: attachment; filename="' . $filename . '"');
readfile($full_path);
exit;