<?php
require_once('../service/ContactService.php');
header('Content-Type: application/json; charset=UTF-8');

// Chỉ chấp nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ.'], JSON_UNESCAPED_UNICODE);
    exit;
}



// ============================================================
// 1. Đọc & làm sạch dữ liệu gửi lên
// ============================================================
$fullname   = trim($_POST['fullname'] ?? '');
$phone      = trim($_POST['phone'] ?? '');
$technology = trim($_POST['technology'] ?? '');
$message    = trim($_POST['message'] ?? '');

// ============================================================
// 2. Validate cơ bản
// ============================================================
if (empty($fullname) || empty($phone)) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Vui lòng nhập đầy đủ Họ tên và Zalo/SĐT.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================
// 3. Lưu vào database
// ============================================================
$id = addContact($fullname, $phone, $technology, $message);

if ($id > 0) {
    echo json_encode([
        'status'  => 'success',
        'message' => 'Đã gửi yêu cầu! Mình sẽ liên hệ lại trong vòng 30 phút.',
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Gửi liên hệ thất bại, vui lòng thử lại.',
    ], JSON_UNESCAPED_UNICODE);
}
exit;
