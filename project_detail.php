<?php
require_once('database/connect.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ============================================================
// 1. Lấy thông tin chi tiết đồ án (kèm danh mục + danh sách công nghệ)
// ============================================================
$project = null;

if ($id > 0) {
    $rows = executeresult(
        "SELECT p.*, c.name AS category_name,
                GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') AS tech_list
         FROM `projects` p
         LEFT JOIN `categories` c ON p.category_id = c.id
         LEFT JOIN `project_technology` pt ON p.id = pt.project_id
         LEFT JOIN `technologies` t ON pt.technology_id = t.id
         WHERE p.id = $id
         GROUP BY p.id
         LIMIT 1"
    );
    $project = $rows[0] ?? null;
}

// ============================================================
// 2. Lấy album ảnh demo của đồ án (nếu có), ảnh đại diện lên đầu
// ============================================================
$images = [];
if ($project) {
    $images = executeresult(
        "SELECT `image_path`, `is_featured`
         FROM `project_images`
         WHERE `project_id` = $id
         ORDER BY `is_featured` DESC, `id` ASC"
    );
}

// ============================================================
// 3. Lấy danh sách đồ án nổi bật cho cột bên phải (loại trừ đồ án đang xem)
// ============================================================
$featured = executeresult(
    "SELECT p.*, c.name as category_name,
               GROUP_CONCAT(t.name SEPARATOR ', ') as tech_list
        FROM `projects` p 
        LEFT JOIN `categories` c ON p.category_id = c.id 
        LEFT JOIN `project_technology` pt ON p.id = pt.project_id
        LEFT JOIN `technologies` t ON pt.technology_id = t.id
        where p.is_featured = 1
        GROUP BY p.id
        ORDER BY p.id DESC"
);
?>
<?php include('fragment/header.php'); ?>

<section class="section project-detail" style="padding-top:calc(var(--header-h) + 50px);">
  <div class="container-xl">

    <?php if (!$project): ?>

      <!-- ===================== KHÔNG TÌM THẤY ĐỒ ÁN ===================== -->
      <div class="demo-empty">
        <i class="bi bi-emoji-frown"></i>
        <p>Không tìm thấy đồ án này. Có thể đường dẫn không đúng hoặc đồ án đã bị gỡ.</p>
        <a href="projects" class="btn btn-outline-ghost mt-3">
          <i class="bi bi-arrow-left me-1"></i> Quay lại danh sách đồ án
        </a>
      </div>

    <?php else: ?>

      <a href="projects" class="project-detail__back reveal-up">
        <i class="bi bi-arrow-left"></i> Quay lại danh sách đồ án
      </a>

      <div class="row g-4 mt-1">

        <!-- ===================== CỘT TRÁI: THÔNG TIN CHI TIẾT ===================== -->
        <div class="col-lg-8">
          <div class="project-detail__main">

            <span class="demo-card__tag"><?= htmlspecialchars($project['category_name'] ?: 'Chưa phân loại') ?></span>
            <h1 class="project-detail__title"><?= htmlspecialchars($project['title']) ?></h1>

            <div class="project-detail__techs">
              <?php if (!empty($project['tech_list'])): ?>
                <?php foreach (explode(', ', $project['tech_list']) as $tech): ?>
                  <span class="tech-pill"><?= htmlspecialchars($tech) ?></span>
                <?php endforeach; ?>
              <?php else: ?>
                <span class="text-white small">Chưa chọn công nghệ</span>
              <?php endif; ?>
            </div>

            <?php if (!empty($images)): ?>
              <!-- ===================== ALBUM ẢNH DEMO ===================== -->
              <div id="projectGallery" class="carousel slide project-detail__gallery mb-4">
                <div class="carousel-inner">
                  <?php foreach ($images as $i => $img): ?>
                    <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                      <img src="<?= htmlspecialchars($img['image_path']) ?>"
                           alt="<?= htmlspecialchars($project['title']) ?> - ảnh <?= $i + 1 ?>"
                           loading="<?= $i === 0 ? 'eager' : 'lazy' ?>">
                    </div>
                  <?php endforeach; ?>
                </div>

                <?php if (count($images) > 1): ?>
                  <button class="carousel-control-prev" type="button" data-bs-target="#projectGallery" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Ảnh trước</span>
                  </button>
                  <button class="carousel-control-next" type="button" data-bs-target="#projectGallery" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Ảnh sau</span>
                  </button>
                  <div class="carousel-indicators">
                    <?php foreach ($images as $i => $img): ?>
                      <button type="button" data-bs-target="#projectGallery" data-bs-slide-to="<?= $i ?>"
                              class="<?= $i === 0 ? 'active' : '' ?>"
                              aria-current="<?= $i === 0 ? 'true' : 'false' ?>"
                              aria-label="Ảnh <?= $i + 1 ?>"></button>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($project['youtube_id'])): ?>
              <!-- ===================== VIDEO YOUTUBE ===================== -->
              <div class="ratio ratio-16x9 mb-4 project-detail__video" id="projectDetailVideoWrap">
                <div id="projectDetailVideo"></div>
              </div>
            <?php endif; ?>

            <!-- ===================== MÔ TẢ CHI TIẾT (HTML từ TinyMCE) ===================== -->
            <div class="project-modal__desc">
              <?= $project['description'] !== null && $project['description'] !== ''
                    ? $project['description']
                    : '<em>Chưa có mô tả.</em>' ?>
            </div>

            <?php if (!empty($project['drive_link'])): ?>
              <a href="<?= htmlspecialchars($project['drive_link']) ?>" target="_blank" rel="noopener" class="btn btn-outline-ghost mt-4">
                <i class="bi bi-google me-1"></i> Xem Source / Tài liệu / Demo (Drive)
              </a>
            <?php endif; ?>

          </div>
        </div>

        <!-- ===================== CỘT PHẢI: ĐỒ ÁN NỔI BẬT ===================== -->
        <div class="col-lg-4">
          <aside class="project-detail__sidebar reveal-up">
            <h3 class="project-detail__sidebar-title">
              <i class="bi bi-star-fill text-amber me-2"></i>Đồ án nổi bật
            </h3>

            <?php if (empty($featured)): ?>
              <p class="text-muted small">Chưa có đồ án nổi bật nào khác.</p>
            <?php else: ?>
              <div class="related-list">
                <?php foreach ($featured as $f): ?>
                  <a href="project_detail?id=<?= (int)$f['id'] ?>" class="related-item">
                    <div class="related-item__thumb">
                      <?php if (!empty($f['thumbnail'])): ?>
                        <img src="<?= htmlspecialchars($f['thumbnail']) ?>" alt="<?= htmlspecialchars($f['title']) ?>" loading="lazy">
                      <?php else: ?>
                        <i class="bi bi-code-slash"></i>
                      <?php endif; ?>
                    </div>
                    <div class="related-item__body">
                      <span class="related-item__tag"><?= htmlspecialchars($f['category_name'] ?: 'Đồ án') ?></span>
                      <h4 class="related-item__title"><?= htmlspecialchars($f['title']) ?></h4>
                    </div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </aside>
        </div>

      </div>

    <?php endif; ?>

  </div>
</section>

</main>
<?php include 'fragment/bottom-nav.php'; ?>

<?php include('fragment/footer.php'); ?>
<?php include('fragment/chat.php'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="js/script.js"></script>

<?php if ($project && !empty($project['youtube_id'])): ?>
<script>
  // Nhúng nguyên đoạn <iframe> YouTube được admin lưu trong DB, ép full khung 16:9
  document.addEventListener('DOMContentLoaded', function () {
    const holder = document.getElementById('projectDetailVideo');
    if (!holder) return;

    holder.innerHTML = <?= json_encode(
      $project['youtube_id'],
      JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    ) ?>;

    const iframeEl = holder.querySelector('iframe');
    if (iframeEl) {
      iframeEl.removeAttribute('width');
      iframeEl.removeAttribute('height');
      iframeEl.style.width = '100%';
      iframeEl.style.height = '100%';
    }
  });
</script>
<?php endif; ?>
</body>
</html>