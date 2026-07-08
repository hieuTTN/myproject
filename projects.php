<?php
require_once('database/connect.php');
$categories = executeresult("SELECT * FROM `categories` ORDER BY `name` ASC");
?>
<?php include('fragment/header.php'); ?>

<section class="section all-projects-cl" id="all-projects">
  <div class="container-xl">

    <div class="section-head">
      <h1 class="section-title mt-1">Tất cả đồ án</h1>
      <p class="section-desc">
        Tìm theo tên hoặc mô tả, lọc theo danh mục để xem nhanh hơn.
      </p>
    </div>

    <!-- Thanh công cụ: ô tìm kiếm + chip lọc danh mục -->
    <div class="demo-toolbar">
      <div class="demo-search">
        <i class="bi bi-search"></i>
        <input type="text" id="projectSearchInput" placeholder="Tìm theo tên hoặc mô tả đồ án...">
      </div>
      <div class="demo-filters" id="categoryFilter">
        <button class="filter-chip is-active" data-category="0">Tất cả</button>
        <?php foreach ($categories as $cat): ?>
          <button class="filter-chip text-white" data-category="<?= (int)$cat['id'] ?>">
            <?= htmlspecialchars($cat['name']) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Trạng thái đang tải -->
    <div id="projectsStatus" class="text-center py-4 d-none">
      <span class="spinner-border spinner-border-sm text-info me-2"></span>
      <span class="text-muted small">Đang tải dữ liệu...</span>
    </div>

    <!-- Trạng thái không có kết quả -->
    <div class="demo-empty d-none" id="projectsEmpty">
      <i class="bi bi-emoji-frown"></i>
      <p>Không tìm thấy đồ án phù hợp. Thử từ khóa khác nhé!</p>
    </div>

    <!-- Grid danh sách project, render bằng JS -->
    <div class="demo-grid" id="projectsGrid"></div>

    <!-- Phân trang, render bằng JS -->
    <nav class="demo-pagination" id="projectsPagination" aria-label="Phân trang đồ án"></nav>

  </div>
</section>


</main>
<?php include 'fragment/bottom-nav.php'; ?>
<!-- ===================== MODAL CHI TIẾT ĐỒ ÁN ===================== -->
<div class="modal fade" id="projectModal" tabindex="-1" aria-labelledby="projectModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content project-modal">
      <div class="modal-header">
        <h5 class="modal-title" id="projectModalLabel"></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body">

        <!-- Video YouTube (nhận nguyên đoạn iframe được lưu trong DB) -->
        <div class="ratio ratio-16x9 mb-3 d-none" id="projectModalVideoWrap">
          <div id="projectModalVideo"></div>
        </div>

        <!-- Công nghệ -->
        <div class="mb-3" id="projectModalTechs"></div>

        <!-- Mô tả (HTML từ TinyMCE) -->
        <div class="project-modal__desc" id="projectModalDesc"></div>

      </div>
      <div class="modal-footer">
        <a href="#" target="_blank" rel="noopener" class="btn btn-outline-ghost d-none" id="projectModalDrive">
          <i class="bi bi-google me-1"></i> Xem Source / Tài liệu (Drive)
        </a>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>
<?php include('fragment/footer.php'); ?>
<?php include('fragment/chat.php'); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="js/projects.js"></script>
</body>
</html>