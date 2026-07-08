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
  const sections = ["home", "techstack", "pricing", "demo", "contact", "projects"];
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

    const submitBtn = contactForm.querySelector('button[type="submit"]');
    const formData = new FormData(contactForm);

    formNote.classList.remove("text-danger", "text-success");
    if (submitBtn) submitBtn.disabled = true;

    fetch(contactForm.action, {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        formNote.textContent = data.message || "";
        formNote.classList.add(data.status === "success" ? "text-success" : "text-danger");
        if (data.status === "success") {
          contactForm.reset();
        }
      })
      .catch(() => {
        formNote.textContent = "Có lỗi xảy ra, vui lòng thử lại sau.";
        formNote.classList.add("text-danger");
      })
      .finally(() => {
        if (submitBtn) submitBtn.disabled = false;
        setTimeout(() => {
          formNote.textContent = "";
          formNote.classList.remove("text-danger", "text-success");
        }, 5000);
      });
  });
})();