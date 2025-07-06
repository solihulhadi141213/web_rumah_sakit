<?php
    header('Content-Type: application/json');
    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';

    function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus PUT'], 405);
    }

    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (empty($token)) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan'], 401);
    }

    $Conn = (new Database())->getConnection();
    $validasi = validasi_x_token($Conn, $token);
    if ($validasi !== 'Valid') {
        sendResponse(['status' => 'error', 'message' => $validasi], 401);
    }

    $raw = file_get_contents("php://input");
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    $stmt = $Conn->prepare("SELECT * FROM setting WHERE setting_parameter = 'layout_static' LIMIT 1");
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) {
        sendResponse(['status' => 'error', 'message' => 'Data layout_static tidak ditemukan'], 404);
    }

    $setting_value = json_decode($data['setting_value'], true);
    if (!is_array($setting_value)) {
        sendResponse(['status' => 'error', 'message' => 'Format setting_value tidak valid'], 500);
    }

    $targetDir = '../assets/img/';
    $maxSize = 2 * 1024 * 1024;
    $allowedMime = ['image/png', 'image/jpeg', 'image/jpg'];

    function base64ToImage($base64, $width, $height, $filename) {
        global $targetDir, $allowedMime, $maxSize;

        if (!preg_match('/^data:(image\/[a-zA-Z]+);base64,/', $base64, $matches)) {
            return ['status' => false, 'message' => 'Format base64 tidak valid'];
        }

        $mime = $matches[1];
        if (!in_array($mime, $allowedMime)) {
            return ['status' => false, 'message' => 'Hanya gambar PNG/JPEG yang diizinkan'];
        }

        $imageData = base64_decode(preg_replace('/^data:image\/[a-zA-Z]+;base64,/', '', $base64));
        if (strlen($imageData) > $maxSize) {
            return ['status' => false, 'message' => 'Ukuran gambar melebihi 2MB'];
        }

        $srcImg = imagecreatefromstring($imageData);
        if (!$srcImg) {
            return ['status' => false, 'message' => 'Gagal membuat gambar dari base64'];
        }

        $resized = imagecreatetruecolor($width, $height);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $srcImg, 0, 0, 0, 0, $width, $height, imagesx($srcImg), imagesy($srcImg));

        $filePath = $targetDir . $filename;
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if ($ext === 'png') {
            imagepng($resized, $filePath);
        } else {
            imagejpeg($resized, $filePath, 90);
        }

        imagedestroy($srcImg);
        imagedestroy($resized);

        return ['status' => true];
    }

    $mapping = [
        '16x16' => ['filename' => 'favicon-16x16.png', 'size' => [16, 16]],
        '32x32' => ['filename' => 'favicon-32x32.png', 'size' => [32, 32]],
        '180x180' => ['filename' => 'apple-touch-icon.png', 'size' => [180, 180]]
    ];

    foreach ($mapping as $key => $info) {
        if (!empty($input[$key])) {
            $filename = $info['filename'];

            // Hapus file lama jika berbeda
            if (!empty($setting_value['favicon'][$key]) && $setting_value['favicon'][$key] !== $filename) {
                $old = $targetDir . basename($setting_value['favicon'][$key]);
                if (file_exists($old)) {
                    @unlink($old);
                }
            }

            // Generate dan simpan gambar
            $result = base64ToImage($input[$key], $info['size'][0], $info['size'][1], $filename);
            if (!$result['status']) {
                sendResponse(['status' => 'error', 'message' => $result['message']], 400);
            }

            $setting_value['favicon'][$key] = $filename;
        }
    }

    // Untuk manifest: hanya update nama file
    if (!empty($input['manifest'])) {
        $setting_value['favicon']['manifest'] = basename($input['manifest']);
    }

    $jsonNew = json_encode($setting_value, JSON_UNESCAPED_UNICODE);
    $stmtUpdate = $Conn->prepare("UPDATE setting SET setting_value = :setting_value WHERE id_setting = :id_setting");
    $stmtUpdate->bindValue(':setting_value', $jsonNew);
    $stmtUpdate->bindValue(':id_setting', $data['id_setting']);
    $stmtUpdate->execute();

    sendResponse([
        'status' => 'success',
        'message' => 'Favicon berhasil diperbarui',
        'favicon' => $setting_value['favicon']
    ]);
?>