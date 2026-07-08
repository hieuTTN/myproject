<?php include('fragment/header.php'); ?>
<!-- ===================== HERO ===================== -->

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