<?php
require_once('config.php');

// insert, update, delete,
function execute($sql){
    $conn = mysqli_connect(HOST, USERNAME, PASSWORD, DATABASE) or die("connect fail");
    mysqli_set_charset($conn, "utf8mb4"); // SỬA LỖI FONT TẠI ĐÂY
    mysqli_query($conn, $sql);
}

function insert($sql){
    $conn = mysqli_connect(HOST, USERNAME, PASSWORD, DATABASE) or die("connect fail");
    mysqli_set_charset($conn, "utf8mb4"); // SỬA LỖI FONT TẠI ĐÂY
    mysqli_query($conn , $sql);
    $id = mysqli_insert_id($conn);
    return $id;
}

// thực hiện câu select 
function executeresult($sql) {
    // 1. Kết nối như bình thường
    $conn = mysqli_connect(HOST, USERNAME, PASSWORD, DATABASE) or die("connect fail");
    mysqli_set_charset($conn, "utf8mb4"); // SỬA LỖI FONT TẠI ĐÂY
    
    $List = [];

    // 2. SỬ DỤNG mysqli_multi_query()
    // Hàm này cho phép thực thi nhiều câu lệnh SQL được phân tách bằng dấu chấm phẩy (;)
    if (mysqli_multi_query($conn, $sql)) {
        
        // 3. Lặp qua các bộ kết quả (result sets)
        do {
            /* Lấy bộ kết quả đầu tiên */
            if ($resultset = mysqli_store_result($conn)) {
                // Chỉ xử lý kết quả cho các câu lệnh SELECT
                while ($row = mysqli_fetch_array($resultset, 1)) {
                    $List[] = $row;
                }
                mysqli_free_result($resultset);
            }
            
            /* Di chuyển đến bộ kết quả tiếp theo */
        } while (mysqli_more_results($conn) && mysqli_next_result($conn));
    } else {
        // Xử lý lỗi
        echo "Lỗi truy vấn đa câu lệnh: " . mysqli_error($conn);
    }
    
    // Đóng kết nối
    mysqli_close($conn); 
    
    return $List;
}

function querySingleResult($sql){
    $conn = mysqli_connect(HOST, USERNAME, PASSWORD, DATABASE) or die("connect fail");
    mysqli_set_charset($conn, "utf8mb4"); // SỬA LỖI FONT TẠI ĐÂY
    $result = mysqli_query($conn, $sql);
    $data = mysqli_fetch_assoc($result);
    return $data;
}
?>