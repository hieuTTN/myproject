<?php
require_once(__DIR__ . '/../database/connect.php');
define('TELEGRAM_BOT_TOKEN', '8983837557:AAGuBgT6T07UJFTRFd-lozxUsuxKxY1pDfY');
define('TELEGRAM_CHAT_ID', '1467011082');
/**
 * Thêm 1 liên hệ mới từ form ở trang chủ (index.php)
 * Trả về id của liên hệ vừa thêm, hoặc 0 nếu dữ liệu không hợp lệ
 */
function addContact($fullname, $phone, $technology, $message) {
    $fullname   = addslashes(trim($fullname));
    $phone      = addslashes(trim($phone));
    $technology = addslashes(trim($technology));
    $message    = addslashes(trim($message));

    if (empty($fullname) || empty($phone)) {
        return 0;
    }

    $id = insert(
        "INSERT INTO `contacts` (`fullname`, `phone`, `technology`, `message`, `is_read`)
         VALUES ('$fullname', '$phone', '$technology', '$message', 0)"
    );

    // Bắn thông báo Telegram ngay khi có liên hệ mới (không chặn luồng nếu gửi lỗi)
    if ($id > 0) {
        $telegramMessage = "📩 <b>Có liên hệ mới từ website!</b>\n"
            . "👤 Họ tên: " . htmlspecialchars(stripslashes($fullname), ENT_QUOTES) . "\n"
            . "📱 Zalo/SĐT: " . htmlspecialchars(stripslashes($phone), ENT_QUOTES) . "\n"
            . "💻 Công nghệ: " . htmlspecialchars(stripslashes($technology) ?: 'Chưa rõ', ENT_QUOTES) . "\n"
            . "📝 Nội dung: " . htmlspecialchars(stripslashes($message) ?: 'Không có mô tả', ENT_QUOTES) . "\n"
            . "🕒 Thời gian: " . date('H:i d/m/Y');

        sendTelegramNotification($telegramMessage);
    }

    return $id;
}

/**
 * Gửi tin nhắn thông báo qua Telegram Bot (dùng khi có liên hệ mới)
 * Không throw lỗi ra ngoài nếu gửi thất bại, để không ảnh hưởng luồng lưu contact chính
 */
function sendTelegramNotification($message) {
    if (empty(TELEGRAM_BOT_TOKEN) || empty(TELEGRAM_CHAT_ID)) {
        return false;
    }
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'chat_id'    => TELEGRAM_CHAT_ID,
        'text'       => $message,
        'parse_mode' => 'HTML',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Tránh lỗi SSL cục bộ (XAMPP/Laragon)

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    return empty($error) && $response !== false;
}

/**
 * Lấy danh sách liên hệ, có thể lọc theo trạng thái đọc và phân trang
 * $status: 'all' | 'unread' | 'read'
 * $limit / $offset: truyền null để lấy toàn bộ (không phân trang)
 */
function getContacts($status = 'all', $limit = null, $offset = 0) {
    $where = '';
    if ($status === 'unread') {
        $where = "WHERE `is_read` = 0";
    } elseif ($status === 'read') {
        $where = "WHERE `is_read` = 1";
    }

    $limitSql = '';
    if ($limit !== null) {
        $limit  = (int)$limit;
        $offset = (int)$offset;
        $limitSql = "LIMIT $limit OFFSET $offset";
    }

    return executeresult(
        "SELECT * FROM `contacts` $where ORDER BY `created_at` DESC $limitSql"
    );
}

/**
 * Đếm tổng số liên hệ theo trạng thái lọc (dùng để tính số trang)
 * $status: 'all' | 'unread' | 'read'
 */
function countContacts($status = 'all') {
    $where = '';
    if ($status === 'unread') {
        $where = "WHERE `is_read` = 0";
    } elseif ($status === 'read') {
        $where = "WHERE `is_read` = 1";
    }

    $row = querySingleResult("SELECT COUNT(*) as total FROM `contacts` $where");
    return (int)($row['total'] ?? 0);
}

/**
 * Lấy chi tiết 1 liên hệ theo id
 */
function getContactById($id) {
    $id = (int)$id;
    return querySingleResult("SELECT * FROM `contacts` WHERE `id` = $id LIMIT 1");
}

/**
 * Đếm số liên hệ chưa đọc (dùng để hiển thị badge ở sidebar admin)
 */
function countUnreadContacts() {
    $row = querySingleResult("SELECT COUNT(*) as total FROM `contacts` WHERE `is_read` = 0");
    return (int)($row['total'] ?? 0);
}

/**
 * Đánh dấu 1 liên hệ là đã đọc
 */
function markContactRead($id) {
    $id = (int)$id;
    execute("UPDATE `contacts` SET `is_read` = 1 WHERE `id` = $id");
}

/**
 * Đánh dấu 1 liên hệ là chưa đọc
 */
function markContactUnread($id) {
    $id = (int)$id;
    execute("UPDATE `contacts` SET `is_read` = 0 WHERE `id` = $id");
}

/**
 * Xóa 1 liên hệ khỏi hệ thống
 */
function deleteContact($id) {
    $id = (int)$id;
    execute("DELETE FROM `contacts` WHERE `id` = $id");
}
?>