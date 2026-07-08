<?php
require_once('../database/connect.php');
header('Content-Type: application/json; charset=UTF-8');

// ============================================================
// 1. Đọc & làm sạch tham số đầu vào
// ============================================================
$q          = trim($_GET['q'] ?? '');
$categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$page       = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage    = 9; // số project mỗi trang, chỉnh tùy ý
$offset     = ($page - 1) * $perPage;

// ============================================================
// 2. Xây điều kiện WHERE dùng chung cho cả câu COUNT và câu SELECT chính
// ============================================================
$where = "1=1";

if ($categoryId > 0) {
    $where .= " AND p.category_id = $categoryId";
}

if ($q !== '') {
    // Chống SQL Injection cơ bản (đồng bộ cách làm addslashes như các file admin khác trong dự án)
    $safe_q = addslashes($q);

    // Kết hợp Fulltext (ưu tiên độ liên quan) + LIKE (bắt các từ khóa ngắn/stopword
    // mà MySQL Fulltext có thể bỏ qua do dưới ngưỡng ft_min_word_len)
    $where .= " AND (
         p.title LIKE '%$safe_q%'
    )";
}

// ============================================================
// 3. Đếm tổng số bản ghi thỏa điều kiện
//    Lưu ý: đếm trên bảng projects thuần, KHÔNG join technologies
//    để tránh JOIN làm nhân bản dòng khiến COUNT bị sai.
// ============================================================
$sql_count    = "SELECT COUNT(*) as total FROM `projects` p WHERE $where";
$count_result = executeresult($sql_count);
$total        = isset($count_result[0]['total']) ? (int)$count_result[0]['total'] : 0;
$totalPages   = $total > 0 ? (int)ceil($total / $perPage) : 0;

// ============================================================
// 4. Truy vấn dữ liệu chính (kèm JOIN category/technology/ảnh đại diện)
// ============================================================
$items = [];

if ($total > 0) {
    $sql = "SELECT p.id, p.title, p.banner, p.description, p.youtube_id, p.drive_link,
                   c.name AS category_name,
                   GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') AS tech_list
            FROM `projects` p
            LEFT JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `project_technology` pt ON p.id = pt.project_id
            LEFT JOIN `technologies` t ON pt.technology_id = t.id
            WHERE $where
            GROUP BY p.id
            ORDER BY p.id DESC
            LIMIT $offset, $perPage";

    $rows = executeresult($sql);

    foreach ($rows as $row) {
        $items[] = [
            'id'            => (int)$row['id'],
            'title'         => $row['title'],
            'category_name' => $row['category_name'] ?: 'Chưa phân loại',
            'tech_list'     => $row['tech_list'] ?: '',
            'description'   => $row['description'] ?: '',
            'drive_link'    => $row['drive_link'] ?: '',
            'youtube_id'    => $row['youtube_id'] ?: '', // chứa nguyên đoạn <iframe> đã lưu trong DB
            'banner'        => $row['banner'] ?: '',
        ];
    }
}

// ============================================================
// 5. Trả kết quả JSON
// ============================================================
echo json_encode([
    'status'     => 'success',
    'items'      => $items,
    'pagination' => [
        'page'       => $page,
        'perPage'    => $perPage,
        'total'      => $total,
        'totalPages' => $totalPages,
    ],
], JSON_UNESCAPED_UNICODE);
exit;