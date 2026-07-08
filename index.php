<?php
  require_once('database/connect.php');
  $sql = "SELECT p.*, c.name as category_name,
               GROUP_CONCAT(t.name SEPARATOR ', ') as tech_list
        FROM `projects` p 
        LEFT JOIN `categories` c ON p.category_id = c.id 
        LEFT JOIN `project_technology` pt ON p.id = pt.project_id
        LEFT JOIN `technologies` t ON pt.technology_id = t.id
        where p.is_featured = 1
        GROUP BY p.id
        ORDER BY p.id DESC";
$projects = executeresult($sql);
?>
<?php include('fragment/header.php'); ?>
<!-- ===================== HERO ===================== -->
<section class="hero" id="home">
  <div class="container-xl">
    <div class="row align-items-center gy-5">
      <div class="col-lg-8">
        <p class="eyebrow reveal-up">
          <span class="eyebrow__ping"></span>
          Đang nhận đồ án · học kỳ hiện tại
        </p>
        <h1 class="hero__title reveal-up" style="--d:.05s">
          Code thuê đồ án,<br>
          <span class="grad-text">chạy đúng deadline,</span><br>
          bảo vệ tự tin.
        </h1>
        <p class="hero__desc reveal-up" style="--d:.12s">
          Nhận triển khai đồ án môn học, khóa luận, báo cáo thực tập trên đa nền tảng:
          Java / Spring Boot / Servlet, .NET (C#), PHP Laravel, ReactJS, Angular — kèm
          database MySQL, SQL Server, Oracle. Bàn giao source sạch, có tài liệu, hỗ trợ
          giải thích khi bảo vệ.
        </p>
        <div class="hero__actions reveal-up" style="--d:.2s">
          <a href="#demo" class="btn btn-primary-grad">
            <i class="bi bi-play-circle-fill"></i> Xem demo sản phẩm
          </a>
          <a href="http://zalo.me/0944666371" class="btn btn-outline-ghost">
            Tư vấn nhanh qua Zalo
          </a>
        </div>
        <div class="hero__stats reveal-up" style="--d:.28s">
          <div class="hero-stat">
            <span class="hero-stat__value"><span class="hero-stat__num" data-count="5">0</span><span class="grad-text">+</span></span>
            <span class="hero-stat__label">năm kinh nghiệm</span>
          </div>
          <div class="hero-stat">
            <span class="hero-stat__value"><span class="hero-stat__num" data-count="120">0</span><span class="grad-text">+</span></span>
            <span class="hero-stat__label">đồ án đã bàn giao</span>
          </div>
          <div class="hero-stat">
            <span class="hero-stat__value"><span class="hero-stat__num" data-count="9">0</span></span>
            <span class="hero-stat__label">công nghệ thành thạo</span>
          </div>
          <div class="hero-stat">
            <span class="hero-stat__value"><span class="hero-stat__num" data-count="98">0</span><span class="grad-text">%</span></span>
            <span class="hero-stat__label">bảo vệ đạt yêu cầu</span>
          </div>
        </div>
      </div>

    </div>
  </div>

</section>

<!-- ===================== TECH & EXPERIENCE ===================== -->
<section class="section" id="techstack">
  <div class="container-xl">
    <div class="section-head reveal-up">
      <h2 class="section-title">Công nghệ &amp; kinh nghiệm triển khai</h2>
      <p class="section-desc">
        Hơn <strong class="text-white">5 năm đồng hành cùng sinh viên</strong> qua nhiều kỳ đồ án —
        từ desktop, web truyền thống đến SPA hiện đại, cùng hệ quản trị cơ sở dữ liệu phù hợp.
      </p>
    </div>

    <div class="tech-grid">
      <!-- Backend -->
      <div class="tech-card reveal-up" style="--d:.02s">
        <div class="tech-card__icon" style="--c1:#f89820;--c2:#5382a1;"><i class="bi bi-filetype-java"></i></div>
        <h3>Java</h3>
        <p>Core Java, OOP, cấu trúc dữ liệu &amp; giải thuật cho đồ án môn học.</p>
      </div>
      <div class="tech-card reveal-up" style="--d:.06s">
        <div class="tech-card__icon" style="--c1:#6db33f;--c2:#2f5d1f;"><i class="bi bi-gear-wide-connected"></i></div>
        <h3>Spring Boot</h3>
        <p>REST API, Spring MVC, Spring Security, JPA/Hibernate cho hệ thống quản lý.</p>
      </div>
      <div class="tech-card reveal-up" style="--d:.1s">
        <div class="tech-card__icon" style="--c1:#b07219;--c2:#6b430f;"><i class="bi bi-server"></i></div>
        <h3>Servlet / JSP</h3>
        <p>Website Java truyền thống, MVC thuần cho yêu cầu học phần Java Web.</p>
      </div>
      <div class="tech-card reveal-up" style="--d:.14s">
        <div class="tech-card__icon" style="--c1:#9b4f96;--c2:#512c8f;"><i class="bi bi-file-earmark-code"></i></div>
        <h3>C# / .NET</h3>
        <p>WinForm, ASP.NET MVC / Core cho đồ án desktop &amp; web doanh nghiệp.</p>
      </div>
      <div class="tech-card reveal-up" style="--d:.18s">
        <div class="tech-card__icon" style="--c1:#777bb4;--c2:#4a4d78;"><i class="bi bi-filetype-php"></i></div>
        <h3>PHP / Laravel</h3>
        <p>Website động, hệ thống quản lý nội dung, thương mại điện tử mini.</p>
      </div>

      <!-- Frontend -->
      <div class="tech-card reveal-up" style="--d:.02s">
        <div class="tech-card__icon" style="--c1:#61dafb;--c2:#20232a;"><i class="bi bi-filetype-jsx"></i></div>
        <h3>ReactJS</h3>
        <p>SPA hiện đại, quản lý state, kết nối API cho giao diện đồ án tốt nghiệp.</p>
      </div>
      <div class="tech-card reveal-up" style="--d:.06s">
        <div class="tech-card__icon" style="--c1:#dd0031;--c2:#c3002f;"><i class="bi bi-triangle-fill"></i></div>
        <h3>Angular</h3>
        <p>Ứng dụng doanh nghiệp, kiến trúc component rõ ràng, TypeScript.</p>
      </div>
      <div class="tech-card reveal-up" style="--d:.1s">
        <div class="tech-card__icon" style="--c1:#00758f;--c2:#f29111;"><i class="bi bi-database-fill"></i></div>
        <h3>MySQL / SQL Server</h3>
        <p>Thiết kế CSDL chuẩn hóa, thủ tục, trigger cho báo cáo &amp; đồ án.</p>
      </div>
      <div class="tech-card reveal-up" style="--d:.14s">
        <div class="tech-card__icon" style="--c1:#f80000;--c2:#8c0000;"><i class="bi bi-hdd-stack-fill"></i></div>
        <h3>Oracle DB</h3>
        <p>PL/SQL, tối ưu truy vấn cho hệ thống quy mô lớn hơn.</p>
      </div>
      <div class="tech-card tech-card--more reveal-up" style="--d:.18s">
        <div class="tech-card__icon" style="--c1:#7c5cfc;--c2:#34d1bf;"><i class="bi bi-stars"></i></div>
        <h3>+ HTML/CSS/JS</h3>
        <p>Và các công nghệ nền tảng khác theo yêu cầu riêng của đề bài.</p>
      </div>
    </div>

    <!-- experience timeline -->
    <div class="exp-strip reveal-up">
      <div class="exp-item">
        <i class="bi bi-mortarboard-fill"></i>
        <div>
          <h4>Hiểu rõ yêu cầu học thuật</h4>
          <p>Code theo đúng chuẩn giáo trình, dễ giải thích, dễ bảo vệ trước hội đồng.</p>
        </div>
      </div>
      <div class="exp-item">
        <i class="bi bi-file-earmark-text-fill"></i>
        <div>
          <h4>Kèm tài liệu đầy đủ</h4>
          <p>Báo cáo, sơ đồ use-case, ERD, hướng dẫn cài đặt &amp; chạy dự án.</p>
        </div>
      </div>
      <div class="exp-item">
        <i class="bi bi-shield-check"></i>
        <div>
          <h4>Bảo hành sau bàn giao</h4>
          <p>Hỗ trợ sửa lỗi, chỉnh sửa nhỏ theo góp ý của giảng viên hướng dẫn.</p>
        </div>
      </div>
      <div class="exp-item">
        <i class="bi bi-cloud-arrow-up-fill"></i>
        <div>
          <h4>Hỗ trợ deploy production</h4>
          <p>Triển khai lên VPS Ubuntu, Windows Server hoặc AWS — chạy thật, demo online cho hội đồng.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===================== PRICING ===================== -->
<section class="section" id="pricing">
  <div class="container-xl">
    <div class="section-head reveal-up">
      <h2 class="section-title">Bảng giá dịch vụ</h2>
      <p class="section-desc">
        Minh bạch theo từng nhu cầu — làm mới hoàn toàn theo đề bài riêng, hoặc chọn
        web có sẵn cho deadline gấp.
      </p>
    </div>

    <div class="pricing-grid">
      <div class="price-card reveal-up">
        <span class="price-card__ribbon">Bàn giao đúng hạn</span>
        <div class="price-card__head">
          <span class="price-card__badge">Đồ án tốt nghiệp / yêu cầu riêng</span>
          <h3>Làm mới theo yêu cầu</h3>
          <p class="price-card__price">Thỏa thuận <span>theo độ khó &amp; phạm vi đồ án</span></p>
        </div>
        <ul class="price-card__list">
          <li><i class="bi bi-check2"></i> Phân tích đề bài, thiết kế CSDL riêng</li>
          <li><i class="bi bi-check2"></i> Code từ đầu, đúng chuẩn giáo trình</li>
          <li><i class="bi bi-check2"></i> Báo cáo, sơ đồ UML / ERD đầy đủ</li>
          <li><i class="bi bi-check2"></i> Hỗ trợ giải thích code khi bảo vệ</li>
          <li><i class="bi bi-check2"></i> Deploy VPS / AWS nếu cần demo online</li>
        </ul>
        <a href="#contact" class="btn btn-outline-ghost w-100">Gửi đề bài để báo giá</a>
      </div>

      <div class="price-card price-card--highlight reveal-up" style="--d:.08s">
        <span class="price-card__ribbon">Giao nhanh trong 2h</span>
        <div class="price-card__head">
          <span class="price-card__badge">Deadline gấp / bài tập lớn</span>
          <h3>Web có sẵn (mẫu)</h3>
          <p class="price-card__price">300.000đ &mdash; 2.000.000đ</p>
        </div>
        <ul class="price-card__list">
          <li><i class="bi bi-check2"></i> Chọn từ kho project đã build sẵn, đa dạng đề tài</li>
          <li><i class="bi bi-check2"></i> Tặng kèm báo cáo Word hoàn chỉnh</li>
          <li><i class="bi bi-check2"></i> Tặng video training giải thích full source</li>
          <li><i class="bi bi-check2"></i> Bàn giao nhanh, phù hợp deadline gấp</li>
          <li><i class="bi bi-check2"></i> Có thể chỉnh sửa nhỏ theo yêu cầu riêng</li>
        </ul>
        <a href="#demo" class="btn btn-primary-grad w-100">Xem demo có sẵn</a>
      </div>
    </div>

    <div class="deploy-strip reveal-up">
      <div class="deploy-strip__label">
        <i class="bi bi-cloud-arrow-up-fill"></i>
        <div>
          <h4>Hỗ trợ deploy production</h4>
          <p>Đồ án chạy thật, có link demo online để nộp báo cáo &amp; trình bày hội đồng.</p>
        </div>
      </div>
      <div class="deploy-strip__badges">
        <span><i class="bi bi-hdd-rack-fill"></i> Ubuntu VPS</span>
        <span><i class="bi bi-windows"></i> Windows Server</span>
        <span><i class="bi bi-cloud-fill"></i> AWS</span>
      </div>
    </div>
  </div>
</section>

<!-- ===================== DEMO ===================== -->
<section class="section section--alt" id="demo">
  <div class="container-xl">
    <div class="section-head reveal-up">
      <h2 class="section-title">Demo sản phẩm mới và hot nhất</h2>
      <p class="section-desc">
        Một số đồ án tiêu biểu đã bàn giao. Video được cập nhật liên tục —
        tìm theo tên hoặc công nghệ bạn quan tâm.
      </p>
    </div>

    <div class="demo-toolbar reveal-up">
      <div class="demo-filters" id="demoFilter">
        <button class="filter-chip is-active" data-filter="all">Xem tất cả đồ án</button>
        <!-- filled by JS -->
      </div>
    </div>
    <?php if (empty($projects)): ?>
      <div class="demo-empty d-none">
        <i class="bi bi-emoji-frown"></i>
        <p>Không tìm thấy demo phù hợp. Thử từ khóa khác nhé!</p>
      </div>
    <?php else: ?>
      <div class="demo-grid" id="demoGrids">
      <?php foreach ($projects as $p): ?>
        <a href="project_detail?id=<?= (int)$p['id'] ?>" class="demo-card">
          <div class="demo-card__thumb">
            <?php if (!empty($p['banner'])): ?>
                <img src="<?= htmlspecialchars($p['banner']) ?>" alt="<?= htmlspecialchars($p['title']) ?>" loading="lazy">
            <?php else: ?>
                <img src="image/logo.jpg" alt="<?= htmlspecialchars($p['title']) ?>" loading="lazy">
            <?php endif; ?>
            <div class="demo-card__play"></div>
          </div>
          <div class="demo-card__body">
            <span class="demo-card__tag"><?= htmlspecialchars($p['category_name']) ?></span>
            <h3 class="demo-card__title" style="color: #ffffff;"><?= htmlspecialchars($p['title']) ?></h3>
            <div class="demo-card__techs">
              <?php if (!empty($p['tech_list'])): ?>
                  <?php foreach (explode(', ', $p['tech_list']) as $tech): ?>
                      <span class="tech-pill"><?= htmlspecialchars($tech) ?></span>
                  <?php endforeach; ?>
              <?php else: ?>
                  <span class="text-white small">Chưa chọn</span>
              <?php endif; ?>
            </div>
            <p class="demo-card__meta"><i class="bi bi-patch-check-fill"></i> Kèm báo cáo &amp; video hướng dẫn</p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <nav class="demo-pagination" id="demoPagination" aria-label="Phân trang demo"></nav>
  </div>
</section>

<!-- ===================== CONTACT ===================== -->
<section class="section" id="contact">
  <div class="container-xl">
    <div class="contact-panel reveal-up">
      <div class="row g-0">
        <div class="col-lg-5 contact-panel__side">
          <h2 class="section-title section-title--sm">Sẵn sàng bắt đầu đồ án của bạn?</h2>
          <p class="section-desc">
            Gửi yêu cầu kèm đề bài / mô tả đồ án, mình sẽ phản hồi báo giá và thời gian
            hoàn thành trong vòng 30 phút.
          </p>

          <ul class="contact-list">
            <li>
              <i class="bi bi-chat-dots-fill"></i>
              <div><span>Zalo</span><strong>0944.666.371</strong></div>
            </li>
            <li>
              <i class="bi bi-messenger"></i>
              <div><span>Messenger</span><a href="https://www.facebook.com/tranhieu.webapp" target="_blank">Click để liên hệ</a></div>
            </li>
          </ul>
        </div>

        <div class="col-lg-7 contact-panel__form">
          <form id="contactForm" action="api/contact_submit.php" method="POST" novalidate>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Họ tên</label>
                <input type="text" name="fullname" class="form-control" placeholder="Nguyễn Văn A" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Zalo / SĐT</label>
                <input type="text" name="phone" class="form-control" placeholder="09xx xxx xxx" required>
              </div>
              <div class="col-12">
                <label class="form-label">Công nghệ mong muốn</label>
                <select name="technology" class="form-select">
                  <option>Java / Spring Boot</option>
                  <option>Servlet / JSP</option>
                  <option>C# / .NET</option>
                  <option>PHP / Laravel</option>
                  <option>ReactJS</option>
                  <option>Angular</option>
                  <option>Khác / Chưa rõ</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label">Mô tả đồ án</label>
                <textarea name="message" class="form-control" rows="4" placeholder="Đề bài, deadline, yêu cầu chức năng..."></textarea>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-primary-grad w-100">
                  Gửi yêu cầu <i class="bi bi-send-fill"></i>
                </button>
                <p class="form-note" id="formNote"></p>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>


</main>
<?php include 'fragment/bottom-nav.php'; ?>
<?php include('fragment/footer.php'); ?>

<?php include('fragment/chat.php'); ?>
<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="js/script.js"></script>
</body>
</html>