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

// 3. Xử lý CẬP NHẬT công nghệ (?action=edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $edit_id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');

    if ($edit_id <= 0 || empty($name)) {
        header("Location: technology.php?msg=empty");
        exit;
    }

    // technologies.name có ràng buộc UNIQUE nên cần kiểm tra trùng với công nghệ khác trước khi lưu
    $safe_name_check = addslashes($name);
    $check = querySingleResult("SELECT id FROM `technologies` WHERE `name` = '$safe_name_check' AND `id` != $edit_id LIMIT 1");
    if ($check) {
        header("Location: technology.php?msg=duplicate");
        exit;
    }

    $safe_name = addslashes($name);
    execute("UPDATE `technologies` SET `name` = '$safe_name' WHERE `id` = $edit_id");
    header("Location: technology.php?msg=updated");
    exit;
}

// 4. Xử lý xóa công nghệ (?action=delete&id=...)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    // project_technology có ON DELETE CASCADE nên xóa technologies sẽ tự dọn liên kết liên quan
    execute("DELETE FROM `technologies` WHERE `id` = $delete_id");
    header("Location: technology.php?msg=deleted");
    exit;
}

// 5. Lấy danh sách công nghệ kèm số lượng đồ án đang dùng mỗi công nghệ
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

                <!-- Thông báo trạng thái -->
                <?php if (isset($_GET['msg'])): ?>
                    <?php if ($_GET['msg'] === 'added'): ?>
                        <div class="alert alert-success bg-opacity-10 bg-success text-success border-success border-opacity-20 small py-2 mb-4" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>Đã thêm công nghệ mới thành công!
                        </div>
                    <?php elseif ($_GET['msg'] === 'updated'): ?>
                        <div class="alert alert-success bg-opacity-10 bg-success text-success border-success border-opacity-20 small py-2 mb-4" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>Đã cập nhật công nghệ thành công!
                        </div>
                    <?php elseif ($_GET['msg'] === 'deleted'): ?>
                        <div class="alert alert-success bg-opacity-10 bg-success text-success border-success border-opacity-20 small py-2 mb-4" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>Đã xóa công nghệ khỏi hệ thống!
                        </div>
                    <?php elseif ($_GET['msg'] === 'duplicate'): ?>
                        <div class="alert alert-danger bg-opacity-10 bg-danger text-danger border-danger border-opacity-20 small py-2 mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Tên công nghệ này đã tồn tại, vui lòng chọn tên khác!
                        </div>
                    <?php elseif ($_GET['msg'] === 'empty'): ?>
                        <div class="alert alert-danger bg-opacity-10 bg-danger text-danger border-danger border-opacity-20 small py-2 mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Vui lòng nhập tên công nghệ!
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- FORM THÊM NHANH -->
                    <div class="col-lg-4">
                        <div class="card card-custom p-4">
                            <h3 class="fs-6 fw-bold mb-3 text-white"><i class="bi bi-plus-circle-fill text-teal me-2"></i>Thêm công nghệ mới</h3>
                            <form method="POST" action="technology.php">
                                <input type="hidden" name="action" value="add">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Tên công nghệ</label>
                                    <input type="text" name="name" id="name" class="form-control" placeholder="Ví dụ: Spring Boot, ReactJS, JWT..." required>
                                </div>
                                <button type="submit" class="btn btn-teal w-100">
                                    <i class="bi bi-plus-lg me-1"></i>Thêm vào danh sách
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- DANH SÁCH CÔNG NGHỆ -->
                    <div class="col-lg-8">
                        <div class="card card-custom p-4">
                            <div class="table-responsive">
                                <table class="table table-custom mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 8%">ID</th>
                                            <th>Tên công nghệ</th>
                                            <th style="width: 25%">Đang dùng ở</th>
                                            <th style="width: 20%" class="text-end">Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($technologies)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-5">Chưa có công nghệ nào. Thêm mới ở form bên trái!</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($technologies as $tech): ?>
                                                <tr>
                                                    <td class="text-secondary fw-bold"><?= $tech['id'] ?></td>
                                                    <td class="text-white"><?= htmlspecialchars($tech['name']) ?></td>
                                                    <td>
                                                        <span class="badge bg-secondary bg-opacity-20 text-white border border-secondary border-opacity-50 px-2 py-1">
                                                            <?= (int)$tech['project_count'] ?> đồ án
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-warning me-1 btn-edit-tech"
                                                                data-id="<?= $tech['id'] ?>"
                                                                data-name="<?= htmlspecialchars($tech['name'], ENT_QUOTES) ?>">
                                                            <i class="bi bi-pencil-square"></i> Sửa
                                                        </button>
                                                        <a href="technology.php?action=delete&id=<?= $tech['id'] ?>"
                                                           class="btn btn-sm btn-outline-danger"
                                                           onclick="return confirm('Xóa công nghệ &quot;<?= htmlspecialchars($tech['name'], ENT_QUOTES) ?>&quot;? Liên kết với các đồ án đang dùng công nghệ này cũng sẽ bị gỡ bỏ.');">
                                                            <i class="bi bi-trash3"></i> Xóa
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /.container-fluid -->
        </div><!-- /.admin-main -->
    </div><!-- /.admin-content -->
</div><!-- /.admin-layout -->

<!-- MODAL SỬA CÔNG NGHỆ -->
<div class="modal fade" id="editTechModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <form method="POST" action="technology.php" class="card card-custom p-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_tech_id">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="fs-6 fw-bold mb-0 text-white"><i class="bi bi-pencil-square text-teal me-2"></i>Sửa công nghệ</h3>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="mb-4">
                    <label for="edit_tech_name" class="form-label">Tên công nghệ</label>
                    <input type="text" name="name" id="edit_tech_name" class="form-control" required>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-teal px-4"><i class="bi bi-check-lg me-1"></i>Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/admin-sidebar.js"></script>
<script>
    // Đổ dữ liệu của dòng được bấm "Sửa" vào modal
    const editTechModalEl = document.getElementById('editTechModal');
    const editTechModal = new bootstrap.Modal(editTechModalEl);

    document.querySelectorAll('.btn-edit-tech').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('edit_tech_id').value = btn.dataset.id;
            document.getElementById('edit_tech_name').value = btn.dataset.name;
            editTechModal.show();
        });
    });
</script>
</body>
</html>