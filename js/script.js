(function () {
  "use strict";

  /* ========================================================================
     0. FOOTER YEAR
     ======================================================================== */
  document.getElementById("year").textContent = new Date().getFullYear();

  /* ========================================================================
     1. HEADER: trạng thái scroll + active hashtag theo section đang xem
     ======================================================================== */
  const header = document.getElementById("siteHeader");
  const sections = ["home", "techstack", "pricing", "demo", "contact"];
  const navTags = document.querySelectorAll(".nav-tag");

  function onScroll() {
    header.classList.toggle("is-scrolled", window.scrollY > 30);

    let current = sections[0];
    const offset = header.offsetHeight + 40;
    for (const id of sections) {
      const el = document.getElementById(id);
      if (el && el.getBoundingClientRect().top - offset <= 0) current = id;
    }
    navTags.forEach((tag) => {
      tag.classList.toggle("is-active", tag.dataset.target === current);
    });
  }
  window.addEventListener("scroll", onScroll, { passive: true });
  onScroll();

  /* ========================================================================
     2. MOBILE MENU: Tự động đóng menu Bootstrap Collapse khi chọn link
     ======================================================================== */
  const navCollapseEl = document.getElementById("navCollapse");
  if (navCollapseEl && window.bootstrap) {
    const bsCollapse = window.bootstrap.Collapse.getOrCreateInstance(navCollapseEl, { toggle: false });
    navCollapseEl.querySelectorAll("a").forEach((a) =>
      a.addEventListener("click", () => {
        if (navCollapseEl.classList.contains("show")) bsCollapse.hide();
      })
    );
  }

  /* ========================================================================
     3. SMOOTH SCROLL với offset tính theo chiều cao header cố định
     ======================================================================== */
  document.querySelectorAll('a[href^="#"]').forEach((link) => {
    link.addEventListener("click", (e) => {
      const targetId = link.getAttribute("href").slice(1);
      const target = document.getElementById(targetId);
      if (!target) return;
      e.preventDefault();
      const top = target.getBoundingClientRect().top + window.scrollY - (header.offsetHeight - 4);
      window.scrollTo({ top, behavior: "smooth" });
    });
  });

  /* ========================================================================
     4. REVEAL ON SCROLL (Hiệu ứng xuất hiện khi cuộn trang)
     ======================================================================== */
  const revealEls = document.querySelectorAll(".reveal-up");
  const revealObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          revealObserver.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15, rootMargin: "0px 0px -40px 0px" }
  );
  revealEls.forEach((el) => revealObserver.observe(el));

  /* ========================================================================
     5. COUNT-UP CHO SỐ LIỆU HERO
     ======================================================================== */
  const counters = document.querySelectorAll("[data-count]");
  const counterObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        const target = parseInt(el.dataset.count, 10);
        const duration = 1400;
        const start = performance.now();
        function tick(now) {
          const progress = Math.min((now - start) / duration, 1);
          const eased = 1 - Math.pow(1 - progress, 3);
          el.textContent = Math.round(eased * target);
          if (progress < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
        counterObserver.unobserve(el);
      });
    },
    { threshold: 0.6 }
  );
  counters.forEach((el) => counterObserver.observe(el));

  /* ========================================================================
     6. HIỆU ỨNG GÕ CHỮ TRONG "IDE" MÔ PHỎNG Ở HERO
     ======================================================================== */
  const codeLines = [
    { text: "// Đồ án: Hệ thống quản lý bán hàng\n", cls: "tok-com" },
    { text: "@RestController", cls: "tok-fn" },
    { text: "public class ", cls: "tok-kw", inline: true },
    { text: "OrderController {\n", cls: "tok-type", inline: true },
    { text: "\n  @PostMapping(", cls: "tok-fn", inline: true },
    { text: '"/api/orders"', cls: "tok-str", inline: true },
    { text: ")\n  public ", cls: "", inline: true },
    { text: "ResponseEntity", cls: "tok-type", inline: true },
    { text: " placeOrder(...) {\n    return ", cls: "", inline: true },
    { text: "service", cls: "tok-fn", inline: true },
    { text: ".save(order); // bàn giao đúng hạn\n  }\n}", cls: "", inline: true },
  ];

  const typedCodeEl = document.getElementById("typedCode");

  function typeSequence() {
    typedCodeEl.innerHTML = "";
    let lineIndex = 0;

    function typeNextLine() {
      if (lineIndex >= codeLines.length) {
        setTimeout(typeSequence, 2200);
        return;
      }
      const line = codeLines[lineIndex];
      const span = document.createElement("span");
      if (line.cls) span.className = line.cls;
      typedCodeEl.appendChild(span);

      let charIndex = 0;
      const speed = 18;
      (function typeChar() {
        if (charIndex < line.text.length) {
          span.textContent += line.text[charIndex];
          charIndex++;
          setTimeout(typeChar, speed);
        } else {
          lineIndex++;
          setTimeout(typeNextLine, 90);
        }
      })();
    }
    typeNextLine();
  }

  const heroObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          typeSequence();
          heroObserver.disconnect();
        }
      });
    },
    { threshold: 0.2 }
  );
  heroObserver.observe(document.getElementById("home"));

  /* ========================================================================
     7. DEMO: Quản lý dữ liệu mẫu + Bộ lọc + Tìm kiếm + Phân trang
     ------------------------------------------------------------------------
     Mẹo kết nối Firebase Realtime Database sau này:
     Chỉ cần Fetch data từ Firebase rồi gán lại cho mảng `demoData` dưới đây,
     sau đó gọi `renderFilters()` và `applyFilters()` là xong.
     ======================================================================== */
  let demoData = [
    { 
      title: "Hệ thống Quản lý Bán hàng Đa chi nhánh & Phân quyền", 
      category: "spring", 
      techs: ["Java", "Spring Boot", "Spring Security", "MySQL", "Bootstrap 5"], 
      youtubeId: "dQw4w9WgXcQ" 
    },
    { 
      title: "Website Quản lý Thư viện, Mượn trả Sách & Tính tiền phạt", 
      category: "servlet", 
      techs: ["Java Servlet", "JSP", "JDBC", "MySQL", "JavaScript"], 
      youtubeId: "dQw4w9WgXcQ" 
    },
    { 
      title: "Website Đặt phòng Khách sạn & Quản lý Tour du lịch trực tuyến", 
      category: "php", 
      techs: ["PHP 8.x", "Laravel Framework", "MySQL", "Blade Template"], 
      youtubeId: "dQw4w9WgXcQ" 
    },
    { 
      title: "Phần mềm Quản lý Nhân sự, Tính lương & Chấm công Vân tay", 
      category: "dotnet", 
      techs: ["C# (.NET)", "Windows Forms", "ADO.NET", "SQL Server"], 
      youtubeId: "dQw4w9WgXcQ" 
    },
    { 
      title: "Sàn Thương mại Điện tử Mini (Bán linh kiện máy tính)", 
      category: "react", 
      techs: ["ReactJS (SPA)", "Redux Toolkit", "Spring Boot", "JWT", "MySQL"], 
      youtubeId: "dQw4w9WgXcQ" 
    },
    { 
      title: "Hệ thống Đặt vé Xem phim trực tuyến & Sơ đồ Ghế ngồi realtime", 
      category: "angular", 
      techs: ["Angular 17", "TypeScript", ".NET Core API", "SQL Server"], 
      youtubeId: "dQw4w9WgXcQ" 
    },
    { 
      title: "Website Tuyển dụng Việc làm & Nộp CV trực tuyến cho Ứng viên", 
      category: "php", 
      techs: ["PHP thuần", "MVC Architecture", "PDO", "MySQL"], 
      youtubeId: "dQw4w9WgXcQ" 
    },
    { 
      title: "Hệ thống Điểm danh Sinh viên bằng Nhận diện Khuôn mặt", 
      category: "spring", 
      techs: ["Java Core", "Spring Boot", "OpenCV", "Python (Flask API)"], 
      youtubeId: "dQw4w9WgXcQ" 
    },
    { 
      title: "Ứng dụng Quản lý Quán Cà phê & Gọi món tại bàn qua QR Code", 
      category: "dotnet", 
      techs: ["C#", "ASP.NET Core MVC", "SignalR Hub", "Oracle DB"], 
      youtubeId: "dQw4w9WgXcQ" 
    }
  ];

  const categoryLabels = {
    all: "Tất cả",
    spring: "Java / Spring",
    servlet: "Servlet / JSP",
    php: "PHP / Laravel",
    dotnet: "C# / .NET",
    react: "ReactJS",
    angular: "Angular",
  };

  const demoGrid = document.getElementById("demoGrid");
  const demoEmpty = document.getElementById("demoEmpty");
  const demoPagination = document.getElementById("demoPagination");
  const demoSearch = document.getElementById("demoSearch");
  const demoFilters = document.getElementById("demoFilters");

  const PAGE_SIZE = 6;
  let currentPage = 1;
  let activeCategory = "all";

  function renderFilters() {
    const used = new Set(demoData.map((d) => d.category));
    demoFilters.querySelectorAll(".filter-chip:not([data-filter='all'])").forEach((b) => b.remove());
    used.forEach((cat) => {
      if (!categoryLabels[cat]) return;
      const btn = document.createElement("button");
      btn.className = "filter-chip";
      btn.dataset.filter = cat;
      btn.textContent = categoryLabels[cat];
      demoFilters.appendChild(btn);
    });
    bindFilterEvents();
  }

  function bindFilterEvents() {
    demoFilters.querySelectorAll(".filter-chip").forEach((btn) => {
      btn.onclick = () => {
        demoFilters.querySelectorAll(".filter-chip").forEach((b) => b.classList.remove("is-active"));
        btn.classList.add("is-active");
        activeCategory = btn.dataset.filter;
        currentPage = 1;
        applyFilters();
      };
    });
  }

  function getFilteredData() {
    const query = demoSearch.value.trim().toLowerCase();
    return demoData.filter((item) => {
      const matchCategory = activeCategory === "all" || item.category === activeCategory;
      const techString = (item.techs || []).join(" ").toLowerCase();
      const matchQuery =
        !query ||
        item.title.toLowerCase().includes(query) ||
        techString.includes(query);
      return matchCategory && matchQuery;
    });
  }

  function renderDemoCard(item) {
    const card = document.createElement("article");
    card.className = "demo-card";
    
    const techPills = (item.techs || [])
      .map((t) => `<span class="tech-pill">${t}</span>`)
      .join("");
      
    card.innerHTML = `
      <a href="https://www.youtube.com/watch?v=${item.youtubeId}" target="_blank" rel="noopener" class="demo-card__thumb">
        <div class="demo-card__play"><i class="bi bi-play-fill"></i></div>
      </a>
      <div class="demo-card__body">
        <span class="demo-card__tag">${categoryLabels[item.category] || "Đồ án"}</span>
        <h3 class="demo-card__title" style="color: #ffffff;">${item.title}</h3>
        <div class="demo-card__techs">${techPills}</div>
        <p class="demo-card__meta"><i class="bi bi-patch-check-fill"></i> Kèm báo cáo &amp; video hướng dẫn</p>
      </div>`;
    return card;
  }

  function renderPagination(totalItems) {
    demoPagination.innerHTML = "";
    const totalPages = Math.max(1, Math.ceil(totalItems / PAGE_SIZE));
    if (totalPages <= 1) return;

    const prev = document.createElement("button");
    prev.innerHTML = '<i class="bi bi-chevron-left"></i>';
    prev.disabled = currentPage === 1;
    prev.onclick = () => { currentPage--; applyFilters(); };
    demoPagination.appendChild(prev);

    for (let i = 1; i <= totalPages; i++) {
      const btn = document.createElement("button");
      btn.textContent = i;
      btn.classList.toggle("is-active", i === currentPage);
      btn.onclick = () => { currentPage = i; applyFilters(); };
      demoPagination.appendChild(btn);
    }

    const next = document.createElement("button");
    next.innerHTML = '<i class="bi bi-chevron-right"></i>';
    next.disabled = currentPage === totalPages;
    next.onclick = () => { currentPage++; applyFilters(); };
    demoPagination.appendChild(next);
  }

  function applyFilters() {
    const filtered = getFilteredData();
    const totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
    currentPage = Math.min(currentPage, totalPages);

    const start = (currentPage - 1) * PAGE_SIZE;
    const pageItems = filtered.slice(start, start + PAGE_SIZE);

    demoGrid.innerHTML = "";
    demoEmpty.classList.toggle("d-none", filtered.length > 0);

    pageItems.forEach((item, i) => {
      const card = renderDemoCard(item);
      card.style.animationDelay = `${i * 0.06}s`;
      demoGrid.appendChild(card);
    });

    renderPagination(filtered.length);
  }

  let searchDebounce;
  demoSearch.addEventListener("input", () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
      currentPage = 1;
      applyFilters();
    }, 250);
  });

  renderFilters();
  applyFilters();

  /* ========================================================================
     8. FLOATING CHAT WIDGET (Messenger / Zalo)
     ======================================================================== */
  const chatWidget = document.getElementById("chatWidget");
  const chatToggle = document.getElementById("chatToggle");

  chatToggle.addEventListener("click", () => {
    const isOpen = chatWidget.classList.toggle("is-open");
    chatToggle.setAttribute("aria-expanded", String(isOpen));
  });

  document.addEventListener("click", (e) => {
    if (!chatWidget.contains(e.target)) chatWidget.classList.remove("is-open");
  });

  /* ========================================================================
     9. FORM LIÊN HỆ
     ======================================================================== */
  const contactForm = document.getElementById("contactForm");
  const formNote = document.getElementById("formNote");

  contactForm.addEventListener("submit", (e) => {
    e.preventDefault();
    if (!contactForm.checkValidity()) {
      contactForm.reportValidity();
      return;
    }
    formNote.textContent = "Đã gửi yêu cầu! Mình sẽ liên hệ lại trong vòng 30 phút.";
    contactForm.reset();
    setTimeout(() => (formNote.textContent = ""), 5000);
  });
})();