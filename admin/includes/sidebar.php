<?php
/**
 * SIDEBAR DÙNG CHUNG CHO KHU VỰC ADMIN
 * ------------------------------------
 * File này được include() ở các trang: admin_dashboard.php, project_add.php,
 * technology.php,... Yêu cầu trước khi include:
 *   1. session_start() đã được gọi.
 *   2. Đã kiểm tra đăng nhập ($_SESSION['admin_logged_in']).
 *
 * Cách include (đặt ngay sau thẻ <body>):
 *   <div class="admin-layout">
 *       <?php include 'includes/sidebar.php'; ?>
 *       <div class="admin-content"> ... nội dung trang ... </div>
 *   </div>
 */

// Xác định file hiện tại để tô sáng (active) đúng mục menu tương ứng
$current_page = basename($_SERVER['PHP_SELF']);

// Danh sách menu — chỉ cần thêm 1 dòng ở đây nếu sau này có thêm trang admin mới
$menu_items = [
    ['file' => 'admin_dashboard.php', 'icon' => 'bi-grid-1x2-fill',   'label' => 'Quản lý đồ án'],
    ['file' => 'project_add.php',     'icon' => 'bi-plus-circle-fill','label' => 'Thêm đồ án'],
    ['file' => 'technology.php',      'icon' => 'bi-cpu-fill',        'label' => 'Công nghệ'],
    ['file' => 'category.php',        'icon' => 'bi-folder-fill',     'label' => 'Danh mục'],
    ['file' => 'cloudkey.php',        'icon' => 'bi-key-fill',        'label' => 'Cloud Key'],
];
?>
<!-- ============ MOBILE TOPBAR (chỉ hiện < 992px) ============ -->
<header class="admin-topbar d-lg-none">
    <button class="admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Mở menu quản trị" aria-expanded="false">
        <i class="bi bi-list"></i>
    </button>
    <div class="admin-topbar__brand">
        <i class="bi bi-speedometer2 text-teal"></i>
        <span>ADMIN<span class="text-teal">PANEL</span></span>
    </div>
    <div style="width:40px;"></div> <!-- spacer để brand luôn canh giữa -->
</header>

<!-- ============ OVERLAY khi mở sidebar trên mobile ============ -->
<div class="admin-sidebar-overlay" id="adminSidebarOverlay"></div>

<!-- ============ SIDEBAR ============ -->
<aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-sidebar__brand">
        <i class="bi bi-speedometer2 text-teal"></i>
        <span>ADMIN<span class="text-teal">PANEL</span></span>
    </div>

    <nav class="admin-sidebar__nav">
        <?php foreach ($menu_items as $item): ?>
            <a href="<?= $item['file'] ?>"
               class="admin-nav-link <?= $current_page === $item['file'] ? 'is-active' : '' ?>">
                <i class="bi <?= $item['icon'] ?>"></i> <?= $item['label'] ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="admin-sidebar__footer">
        <div class="admin-sidebar__user">
            <i class="bi bi-person-circle"></i>
            <span><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></span>
        </div>
        <a href="logout.php" class="admin-sidebar__logout">
            <i class="bi bi-box-arrow-right"></i> Đăng xuất
        </a>
    </div>
</aside>

<script src="js/admin-sidebar.js"></script>