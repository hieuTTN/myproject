<?php
// 1. Khởi động Session để lưu trạng thái đăng nhập
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu admin đã đăng nhập từ trước, tự động chuyển hướng thẳng vào trang quản trị
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin_dashboard.php"); // Thay bằng file trang quản trị của bạn
    exit;
}

require_once('../database/connect.php'); // Đọc cấu hình từ config.php và nạp các hàm bổ trợ của bạn[cite: 4, 5]

$error_msg = "";

// 2. Xử lý khi Admin nhấn nút Đăng nhập (Gửi dữ liệu qua phương thức POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error_msg = "Vui lòng nhập đầy đủ tài khoản và mật khẩu!";
    } else {
        // Chống SQL Injection cơ bản bằng hàm addslashes trước khi đưa vào câu lệnh query
        $safe_username = addslashes($username);
        
        // Truy vấn tìm thông tin Admin theo username bằng hàm của bạn[cite: 5]
        $sql = "SELECT * FROM `admins` WHERE `username` = '$safe_username' LIMIT 1";
        $admin = querySingleResult($sql); // Gọi hàm lấy ra 1 dòng kết quả duy nhất[cite: 5]

        if ($admin) {
            // Kiểm tra mật khẩu chữ thuần có khớp với chuỗi mật khẩu đã băm (hash) trong DB không
            if (password_verify($password, $admin['password'])) {
                
                // Tái tạo ID session để chống tấn công Session Fixation (Bảo mật nâng cao)
                session_regenerate_id(true);
                
                // Lưu thông tin phiên đăng nhập của admin
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_user'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['fullname'];

                // Chuyển hướng đến trang quản lý demo
                header("Location: admin_dashboard.php");
                exit;
            } else {
                $error_msg = "Tài khoản hoặc mật khẩu không chính xác!";
            }
        } else {
            $error_msg = "Tài khoản hoặc mật khẩu không chính xác!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Hệ thống Quản trị Demo</title>
    <!-- Nhúng Bootstrap 5 và Bootstrap Icons tương tự như Landing Page -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0b0f19;
            --bg-surface: #131a26;
            --bg-surface-2: #1e293b;
            --accent-teal: #34d1bf;
            --text-muted: #94a3b8;
        }
        body {
            background-color: var(--bg-dark);
            color: #ffffff;
            font-family: system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background-color: var(--bg-surface);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        .brand-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            text-align: center;
            margin-bottom: 2rem;
        }
        .brand-logo span {
            color: var(--accent-teal);
        }
        .form-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }
        .form-control {
            background-color: var(--bg-surface-2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #ffffff;
            padding: 0.75rem 1rem;
            border-radius: 8px;
        }
        .form-control:focus {
            background-color: var(--bg-surface-2);
            border-color: var(--accent-teal);
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(52, 209, 191, 0.15);
        }
        .btn-teal {
            background-color: var(--accent-teal);
            color: #0b0f19;
            font-weight: 600;
            padding: 0.75rem;
            border-radius: 8px;
            border: none;
            width: 100%;
            transition: all 0.2s ease-in-out;
        }
        .btn-teal:hover {
            background-color: #2bc1af;
            transform: translateY(-1px);
        }
        .alert-custom {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
            font-size: 0.875rem;
            border-radius: 8px;
            padding: 0.75rem 1rem;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand-logo">
        <i class="bi bi-shield-lock-fill me-2"></i><span>ADMIN</span>PANEL
    </div>
    
    <p class="text-center text-secondary small mb-4">Đăng nhập để quản lý danh sách demo đồ án</p>

    <!-- Hiển thị thông báo lỗi nếu đăng nhập thất bại -->
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-custom mb-4 d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div><?= htmlspecialchars($error_msg) ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <div class="mb-3">
            <label for="username" class="form-label">Tài khoản</label>
            <input type="text" class="form-control" id="username" name="username" placeholder="Nhập username..." required value="<?= htmlspecialchars($username ?? '') ?>">
        </div>
        
        <div class="mb-4">
            <label for="password" class="form-label">Mật khẩu</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-teal">
            <i class="bi bi-box-arrow-in-right me-2"></i>Đăng nhập hệ thống
        </button>
    </form>
    
    <div class="text-center mt-4">
        <a href="/" class="text-decoration-none small" style="color: var(--text-muted);"><i class="bi bi-arrow-left me-1"></i> Quay lại trang chủ</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>