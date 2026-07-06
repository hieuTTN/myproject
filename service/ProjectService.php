<?php
require_once('../database/connect.php');

/**
 * LẤY DANH SÁCH ĐỒ ÁN HIỂN THỊ RA TRANG CHỦ (Kèm ảnh chính và mảng công nghệ)
 * @param string $categorySlug Bộ lọc theo danh mục ('all', 'spring', 'php'...)
 * @param string $search Từ khóa tìm kiếm (Tên đồ án hoặc tên công nghệ)
 * @return array
 */
function getProjectsForLandingPage($categorySlug = 'all', $search = '') {
    $search = addslashes(trim($search));
    
    // 1. Lấy toàn bộ danh sách dự án kèm ảnh chính (is_featured = 1) và tên danh mục
    $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, img.image_path as thumbnail 
            FROM projects p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN project_images img ON p.id = img.project_id AND img.is_featured = 1
            WHERE 1=1";
            
    if ($categorySlug !== 'all') {
        $sql .= " AND c.slug = '" . addslashes($categorySlug) . "'";
    }
    
    if (!empty($search)) {
        // Tìm kiếm theo tên đồ án HOẶC tìm kiếm theo công nghệ sử dụng trong đồ án đó
        $sql .= " AND (p.title LIKE '%$search%' OR p.id IN (
                    SELECT pt.project_id FROM project_technology pt 
                    JOIN technologies t ON pt.technology_id = t.id 
                    WHERE t.name LIKE '%$search%'
                 ))";
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    $projects = executeresult($sql);
    
    // 2. Với mỗi dự án, lặp qua để lấy danh sách các công nghệ (Tech Pills) thuộc về nó
    for ($i = 0; $i < count($projects); $i++) {
        $p_id = $projects[$i]['id'];
        $sqlTech = "SELECT t.name FROM technologies t 
                    JOIN project_technology pt ON t.id = pt.technology_id 
                    WHERE pt.project_id = $p_id";
        $techRows = executeresult($sqlTech);
        
        // Chuyển mảng kết quả thành mảng phẳng các chuỗi công nghệ giống hệt cấu trúc JSON cũ của bạn
        $projects[$i]['techs'] = array_column($techRows, 'name');
    }
    
    return $projects;
}

/**
 * LẤY CHI TIẾT 1 ĐỒ ÁN (Dùng cho trang xem chi tiết hoặc trang Sửa dữ liệu trong Admin)
 */
function getProjectById($id) {
    $id = (int)$id;
    $sql = "SELECT * FROM projects WHERE id = $id";
    $project = querySingleResult($sql);
    
    if ($project) {
        // Lấy danh sách công nghệ (dạng ID để đẩy vào checkbox/select2 trong admin)
        $sqlTech = "SELECT technology_id FROM project_technology WHERE project_id = $id";
        $project['tech_ids'] = array_column(executeresult($sqlTech), 'technology_id');
        
        // Lấy toàn bộ danh sách album ảnh của đồ án này
        $sqlImages = "SELECT * FROM project_images WHERE project_id = $id ORDER BY is_featured DESC";
        $project['images'] = executeresult($sqlImages);
    }
    
    return $project;
}

/**
 * THÊM MỚI ĐỒ ÁN (Xử lý đồng thời cả bảng chính, bảng ảnh và bảng trung gian công nghệ)
 * @param array $data Dữ liệu đồ án cơ bản
 * @param array $techIds Mảng ID các công nghệ được chọn (Ví dụ: [1, 3, 5])
 * @param array $images Mảng các đường dẫn ảnh (Ví dụ: ['uploads/a.png', 'uploads/b.png'])
 */
function addProject($data, $techIds = [], $images = []) {
    $title = addslashes($data['title']);
    $categoryId = !empty($data['category_id']) ? (int)$data['category_id'] : 'NULL';
    $description = addslashes($data['description']);
    $youtubeId = addslashes($data['youtube_id']);
    $driveLink = addslashes($data['drive_link']);
    
    // 1. Thêm vào bảng projects
    $sqlProject = "INSERT INTO projects (category_id, title, description, youtube_id, drive_link) 
                   VALUES ($categoryId, '$title', '$description', '$youtubeId', '$driveLink')";
    $projectId = insert($sqlProject); // Hàm insert() của bạn sẽ trả về ID vừa sinh tự động
    
    // 2. Thêm các liên kết công nghệ vào bảng trung gian
    if (!empty($techIds) && $projectId > 0) {
        foreach ($techIds as $techId) {
            $techId = (int)$techId;
            execute("INSERT INTO project_technology (project_id, technology_id) VALUES ($projectId, $techId)");
        }
    }
    
    // 3. Thêm danh sách ảnh vào bảng project_images
    if (!empty($images) && $projectId > 0) {
        foreach ($images as $index => $path) {
            $path = addslashes($path);
            $isFeatured = ($index === 0) ? 1 : 0; // Tấm ảnh đầu tiên mặc định làm ảnh đại diện chính
            execute("INSERT INTO project_images (project_id, image_path, is_featured) VALUES ($projectId, '$path', $isFeatured)");
        }
    }
    
    return $projectId;
}

/**
 * CẬP NHẬT ĐỒ ÁN
 */
function updateProject($id, $data, $techIds = [], $newImages = []) {
    $id = (int)$id;
    $title = addslashes($data['title']);
    $categoryId = !empty($data['category_id']) ? (int)$data['category_id'] : 'NULL';
    $description = addslashes($data['description']);
    $youtubeId = addslashes($data['youtube_id']);
    $driveLink = addslashes($data['drive_link']);
    
    // 1. Cập nhật thông tin cơ bản
    $sql = "UPDATE projects SET 
                category_id = $categoryId, 
                title = '$title', 
                description = '$description', 
                youtube_id = '$youtubeId', 
                drive_link = '$driveLink' 
            WHERE id = $id";
    execute($sql);
    
    // 2. Đồng bộ lại bảng công nghệ (Xóa cũ, chèn mới)
    execute("DELETE FROM project_technology WHERE project_id = $id");
    if (!empty($techIds)) {
        foreach ($techIds as $techId) {
            $techId = (int)$techId;
            execute("INSERT INTO project_technology (project_id, technology_id) VALUES ($id, $techId)");
        }
    }
    
    // 3. Nếu có up thêm ảnh mới thì chèn vào hệ thống ảnh cũ
    if (!empty($newImages)) {
        foreach ($newImages as $path) {
            $path = addslashes($path);
            execute("INSERT INTO project_images (project_id, image_path, is_featured) VALUES ($id, '$path', 0)");
        }
    }
}

/**
 * XÓA ĐỒ ÁN (Nhờ cấu hình ON DELETE CASCADE nên các bảng liên kết sẽ tự động sạch theo)
 */
function deleteProject($id) {
    $id = (int)$id;
    
    // Lưu ý: Bạn nên viết thêm đoạn lấy danh sách file ảnh vật lý trên hosting để dùng hàm unlink() xóa file ảnh đi cho trống hosting, trước khi chạy câu lệnh SQL xóa dưới đây:
    $sqlGetImages = "SELECT image_path FROM project_images WHERE project_id = $id";
    $imgs = executeresult($sqlGetImages);
    foreach ($imgs as $img) {
        if (file_exists($img['image_path'])) {
            @unlink($img['image_path']); // Xóa file ảnh vật lý
        }
    }
    
    $sql = "DELETE FROM projects WHERE id = $id";
    execute($sql);
}
?>