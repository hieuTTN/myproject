<?php
// 1. Khởi động Session để có thể thao tác với dữ liệu phiên hiện tại
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Xóa sạch tất cả các biến đã lưu trong $_SESSION (như admin_id, admin_user...)
$_SESSION = array();

// 3. Nếu sử dụng Cookie để lưu Session ID (mặc định của PHP), hãy xóa bỏ nó đi
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// 4. Hủy toàn bộ phiên làm việc (Session) trên Server
session_destroy();

// 5. Chuyển hướng Admin quay trở lại trang đăng nhập
header("Location: login.php");
exit;
?>