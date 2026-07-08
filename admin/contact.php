<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Kiểm tra quyền truy cập admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

require_once('../service/ContactService.php');

// 2. Đọc tham số lọc trạng thái + phân trang từ query string
$status = $_GET['status'] ?? 'all';
if (!in_array($status, ['all', 'unread', 'read'], true)) {
    $status = 'all';
}
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

/**
 * Hàm hỗ trợ: dựng lại URL của trang, giữ nguyên trạng thái lọc + trang hiện tại,
 * cho phép ghi đè một vài tham số (vd: action, id, page)
 */
function buildContactUrl($overrides = []) {
    global $status, $page;
    $params = [];
    if ($status !== 'all') {
        $params['status'] = $status;
    }
    if ($page > 1) {
        $params['page'] = $page;
    }
    foreach ($overrides as $key => $value) {
        if ($value === null) {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }
    return 'contact.php' . (!empty($params) ? '?' . http_build_query($params) : '');
}

// 3. Xử lý xem chi tiết (?action=view&id=...) -> tự động đánh dấu đã đọc
$openedId = 0;
if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
    $openedId = (int)$_GET['id'];
    markContactRead($openedId);
    header("Location: " . buildContactUrl(['action' => null, 'id' => null, 'opened' => $openedId]));
    exit;
}

// 4. Xử lý đánh dấu chưa đọc (?action=unread&id=...)
if (isset($_GET['action']) && $_GET['action'] === 'unread' && isset($_GET['id'])) {
    markContactUnread((int)$_GET['id']);
    header("Location: " . buildContactUrl(['action' => null, 'id' => null]));
    exit;
}

// 5. Xử lý xóa liên hệ (?action=delete&id=...)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    deleteContact((int)$_GET['id']);
    header("Location: " . buildContactUrl(['action' => null, 'id' => null, 'msg' => 'deleted']));
    exit;
}

// 6. Tính tổng số bản ghi & tổng số trang theo trạng thái lọc hiện tại
$totalRows  = countContacts($status);
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

// 7. Lấy danh sách liên hệ của trang hiện tại + số lượng chưa đọc (dùng cho badge)
$contacts    = getContacts($status, $perPage, $offset);
$unreadCount = countUnreadContacts();

// Nếu URL có ?opened=X (vừa xem xong) thì lấy id đó ra để JS tự mở modal
$openedFromQuery = isset($_GET['opened']) ? (int)$_GET['opened'] : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Liên hệ</title>
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
                        <h2 class="fw-bold mb-1">Quản lý Liên hệ</h2>
                        <p class="text-secondary small mb-0">Danh sách yêu cầu liên hệ gửi từ form ở trang chủ</p>
                    </div>
                </div>

                <!-- Thông báo xóa thành công nếu có -->
                <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
                    <div class="alert alert-success bg-opacity-10 bg-success text-success border-success border-opacity-20 small py-2 mb-4" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>Đã xóa liên hệ khỏi hệ thống!
                    </div>
                <?php endif; ?>

                <!-- TABS LỌC TRẠNG THÁI -->
                <ul class="nav nav-pills mb-4 gap-2">
                    <li class="nav-item">
                        <a class="nav-link <?= $status === 'all' ? 'active' : '' ?>" href="<?= htmlspecialchars(buildContactUrl(['status' => null, 'page' => null])) ?>">
                            Tất cả
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $status === 'unread' ? 'active' : '' ?>" href="<?= htmlspecialchars(buildContactUrl(['status' => 'unread', 'page' => null])) ?>">
                            Chưa đọc
                            <?php if ($unreadCount > 0): ?>
                                <span class="badge bg-danger ms-1"><?= $unreadCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $status === 'read' ? 'active' : '' ?>" href="<?= htmlspecialchars(buildContactUrl(['status' => 'read', 'page' => null])) ?>">
                            Đã đọc
                        </a>
                    </li>
                </ul>

                <!-- DANH SÁCH LIÊN HỆ -->
                <div class="card card-custom p-4">
                    <div class="table-responsive">
                        <table class="table table-custom mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 5%">ID</th>
                                    <th style="width: 8%">Trạng thái</th>
                                    <th style="width: 15%">Họ tên</th>
                                    <th style="width: 13%">Zalo / SĐT</th>
                                    <th style="width: 15%">Công nghệ</th>
                                    <th style="width: 15%">Thời gian gửi</th>
                                    <th style="width: 15%" class="text-end">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($contacts)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">
                                            <?php if ($status === 'unread'): ?>
                                                Không có liên hệ nào chưa đọc.
                                            <?php elseif ($status === 'read'): ?>
                                                Chưa có liên hệ nào được đánh dấu đã đọc.
                                            <?php else: ?>
                                                Chưa có liên hệ nào được gửi từ trang chủ.
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($contacts as $c): ?>
                                        <tr class="contact-row-item <?= (int)$c['is_read'] === 0 ? 'fw-semibold' : '' ?>"
                                            data-id="<?= (int)$c['id'] ?>"
                                            data-fullname="<?= htmlspecialchars($c['fullname'], ENT_QUOTES) ?>"
                                            data-phone="<?= htmlspecialchars($c['phone'], ENT_QUOTES) ?>"
                                            data-technology="<?= htmlspecialchars($c['technology'] ?: 'Chưa rõ', ENT_QUOTES) ?>"
                                            data-message="<?= htmlspecialchars($c['message'] ?: 'Không có mô tả.', ENT_QUOTES) ?>"
                                            data-created="<?= htmlspecialchars(date('H:i d/m/Y', strtotime($c['created_at'])), ENT_QUOTES) ?>">
                                            <td class="text-secondary fw-bold"><?= (int)$c['id'] ?></td>
                                            <td>
                                                <?php if ((int)$c['is_read'] === 0): ?>
                                                    <span class="badge bg-danger bg-opacity-20 border border-danger text-white border-opacity-50 px-2 py-1">
                                                        <i class="bi bi-envelope-fill me-1"></i>Chưa đọc
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary bg-opacity-20 border border-secondary text-white border-opacity-50 px-2 py-1">
                                                        <i class="bi bi-envelope-open-fill me-1"></i>Đã đọc
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-white"><?= htmlspecialchars($c['fullname']) ?></td>
                                            <td class="text-white"><?= htmlspecialchars($c['phone']) ?></td>
                                            <td>
                                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-20 small">
                                                    <?= htmlspecialchars($c['technology'] ?: 'Chưa rõ') ?>
                                                </span>
                                            </td>
                                            <td class="text-secondary small"><?= htmlspecialchars(date('H:i d/m/Y', strtotime($c['created_at']))) ?></td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-outline-info mb-1 w-100 btn-view-contact" data-id="<?= (int)$c['id'] ?>">
                                                    <i class="bi bi-eye-fill"></i> Xem
                                                </button>
                                                <?php if ((int)$c['is_read'] === 1): ?>
                                                    <a href="<?= htmlspecialchars(buildContactUrl(['action' => 'unread', 'id' => $c['id']])) ?>" class="btn btn-sm btn-outline-warning mb-1 w-100">
                                                        <i class="bi bi-envelope-fill"></i> Đánh dấu chưa đọc
                                                    </a>
                                                <?php endif; ?>
                                                <a href="<?= htmlspecialchars(buildContactUrl(['action' => 'delete', 'id' => $c['id']])) ?>" class="btn btn-sm btn-outline-danger w-100"
                                                   onclick="return confirm('Xóa liên hệ của &quot;<?= htmlspecialchars($c['fullname'], ENT_QUOTES) ?>&quot;?');">
                                                    <i class="bi bi-trash3"></i> Xóa
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- PHÂN TRANG -->
                    <?php if ($totalPages > 1): ?>
                        <nav class="mt-4" aria-label="Phân trang danh sách liên hệ">
                            <ul class="pagination pagination-sm justify-content-center mb-0">
                                <!-- Nút Trước -->
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= htmlspecialchars(buildContactUrl(['page' => max(1, $page - 1)])) ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>

                                <?php
                                // Hiển thị tối đa 5 số trang quanh trang hiện tại
                                $windowSize = 2;
                                $startPage = max(1, $page - $windowSize);
                                $endPage = min($totalPages, $page + $windowSize);

                                if ($startPage > 1): ?>
                                    <li class="page-item"><a class="page-link" href="<?= htmlspecialchars(buildContactUrl(['page' => 1])) ?>">1</a></li>
                                    <?php if ($startPage > 2): ?>
                                        <li class="page-item disabled"><span class="page-link">…</span></li>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                                    <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= htmlspecialchars(buildContactUrl(['page' => $p])) ?>"><?= $p ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($endPage < $totalPages): ?>
                                    <?php if ($endPage < $totalPages - 1): ?>
                                        <li class="page-item disabled"><span class="page-link">…</span></li>
                                    <?php endif; ?>
                                    <li class="page-item"><a class="page-link" href="<?= htmlspecialchars(buildContactUrl(['page' => $totalPages])) ?>"><?= $totalPages ?></a></li>
                                <?php endif; ?>

                                <!-- Nút Sau -->
                                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= htmlspecialchars(buildContactUrl(['page' => min($totalPages, $page + 1)])) ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                            <p class="text-center text-secondary small mt-2 mb-0">
                                Trang <?= $page ?> / <?= $totalPages ?> — Tổng cộng <?= $totalRows ?> liên hệ
                            </p>
                        </nav>
                    <?php endif; ?>
                </div>

            </div><!-- /.container-fluid -->
        </div><!-- /.admin-main -->
    </div><!-- /.admin-content -->
</div><!-- /.admin-layout -->

<!-- MODAL XEM CHI TIẾT LIÊN HỆ -->
<div class="modal fade" id="viewContactModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-custom border-0">
            <div class="modal-header border-secondary border-opacity-25">
                <h5 class="modal-title text-white"><i class="bi bi-person-lines-fill text-teal me-2"></i>Chi tiết liên hệ</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <span class="text-secondary small d-block">Họ tên</span>
                    <span class="text-white fw-semibold" id="viewContactFullname"></span>
                </div>
                <div class="mb-3">
                    <span class="text-secondary small d-block">Zalo / SĐT</span>
                    <span class="text-white fw-semibold" id="viewContactPhone"></span>
                </div>
                <div class="mb-3">
                    <span class="text-secondary small d-block">Công nghệ mong muốn</span>
                    <span class="text-white" id="viewContactTechnology"></span>
                </div>
                <div class="mb-3">
                    <span class="text-secondary small d-block">Thời gian gửi</span>
                    <span class="text-white" id="viewContactCreated"></span>
                </div>
                <div>
                    <span class="text-secondary small d-block mb-1">Mô tả đồ án</span>
                    <p class="text-white" id="viewContactMessage" style="white-space: pre-line;"></p>
                </div>
            </div>
            <div class="modal-footer border-secondary border-opacity-25">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/admin-sidebar.js"></script>
<script>
    const viewContactModalEl = document.getElementById('viewContactModal');
    const viewContactModal = new bootstrap.Modal(viewContactModalEl);

    function fillAndShowModal(row) {
        document.getElementById('viewContactFullname').textContent = row.dataset.fullname;
        document.getElementById('viewContactPhone').textContent = row.dataset.phone;
        document.getElementById('viewContactTechnology').textContent = row.dataset.technology;
        document.getElementById('viewContactCreated').textContent = row.dataset.created;
        document.getElementById('viewContactMessage').textContent = row.dataset.message;
        viewContactModal.show();
    }

    // Bấm nút "Xem" -> điều hướng qua action=view (server sẽ đánh dấu đã đọc rồi quay lại trang)
    document.querySelectorAll('.btn-view-contact').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = btn.dataset.id;
            window.location.href = <?= json_encode(buildContactUrl(['action' => 'view'])) ?> + '&id=' + id;
        });
    });

    // Nếu vừa được điều hướng lại sau khi đánh dấu đã đọc (?opened=X) -> tự mở modal tương ứng
    <?php if ($openedFromQuery > 0): ?>
    (function () {
        const row = document.querySelector('.contact-row-item[data-id="<?= $openedFromQuery ?>"]');
        if (row) fillAndShowModal(row);
    })();
    <?php endif; ?>
</script>
</body>
</html>