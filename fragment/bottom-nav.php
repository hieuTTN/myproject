<?php
// Lấy tên file hiện tại của trang (ví dụ: index.php, projects.php)
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<div class="bottom-nav d-lg-none">
  <div class="bottom-nav__wrapper">
    <a href="index" class="bottom-nav__item nav-tag" data-target="home">
      <i class="bi bi-house-door-fill"></i>
      <span>Trang chủ</span>
    </a>
    <a href="projects" class="bottom-nav__item nav-tag  <?php echo ($current_page == 'projects.php' || $current_page == 'projects') ? 'is-active' : ''; ?>" data-target="projects">
      <i class="bi bi-cpu-fill"></i>
      <span>Các đồ án</span>
    </a>
    <a href="index#pricing" class="bottom-nav__item nav-tag" data-target="pricing">
      <i class="bi bi-tags-fill"></i>
      <span>Bảng giá</span>
    </a>
    <a href="index#demo" class="bottom-nav__item nav-tag" data-target="demo">
      <i class="bi bi-laptop"></i>
      <span>Demo</span>
    </a>
    <a href="contact" class="bottom-nav__item nav-tag" data-target="contact">
      <i class="bi bi-chat-left-text-fill"></i>
      <span>Liên hệ</span>
    </a>
  </div>
</div>