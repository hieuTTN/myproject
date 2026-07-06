<?php
/**
 * cloudinary_concurrent_upload.php
 *
 * Upload nhiều ảnh lên Cloudinary CÙNG LÚC bằng curl_multi thay vì tuần tự từng ảnh một.
 * PHP không có multi-thread thật (không có pthreads/parallel), nhưng việc upload ảnh là
 * tác vụ I/O-bound (chủ yếu là chờ mạng), nên dùng curl_multi để bắn nhiều request cùng lúc
 * cho tốc độ gần tương đương chạy song song, mà không cần thread hay extension đặc biệt nào.
 *
 * Vì không dùng SDK (SDK upload tuần tự, không hỗ trợ multi sẵn), hàm này tự ký (sign)
 * request theo đúng chuẩn Cloudinary (signed upload) rồi gọi thẳng REST API.
 */

/**
 * Ký các tham số theo chuẩn chữ ký Cloudinary (SHA1)
 * Xem: https://cloudinary.com/documentation/upload_images#generating_authentication_signatures
 */
function cloudinarySignParams(array $params, string $apiSecret): string {
    ksort($params);
    $pairs = [];
    foreach ($params as $key => $value) {
        $pairs[] = "$key=$value";
    }
    $stringToSign = implode('&', $pairs) . $apiSecret;
    return sha1($stringToSign);
}

/**
 * Upload nhiều file lên Cloudinary song song bằng curl_multi.
 *
 * @param array  $filesArray  Mảng theo cấu trúc $_FILES['field'] chuẩn (name/type/tmp_name/error/size là mảng con)
 * @param string $cloudName
 * @param string $apiKey
 * @param string $apiSecret
 * @param string $folder     Thư mục lưu trên Cloudinary
 * @param array  $tags       Danh sách tag gắn cho ảnh (tuỳ chọn)
 *
 * @return array Mảng kết quả, GIỮ NGUYÊN chỉ số (index) gốc của $filesArray để nơi gọi
 *               có thể đối chiếu lại đúng file nào ứng với ảnh nào (vd: chọn ảnh đại diện).
 *               Mỗi phần tử: ['success' => bool, 'secure_url' => string|null, 'error' => string|null, 'file_name' => string]
 */
function cloudinaryUploadFilesConcurrently(
    array $filesArray,
    string $cloudName,
    string $apiKey,
    string $apiSecret,
    string $folder,
    array $tags = []
): array {
    $results = [];
    $fileCount = count($filesArray['name'] ?? []);
    if ($fileCount === 0) {
        return $results;
    }

    $endpoint = "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload";
    $multiHandle = curl_multi_init();
    $curlHandles = []; // index gốc => ['handle' => ch, 'file_name' => ...]

    // BƯỚC 1: Khởi tạo tất cả các cURL handle (chưa chạy vội)
    for ($i = 0; $i < $fileCount; $i++) {
        // Bỏ qua ngay các file lỗi từ khi upload lên server (không cần gửi lên Cloudinary)
        if ($filesArray['error'][$i] !== UPLOAD_ERR_OK) {
            $results[$i] = [
                'success'    => false,
                'secure_url' => null,
                'error'      => "Lỗi upload (mã " . $filesArray['error'][$i] . ")",
                'file_name'  => $filesArray['name'][$i],
            ];
            continue;
        }

        $tmpName  = $filesArray['tmp_name'][$i];
        $fileName = $filesArray['name'][$i];
        $mimeType = $filesArray['type'][$i] ?: 'application/octet-stream';

        $timestamp = time();
        $publicId  = pathinfo($fileName, PATHINFO_FILENAME) . '_' . $timestamp . '_' . $i;

        $paramsToSign = [
            'folder'        => $folder,
            'public_id'     => $publicId,
            'timestamp'     => $timestamp,
            'transformation'=> 'q_auto:best,f_auto', // tương đương quality:auto:best + fetch_format:auto của SDK
        ];
        if (!empty($tags)) {
            $paramsToSign['tags'] = implode(',', $tags);
        }

        $signature = cloudinarySignParams($paramsToSign, $apiSecret);

        $postFields = $paramsToSign;
        $postFields['api_key']    = $apiKey;
        $postFields['signature']  = $signature;
        $postFields['file']       = new CURLFile($tmpName, $mimeType, $fileName);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Tránh lỗi SSL cục bộ (XAMPP/Laragon)
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        curl_multi_add_handle($multiHandle, $ch);
        $curlHandles[$i] = ['handle' => $ch, 'file_name' => $fileName];
    }

    // BƯỚC 2: Chạy tất cả các request song song
    if (!empty($curlHandles)) {
        $running = null;
        do {
            $status = curl_multi_exec($multiHandle, $running);
            if ($running > 0) {
                curl_multi_select($multiHandle); // Chờ có hoạt động mạng mới, tránh CPU busy-loop
            }
        } while ($running > 0 && $status === CURLM_OK);

        // BƯỚC 3: Thu thập kết quả từng request
        foreach ($curlHandles as $i => $info) {
            $ch = $info['handle'];
            $response = curl_multi_getcontent($ch);
            $err = curl_error($ch);

            if ($err) {
                $results[$i] = [
                    'success'    => false,
                    'secure_url' => null,
                    'error'      => "Lỗi kết nối: $err",
                    'file_name'  => $info['file_name'],
                ];
            } else {
                $decoded = json_decode($response, true);
                if (isset($decoded['secure_url'])) {
                    $results[$i] = [
                        'success'    => true,
                        'secure_url' => $decoded['secure_url'],
                        'error'      => null,
                        'file_name'  => $info['file_name'],
                    ];
                } else {
                    $errMsg = $decoded['error']['message'] ?? 'Không nhận được secure_url từ Cloudinary';
                    $results[$i] = [
                        'success'    => false,
                        'secure_url' => null,
                        'error'      => $errMsg,
                        'file_name'  => $info['file_name'],
                    ];
                }
            }

            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }
    }

    curl_multi_close($multiHandle);

    // Giữ đúng thứ tự chỉ số gốc (0, 1, 2...) để nơi gọi map lại chính xác
    ksort($results);
    return $results;
}
?>