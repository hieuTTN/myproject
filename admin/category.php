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
require_once('includes/csrf.php');

/**
 * Chuyển tên tiếng Việt có dấu thành slug không dấu, chỉ gồm a-z, 0-9 và dấu gạch ngang.
 * Ví dụ: "Java / Spring Boot" -> "java-spring-boot"
 */
function slugify_vn($str) {
    $str = mb_strtolower(trim($str), 'UTF-8');
    $vietnamese = ['à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ',
                   'è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ',
                   'ì','í','ị','ỉ','ĩ',
                   'ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ',
                   'ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ',
                   'ỳ','ý','ỵ','ỷ','ỹ','đ'];
    $latin      = ['a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
                   'e','e','e','e','e','e','e','e','e','e','e',
                   'i','i','i','i','i',
                   'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
                   'u','u','u','u','u','u','u','u','u','u','u',
                   'y','y','y','y','y','d'];
    $str = str_replace($vietnamese, $latin, $str);
    $str = preg_replace('/[^a-z0-9]+/', '-', $str);
    return trim($str, '-');
}

// 2. XỬ LÝ THÊM DANH MỤC MỚI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        header("Location: category.php?msg=invalid_token");
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if ($slug === '') $slug = slugify_vn($name);
    else $slug = slugify_vn($slug);

    if (empty($name) || empty($slug)) {
        header("Location: category.php?msg=empty");
        exit;
    }

    $check = querySingleResult("SELECT id FROM `categories` WHERE `slug` = '" . addslashes($slug) . "' LIMIT 1");
    if ($check) {
        header("Location: category.php?msg=duplicate");
        exit;
    }

    $safe_name = addslashes($name);
    $safe_slug = addslashes($slug);
    insert("INSERT INTO `categories` (`slug`, `name`) VALUES ('$safe_slug', '$safe_name')");
    header("Location: category.php?msg=added");
    exit;
}

// 3. XỬ LÝ CẬP NHẬT DANH MỤC
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        header("Location: category.php?msg=invalid_token");
        exit;
    }

    $edit_id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if ($slug === '') $slug = slugify_vn($name);
    else $slug = slugify_vn($slug);

    if (empty($name) || empty($slug) || $edit_id <= 0) {
        header("Location: category.php?msg=empty");
        exit;
    }

    $check = querySingleResult("SELECT id FROM `categories` WHERE `slug` = '" . addslashes($slug) . "' AND `id` != $edit_id LIMIT 1");
    if ($check) {
        header("Location: category.php?msg=duplicate");
        exit;
    }

    $safe_name = addslashes($name);
    $safe_slug = addslashes($slug);
    execute("UPDATE `categories` SET `slug` = '$safe_slug', `name` = '$safe_name' WHERE `id` = $edit_id");
    header("Location: category.php?msg=updated");
    exit;
}

// 4. XỬ LÝ XÓA DANH MỤC (?action=delete&id=...)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if (!csrf_verify($_GET['csrf_token'] ?? '')) {
        header("Location: category.php?msg=invalid_token");
        exit;
    }

    $delete_id = (int)$_GET['id'];
    // categories.id được projects.category_id tham chiếu với ON DELETE SET NULL
    // nên xóa danh mục an toàn, các đồ án liên quan sẽ tự chuyển về "Chưa phân loại"
    execute("DELETE FROM `categories` WHERE `id` = $delete_id");
    header("Location: category.php?msg=deleted");
    exit;
}

// 5. LẤY DANH SÁCH DANH MỤC KÈM SỐ ĐỒ ÁN ĐANG DÙNG
$categories = executeresult(
    "SELECT c.*, COUNT(p.id) AS project_count
     FROM `categories` c
     LEFT JOIN `projects` p ON p.category_id = c.id
     GROUP BY c.id
     ORDER BY c.id ASC"
);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Danh mục</title>
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
                        <h2 class="fw-bold mb-1">Quản lý Danh mục</h2>
                        <p class="text-secondary small mb-0">Các danh mục lớn dùng để phân loại &amp; lọc đồ án (Java/Spring, PHP/Laravel, C#/.NET,...)</p>
                    </div>
                </div>

                <!-- Thông báo trạng thái -->
                <?php if (isset($_GET['msg'])): ?>
                    <?php if ($_GET['msg'] === 'added'): ?>
                        <div class="alert alert-success bg-opacity-10 bg-success text-success border-success border-opacity-20 small py-2 mb-4" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>Đã thêm danh mục mới thành công!
                        </div>
                    <?php elseif ($_GET['msg'] === 'updated'): ?>
                        <div class="alert alert-success bg-opacity-10 bg-success text-success border-success border-opacity-20 small py-2 mb-4" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>Đã cập nhật danh mục thành công!
                        </div>
                    <?php elseif ($_GET['msg'] === 'deleted'): ?>
                        <div class="alert alert-success bg-opacity-10 bg-success text-success border-success border-opacity-20 small py-2 mb-4" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>Đã xóa danh mục khỏi hệ thống!
                        </div>
                    <?php elseif ($_GET['msg'] === 'duplicate'): ?>
                        <div class="alert alert-danger bg-opacity-10 bg-danger text-danger border-danger border-opacity-20 small py-2 mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Slug này đã tồn tại, vui lòng chọn tên/slug khác!
                        </div>
                    <?php elseif ($_GET['msg'] === 'empty'): ?>
                        <div class="alert alert-danger bg-opacity-10 bg-danger text-danger border-danger border-opacity-20 small py-2 mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Vui lòng nhập đầy đủ tên danh mục!
                        </div>
                    <?php elseif ($_GET['msg'] === 'invalid_token'): ?>
                        <div class="alert alert-danger bg-opacity-10 bg-danger text-danger border-danger border-opacity-20 small py-2 mb-4" role="alert">
                            <i class="bi bi-shield-exclamation me-2"></i>Phiên làm việc đã hết hạn hoặc yêu cầu không hợp lệ, vui lòng thử lại!
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- FORM THÊM DANH MỤC -->
                    <div class="col-lg-4">
                        <div class="card card-custom p-4">
                            <h3 class="fs-6 fw-bold mb-3"><i class="bi bi-plus-circle-fill text-teal me-2"></i>Thêm danh mục mới</h3>
                            <form method="POST" action="category.php" id="addCategoryForm">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="add">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Tên danh mục</label>
                                    <input type="text" name="name" id="name" class="form-control" placeholder="Ví dụ: Java / Spring Boot" required>
                                </div>
                                <div class="mb-3">
                                    <label for="slug" class="form-label">Slug (để trống sẽ tự sinh từ tên)</label>
                                    <input type="text" name="slug" id="slug" class="form-control" placeholder="vi-du: java-spring">
                                </div>
                                <button type="submit" class="btn btn-teal w-100">
                                    <i class="bi bi-plus-lg me-1"></i>Thêm danh mục
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- DANH SÁCH DANH MỤC -->
                    <div class="col-lg-8">
                        <div class="card card-custom p-4">
                            <div class="table-responsive">
                                <table class="table table-custom mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 8%">ID</th>
                                            <th>Tên danh mục</th>
                                            <th>Slug</th>
                                            <th style="width: 15%">Số đồ án</th>
                                            <th style="width: 18%" class="text-end">Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($categories)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-5">Chưa có danh mục nào. Thêm mới ở form bên trái!</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($categories as $cat): ?>
                                                <tr>
                                                    <td class="text-secondary fw-bold"><?= $cat['id'] ?></td>
                                                    <td class="fw-semibold text-white"><?= htmlspecialchars($cat['name']) ?></td>
                                                    <td><code class="text-teal"><?= htmlspecialchars($cat['slug']) ?></code></td>
                                                    <td>
                                                        <span class="badge bg-secondary bg-opacity-20 text-white border border-secondary border-opacity-50 px-2 py-1">
                                                            <?= (int)$cat['project_count'] ?> đồ án
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-warning me-1 btn-edit-category"
                                                                data-id="<?= $cat['id'] ?>"
                                                                data-name="<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>"
                                                                data-slug="<?= htmlspecialchars($cat['slug'], ENT_QUOTES) ?>">
                                                            <i class="bi bi-pencil-square"></i> Sửa
                                                        </button>
                                                        <a href="category.php?action=delete&id=<?= $cat['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>"
                                                           class="btn btn-sm btn-outline-danger"
                                                           onclick="return confirm('Xóa danh mục &quot;<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>&quot;? Các đồ án đang thuộc danh mục này sẽ chuyển về \'Chưa phân loại\'.');">
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

<!-- MODAL SỬA DANH MỤC -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <form method="POST" action="category.php" class="card card-custom p-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="fs-6 fw-bold mb-0"><i class="bi bi-pencil-square text-teal me-2"></i>Sửa danh mục</h3>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="mb-3">
                    <label for="edit_name" class="form-label">Tên danh mục</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>
                <div class="mb-4">
                    <label for="edit_slug" class="form-label">Slug</label>
                    <input type="text" name="slug" id="edit_slug" class="form-control" required>
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
    const editCategoryModalEl = document.getElementById('editCategoryModal');
    const editCategoryModal = new bootstrap.Modal(editCategoryModalEl);

    document.querySelectorAll('.btn-edit-category').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('edit_id').value = btn.dataset.id;
            document.getElementById('edit_name').value = btn.dataset.name;
            document.getElementById('edit_slug').value = btn.dataset.slug;
            editCategoryModal.show();
        });
    });

    // Gợi ý tự sinh slug từ tên khi thêm mới (chỉ gợi ý khi slug đang trống)
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    nameInput.addEventListener('input', function () {
        if (slugInput.dataset.touched === 'true') return;
        slugInput.value = nameInput.value
            .toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd').replace(/Đ/g, 'D')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    });
    slugInput.addEventListener('input', function () {
        slugInput.dataset.touched = 'true';
    });
</script>
</body>
</html>