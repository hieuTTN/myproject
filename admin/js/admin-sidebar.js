(function () {
  "use strict";

  const sidebar = document.getElementById("adminSidebar");
  const overlay = document.getElementById("adminSidebarOverlay");
  const toggleBtn = document.getElementById("adminSidebarToggle");

  if (!sidebar || !toggleBtn) return;

  function openSidebar() {
    sidebar.classList.add("is-open");
    document.body.classList.add("admin-sidebar-open");
    toggleBtn.setAttribute("aria-expanded", "true");
  }

  function closeSidebar() {
    sidebar.classList.remove("is-open");
    document.body.classList.remove("admin-sidebar-open");
    toggleBtn.setAttribute("aria-expanded", "false");
  }

  toggleBtn.addEventListener("click", function () {
    sidebar.classList.contains("is-open") ? closeSidebar() : openSidebar();
  });

  if (overlay) overlay.addEventListener("click", closeSidebar);

  // Đóng sidebar khi bấm chọn 1 mục menu (trên mobile)
  sidebar.querySelectorAll(".admin-nav-link").forEach((link) => {
    link.addEventListener("click", closeSidebar);
  });

  // Nếu resize màn hình lên desktop mà sidebar đang mở dạng overlay thì reset lại
  window.addEventListener("resize", function () {
    if (window.innerWidth >= 992) closeSidebar();
  });
})();