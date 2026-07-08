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
require_once('config/config.php');       // Nạp cấu hình Cloudinary SDK ($cloudinary, $uploadFolder)

// 2. Xác định ID đồ án cần sửa
$project_id = (int)($_GET['id'] ?? 0);
if ($project_id <= 0) {
    header("Location: admin_dashboard.php");
    exit;
}

/**
 * Hàm hỗ trợ: Trích public_id từ secure_url của Cloudinary để phục vụ việc xóa ảnh
 * Ví dụ: https://res.cloudinary.com/xxx/image/upload/v1234567890/uploads/ten_anh.png
 * => public_id: uploads/ten_anh
 */
function getCloudinaryPublicId($url) {
    $marker = '/upload/';
    $pos = strpos($url, $marker);
    if ($pos === false) return null;

    $path = substr($url, $pos + strlen($marker));
    // Bỏ đoạn version (vd: v1234567890/) nếu có
    $path = preg_replace('#^v\d+/#', '', $path);
    // Bỏ phần đuôi mở rộng file (vd: .png, .jpg)
    $path = preg_replace('#\.[a-zA-Z0-9]+$#', '', $path);

    return $path;
}

// 3. Xử lý API bằng PHP khi Admin bấm Lưu (Nhận FormData)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['api']) && $_GET['api'] === 'save') {
    header('Content-Type: application/json; charset=UTF-8');

    // Kiểm tra đồ án có tồn tại không
    $existing = querySingleResult("SELECT id, banner FROM `projects` WHERE id = $project_id");
    if (empty($existing)) {
        echo json_encode(["status" => "error", "message" => "Không tìm thấy đồ án cần sửa!"]);
        exit;
    }

    // Nhận dữ liệu text từ $_POST tiêu chuẩn
    $title = trim($_POST['title'] ?? '');
    $category_id = trim($_POST['category_id'] ?? '');
    $is_featured = (isset($_POST['is_featured']) && $_POST['is_featured'] == '1') ? 1 : 0;
    $description = trim($_POST['description'] ?? '');
    $youtube_id = trim($_POST['youtube_id'] ?? '');
    $drive_link = trim($_POST['drive_link'] ?? '');
    $tech_ids = $_POST['tech_ids'] ?? [];
    $delete_image_ids = $_POST['delete_images'] ?? [];
    $featured_selection = trim($_POST['featured_image'] ?? ''); // "existing_<id>" hoặc "new_<index>"
    $remove_banner = (isset($_POST['remove_banner']) && $_POST['remove_banner'] == '1');

    if (empty($title)) {
        echo json_encode(["status" => "error", "message" => "Vui lòng nhập tên đồ án!"]);
        exit;
    }

    $upload_errors = [];

    // BƯỚC 1: Xóa các ảnh cũ được đánh dấu xóa (DB + cố gắng xóa trên Cloudinary)
    if (!empty($delete_image_ids)) {
        foreach ($delete_image_ids as $del_id) {
            $del_id = (int)$del_id;
            $imgRow = querySingleResult("SELECT * FROM `project_images` WHERE id = $del_id AND project_id = $project_id");
            if (!empty($imgRow)) {
                $public_id = getCloudinaryPublicId($imgRow['image_path']);
                if ($public_id) {
                    try {
                        $cloudinary->uploadApi()->destroy($public_id);
                    } catch (Exception $e) {
                        // Không chặn luồng nếu xóa trên Cloudinary thất bại, chỉ ghi nhận
                        $upload_errors[] = "Không thể xóa ảnh cũ trên Cloudinary: " . $e->getMessage();
                    }
                }
                execute("DELETE FROM `project_images` WHERE id = $del_id");
            }
        }
    }

    // BƯỚC 2: Upload ảnh mới lên Cloudinary SONG SONG bằng curl_multi (nhanh hơn upload tuần tự)
    $new_image_ids = []; // map index (thứ tự file, giữ nguyên như $_FILES gốc) => id vừa insert vào DB

    if (!empty($_FILES['imageFiles']['name'][0])) {
        $uploadResults = cloudinaryUploadFilesConcurrently(
            $_FILES['imageFiles'],
            $cloudinaryCloudName,
            $cloudinaryApiKey,
            $cloudinaryApiSecret,
            $uploadFolder,
            ['project_edit']
        );

        foreach ($uploadResults as $index => $result) {
            if ($result['success']) {
                $safe_url = addslashes($result['secure_url']);
                $new_id = insert("INSERT INTO `project_images` (`project_id`, `image_path`, `is_featured`) 
                                  VALUES ($project_id, '$safe_url', 0)");
                $new_image_ids[$index] = $new_id; // giữ đúng index gốc để khớp với lựa chọn "featured_image" = new_<index>
            } else {
                $upload_errors[] = "Lỗi upload file " . $result['file_name'] . ": " . $result['error'];
            }
        }
    }


    // BƯỚC 2b: Xử lý banner (ảnh đại diện lớn của đồ án)
    $old_banner = $existing['banner'] ?? null;
    $db_banner = $old_banner ? "'" . addslashes($old_banner) . "'" : 'NULL'; // Mặc định giữ nguyên banner cũ

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
            // Xóa banner cũ trên Cloudinary (nếu có) để tránh rác file
            if (!empty($old_banner)) {
                $old_banner_public_id = getCloudinaryPublicId($old_banner);
                if ($old_banner_public_id) {
                    try { $cloudinary->uploadApi()->destroy($old_banner_public_id); } catch (Exception $e) {}
                }
            }
            $db_banner = "'" . addslashes($bannerUploadResults[0]['secure_url']) . "'";
        } else {
            $upload_errors[] = "Lỗi upload banner: " . $bannerUploadResults[0]['error'];
        }
    } elseif ($remove_banner) {
        if (!empty($old_banner)) {
            $old_banner_public_id = getCloudinaryPublicId($old_banner);
            if ($old_banner_public_id) {
                try { $cloudinary->uploadApi()->destroy($old_banner_public_id); } catch (Exception $e) {}
            }
        }
        $db_banner = 'NULL';
    }

    // BƯỚC 3: Cập nhật thông tin chính của đồ án
    $safe_title = addslashes($title);
    $safe_description = addslashes($description);
    $safe_youtube_id = addslashes($youtube_id);
    $safe_drive_link = addslashes($drive_link);
    $db_category_id = !empty($category_id) ? (int)$category_id : 'NULL';

    execute("UPDATE `projects` SET 
                `category_id` = $db_category_id,
                `title` = '$safe_title',
                `description` = '$safe_description',
                `youtube_id` = '$safe_youtube_id',
                `drive_link` = '$safe_drive_link',
                `is_featured` = $is_featured,
                `banner` = $db_banner
             WHERE `id` = $project_id");

    // BƯỚC 4: Cập nhật lại danh sách công nghệ (xóa hết rồi thêm lại theo lựa chọn mới)
    execute("DELETE FROM `project_technology` WHERE `project_id` = $project_id");
    if (!empty($tech_ids)) {
        foreach ($tech_ids as $tech_id) {
            $tech_id = (int)$tech_id;
            execute("INSERT INTO `project_technology` (`project_id`, `technology_id`) VALUES ($project_id, $tech_id)");
        }
    }

    // BƯỚC 5: Xác định lại ảnh đại diện (is_featured)
    // Trước tiên bỏ trạng thái featured của toàn bộ ảnh thuộc đồ án này
    execute("UPDATE `project_images` SET `is_featured` = 0 WHERE `project_id` = $project_id");

    $featured_set = false;
    if (!empty($featured_selection)) {
        if (strpos($featured_selection, 'existing_') === 0) {
            $existing_img_id = (int)str_replace('existing_', '', $featured_selection);
            execute("UPDATE `project_images` SET `is_featured` = 1 WHERE id = $existing_img_id AND project_id = $project_id");
            $featured_set = true;
        } elseif (strpos($featured_selection, 'new_') === 0) {
            $new_index = (int)str_replace('new_', '', $featured_selection);
            if (isset($new_image_ids[$new_index])) {
                $chosen_id = (int)$new_image_ids[$new_index];
                execute("UPDATE `project_images` SET `is_featured` = 1 WHERE id = $chosen_id AND project_id = $project_id");
                $featured_set = true;
            }
        }
    }

    // Nếu chưa có ảnh nào được chọn làm đại diện, tự động gán ảnh đầu tiên còn lại làm đại diện
    if (!$featured_set) {
        $firstImage = querySingleResult("SELECT id FROM `project_images` WHERE project_id = $project_id ORDER BY id ASC LIMIT 1");
        if (!empty($firstImage)) {
            execute("UPDATE `project_images` SET `is_featured` = 1 WHERE id = " . (int)$firstImage['id']);
        }
    }

    $success_message = "Cập nhật đồ án thành công!";
    if (!empty($upload_errors)) {
        $success_message .= " (Lưu ý: " . implode('; ', $upload_errors) . ")";
    }
    echo json_encode(["status" => "success", "message" => $success_message]);
    exit;
}

// 4. Lấy dữ liệu đồ án hiện tại để đổ ra Form
$project = querySingleResult("SELECT * FROM `projects` WHERE id = $project_id");
if (empty($project)) {
    header("Location: admin_dashboard.php");
    exit;
}

// Lấy danh sách công nghệ đã chọn của đồ án này
$selectedTechRows = executeresult("SELECT technology_id FROM `project_technology` WHERE project_id = $project_id");
$selectedTechIds = array_column($selectedTechRows, 'technology_id');

// Lấy danh sách ảnh hiện có của đồ án (ảnh đại diện lên trước)
$existingImages = executeresult("SELECT * FROM `project_images` WHERE project_id = $project_id ORDER BY is_featured DESC, id ASC");

// Lấy dữ liệu danh mục & công nghệ đổ ra giao diện Form
$categories = executeresult("SELECT * FROM `categories` ORDER BY `id` ASC");
$technologies = executeresult("SELECT * FROM `technologies` ORDER BY `name` ASC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa Đồ Án</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">

    <!-- Tích hợp TinyMCE miễn phí qua CDN uy tín -->
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        .existing-image-item {
            position: relative;
            width: 150px;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 6px;
            padding: 8px;
            text-align: center;
        }
        .existing-image-item img {
            width: 100%;
            height: 110px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 6px;
        }
        .new-image-item {
            position: relative;
            width: 150px;
            border: 1px dashed rgba(255,255,255,0.25);
            border-radius: 6px;
            padding: 8px;
            text-align: center;
        }
        .new-image-item img {
            width: 100%;
            height: 110px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 6px;
        }
        .featured-badge {
            font-size: 11px;
        }
    </style>
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

    <h2 class="fw-bold mb-4"><i class="bi bi-pencil-square text-warning me-2"></i>Sửa Đồ Án: <?= htmlspecialchars($project['title']) ?></h2>
    <div id="statusMessage"></div>

    <!-- FORM CHỈNH SỬA -->
    <form id="editProjectForm">
        <div class="card card-custom p-4 mb-4">
            <div class="row">
                <div class="col-md-8">
                    <label for="title" class="form-label">Tên đồ án <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" required value="<?= htmlspecialchars($project['title']) ?>">
                </div>
                <div class="col-md-4">
                    <label for="category_id" class="form-label">Danh mục chính</label>
                    <select class="form-select" id="category_id">
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($project['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label d-block">&ThinSpace;</label>
                    <div class="form-check form-switch fs-5">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_featured" name="is_featured" value="1"
                            <?= ($project['is_featured'] == 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_featured" id="toggle-label">
                            Đồ án nổi bật
                        </label>
                    </div>
                </div>
            </div>

            <!-- Trình soạn thảo mô tả chi tiết TinyMCE -->
            <div class="mb-3">
                <label for="description" class="form-label">Mô tả tính năng chi tiết</label>
                <textarea id="description" placeholder="Mô tả các chức năng nổi bật..."><?= htmlspecialchars($project['description'] ?? '') ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="youtube_id" class="form-label">YouTube Video ID</label>
                    <input type="text" class="form-control" id="youtube_id" placeholder="Ví dụ: dQw4w9WgXcQ" value="<?= htmlspecialchars($project['youtube_id'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="drive_link" class="form-label">Đường dẫn Google Drive (Source code / Tài liệu)</label>
                    <input type="url" class="form-control" id="drive_link" placeholder="https://drive.google.com/..." value="<?= htmlspecialchars($project['drive_link'] ?? '') ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="bannerFile" class="form-label">Ảnh banner đại diện (hiển thị đầu trang)</label>
                    <?php if (!empty($project['banner'])): ?>
                        <div class="mb-2">
                            <img src="<?= htmlspecialchars($project['banner']) ?>" alt="Banner hiện tại"
                                 style="max-width:220px;max-height:130px;border-radius:6px;object-fit:cover;">
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" id="remove_banner" value="1">
                                <label class="form-check-label small text-danger" for="remove_banner">Xóa banner hiện tại</label>
                            </div>
                        </div>
                    <?php endif; ?>
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
                                    <input class="form-check-input tech-checkbox" type="checkbox"
                                           value="<?= $tech['id'] ?>" id="tech_<?= $tech['id'] ?>"
                                           <?= in_array($tech['id'], $selectedTechIds) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="tech_<?= $tech['id'] ?>">
                                        <?= htmlspecialchars($tech['name']) ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ẢNH HIỆN CÓ -->
            <div class="mb-4">
                <label class="form-label mb-2">Ảnh hiện có (chọn nút tròn để đặt làm ảnh đại diện, tick ô để xóa ảnh)</label>
                <div class="d-flex flex-wrap gap-3" id="existingImagesContainer">
                    <?php if (empty($existingImages)): ?>
                        <div class="text-muted small">Đồ án này chưa có ảnh nào.</div>
                    <?php else: ?>
                        <?php foreach ($existingImages as $img): ?>
                            <div class="existing-image-item" data-image-id="<?= $img['id'] ?>">
                                <img src="<?= htmlspecialchars($img['image_path']) ?>" alt="Ảnh đồ án">
                                <div class="form-check d-flex justify-content-center align-items-center gap-1 mb-1">
                                    <input class="form-check-input featured-radio" type="radio" name="featured_image_ui"
                                           value="existing_<?= $img['id'] ?>" id="featured_existing_<?= $img['id'] ?>"
                                           <?= ($img['is_featured'] == 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label small featured-badge" for="featured_existing_<?= $img['id'] ?>">Đại diện</label>
                                </div>
                                <div class="form-check d-flex justify-content-center align-items-center gap-1">
                                    <input class="form-check-input delete-image-checkbox" type="checkbox"
                                           value="<?= $img['id'] ?>" id="delete_existing_<?= $img['id'] ?>">
                                    <label class="form-check-label small text-danger" for="delete_existing_<?= $img['id'] ?>">Xóa ảnh</label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Thêm ảnh mới -->
            <div class="mb-3">
                <label for="imageFiles" class="form-label">Thêm ảnh demo mới (không bắt buộc)</label>
                <input class="form-control" type="file" id="imageFiles" multiple accept="image/*">
                <div class="d-flex flex-wrap gap-3 mt-3" id="newImagesContainer"></div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="admin_dashboard.php" class="btn btn-outline-secondary px-4">Hủy bỏ</a>
            <button type="submit" id="btnSubmit" class="btn btn-teal px-4">
                <i class="bi bi-cloud-arrow-up-fill me-1"></i>Lưu thay đổi
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
const PROJECT_ID = <?= (int)$project_id ?>;

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

// Xem trước ảnh banner mới khi chọn
document.getElementById('bannerFile').addEventListener('change', function () {
    const preview = document.getElementById('bannerPreview');
    preview.innerHTML = '';
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.innerHTML = `<img src="${e.target.result}" style="max-width:220px;max-height:130px;border-radius:6px;object-fit:cover;">`;
        };
        reader.readAsDataURL(file);
    }
});

// 2. Xem trước các ảnh mới được chọn + gắn radio "Đại diện" cho từng ảnh mới
const fileInput = document.getElementById('imageFiles');
const newImagesContainer = document.getElementById('newImagesContainer');

fileInput.addEventListener('change', function () {
    newImagesContainer.innerHTML = '';
    const files = Array.from(this.files);

    files.forEach((file, index) => {
        if (!file.type.startsWith('image/')) return;

        const reader = new FileReader();
        reader.onload = function (e) {
            const item = document.createElement('div');
            item.className = 'new-image-item';

            item.innerHTML = `
                <img src="${e.target.result}" alt="${file.name}">
                <div class="form-check d-flex justify-content-center align-items-center gap-1">
                    <input class="form-check-input featured-radio" type="radio" name="featured_image_ui"
                           value="new_${index}" id="featured_new_${index}">
                    <label class="form-check-label small featured-badge" for="featured_new_${index}">Đại diện</label>
                </div>
            `;
            newImagesContainer.appendChild(item);
        };
        reader.readAsDataURL(file);
    });
});

// 3. Đón sự kiện Submit Form
document.getElementById('editProjectForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const btnSubmit = document.getElementById('btnSubmit');
    const statusMsg = document.getElementById('statusMessage');

    btnSubmit.disabled = true;
    statusMsg.innerHTML = `<div class="alert alert-info py-2 small"><span class="spinner-border spinner-border-sm me-2"></span> Đang cập nhật dữ liệu và đồng bộ ảnh với Cloudinary...</div>`;

    try {
        const formData = new FormData();

        formData.append('title', document.getElementById('title').value);
        formData.append('category_id', document.getElementById('category_id').value);
        formData.append('description', tinymce.get('description').getContent());
        formData.append('youtube_id', document.getElementById('youtube_id').value);
        formData.append('drive_link', document.getElementById('drive_link').value);
        const isFeatured = document.getElementById('is_featured').checked ? 1 : 0;
        formData.append('is_featured', isFeatured);

        // Công nghệ được chọn
        document.querySelectorAll('.tech-checkbox:checked').forEach(cb => {
            formData.append('tech_ids[]', cb.value);
        });

        // Ảnh cũ bị đánh dấu xóa
        document.querySelectorAll('.delete-image-checkbox:checked').forEach(cb => {
            formData.append('delete_images[]', cb.value);
        });

        // Ảnh được chọn làm đại diện (existing_<id> hoặc new_<index>)
        const featuredChecked = document.querySelector('input[name="featured_image_ui"]:checked');
        if (featuredChecked) {
            formData.append('featured_image', featuredChecked.value);
        }

        // Ảnh mới thêm vào
        const files = fileInput.files;
        for (let i = 0; i < files.length; i++) {
            formData.append('imageFiles[]', files[i]);
        }

        // Ảnh banner đại diện
        const bannerFile = document.getElementById('bannerFile').files[0];
        if (bannerFile) {
            formData.append('bannerFile', bannerFile);
        }
        const removeBannerCheckbox = document.getElementById('remove_banner');
        if (removeBannerCheckbox && removeBannerCheckbox.checked) {
            formData.append('remove_banner', '1');
        }
        
        const response = await fetch(`project_edit.php?id=${PROJECT_ID}&api=save`, {
            method: 'POST',
            body: formData
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