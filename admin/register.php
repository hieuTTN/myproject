<?php
// 1. Khởi động Session để quản lý thông báo thành công/thất bại
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('../database/connect.php'); // Nạp cấu hình config.php và các hàm execute(), querySingleResult()[cite: 4, 5]

$error_msg = "";
$success_msg = "";

// 2. Xử lý khi bấm nút "Tạo tài khoản"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');

    if (empty($username) || empty($password) || empty($fullname)) {
        $error_msg = "Vui lòng điền đầy đủ tất cả thông tin!";
    } elseif (strlen($password) < 6) {
        $error_msg = "Mật khẩu phải chứa ít nhất 6 ký tự!";
    } else {
        // Chống SQL Injection cơ bản
        $safe_username = addslashes($username);
        $safe_fullname = addslashes($fullname);

        // Kiểm tra xem Username này đã có ai đăng ký chưa[cite: 5]
        $sql_check = "SELECT `id` FROM `admins` WHERE `username` = '$safe_username' LIMIT 1";
        $check_exist = querySingleResult($sql_check);

        if ($check_exist) {
            $error_msg = "Tài khoản '$username' đã tồn tại trong hệ thống!";
        } else {
            // 3. MÃ HÓA MẬT KHẨU (BẢO MẬT TUYỆT ĐỐI)
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // 4. Tiến hành chèn dữ liệu vào bảng admins bằng hàm execute() của bạn[cite: 5]
            $sql_insert = "INSERT INTO `admins` (`username`, `password`, `fullname`) 
                           VALUES ('$safe_username', '$hashed_password', '$safe_fullname')";
            
            execute($sql_insert);

            $success_msg = "Tạo tài khoản admin mới thành công!";
            // Reset dữ liệu input để form trống
            $username = $fullname = "";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khởi tạo Tài khoản Admin</title>
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
        .register-card {
            background-color: var(--bg-surface);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
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
    </style>
</head>
<body>

<div class="register-card">
    <h3 class="text-center mb-2" style="color: var(--accent-teal); font-weight: 700;">TẠO TÀI KHOẢN ADMIN</h3>
    <p class="text-center text-secondary small mb-4">Khởi tạo tài khoản quản trị hệ thống</p>

    <!-- Thông báo Lỗi -->
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger d-flex align-items-center small py-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div><?= htmlspecialchars($error_msg) ?></div>
        </div>
    <?php endif; ?>

    <!-- Thông báo Thành công -->
    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success d-flex align-items-center small py-2" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <div><?= htmlspecialchars($success_msg) ?></div>
        </div>
    <?php endif; ?>

    <form action="register.php" method="POST" autocomplete="off">
        <div class="mb-3">
            <label for="fullname" class="form-label">Tên hiển thị (Họ và Tên)</label>
            <input type="text" class="form-control" id="fullname" name="fullname" placeholder="Ví dụ: Nguyễn Văn A" required value="<?= htmlspecialchars($fullname ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="username" class="form-label">Tài khoản (Username)</label>
            <input type="text" class="form-control" id="username" name="username" placeholder="Dùng để đăng nhập..." required value="<?= htmlspecialchars($username ?? '') ?>">
        </div>
        
        <div class="mb-4">
            <label for="password" class="form-label">Mật khẩu</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="Tối thiểu 6 ký tự..." required>
        </div>

        <button type="submit" class="btn btn-teal mb-3">
            <i class="bi bi-person-plus-fill me-2"></i>Kích hoạt Tài khoản
        </button>
    </form>
    
    <div class="text-center">
        <a href="login.php" class="text-decoration-none small" style="color: var(--text-muted);"><i class="bi bi-arrow-right me-1"></i> Đi tới trang Đăng nhập</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>