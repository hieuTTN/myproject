<?php
require_once('../database/connect.php');

/**
 * Lấy toàn bộ danh mục đồ án
 */
function getAllCategories() {
    return executeresult("SELECT * FROM categories ORDER BY id ASC");
}

/**
 * Lấy toàn bộ danh sách các từ khóa công nghệ (Hỗ trợ thêm/chọn nhãn)
 */
function getAllTechnologies() {
    return executeresult("SELECT * FROM technologies ORDER BY name ASC");
}

/**
 * Thêm nhanh một công nghệ mới từ ngoài giao diện admin nếu chưa tồn tại
 */
function addTechnologyOnly($name) {
    $name = addslashes(trim($name));
    if (empty($name)) return 0;
    
    // Kiểm tra trùng lặp
    $check = querySingleResult("SELECT id FROM technologies WHERE name = '$name' LIMIT 1");
    if ($check) {
        return $check['id'];
    }
    
    return insert("INSERT INTO technologies (name) VALUES ('$name')");
}
?>