<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Kiểm tra quyền truy cập admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

require_once('../database/connect.php'); // Nạp file kết nối và các hàm xử lý dữ liệu
require_once('config/config.php'); // Nạp cấu hình Cloudinary SDK ($cloudinary, $uploadFolder)


// Upload banner riêng cho đồ án (ảnh đại diện lớn hiển thị đầu trang)
$banner_url = null;
if (!empty($_FILES['bannerFile']['name'])) {
    $bannerFilesArray = [
        'name'     => [$_FILES['bannerFile']['name']],
        'type'     => [$_FILES['bannerFile']['type']],
        'tmp_name' => [$_FILES['bannerFile']['tmp_name']],
        'error'    => [$_FILES['bannerFile']['error']],
        'size'     => [$_FILES['bannerFile']['size']],
    ];
    $bannerUploadResults = cloudinaryUploadFilesConcurrently(
        $bannerFilesArray,
        $cloudinaryCloudName,
        $cloudinaryApiKey,
        $cloudinaryApiSecret,
        $uploadFolder,
        ['project_banner']
    );
    if (!empty($bannerUploadResults[0]['success'])) {
        $banner_url = $bannerUploadResults[0]['secure_url'];
    } else {
        $upload_errors[] = "Lỗi upload banner: " . $bannerUploadResults[0]['error'];
    }
}

// 2. Xử lý API bằng PHP khi Admin bấm Lưu (Nhận FormData)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['api']) && $_GET['api'] === 'save') {
    header('Content-Type: application/json; charset=UTF-8');
    
    // Nhận dữ liệu text từ $_POST tiêu chuẩn
    $title = trim($_POST['title'] ?? '');
    $category_id = trim($_POST['category_id'] ?? '');
    $description = trim($_POST['description'] ?? ''); // Nhận code HTML từ TinyMCE
    $youtube_id = trim($_POST['youtube_id'] ?? '');
    $drive_link = trim($_POST['drive_link'] ?? '');
    $is_featured = (isset($_POST['is_featured']) && $_POST['is_featured'] == '1') ? 1 : 0;
    
    // Nhận mảng dữ liệu chọn từ Form
    $tech_ids = $_POST['tech_ids'] ?? []; // Bản chất truyền từ FormData qua mảng

    if (empty($title)) {
        echo json_encode(["status" => "error", "message" => "Vui lòng nhập tên đồ án!"]);
        exit;
    }

    // Upload ảnh lên Cloudinary SONG SONG bằng curl_multi (nhanh hơn nhiều so với upload tuần tự)
    // -> $cloudinaryCloudName / $cloudinaryApiKey / $cloudinaryApiSecret / $uploadFolder nạp từ config/config.php
    $image_urls = [];
    $upload_errors = [];

    if (!empty($_FILES['imageFiles']['name'][0])) {
        $uploadResults = cloudinaryUploadFilesConcurrently(
            $_FILES['imageFiles'],
            $cloudinaryCloudName,
            $cloudinaryApiKey,
            $cloudinaryApiSecret,
            $uploadFolder,
            ['project_add']
        );

        foreach ($uploadResults as $result) {
            if ($result['success']) {
                $image_urls[] = $result['secure_url'];
            } else {
                $upload_errors[] = "Lỗi upload file " . $result['file_name'] . ": " . $result['error'];
            }
        }
    }

    // Chống SQL Injection
    $safe_title = addslashes($title);
    $safe_description = addslashes($description); 
    $safe_youtube_id = addslashes($youtube_id);
    $safe_drive_link = addslashes($drive_link);
    $db_category_id = !empty($category_id) ? (int)$category_id : 'NULL';

    // BƯỚC 1: Thêm vào bảng chính projects
    $db_banner = $banner_url ? "'" . addslashes($banner_url) . "'" : 'NULL';

    $sql_project = "INSERT INTO `projects` (`category_id`, `title`, `description`, `youtube_id`, `drive_link`, `is_featured`, `banner`) 
                    VALUES ($db_category_id, '$safe_title', '$safe_description', '$safe_youtube_id', '$safe_drive_link', $is_featured, $db_banner)";
    $project_id = insert($sql_project);

    if ($project_id > 0) {
        // BƯỚC 2: Thêm liên kết công nghệ
        if (!empty($tech_ids)) {
            foreach ($tech_ids as $tech_id) {
                $tech_id = (int)$tech_id;
                execute("INSERT INTO `project_technology` (`project_id`, `technology_id`) VALUES ($project_id, $tech_id)");
            }
        }

        // BƯỚC 3: Lưu chuỗi URL ảnh online từ Cloudinary vào DB
        if (!empty($image_urls)) {
            foreach ($image_urls as $index => $url) {
                $safe_url = addslashes($url);
                $is_featured = ($index === 0) ? 1 : 0; // Ảnh đầu tiên làm thumbnail
                execute("INSERT INTO `project_images` (`project_id`, `image_path`, `is_featured`) 
                        VALUES ($project_id, '$safe_url', $is_featured)");
            }
        }

        $success_message = "Thêm đồ án mới thành công!";
        if (!empty($upload_errors)) {
            $success_message .= " (Lưu ý: " . implode('; ', $upload_errors) . ")";
        }
        echo json_encode(["status" => "success", "message" => $success_message]);
    } else {
        echo json_encode(["status" => "error", "message" => "Không thể ghi dữ liệu vào database."]);
    }
    exit; // Dừng luồng xử lý API tại đây
}

// 3. Lấy dữ liệu danh mục & công nghệ đổ ra giao diện Form
$categories = executeresult("SELECT * FROM `categories` ORDER BY `id` ASC");
$technologies = executeresult("SELECT * FROM `technologies` ORDER BY `name` ASC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Đồ Án Mới</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">

    <!-- Tích hợp TinyMCE miễn phí qua CDN uy tín -->
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>

    <div class="admin-content">
        <div class="admin-main">
            <div class="container-fluid px-4">

    <div class="mb-4">
        <a href="admin_dashboard.php" class="text-decoration-none small text-secondary">
            <i class="bi bi-arrow-left me-1"></i> Quay lại bảng điều khiển
        </a>
    </div>

    <h2 class="fw-bold mb-4"><i class="bi bi-plus-circle-fill text-info me-2"></i>Thêm Sản Phẩm Đồ Án (PHP-Cloudinary)</h2>
    <div id="statusMessage"></div>

    <!-- FORM NHẬP LIỆU (Giữ nguyên cấu trúc form) -->
    <form id="addProjectForm">
        <div class="card card-custom p-4 mb-4">
            <div class="row">
                <div class="col-md-8">
                    <label for="title" class="form-label">Tên đồ án <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" required placeholder="Ví dụ: Website E-Learner học trực tuyến">
                </div>
                <div class="col-md-4">
                    <label for="category_id" class="form-label">Danh mục chính</label>
                    <select class="form-select" id="category_id">
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label d-block">&ThinSpace;</label>
                    <div class="form-check form-switch fs-5">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_featured" name="is_featured" value="1"
                            checked>
                        <label class="form-check-label" for="is_featured" id="toggle-label">
                            Đồ án nổi bật
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Trình soạn thảo mô tả chi tiết TinyMCE -->
            <div class="mb-3">
                <label for="description" class="form-label">Mô tả tính năng chi tiết</label>
                <textarea id="description" placeholder="Mô tả các chức năng nổi bật..."></textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="youtube_id" class="form-label">YouTube Video ID</label>
                    <input type="text" class="form-control" id="youtube_id" placeholder="Ví dụ: dQw4w9WgXcQ">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="drive_link" class="form-label">Đường dẫn Google Drive (Source code / Tài liệu)</label>
                    <input type="url" class="form-control" id="drive_link" placeholder="https://drive.google.com/...">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="bannerFile" class="form-label">Ảnh banner đại diện (hiển thị đầu trang)</label>
                    <input class="form-control" type="file" id="bannerFile" accept="image/*">
                    <div class="mt-2" id="bannerPreview"></div>
                </div>
            </div>
            <!-- Công nghệ sử dụng -->
            <div class="mb-4">
                <label class="form-label mb-2">Các công nghệ chi tiết sử dụng</label>
                <div class="tech-box">
                    <div class="row">
                        <?php foreach ($technologies as $tech): ?>
                            <div class="col-md-3 col-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input tech-checkbox" type="checkbox" value="<?= $tech['id'] ?>" id="tech_<?= $tech['id'] ?>">
                                    <label class="form-check-label small" for="tech_<?= $tech['id'] ?>">
                                        <?= htmlspecialchars($tech['name']) ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Chọn File -->
            <div class="mb-3">
                <label for="imageFiles" class="form-label">Chọn các ảnh demo chụp màn hình</label>
                <input class="form-control" type="file" id="imageFiles" multiple accept="image/*">
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="admin_dashboard.php" class="btn btn-outline-secondary px-4">Hủy bỏ</a>
            <button type="submit" id="btnSubmit" class="btn btn-teal px-4">
                <i class="bi bi-cloud-arrow-up-fill me-1"></i>Lưu đồ án
            </button>
        </div>
    </form>

            </div><!-- /.container-fluid -->
        </div><!-- /.admin-main -->
    </div><!-- /.admin-content -->
</div><!-- /.admin-layout -->

<!-- KHU VỰC SCRIPT XỬ LÝ SỰ KIỆN LOGIC -->
<script src="js/admin-sidebar.js"></script>
<script>
// 1. Cấu hình Khởi tạo Trình soạn thảo văn bản giàu tính năng TinyMCE
tinymce.init({
    selector: '#description',
    height: 320,
    menubar: false,
    skin: 'oxide-dark',
    content_css: 'dark',
    plugins: 'lists link table code wordcount',
    toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table code',
    placeholder: 'Nhập nội dung mô tả đồ án chi tiết tại đây...'
});
// Xem trước ảnh banner khi chọn
document.getElementById('bannerFile').addEventListener('change', function () {
    const preview = document.getElementById('bannerPreview');
    preview.innerHTML = '';
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.innerHTML = `<img src="${e.target.result}" style="max-width:200px;max-height:120px;border-radius:6px;object-fit:cover;">`;
        };
        reader.readAsDataURL(file);
    }
});
// 2. Đón sự kiện Submit Form
document.getElementById('addProjectForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btnSubmit = document.getElementById('btnSubmit');
    const statusMsg = document.getElementById('statusMessage');
    
    btnSubmit.disabled = true;
    statusMsg.innerHTML = `<div class="alert alert-info py-2 small"><span class="spinner-border spinner-border-sm me-2"></span> Đang xử lý tải dữ liệu và đẩy ảnh lên Cloudinary server...</div>`;

    try {
        // Khởi tạo FormData để đóng gói file thô gửi sang PHP xử lý
        const formData = new FormData();
        
        // Append các trường text thông thường
        formData.append('title', document.getElementById('title').value);
        formData.append('category_id', document.getElementById('category_id').value);
        formData.append('description', tinymce.get('description').getContent()); // Lấy nội dung TinyMCE
        formData.append('youtube_id', document.getElementById('youtube_id').value);
        formData.append('drive_link', document.getElementById('drive_link').value);
        const isFeatured = document.getElementById('is_featured').checked ? 1 : 0;
        formData.append('is_featured', isFeatured);

        // Duyệt gắn các công nghệ được checked vào mảng FormData
        const checkedTechs = document.querySelectorAll('.tech-checkbox:checked');
        checkedTechs.forEach(cb => {
            formData.append('tech_ids[]', cb.value); // Để dạng mảng [] cho PHP nhận diện
        });

        // Duyệt gắn các File ảnh được chọn từ input
        const fileInput = document.getElementById('imageFiles');
        const files = fileInput.files;
        for (let i = 0; i < files.length; i++) {
            formData.append('imageFiles[]', files[i]); // Thêm mảng file gửi lên PHP
        }
        const bannerFile = document.getElementById('bannerFile').files[0];
        if (bannerFile) {
            formData.append('bannerFile', bannerFile);
        }
        // Thực hiện lệnh AJAX POST sang endpoint PHP
        const response = await fetch('project_add.php?api=save', {
            method: 'POST',
            body: formData // Truyền trực tiếp đối tượng FormData thay vì chuỗi JSON[cite: 2]
        });

        const resData = await response.json();

        if (resData.status === 'success') {
            statusMsg.innerHTML = `<div class="alert alert-success py-2 small"><i class="bi bi-check-circle-fill me-2"></i> ${resData.message} Hệ thống đang điều hướng...</div>`;
            setTimeout(() => {
                window.location.href = 'admin_dashboard.php';
            }, 1500);
        } else {
            throw new Error(resData.message);
        }

    } catch (error) {
        console.error(error);
        statusMsg.innerHTML = `<div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle-fill me-2"></i> Lỗi hệ thống: ${error.message}</div>`;
        btnSubmit.disabled = false;
    }
});
</script>
</body>
</html>