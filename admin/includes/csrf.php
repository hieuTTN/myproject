<?php
/**
 * CSRF HELPER DÙNG CHUNG CHO KHU VỰC ADMIN
 * -----------------------------------------
 * Sinh & lưu 1 token ngẫu nhiên trong session, dùng để chống tấn công CSRF
 * (Cross-Site Request Forgery) cho các action làm thay đổi dữ liệu (thêm/sửa/xóa).
 *
 * Cách dùng:
 *   1. require_once 'includes/csrf.php'; (SAU khi đã session_start())
 *   2. Trong <form>: <?= csrf_field() ?>
 *   3. Trong link GET (vd link Xóa): thêm '&csrf_token=' . urlencode(csrf_token())
 *   4. Trước khi xử lý POST/GET action: kiểm tra
 *          if (!csrf_verify($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '')) { ... chặn lại ... }
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sinh token 1 lần duy nhất cho mỗi phiên đăng nhập, giữ nguyên xuyên suốt session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Lấy token CSRF hiện tại của session
 */
function csrf_token() {
    return $_SESSION['csrf_token'];
}

/**
 * Trả về đoạn HTML input ẩn để nhúng vào trong <form>
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

/**
 * Kiểm tra token gửi lên có khớp với token trong session không
 * Dùng hash_equals() để tránh bị dò token qua timing attack
 */
function csrf_verify($token) {
    return isset($_SESSION['csrf_token']) && is_string($token) && $token !== ''
        && hash_equals($_SESSION['csrf_token'], $token);
}
?>