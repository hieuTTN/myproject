-- 1. BẢNG QUẢN TRỊ VIÊN (ADMIN)
CREATE TABLE `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL, -- Lưu mật khẩu đã mã hóa bằng password_hash() của PHP
  `fullname` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. BẢNG DANH MỤC CHÍNH (CATEGORY)
-- Giúp bạn phân loại như: Java/Spring, PHP/Laravel, C#/.NET để làm bộ lọc (Filter)
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(50) NOT NULL UNIQUE, -- Ví dụ: 'spring', 'servlet', 'php'
  `name` VARCHAR(100) NOT NULL         -- Ví dụ: 'Java / Spring', 'PHP / Laravel'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. BẢNG DỰ ÁN DEMO (PROJECTS)
CREATE TABLE `projects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,       -- Tên đồ án tiếng Việt
  `description` TEXT DEFAULT NULL,      -- Mô tả chi tiết tính năng đồ án
  `youtube_id` VARCHAR(500) DEFAULT NULL, -- Chỉ lưu ID video (Ví dụ: dQw4w9WgXcQ)
  `drive_link` VARCHAR(550) DEFAULT NULL,-- Link lưu source code/tài liệu trên Drive
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. BẢNG CÔNG NGHỆ CHI TIẾT (TECHNOLOGIES)
-- Lưu danh sách các từ khóa công nghệ riêng lẻ để tái sử dụng và phục vụ tính năng TÌM KIẾM
CREATE TABLE `technologies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE -- Ví dụ: 'Spring Boot', 'MySQL', 'ReactJS', 'JWT'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. BẢNG TRUNG GIAN: DỰ ÁN CÓ NHỮNG CÔNG NGHỆ NÀO (PROJECT_TECHNOLOGY)
-- Giải quyết quan hệ Nhiều - Nhiều (1 dự án có nhiều tech, 1 tech xuất hiện ở nhiều dự án)
CREATE TABLE `project_technology` (
  `project_id` INT NOT NULL,
  `technology_id` INT NOT NULL,
  PRIMARY KEY (`project_id`, `technology_id`),
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`technology_id`) REFERENCES `technologies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. BẢNG LƯU TRỮ ALBUM ẢNH DEMO (PROJECT_IMAGES)
-- Giải quyết quan hệ 1 - Nhiều (1 dự án có thể up nhiều ảnh chụp màn hình giao diện)
CREATE TABLE `project_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT NOT NULL,
  `image_path` VARCHAR(255) NOT NULL, -- Đường dẫn file ảnh trên hosting (Ví dụ: uploads/p1_screen1.png)
  `is_featured` TINYINT(1) DEFAULT 0,  -- 1 là ảnh đại diện chính, 0 là ảnh album phụ
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- 7. BẢNG QUẢN LÝ CÁC TÀI KHOẢN CLOUDINARY (CLOUDINARY_ACCOUNTS)
-- Cho phép cấu hình động nhiều tài khoản Cloudinary khác nhau, chọn 1 tài khoản làm "chính"
-- để toàn bộ hệ thống (upload ảnh) sử dụng, không cần sửa code khi đổi tài khoản.
CREATE TABLE `cloudinary_accounts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `gmail` VARCHAR(255) NOT NULL,           -- Email Gmail dùng đăng ký tài khoản Cloudinary (để nhận diện)
  `cloud_name` VARCHAR(100) NOT NULL,
  `api_key` VARCHAR(100) NOT NULL,
  `api_secret` VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) DEFAULT 0,       -- 1 = tài khoản đang được dùng chính để upload
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_cloud_name` (`cloud_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contacts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `fullname` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `technology` VARCHAR(100) DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `projects`
  ADD FULLTEXT INDEX `ft_title_description` (`title`, `description`);
 
-- Kiểm tra lại xem index đã được tạo thành công chưa
SHOW INDEX FROM `projects` WHERE Key_name = 'ft_title_description';

ALTER TABLE `projects` ADD COLUMN `banner` VARCHAR(500) DEFAULT NULL AFTER `drive_link`;