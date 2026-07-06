<?php
// config.php
require_once '../vendor/autoload.php';

use Cloudinary\Cloudinary;

// Cấu hình gốc dùng chung, tránh lặp lại thông tin ở nhiều nơi

$cloudlist = executeresult("SELECT cloud_name, api_key, api_secret  FROM cloudinary_accounts WHERE is_primary = 1 LIMIT 1");

$cloud = $cloudlist[0];

$cloudinaryCloudName = $cloud['cloud_name'];
$cloudinaryApiKey    = $cloud['api_key'];
$cloudinaryApiSecret = $cloud['api_secret'];

// $cloudinary = new Cloudinary([
//     'cloud' => [
//         'cloud_name' => $cloudinaryCloudName,
//         'api_key'    => $cloudinaryApiKey,
//         'api_secret' => $cloudinaryApiSecret,
//     ],
//     'url' => [
//         'secure' => true
//     ]
// ]);

$cloudinary = new Cloudinary([
    'cloud' => [
        'cloud_name' => $cloudinaryCloudName,
        'api_key'    => $cloudinaryApiKey,
        'api_secret' => $cloudinaryApiSecret,
    ],
    'url' => [
        'secure' => true
    ]
]);

// Cấu hình upload folder (tùy chọn)
$uploadFolder = 'uploads/';

// Nạp helper upload song song (dùng curl_multi) để tăng tốc khi upload nhiều ảnh cùng lúc
require_once __DIR__ . '/cloudinary_concurrent_upload.php';
?>