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

// 2. XỬ LÝ THÊM MỚI TÀI KHOẢN CLOUDINARY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $gmail = trim($_POST['gmail'] ?? '');
    $cloud_name = trim($_POST['cloud_name'] ?? '');
    $api_key = trim($_POST['api_key'] ?? '');
    $api_secret = trim($_POST['api_secret'] ?? '');
    $is_primary = isset($_POST['is_primary']) ? 1 : 0;

    if (empty($gmail) || empty($cloud_name) || empty($api_key) || empty($api_secret)) {
        header("Location: cloudkey.php?msg=empty");
        exit;
    }

    // Kiểm tra trùng lặp cloud_name
    $check = querySingleResult("SELECT id FROM `cloudinary_accounts` WHERE `cloud_name` = '" . addslashes($cloud_name) . "' LIMIT 1");
    if ($check) {
        header("Location: cloudkey.php?msg=duplicate");
        exit;
    }

    // Nếu đặt làm tài khoản chính, bỏ chọn các tài khoản khác trước
    if ($is_primary === 1) {
        execute("UPDATE `cloudinary_accounts` SET `is_primary` = 0");
    }

    $safe_gmail = addslashes($gmail);
    $safe_cloud = addslashes($cloud_name);
    $safe_key = addslashes($api_key);
    $safe_secret = addslashes($api_secret);

    insert("INSERT INTO `cloudinary_accounts` (`gmail`, `cloud_name`, `api_key`, `api_secret`, `is_primary`) 
            VALUES ('$safe_gmail', '$safe_cloud', '$safe_key', '$safe_secret', $is_primary)");
    
    header("Location: cloudkey.php?msg=added");
    exit;
}

// 3. XỬ LÝ CẬP NHẬT TÀI KHOẢN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $edit_id = (int)($_POST['id'] ?? 0);
    $gmail = trim($_POST['gmail'] ?? '');
    $cloud_name = trim($_POST['cloud_name'] ?? '');
    $api_key = trim($_POST['api_key'] ?? '');
    $api_secret = trim($_POST['api_secret'] ?? '');
    $is_primary = isset($_POST['is_primary']) ? 1 : 0;

    if (empty($gmail) || empty($cloud_name) || empty($api_key) || empty($api_secret) || $edit_id <= 0) {
        header("Location: cloudkey.php?msg=empty");
        exit;
    }

    // Kiểm tra trùng lặp cloud_name với tài khoản khác
    $check = querySingleResult("SELECT id FROM `cloudinary_accounts` WHERE `cloud_name` = '" . addslashes($cloud_name) . "' AND `id` != $edit_id LIMIT 1");
    if ($check) {
        header("Location: cloudkey.php?msg=duplicate");
        exit;
    }

    if ($is_primary === 1) {
        execute("UPDATE `cloudinary_accounts` SET `is_primary` = 0");
    }

    $safe_gmail = addslashes($gmail);
    $safe_cloud = addslashes($cloud_name);
    $safe_key = addslashes($api_key);
    $safe_secret = addslashes($api_secret);

    execute("UPDATE `cloudinary_accounts` SET 
                `gmail` = '$safe_gmail', 
                `cloud_name` = '$safe_cloud', 
                `api_key` = '$safe_key', 
                `api_secret` = '$safe_secret', 
                `is_primary` = $is_primary 
            WHERE `id` = $edit_id");

    header("Location: cloudkey.php?msg=updated");
    exit;
}

// 4. XỬ LÝ XÓA TÀI KHOẢN
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];
    execute("DELETE FROM `cloudinary_accounts` WHERE `id` = $delete_id");
    header("Location: cloudkey.php?msg=deleted");
    exit;
}

// LẤY DANH SÁCH TÀI KHOẢN ĐỂ HIỂN THỊ
// Hãy chắc chắn rằng hàm `query` hoặc tương đương của bạn có thể trả về mảng dữ liệu.
// Ví dụ giả định: $cloudinary_accounts = query("SELECT * FROM `cloudinary_accounts` ORDER BY `id` DESC");
// Để tránh lỗi nếu chưa có hàm phù hợp, bạn tự thay thế dòng này bằng hàm lấy dữ liệu từ kết nối database của bạn nhé.
$cloudinary_accounts = executeresult("SELECT * FROM `cloudinary_accounts` ORDER BY is_primary DESC, id DESC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Cloud Key Cloudinary</title>
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
                        <h2 class="fw-bold mb-1">Quản lý API Key Cloudinary</h2>
                        <p class="text-secondary small mb-0 font-monospace">Lưu trữ cấu hình SDK upload ảnh và tài nguyên lên đám mây</p>
                    </div>
                </div>

                <!-- Thông báo trạng thái -->
                <?php if (isset($_GET['msg'])): ?>
                    <?php if ($_GET['msg'] === 'added'): ?>
                        <div class="alert alert-success bg-opacity-10 bg-success text-success border-success border-opacity-20 small py-2 mb-4" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>Đã cấu hình tài khoản Cloudinary mới thành công!
                        </div>
                    <?php elseif ($_GET['msg'] === 'updated'): ?>
                        <div class="alert alert-success bg-opacity-10 bg-success text-success border-success border-opacity-20 small py-2 mb-4" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>Đã cập nhật thông tin tài khoản thành công!
                        </div>
                    <?php elseif ($_GET['msg'] === 'deleted'): ?>
                        <div class="alert alert-success bg-opacity-10 bg-success text-success border-success border-opacity-20 small py-2 mb-4" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>Đã xóa tài khoản cấu hình khỏi hệ thống!
                        </div>
                    <?php elseif ($_GET['msg'] === 'empty'): ?>
                        <div class="alert alert-danger bg-opacity-10 bg-danger text-danger border-danger border-opacity-20 small py-2 mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Vui lòng nhập đầy đủ các thông tin bắt buộc!
                        </div>
                    <?php elseif ($_GET['msg'] === 'duplicate'): ?>
                        <div class="alert alert-danger bg-opacity-10 bg-danger text-danger border-danger border-opacity-20 small py-2 mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Cloud Name này đã tồn tại trong hệ thống!
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- FORM THÊM TÀI KHOẢN -->
                    <div class="col-lg-4">
                        <div class="card card-custom p-4">
                            <h3 class="fs-6 fw-bold mb-3"><i class="bi bi-cloud-plus-fill text-teal me-2"></i>Thêm cấu hình mới</h3>
                            <form method="POST" action="cloudkey.php">
                                <input type="hidden" name="action" value="add">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Gmail Đăng Ký</label>
                                    <input type="email" name="gmail" class="form-control" placeholder="example@gmail.com" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Cloud Name</label>
                                    <input type="text" name="cloud_name" class="form-control" placeholder="Nhập Cloud Name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">API Key</label>
                                    <input type="text" name="api_key" class="form-control" placeholder="Nhập API Key" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">API Secret</label>
                                    <input type="password" name="api_secret" class="form-control" placeholder="Nhập API Secret" required>
                                </div>
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_primary" id="is_primary_add">
                                    <label class="form-check-input-label small text-warning" for="is_primary_add">Đặt làm tài khoản chạy chính</label>
                                </div>
                                <button type="submit" class="btn btn-teal w-100">
                                    <i class="bi bi-plus-lg me-1"></i>Lưu cấu hình
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- DANH SÁCH TÀI KHOẢN -->
                    <div class="col-lg-8">
                        <div class="card card-custom p-4">
                            <div class="table-responsive">
                                <table class="table table-custom mb-0" style="vertical-align: middle;">
                                    <thead>
                                        <tr>
                                            <th style="width: 5%">ID</th>
                                            <th>Thông tin Cloud</th>
                                            <th>Thông tin Auth</th>
                                            <th style="width: 15%">Trạng thái</th>
                                            <th style="width: 18%" class="text-end">Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($cloudinary_accounts)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-5">Chưa có thông tin Cloud Key nào được thiết lập.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($cloudinary_accounts as $account): ?>
                                                <tr>
                                                    <td class="text-secondary fw-bold"><?= $account['id'] ?></td>
                                                    <td>
                                                        <div class="fw-semibold text-white mb-1"><?= htmlspecialchars($account['cloud_name']) ?></div>
                                                        <small class="text-secondary"><?= htmlspecialchars($account['gmail']) ?></small>
                                                    </td>
                                                    <td>
                                                        <div class="small text-white font-monospace">Key: <?= htmlspecialchars($account['api_key']) ?></div>
                                                        <div class="small text-white font-monospace">Secret: *********</div>
                                                    </td>
                                                    <td>
                                                        <?php if ($account['is_primary'] == 1): ?>
                                                            <span class="badge bg-success bg-opacity-20 text-white border border-success border-opacity-50 px-2 py-1">
                                                                <i class="bi bi-patch-check-fill me-1"></i> Chạy chính
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary bg-opacity-20 text-white border border-secondary border-opacity-50 px-2 py-1">
                                                                Dự phòng
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-warning me-1 btn-edit-account"
                                                                data-id="<?= $account['id'] ?>"
                                                                data-gmail="<?= htmlspecialchars($account['gmail'], ENT_QUOTES) ?>"
                                                                data-cloud_name="<?= htmlspecialchars($account['cloud_name'], ENT_QUOTES) ?>"
                                                                data-api_key="<?= htmlspecialchars($account['api_key'], ENT_QUOTES) ?>"
                                                                data-api_secret="<?= htmlspecialchars($account['api_secret'], ENT_QUOTES) ?>"
                                                                data-is_primary="<?= $account['is_primary'] ?>">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </button>
                                                        <a href="cloudkey.php?action=delete&id=<?= $account['id'] ?>"
                                                           class="btn btn-sm btn-outline-danger"
                                                           onclick="return confirm('Bạn thực sự muốn gỡ bỏ cấu hình cloud: <?= htmlspecialchars($account['cloud_name'], ENT_QUOTES) ?>?');">
                                                            <i class="bi bi-trash3"></i>
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

            </div>
        </div>
    </div>
</div>

<!-- MODAL SỬA CẤU HÌNH -->
<div class="modal fade" id="editAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <form method="POST" action="cloudkey.php" class="card card-custom p-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="fs-6 fw-bold mb-0"><i class="bi bi-pencil-square text-teal me-2"></i>Sửa cấu hình Cloud</h3>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Gmail Đăng Ký</label>
                    <input type="email" name="gmail" id="edit_gmail" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Cloud Name</label>
                    <input type="text" name="cloud_name" id="edit_cloud_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">API Key</label>
                    <input type="text" name="api_key" id="edit_api_key" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">API Secret</label>
                    <input type="password" name="api_secret" id="edit_api_secret" class="form-control" placeholder="Để trống nếu giữ nguyên" required>
                </div>
                <div class="mb-4 form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_primary" id="edit_is_primary">
                    <label class="form-check-input-label small text-warning" for="edit_is_primary">Đặt làm tài khoản chạy chính</label>
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
    const editAccountModalEl = document.getElementById('editAccountModal');
    const editAccountModal = new bootstrap.Modal(editAccountModalEl);

    document.querySelectorAll('.btn-edit-account').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('edit_id').value = btn.dataset.id;
            document.getElementById('edit_gmail').value = btn.dataset.gmail;
            document.getElementById('edit_cloud_name').value = btn.dataset.cloud_name;
            document.getElementById('edit_api_key').value = btn.dataset.api_key;
            document.getElementById('edit_api_secret').value = btn.dataset.api_secret;
            
            // Xử lý nút bật/tắt (Switch primary)
            document.getElementById('edit_is_primary').checked = (btn.dataset.is_primary == '1');
            
            editAccountModal.show();
        });
    });
</script>
</body>
</html>