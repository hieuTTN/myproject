<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Kiểm tra quyền truy cập admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

require_once('../database/connect.php');
require_once('../service/CategoryAndTechService.php');

// 2. Xử lý thêm công nghệ mới (form POST thông thường)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    if (!empty($name)) {
        addTechnologyOnly($name);
        header("Location: technology.php?msg=added");
        exit;
    }
    header("Location: technology.php?msg=empty");
    exit;
}

// 3. Xử lý xóa công nghệ (?action=delete&id=...)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    // project_technology có ON DELETE CASCADE nên xóa technologies sẽ tự dọn liên kết liên quan
    execute("DELETE FROM `technologies` WHERE `id` = $delete_id");
    header("Location: technology.php?msg=deleted");
    exit;
}

// 4. Lấy danh sách công nghệ kèm số lượng đồ án đang dùng mỗi công nghệ
$technologies = executeresult(
    "SELECT t.*, COUNT(pt.project_id) AS project_count
     FROM `technologies` t
     LEFT JOIN `project_technology` pt ON pt.technology_id = t.id
     GROUP BY t.id
     ORDER BY t.name ASC"
);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Công nghệ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">
</head>
<body>

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>

    <div class="admin-content">
        <div class="admin-main">
            <div class="container-fluid px-4">

                <!-- Tiêu đề trang -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-1">Quản lý Công nghệ</h2>
                        <p class="text-secondary small mb-0">Danh sách các từ khóa công nghệ dùng để gắn cho đồ án (Java, Spring Boot, MySQL,...)</p>
                    </div>
                </div>



            </div><!-- /.container-fluid -->
        </div><!-- /.admin-main -->
    </div><!-- /.admin-content -->
</div><!-- /.admin-layout -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/admin-sidebar.js"></script>
</body>
</html>