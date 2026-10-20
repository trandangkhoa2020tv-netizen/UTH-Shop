<?php
function postMaxSizeInBytes() {
    $value = trim((string)ini_get('post_max_size'));
    if ($value === '') {
        return 0;
    }

    $unit = strtolower(substr($value, -1));
    $size = (float)$value;
    if ($unit === 'g') {
        $size *= 1024;
    }
    if ($unit === 'g' || $unit === 'm') {
        $size *= 1024;
    }
    if ($unit === 'g' || $unit === 'm' || $unit === 'k') {
        $size *= 1024;
    }

    return (int)$size;
}

function uploadAdminImage($file, $type) {
    if (!in_array($type, ['products', 'banners'], true)) {
        throw new RuntimeException('Thư mục tải ảnh không hợp lệ.');
    }
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        if (isset($file['error']) && in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new RuntimeException('Ảnh vượt quá giới hạn tải lên của máy chủ. Vui lòng chọn ảnh nhỏ hơn.');
        }
        throw new RuntimeException('Không thể tải ảnh lên. Vui lòng chọn lại tệp.');
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Ảnh không được vượt quá 5 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Chỉ chấp nhận ảnh JPG, PNG, WEBP hoặc GIF.');
    }
    if (getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Tệp tải lên không phải là ảnh hợp lệ.');
    }

    $relativeDirectory = 'uploads/' . $type;
    $directory = dirname(__DIR__, 2) . '/' . $relativeDirectory;
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Không thể tạo thư mục lưu ảnh. Kiểm tra quyền ghi của máy chủ.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
        throw new RuntimeException('Không thể lưu ảnh lên máy chủ.');
    }

    return $relativeDirectory . '/' . $filename;
}

function deleteUploadedImage($storedPath) {
    $storedPath = str_replace('\\', '/', (string)$storedPath);
    if (strpos($storedPath, 'uploads/products/') !== 0 && strpos($storedPath, 'uploads/banners/') !== 0) {
        return;
    }

    $root = realpath(dirname(__DIR__, 2));
    $path = realpath(dirname(__DIR__, 2) . '/' . $storedPath);
    if ($path === false) {
        return;
    }
    if ($root === false || strpos($path, $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR) !== 0) {
        throw new RuntimeException('Đường dẫn ảnh cần xóa không hợp lệ.');
    }
    if (!unlink($path)) {
        throw new RuntimeException('Không xóa được tệp ảnh cũ trên máy chủ.');
    }
}
