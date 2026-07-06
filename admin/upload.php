<?php
// upload.php
require_once 'config/config.php';

header('Content-Type: application/json');

try {
    // Kiểm tra có file upload không
    if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
        throw new Exception('Không có file nào được chọn');
    }

    $uploadedFiles = [];
    $errors = [];

    // Lấy danh sách file
    $files = $_FILES['images'];
    $fileCount = count($files['name']);

    // Upload từng file
    for ($i = 0; $i < $fileCount; $i++) {
        // Kiểm tra lỗi upload
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = "File " . ($i + 1) . " bị lỗi: " . getUploadErrorMessage($files['error'][$i]);
            continue;
        }

        // Kiểm tra loại file
        $fileType = mime_content_type($files['tmp_name'][$i]);
        if (!in_array($fileType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'])) {
            $errors[] = "File " . $files['name'][$i] . " không phải là ảnh hợp lệ";
            continue;
        }

        // Kiểm tra dung lượng (giới hạn 10MB)
        if ($files['size'][$i] > 10 * 1024 * 1024) {
            $errors[] = "File " . $files['name'][$i] . " vượt quá dung lượng cho phép (10MB)";
            continue;
        }

        try {
            // Upload lên Cloudinary với các tùy chọn
            $uploadResult = $cloudinary->uploadApi()->upload(
                $files['tmp_name'][$i],
                [
                    'folder' => $uploadFolder, // Thư mục trên Cloudinary
                    'public_id' => pathinfo($files['name'][$i], PATHINFO_FILENAME) . '_' . time(),
                    'overwrite' => true,
                    'resource_type' => 'image',
                    'tags' => ['upload_php', 'web'],
                    // Các tùy chọn xử lý ảnh
                    'transformation' => [
                        'quality' => 'auto:best',
                        'fetch_format' => 'auto'
                    ]
                ]
            );

            $uploadedFiles[] = [
                'public_id' => $uploadResult['public_id'],
                'secure_url' => $uploadResult['secure_url'],
                'original_filename' => $files['name'][$i],
                'size' => $uploadResult['bytes']
            ];

        } catch (Exception $e) {
            $errors[] = "Lỗi upload file " . $files['name'][$i] . ": " . $e->getMessage();
        }
    }

    // Trả về kết quả
    if (!empty($uploadedFiles)) {
        echo json_encode([
            'success' => true,
            'message' => 'Upload thành công ' . count($uploadedFiles) . ' ảnh',
            'data' => $uploadedFiles,
            'errors' => $errors
        ]);
    } else {
        throw new Exception('Không có file nào được upload thành công');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Hàm lấy thông báo lỗi upload
 */
function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return 'File vượt quá dung lượng cho phép (upload_max_filesize)';
        case UPLOAD_ERR_FORM_SIZE:
            return 'File vượt quá dung lượng cho phép (MAX_FILE_SIZE)';
        case UPLOAD_ERR_PARTIAL:
            return 'File chỉ được upload một phần';
        case UPLOAD_ERR_NO_FILE:
            return 'Không có file được chọn';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Không tìm thấy thư mục tạm';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Không thể ghi file';
        case UPLOAD_ERR_EXTENSION:
            return 'Upload bị chặn bởi extension PHP';
        default:
            return 'Lỗi không xác định';
    }
}
?>