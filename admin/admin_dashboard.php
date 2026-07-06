<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Kiểm tra quyền truy cập: Nếu chưa đăng nhập thì đá về trang login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

require_once('../database/connect.php'); // Đọc cấu hình database

// 2. Đọc tham số tìm kiếm & phân trang từ query string
$keyword = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

/**
 * Hàm hỗ trợ: dựng lại URL của trang dashboard, giữ nguyên từ khóa tìm kiếm
 * và số trang hiện tại, cho phép ghi đè một vài tham số (vd: page, action, id)
 */
function buildDashboardUrl($overrides = []) {
    global $keyword, $page;
    $params = [];
    if ($keyword !== '') {
        $params['q'] = $keyword;
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
    return 'admin_dashboard.php' . (!empty($params) ? '?' . http_build_query($params) : '');
}

// 3. Xử lý chức năng XÓA đồ án nhanh bằng query string (?action=delete&id=...)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int)$_GET['id'];

    // Bỏ qua bước kiểm tra khóa ngoại để tránh lỗi nếu chưa thiết kế cascade
    // Bước 1: Xóa các bảng liên quan trước
    execute("DELETE FROM `project_technology` WHERE `project_id` = $delete_id");
    execute("DELETE FROM `project_images` WHERE `project_id` = $delete_id");

    // Bước 2: Xóa bảng chính
    execute("DELETE FROM `projects` WHERE `id` = $delete_id");

    header("Location: " . buildDashboardUrl(['action' => null, 'id' => null, 'msg' => 'success']));
    exit;
}

// 4. Xây dựng điều kiện tìm kiếm (theo tên đồ án, danh mục hoặc công nghệ)
$whereSql = '';
if ($keyword !== '') {
    $safeKeyword = addslashes($keyword);
    $whereSql = "WHERE (
        p.title LIKE '%$safeKeyword%'
        OR c.name LIKE '%$safeKeyword%'
        OR p.id IN (
            SELECT pt2.project_id FROM `project_technology` pt2
            JOIN `technologies` t2 ON pt2.technology_id = t2.id
            WHERE t2.name LIKE '%$safeKeyword%'
        )
    )";
}

// 5. Đếm tổng số bản ghi phù hợp để tính số trang
$countSql = "SELECT COUNT(*) as total 
             FROM `projects` p 
             LEFT JOIN `categories` c ON p.category_id = c.id 
             $whereSql";
$countRow = querySingleResult($countSql);
$totalRows = (int)($countRow['total'] ?? 0);
$totalPages = max(1, (int)ceil($totalRows / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

// 6. CẬP NHẬT: Truy vấn dùng GROUP_CONCAT để lấy toàn bộ công nghệ liên quan (có tìm kiếm + phân trang)
$sql = "SELECT p.*, c.name as category_name,
               GROUP_CONCAT(t.name SEPARATOR ', ') as tech_list
        FROM `projects` p 
        LEFT JOIN `categories` c ON p.category_id = c.id 
        LEFT JOIN `project_technology` pt ON p.id = pt.project_id
        LEFT JOIN `technologies` t ON pt.technology_id = t.id
        $whereSql
        GROUP BY p.id
        ORDER BY p.id DESC
        LIMIT $perPage OFFSET $offset";
$projects = executeresult($sql);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Quản lý Demo Đồ án</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">
</head>
<body>

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>

    <div class="admin-content">
        <!-- MAIN CONTENT -->
        <div class="admin-main">
            <div class="container-fluid px-4">

    
    <!-- Tiêu đề trang & Nút thêm mới -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Quản lý Dự án Demo</h2>
            <p class="text-secondary small mb-0">Thêm, sửa, xóa các sản phẩm đồ án hiển thị ngoài landing page</p>
        </div>
        <a href="project_add.php" class="btn btn-teal"><i class="bi bi-plus-circle-fill me-2"></i>Thêm Đồ Án Mới</a>
    </div>

    <!-- Thông báo xóa thành công nếu có -->
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
        <div class="alert alert-success bg-opacity-10 bg-success text-success border-success border-opacity-20 small py-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>Đã xóa dự án thành công khỏi hệ thống!
        </div>
    <?php endif; ?>

    <!-- TÌM KIẾM -->
    <form method="GET" action="admin_dashboard.php" class="d-flex gap-2 mb-4">
        <input type="text" name="q" class="form-control" placeholder="Tìm theo tên đồ án, danh mục hoặc công nghệ..." value="<?= htmlspecialchars($keyword) ?>">
        <button type="submit" class="btn btn-outline-info text-nowrap"><i class="bi bi-search me-1"></i>Tìm kiếm</button>
        <?php if ($keyword !== ''): ?>
            <a href="admin_dashboard.php" class="btn btn-outline-secondary text-nowrap"><i class="bi bi-x-circle me-1"></i>Xóa lọc</a>
        <?php endif; ?>
    </form>

    <?php if ($keyword !== ''): ?>
        <div class="mb-3 small text-secondary">
            Tìm thấy <strong class="text-white"><?= $totalRows ?></strong> kết quả cho từ khóa "<strong class="text-white"><?= htmlspecialchars($keyword) ?></strong>"
        </div>
    <?php endif; ?>

    <!-- DANH SÁCH SẢN PHẨM -->
    <div class="card card-custom p-4">
        <div class="table-responsive">
            <table class="table table-custom mb-0">
                <thead>
                    <tr>
                        <th style="width: 5%">ID</th>
                        <th style="width: 20%">Tên Đồ Án</th>
                        <th style="width: 15%">Danh Mục</th>
                        <th style="width: 20%">Công Nghệ</th> <!-- THÊM CỘT MỚI -->
                        <th style="width: 15%">Liên Kết</th>
                        <th style="width: 15%">Mô tả</th>
                        <th style="width: 10%" class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($projects)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <?php if ($keyword !== ''): ?>
                                    Không tìm thấy đồ án nào phù hợp với từ khóa "<?= htmlspecialchars($keyword) ?>".
                                <?php else: ?>
                                    Hệ thống chưa có đồ án nào. Bấm nút phía trên để thêm mới!
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($projects as $row): ?>
                            <tr>
                                <td class="text-secondary fw-bold"><?= $row['id'] ?></td>
                                <td>
                                    <div class="fw-semibold text-white"><?= htmlspecialchars($row['title']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-20 border border-secondary text-white border-opacity-50 px-2 py-1">
                                        <?= htmlspecialchars($row['category_name'] ?? 'Chưa phân loại') ?>
                                    </span>
                                </td>
                                <td>
                                    <!-- HIỂN THỊ CÁC CÔNG NGHỆ DƯỚI DẠNG BADGE HOẶC TEXT -->
                                    <?php if (!empty($row['tech_list'])): ?>
                                        <?php 
                                        // Tách chuỗi công nghệ thành mảng để lặp qua đóng gói giao diện nhìn cho đẹp
                                        $tech_array = explode(', ', $row['tech_list']);
                                        foreach ($tech_array as $tech): 
                                        ?>
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-20 me-1 mb-1 small">
                                                <?= htmlspecialchars($tech) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Chưa chọn</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small mb-1">
                                        <i class="bi bi-youtube text-danger me-1"></i>
                                        <span class="text-secondary">YouTube:</span> 
                                        <code class="text-white"><?= htmlspecialchars($row['youtube_id'] ?: 'Trống') ?></code>
                                    </div>
                                    <div class="small">
                                        <i class="bi bi-cloud-arrow-down-fill text-primary me-1"></i>
                                        <span class="text-secondary">Drive:</span> 
                                        <?php if (!empty($row['drive_link'])): ?>
                                            <a href="<?= htmlspecialchars($row['drive_link']) ?>" target="_blank" class="text-info text-decoration-none text-truncate d-inline-block align-bottom" style="max-width: 120px;">Xem Link</a>
                                        <?php else: ?>
                                            <span class="text-muted small">Trống</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-secondary small text-truncate" style="max-width: 180px;">
                                         <?= strip_tags($row['description']) ?> <!-- Dùng strip_tags để lọc bỏ mã HTML từ TinyMCE khi hiển thị dạng rút gọn -->
                                    </div>
                                </td>
                                <td class="text-end">
                                    <a href="project_edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-warning mb-1 w-100">
                                        <i class="bi bi-pencil-square"></i> Sửa
                                    </a>
                                    <a href="<?= htmlspecialchars(buildDashboardUrl(['action' => 'delete', 'id' => $row['id']])) ?>" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Bạn có chắc chắn muốn xóa đồ án này? Tất cả dữ liệu ảnh và công nghệ liên quan cũng sẽ bị xóa bỏ hoàn toàn!');">
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
            <nav class="mt-4" aria-label="Phân trang danh sách đồ án">
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <!-- Nút Trước -->
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars(buildDashboardUrl(['page' => max(1, $page - 1)])) ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>

                    <?php
                    // Hiển thị tối đa 5 số trang quanh trang hiện tại
                    $windowSize = 2;
                    $startPage = max(1, $page - $windowSize);
                    $endPage = min($totalPages, $page + $windowSize);

                    if ($startPage > 1): ?>
                        <li class="page-item"><a class="page-link" href="<?= htmlspecialchars(buildDashboardUrl(['page' => 1])) ?>">1</a></li>
                        <?php if ($startPage > 2): ?>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                        <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= htmlspecialchars(buildDashboardUrl(['page' => $p])) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        <?php endif; ?>
                        <li class="page-item"><a class="page-link" href="<?= htmlspecialchars(buildDashboardUrl(['page' => $totalPages])) ?>"><?= $totalPages ?></a></li>
                    <?php endif; ?>

                    <!-- Nút Sau -->
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars(buildDashboardUrl(['page' => min($totalPages, $page + 1)])) ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
                <p class="text-center text-secondary small mt-2 mb-0">
                    Trang <?= $page ?> / <?= $totalPages ?> — Tổng cộng <?= $totalRows ?> đồ án
                </p>
            </nav>
        <?php endif; ?>
    </div>

            </div><!-- /.container-fluid -->
        </div><!-- /.admin-main -->
    </div><!-- /.admin-content -->
</div><!-- /.admin-layout -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/admin-sidebar.js"></script>
</body>
</html>