(function () {
  "use strict";

  // ============================================================
  // STATE dùng chung cho toàn bộ trang
  // ============================================================
  const state = {
    q: "",
    categoryId: 0,
    page: 1,
  };

  let projectsMap = {};       // lưu data đầy đủ theo id, phục vụ mở modal không cần gọi API lần 2
  let searchDebounceTimer = null;
  let isFirstLoad = true;     // để không cuộn trang ở lần tải đầu tiên

  document.addEventListener("DOMContentLoaded", init);

  function init() {
    bindSearchInput();
    bindCategoryFilter();
    bindModal();
    fetchProjects();
  }

  // ============================================================
  // 1. Ô TÌM KIẾM (debounce để tránh gọi API liên tục khi gõ)
  // ============================================================
  function bindSearchInput() {
    const input = document.getElementById("projectSearchInput");
    input.addEventListener("input", () => {
      clearTimeout(searchDebounceTimer);
      searchDebounceTimer = setTimeout(() => {
        state.q = input.value.trim();
        state.page = 1;
        fetchProjects();
      }, 400); // chờ 400ms sau khi ngừng gõ mới gọi API
    });
  }

  // ============================================================
  // 2. CHIP LỌC DANH MỤC
  // ============================================================
  function bindCategoryFilter() {
    const wrap = document.getElementById("categoryFilter");
    wrap.addEventListener("click", (e) => {
      const btn = e.target.closest(".filter-chip");
      if (!btn) return;

      wrap.querySelectorAll(".filter-chip").forEach((b) => b.classList.remove("is-active"));
      btn.classList.add("is-active");

      state.categoryId = parseInt(btn.dataset.category, 10) || 0;
      state.page = 1;
      fetchProjects();
    });
  }

  // ============================================================
  // 3. GỌI API LẤY DANH SÁCH ĐỒ ÁN
  // ============================================================
  async function fetchProjects() {
    const statusEl = document.getElementById("projectsStatus");
    const emptyEl = document.getElementById("projectsEmpty");
    const gridEl = document.getElementById("projectsGrid");
    const paginationEl = document.getElementById("projectsPagination");

    statusEl.classList.remove("d-none");
    emptyEl.classList.add("d-none");

    const params = new URLSearchParams({
      q: state.q,
      category_id: state.categoryId,
      page: state.page,
    });
    try {
      const res = await fetch(`api/projects_search.php?${params.toString()}`);
      const data = await res.json();
      console.log("API response:", data);

      statusEl.classList.add("d-none");

      if (data.status !== "success") {
        gridEl.innerHTML = "";
        paginationEl.innerHTML = "";
        emptyEl.classList.remove("d-none");
        return;
      }

      // Lưu lại data đầy đủ theo id để mở modal không cần fetch thêm
      projectsMap = {};
      data.items.forEach((item) => {
        projectsMap[item.id] = item;
      });

      renderGrid(data.items);
      renderPagination(data.pagination);

      if (data.items.length === 0) {
        emptyEl.classList.remove("d-none");
      }

      // Cuộn nhẹ lên đầu danh sách khi chuyển trang / lọc / tìm kiếm (trừ lần tải đầu tiên)
      if (!isFirstLoad) {
        document.getElementById("all-projects").scrollIntoView({ behavior: "smooth", block: "start" });
      }
      isFirstLoad = false;

    } catch (err) {
      console.error(err);
      statusEl.classList.add("d-none");
      gridEl.innerHTML = "";
      paginationEl.innerHTML = "";
      emptyEl.classList.remove("d-none");
    }
  }

  // ============================================================
  // 4. RENDER GRID DANH SÁCH ĐỒ ÁN
  // ============================================================
  function escapeHtml(str) {
    const div = document.createElement("div");
    div.textContent = str ?? "";
    return div.innerHTML;
  }

  function renderGrid(items) {
    const gridEl = document.getElementById("projectsGrid");

    if (items.length === 0) {
      gridEl.innerHTML = "";
      return;
    }

    gridEl.innerHTML = items
      .map((p) => {
        const techPills = (p.tech_list || "")
          .split(",")
          .map((t) => t.trim())
          .filter(Boolean)
          .map((t) => `<span class="tech-pill">${escapeHtml(t)}</span>`)
          .join("");
        const bannerUrl = p.banner ? escapeHtml(p.banner) : "image/logo.jpg";

        return `
          <a href="project_detail?id=${p.id}" class="demo-card">
            <div class="demo-card__thumb">
              <img src="${bannerUrl}" alt="${escapeHtml(p.title)}" loading="lazy">
              <div class="demo-card__play"></div>
            </div>
            <div class="demo-card__body">
              <span class="demo-card__tag">${escapeHtml(p.category_name)}</span>
              <h3 class="demo-card__title">${escapeHtml(p.title)}</h3>
              <div class="demo-card__techs">
                ${techPills || '<span class="text-white small">Chưa chọn</span>'}
              </div>
              <p class="demo-card__meta"><i class="bi bi-patch-check-fill"></i> Kèm báo cáo &amp; video hướng dẫn</p>
            </div>
          </a>
        `;
      })
      .join("");
  }

  // ============================================================
  // 5. RENDER PHÂN TRANG (dạng cửa sổ trượt: 1 ... 4 5 [6] 7 8 ... 20)
  // ============================================================
  function renderPagination(pg) {
    const el = document.getElementById("projectsPagination");
    el.innerHTML = "";

    if (pg.totalPages <= 1) return;

    const frag = document.createDocumentFragment();

    function makeBtn(label, page, opts = {}) {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.textContent = label;
      if (opts.active) btn.classList.add("is-active");
      if (opts.disabled) btn.disabled = true;
      if (!opts.disabled && !opts.active) {
        btn.addEventListener("click", () => {
          state.page = page;
          fetchProjects();
        });
      }
      return btn;
    }

    // Nút lùi
    frag.appendChild(makeBtn("«", pg.page - 1, { disabled: pg.page <= 1 }));

    // Các nút số trang
    getPageWindow(pg.page, pg.totalPages).forEach((p) => {
      if (p === "...") {
        const span = document.createElement("button");
        span.type = "button";
        span.textContent = "...";
        span.disabled = true;
        frag.appendChild(span);
      } else {
        frag.appendChild(makeBtn(String(p), p, { active: p === pg.page }));
      }
    });

    // Nút tiến
    frag.appendChild(makeBtn("»", pg.page + 1, { disabled: pg.page >= pg.totalPages }));

    el.appendChild(frag);
  }

  function getPageWindow(current, total) {
    const delta = 1;
    const range = [];
    for (let i = Math.max(2, current - delta); i <= Math.min(total - 1, current + delta); i++) {
      range.push(i);
    }

    const result = [1];
    if (range[0] > 2) result.push("...");
    result.push(...range);
    if (range.length && range[range.length - 1] < total - 1) result.push("...");
    if (total > 1) result.push(total);

    return result;
  }

  // ============================================================
  // 6. MODAL CHI TIẾT ĐỒ ÁN
  // ============================================================
  function bindModal() {
    const projectModalEl = document.getElementById("projectModal");
    const projectModal = new bootstrap.Modal(projectModalEl);

    const modalTitle = document.getElementById("projectModalLabel");
    const modalTechs = document.getElementById("projectModalTechs");
    const modalDesc = document.getElementById("projectModalDesc");
    const modalDrive = document.getElementById("projectModalDrive");
    const modalVideo = document.getElementById("projectModalVideo");
    const modalVideoWrap = document.getElementById("projectModalVideoWrap");

    // Event delegation: grid được render lại liên tục nên gắn sự kiện ở phần tử cha cố định
    const gridEl = document.getElementById("projectsGrid");

    gridEl.addEventListener("click", (e) => {
      const card = e.target.closest(".demo-card");
      if (!card) return;
      openModal(card.dataset.id);
    });

    // Hỗ trợ mở modal bằng bàn phím (Enter / Space) khi card đang được focus
    gridEl.addEventListener("keydown", (e) => {
      if (e.key === "Enter" || e.key === " ") {
        const card = e.target.closest(".demo-card");
        if (card) {
          e.preventDefault();
          openModal(card.dataset.id);
        }
      }
    });

    function openModal(id) {
      const data = projectsMap[id];
      if (!data) return;

      modalTitle.textContent = data.title;

      // Công nghệ
      modalTechs.innerHTML = "";
      (data.tech_list || "")
        .split(",")
        .map((t) => t.trim())
        .filter(Boolean)
        .forEach((tech) => {
          const span = document.createElement("span");
          span.className = "tech-pill";
          span.textContent = tech;
          modalTechs.appendChild(span);
        });

      // Mô tả HTML (từ TinyMCE)
      modalDesc.innerHTML = data.description || "<em>Chưa có mô tả.</em>";

      // Link Drive
      if (data.drive_link) {
        modalDrive.href = data.drive_link;
        modalDrive.classList.remove("d-none");
      } else {
        modalDrive.classList.add("d-none");
      }

      // Video: youtube_id đang lưu nguyên đoạn <iframe> admin đã dán, không phải chỉ ID
      if (data.youtube_id && data.youtube_id.trim() !== "") {
        modalVideo.innerHTML = data.youtube_id;

        // Xóa width/height cứng để CSS full 100% khung .ratio có tác dụng
        const iframeEl = modalVideo.querySelector("iframe");
        if (iframeEl) {
          iframeEl.removeAttribute("width");
          iframeEl.removeAttribute("height");
          iframeEl.style.width = "100%";
          iframeEl.style.height = "100%";
        }

        modalVideoWrap.classList.remove("d-none");
      } else {
        modalVideo.innerHTML = "";
        modalVideoWrap.classList.add("d-none");
      }

      projectModal.show();
    }

    // Dừng video khi đóng modal (tránh phát ngầm)
    projectModalEl.addEventListener("hidden.bs.modal", () => {
      modalVideo.innerHTML = "";
    });
  }
})();