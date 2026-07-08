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

$current_admin_id = (int)($_SESSION['admin_id'] ?? 0);

// ============================================================
// 2. XỬ LÝ TẠO TÀI KHOẢN MỚI
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        header("Location: account.php?msg=invalid_token");
        exit;
    }

    $username         = trim($_POST['username'] ?? '');
    $fullname         = trim($_POST['fullname'] ?? '');
    $password         = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');

    if (empty($username) || empty($fullname) || empty($password)) {
        header("Location: account.php?msg=empty");
        exit;
    }

    if (strlen($password) < 6) {
        header("Location: account.php?msg=weak_password");
        exit;
    }

    if ($password !== $password_confirm) {
        header("Location: account.php?msg=password_mismatch");
        exit;
    }

    // Username chỉ cho phép chữ, số, gạch dưới, gạch ngang - tránh trùng ký tự lạ gây khó đăng nhập
    if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
        header("Location: account.php?msg=invalid_username");
        exit;
    }

    $safe_username = addslashes($username);

    $check = querySingleResult("SELECT id FROM `admins` WHERE `username` = '$safe_username' LIMIT 1");
    if ($check) {
        header("Location: account.php?msg=duplicate");
        exit;
    }

    $safe_fullname    = addslashes($fullname);
    $hashed_password  = password_hash($password, PASSWORD_BCRYPT);

    insert("INSERT INTO `admins` (`username`, `password`, `fullname`)
            VALUES ('$safe_username', '$hashed_password', '$safe_fullname')");

    header("Location: account.php?msg=added");
    exit;
}

// ============================================================
// 3. XỬ LÝ ĐỔI MẬT KHẨU CHO 1 TÀI KHOẢN
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        header("Location: account.php?msg=invalid_token");
        exit;
    }

    $edit_id          = (int)($_POST['id'] ?? 0);
    $password         = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');

    if ($edit_id <= 0 || empty($password)) {
        header("Location: account.php?msg=empty");
        exit;
    }

    if (strlen($password) < 6) {
        header("Location: account.php?msg=weak_password");
        exit;
    }

    if ($password !== $password_confirm) {
        header("Location: account.php?msg=password_mismatch");
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    execute("UPDATE `admins` SET `password` = '$hashed_password' WHERE `id` = $edit_id");

    header("Location: account.php?msg=password_updated");
    exit;
}

// ============================================================
// 4. XỬ LÝ XÓA TÀI KHOẢN (?action=delete&id=...)
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if (!csrf_verify($_GET['csrf_token'] ?? '')) {
        header("Location: account.php?msg=invalid_token");
        exit;
    }

    $delete_id = (int)$_GET['id'];

    // Không cho tự xóa chính tài khoản đang đăng nhập, tránh tự khóa mình ra khỏi hệ thống
    if ($delete_id === $current_admin_id) {
        header("Location: account.php?msg=cannot_delete_self");
        exit;
    }

    // Không cho xóa nếu đây là tài khoản admin cuối cùng còn lại trong hệ thống
    $total_admins_result = querySingleResult("SELECT COUNT(*) as total FROM `admins`");
    $total_admins = (int)($total_admins_result['total'] ?? 0);

    if ($total_admins <= 1) {
        header("Location: account.php?msg=cannot_delete_last");
        exit;
    }

    execute("DELETE FROM `admins` WHERE `id` = $delete_id");
    header("Location: account.php?msg=deleted");
    exit;
}

// ============================================================
// 5. LẤY DANH SÁCH TÀI KHOẢN ADMIN
// ============================================================
$admins = executeresult("SELECT `id`, `username`, `fullname`, `created_at` FROM `admins` ORDER BY `id` ASC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Tài khoản Admin</title>
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
                        <h2 class="fw-bold mb-1">Quản lý Tài khoản Admin</h2>
                        <p class="text-secondary small mb-0">Tạo mới, đổi mật khẩu hoặc thu hồi quyền truy cập của quản trị viên.</p>
                    </div>
                </div>

                <!-- Thông báo trạng thái -->
                <?php if (isset($_GET['msg'])): ?>
                    <?php
                        $messages = [
                            'added'                => ['success', 'Đã tạo tài khoản admin mới thành công!'],
                            'password_updated'     => ['success', 'Đã cập nhật mật khẩu thành công!'],
                            'deleted'               => ['success', 'Đã xóa tài khoản khỏi hệ thống!'],
                            'duplicate'             => ['danger', 'Tài khoản (username) này đã tồn tại, vui lòng chọn tên khác!'],
                            'empty'                 => ['danger', 'Vui lòng điền đầy đủ thông tin bắt buộc!'],
                            'weak_password'         => ['danger', 'Mật khẩu phải có ít nhất 6 ký tự!'],
                            'password_mismatch'     => ['danger', 'Mật khẩu nhập lại không khớp!'],
                            'invalid_username'      => ['danger', 'Username chỉ được chứa chữ, số, gạch dưới, gạch ngang, dấu chấm (3-50 ký tự)!'],
                            'invalid_token'         => ['danger', 'Phiên làm việc đã hết hạn hoặc yêu cầu không hợp lệ, vui lòng thử lại!'],
                            'cannot_delete_self'    => ['danger', 'Không thể tự xóa chính tài khoản đang đăng nhập!'],
                            'cannot_delete_last'    => ['danger', 'Không thể xóa vì đây là tài khoản admin cuối cùng trong hệ thống!'],
                        ];
                        $msg_key = $_GET['msg'];
                    ?>
                    <?php if (isset($messages[$msg_key])): ?>
                        <?php [$type, $text] = $messages[$msg_key]; ?>
                        <div class="alert alert-<?= $type ?> bg-opacity-10 bg-<?= $type ?> text-<?= $type ?> border-<?= $type ?> border-opacity-20 small py-2 mb-4" role="alert">
                            <i class="bi <?= $type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
                            <?= htmlspecialchars($text) ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- FORM TẠO TÀI KHOẢN MỚI -->
                    <div class="col-lg-4">
                        <div class="card card-custom p-4">
                            <h3 class="fs-6 fw-bold mb-3"><i class="bi bi-person-plus-fill text-teal me-2"></i>Tạo tài khoản mới</h3>
                            <form method="POST" action="account.php" id="addAccountForm" autocomplete="off">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="add">

                                <div class="mb-3">
                                    <label for="fullname" class="form-label">Họ và tên</label>
                                    <input type="text" name="fullname" id="fullname" class="form-control" placeholder="Ví dụ: Nguyễn Văn A" required>
                                </div>

                                <div class="mb-3">
                                    <label for="username" class="form-label">Tài khoản (username)</label>
                                    <input type="text" name="username" id="username" class="form-control" placeholder="Dùng để đăng nhập..." pattern="[a-zA-Z0-9_.-]{3,50}" title="3-50 ký tự: chữ, số, gạch dưới, gạch ngang, dấu chấm" required>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Mật khẩu</label>
                                    <div class="input-group">
                                        <input type="password" name="password" id="password" class="form-control" placeholder="Tối thiểu 6 ký tự" minlength="6" required>
                                        <button class="btn btn-outline-secondary btn-toggle-password" type="button" data-target="password">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="password_confirm" class="form-label">Nhập lại mật khẩu</label>
                                    <input type="password" name="password_confirm" id="password_confirm" class="form-control" placeholder="Nhập lại mật khẩu ở trên" minlength="6" required>
                                    <div class="form-text small" id="passwordMatchHint"></div>
                                </div>

                                <button type="submit" class="btn btn-teal w-100">
                                    <i class="bi bi-check-lg me-1"></i>Tạo tài khoản
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- DANH SÁCH TÀI KHOẢN -->
                    <div class="col-lg-8">
                        <div class="card card-custom p-4">
                            <div class="table-responsive">
                                <table class="table table-custom mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 8%">ID</th>
                                            <th>Họ và tên</th>
                                            <th>Username</th>
                                            <th>Ngày tạo</th>
                                            <th class="text-end">Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($admins)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-5">Chưa có tài khoản nào.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($admins as $admin): ?>
                                                <tr>
                                                    <td class="text-secondary fw-bold"><?= $admin['id'] ?></td>
                                                    <td class="fw-semibold text-white">
                                                        <?= htmlspecialchars($admin['fullname'] ?? '') ?>
                                                        <?php if ((int)$admin['id'] === $current_admin_id): ?>
                                                            <span class="badge bg-teal bg-opacity-20 text-teal border border-teal border-opacity-50 ms-1">Bạn</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><code class="text-teal"><?= htmlspecialchars($admin['username']) ?></code></td>
                                                    <td class="text-secondary small"><?= htmlspecialchars($admin['created_at'] ?? '') ?></td>
                                                    <td class="text-end">
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-warning me-1 btn-change-password"
                                                                data-id="<?= $admin['id'] ?>"
                                                                data-fullname="<?= htmlspecialchars($admin['fullname'] ?? '', ENT_QUOTES) ?>">
                                                            <i class="bi bi-key-fill"></i> Đổi mật khẩu
                                                        </button>

                                                        <?php if ((int)$admin['id'] !== $current_admin_id): ?>
                                                            <a href="account.php?action=delete&id=<?= $admin['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>"
                                                               class="btn btn-sm btn-outline-danger"
                                                               onclick="return confirm('Xóa tài khoản &quot;<?= htmlspecialchars($admin['username'], ENT_QUOTES) ?>&quot;? Hành động này không thể hoàn tác.');">
                                                                <i class="bi bi-trash3"></i> Xóa
                                                            </a>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Không thể tự xóa tài khoản đang đăng nhập">
                                                                <i class="bi bi-trash3"></i> Xóa
                                                            </button>
                                                        <?php endif; ?>
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

<!-- MODAL ĐỔI MẬT KHẨU -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <form method="POST" action="account.php" class="card card-custom p-4" autocomplete="off">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="id" id="change_id">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="fs-6 fw-bold mb-0"><i class="bi bi-key-fill text-teal me-2"></i>Đổi mật khẩu cho <span id="change_fullname" class="text-teal"></span></h3>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="mb-3">
                    <label for="change_password" class="form-label">Mật khẩu mới</label>
                    <input type="password" name="password" id="change_password" class="form-control" placeholder="Tối thiểu 6 ký tự" minlength="6" required>
                </div>
                <div class="mb-4">
                    <label for="change_password_confirm" class="form-label">Nhập lại mật khẩu mới</label>
                    <input type="password" name="password_confirm" id="change_password_confirm" class="form-control" placeholder="Nhập lại mật khẩu ở trên" minlength="6" required>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-teal px-4"><i class="bi bi-check-lg me-1"></i>Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/admin-sidebar.js"></script>
<script>
    // Đổ dữ liệu vào modal đổi mật khẩu khi bấm nút tương ứng ở từng dòng
    const changePasswordModalEl = document.getElementById('changePasswordModal');
    const changePasswordModal = new bootstrap.Modal(changePasswordModalEl);

    document.querySelectorAll('.btn-change-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('change_id').value = btn.dataset.id;
            document.getElementById('change_fullname').textContent = btn.dataset.fullname || '';
            changePasswordModal.show();
        });
    });

    // Đóng modal thì xóa trắng mật khẩu đã gõ, tránh lộ ra nếu người khác dùng chung máy
    changePasswordModalEl.addEventListener('hidden.bs.modal', function () {
        changePasswordModalEl.querySelector('form').reset();
    });

    // Hiện/ẩn mật khẩu ở form tạo tài khoản
    document.querySelectorAll('.btn-toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = document.getElementById(btn.dataset.target);
            const icon = btn.querySelector('i');
            const isHidden = target.type === 'password';
            target.type = isHidden ? 'text' : 'password';
            icon.classList.toggle('bi-eye', !isHidden);
            icon.classList.toggle('bi-eye-slash', isHidden);
        });
    });

    // Cảnh báo ngay khi 2 ô mật khẩu gõ không khớp, trước khi submit
    const pwInput = document.getElementById('password');
    const pwConfirmInput = document.getElementById('password_confirm');
    const pwHint = document.getElementById('passwordMatchHint');

    function checkPasswordMatch() {
        if (pwConfirmInput.value === '') {
            pwHint.textContent = '';
            return;
        }
        if (pwInput.value === pwConfirmInput.value) {
            pwHint.textContent = 'Mật khẩu khớp.';
            pwHint.className = 'form-text small text-success';
        } else {
            pwHint.textContent = 'Mật khẩu chưa khớp.';
            pwHint.className = 'form-text small text-danger';
        }
    }
    pwInput.addEventListener('input', checkPasswordMatch);
    pwConfirmInput.addEventListener('input', checkPasswordMatch);
</script>
</body>
</html>