<?php
require_once('../database/connect.php'); // Giả định file chứa các hàm kết nối của bạn tên là dbhelper.php

/**
 * Kiểm tra đăng nhập Admin
 * @param string $username
 * @param string $password Mật khẩu chữ thuần từ form nhập vào
 * @return array|null Trả về mảng thông tin admin nếu đúng, ngược lại trả về null
 */
function loginAdmin($username, $password) {
    $username = addslashes($username); // Tránh SQL Injection cơ bản
    $sql = "SELECT * FROM `admins` WHERE `username` = '$username' LIMIT 1";
    $admin = querySingleResult($sql);
    
    if ($admin) {
        // Kiểm tra mật khẩu khớp với chuỗi đã mã hóa trong DB hay không
        if (password_verify($password, $admin['password'])) {
            return $admin;
        }
    }
    return null;
}

/**
 * Tạo tài khoản admin mới (Dùng để chạy khởi tạo ban đầu hoặc khi thêm admin)
 */
function createAdmin($username, $password, $fullname) {
    $username = addslashes($username);
    $fullname = addslashes($fullname);
    // Mã hóa mật khẩu trước khi lưu vào DB
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    $sql = "INSERT INTO `admins` (`username`, `password`, `fullname`) 
            VALUES ('$username', '$hashedPassword', '$fullname')";
    execute($sql);
}
?>