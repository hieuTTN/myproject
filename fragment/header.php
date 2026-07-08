<?php
// Lấy tên file hiện tại của trang (ví dụ: index.php, projects.php)
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Trần Hiếu - Code Thuê Đồ Án - Java, Spring Boot, .NET, PHP, React, Angular</title>
<meta name="description" content="Nhận code thuê đồ án, luận văn, báo cáo thực tập đa nền tảng: Java, Spring Boot, Servlet, .NET, PHP Laravel, ReactJS, Angular, MySQL, SQL Server, Oracle.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
<link rel="icon" type="image/png" href="image/logo.jpg">
<link rel="shortcut icon" type="image/x-icon" href="image/logo.jpg">
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- ===================== NOISE / BACKGROUND FX ===================== -->
<div class="bg-fx" aria-hidden="true">
  <div class="bg-fx__grid"></div>
  <div class="bg-fx__glow bg-fx__glow--1"></div>
  <div class="bg-fx__glow bg-fx__glow--2"></div>
</div>

<!-- ===================== HEADER (Bootstrap Navbar) ===================== -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top site-navbar" id="siteHeader">
  <div class="container-xl">
    <a href="index" class="navbar-brand brand">
      <span class="brand__bracket">&lt;/&gt;</span>
      <span class="brand__text">HieuDev<span class="brand__dot"></span></span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navCollapse"
      aria-controls="navCollapse" aria-expanded="false" aria-label="Mở menu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navCollapse">
      <ul class="navbar-nav nav-tags mx-lg-auto my-3 my-lg-0" id="navTags">
        <li class="nav-item"><a href="index" class="nav-link nav-tage" data-target="home">Trang chủ</a></li>
        <li class="nav-item"><a href="index#techstack" class="nav-link nav-tag" data-target="techstack">Kỹ năng</a></li>
        <li class="nav-item"><a href="index#pricing" class="nav-link nav-tag" data-target="pricing">Bảng giá</a></li>
        <li class="nav-item"><a href="index#demo" class="nav-link nav-tag" data-target="demo">Demo</a></li>
        <li class="nav-item"><a href="projects" class="nav-link nav-tag 
        <?php echo ($current_page == 'projects.php' || $current_page == 'projects') ? 'is-active' : ''; ?>" 
        data-target="projects">Dự án</a></li>
        <li class="nav-item"><a href="contact" class="nav-link nav-tag" data-target="contact">Liên hệ</a></li>
      </ul>

      <a href="index#contact" class="btn btn-nav-cta">
        Nhận báo giá <i class="bi bi-arrow-up-right"></i>
      </a>
    </div>
  </div>
</nav>

<main>