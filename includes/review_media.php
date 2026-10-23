<?php

function uploadReviewImages($files)
{
    if (!isset($files['name']) || !is_array($files['name'])) {
        return [];
    }

    $selectedFiles = [];
    $totalBytes = 0;
    foreach ($files['name'] as $index => $name) {
        $error = $files['error'][$index] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $selectedFiles[] = $index;
        $totalBytes += (int)($files['size'][$index] ?? 0);
    }

    if (count($selectedFiles) > 5) {
        throw new RuntimeException('Mỗi đánh giá chỉ được tải lên tối đa 5 ảnh.');
    }
    if ($totalBytes > 20 * 1024 * 1024) {
        throw new RuntimeException('Tổng dung lượng ảnh mỗi đánh giá không được vượt quá 20 MB.');
    }

    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $directory = dirname(__DIR__) . '/uploads/reviews';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Không thể tạo thư mục lưu ảnh đánh giá.');
    }

    $uploadedPaths = [];
    try {
        foreach ($selectedFiles as $index) {
            $file = [
                'name' => $files['name'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0,
            ];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException(
                    in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                        ? 'Ảnh vượt quá giới hạn tải lên của máy chủ.'
                        : 'Không thể tải ảnh đánh giá lên. Vui lòng chọn lại tệp.'
                );
            }
            if ($file['size'] < 1 || $file['size'] > 5 * 1024 * 1024 || !is_uploaded_file($file['tmp_name'])) {
                throw new RuntimeException('Mỗi ảnh phải hợp lệ và không vượt quá 5 MB.');
            }

            $imageInfo = getimagesize($file['tmp_name']);
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            if ($imageInfo === false || !isset($extensions[$mime])) {
                throw new RuntimeException('Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP hợp lệ.');
            }

            $relativePath = 'uploads/reviews/' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
            if (!move_uploaded_file($file['tmp_name'], dirname(__DIR__) . '/' . $relativePath)) {
                throw new RuntimeException('Không thể lưu ảnh đánh giá lên máy chủ.');
            }
            $uploadedPaths[] = $relativePath;
        }
    } catch (Throwable $exception) {
        deleteReviewImages($uploadedPaths);
        throw $exception;
    }

    return $uploadedPaths;
}

function deleteReviewImages($paths)
{
    foreach ($paths as $path) {
        if (!preg_match('/\Auploads\/reviews\/[a-f0-9]{32}\.(jpg|png|webp)\z/', $path)) {
            continue;
        }
        $fullPath = dirname(__DIR__) . '/' . $path;
        if (is_file($fullPath) && !unlink($fullPath)) {
            error_log('Unable to remove uploaded review image: ' . $path);
        }
    }
}
