<?php

declare (strict_types=1);
/** @noinspection PhpUnhandledExceptionInspection */
include 'config/config.php';
$store_folder = rtrim(FILE_MANAGER_BASE_DIR . normalize_path(Tools::get_value('path', '')), '/') . '/';
$store_folder_thumb = rtrim(FILE_MANAGER_THUMB_BASE_DIR . normalize_path(Tools::get_value('path_thumb', '')), '/') . '/';
if (!empty($_FILES) && isset($_FILES['file']) && $_FILES['file']['tmp_name']) {
    $info = pathinfo($_FILES['file']['name']);
    $temp_file = $_FILES['file']['tmp_name'];
    $file_extension = fix_strtolower($info['extension'] ?? '');
    $mime_type = mime_content_type($temp_file);
    if (can_upload_file($mime_type, $file_extension)) {
        $target_path = $store_folder;
        $target_path_thumb = $store_folder_thumb;
        $_FILES['file']['name'] = fix_filename($_FILES['file']['name']);
        $file_name_splitted = explode('.', $_FILES['file']['name']);
        array_pop($file_name_splitted);
        $_FILES['file']['name'] = implode('-', $file_name_splitted) . '.' . $file_extension;
        if (file_exists($target_path . $_FILES['file']['name'])) {
            $i = 1;
            $info = pathinfo($_FILES['file']['name']);
            while (file_exists($target_path . $info['filename'] . '_' . $i . '.' . $file_extension)) {
                $i++;
            }
            $_FILES['file']['name'] = $info['filename'] . '_' . $i . '.' . $file_extension;
        }
        $target_file = $target_path . $_FILES['file']['name'];
        $target_file_thumb = $target_path_thumb . $_FILES['file']['name'];
        if (in_array($file_extension, get_file_extensions('image')) && @getimagesize($temp_file) != false) {
            $is_img = true;
        } else {
            $is_img = false;
        }
        if ($is_img) {
            move_uploaded_file($temp_file, $target_file);
            chmod($target_file, 0755);
            create_img_gd($target_file, $target_file_thumb, 122, 91);
        } else {
            move_uploaded_file($temp_file, $target_file);
            chmod($target_file, 0755);
        }
    } else {
        header('HTTP/1.1 406 file not permitted', true, 406);
        die(Tools::display_error('File type not permitted'));
    }
} else {
    header('HTTP/1.1 405 Bad Request', true, 405);
    if (isset($_FILES['file']['error']) && $_FILES['file']['error']) {
        die(Tools::decode_upload_error((int) $_FILES['file']['error']));
    } else {
        die(Tools::display_error('Failed to upload file'));
    }
}
if (Tools::is_submit('submit')) {
    $query = http_build_query(['type' => Tools::get_value('type', ''), 'lang' => Tools::get_value('lang', ''), 'popup' => Tools::get_value('popup', ''), 'fldr' => Tools::get_value('fldr', '')]);
    header('location: dialog.php?' . $query);
}