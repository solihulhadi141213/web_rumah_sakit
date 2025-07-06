<?php
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    header('Content-Type: application/json');

    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';
    require_once '../_Config/log_visitor.php';

    function sendResponse($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validasi metode PUT
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus PUT'], 405);
    }

    // Validasi x-token
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (empty($token)) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan'], 401);
    }
    $Conn = (new Database())->getConnection();
    if (validasi_x_token($Conn, $token) !== 'Valid') {
        sendResponse(['status' => 'error', 'message' => 'Token tidak valid'], 401);
    }

    // Ambil input JSON
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Fungsi simpan gambar base64
    function saveBase64Image($base64, $dir = '../assets/img') {
        if (!preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
            return ['error' => 'Format gambar tidak valid'];
        }
        $data = substr($base64, strpos($base64, ',') + 1);
        $data = base64_decode($data);
        if ($data === false) {
            return ['error' => 'Base64 decode gagal'];
        }
        if (strlen($data) > (2 * 1024 * 1024)) { // 2MB
            return ['error' => 'Ukuran file melebihi 2MB'];
        }
        $ext = strtolower($type[1]);
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed)) {
            return ['error' => 'Ekstensi tidak didukung'];
        }
        $filename = bin2hex(random_bytes(18)) . '.' . $ext;
        $path = rtrim($dir, '/') . '/' . $filename;
        if (!file_put_contents($path, $data)) {
            return ['error' => 'Gagal menyimpan gambar'];
        }
        return ['success' => true, 'filename' => $filename];
    }

    // Ambil data setting existing
    $stmt = $Conn->prepare("SELECT setting_value FROM setting WHERE setting_parameter = 'layout_static' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        sendResponse(['status' => 'error', 'message' => 'Data pengaturan tidak ditemukan'], 404);
    }

    $settingValue = json_decode($row['setting_value'], true);
    if (!isset($settingValue['meta_tag'])) {
        $settingValue['meta_tag'] = [];
    }

    // Update atribut
    $meta = &$settingValue['meta_tag'];
    $meta['type'] = $input['type'] ?? $meta['type'] ?? '';
    $meta['title'] = $input['title'] ?? $meta['title'] ?? '';
    $meta['author'] = $input['author'] ?? $meta['author'] ?? '';
    $meta['robots'] = $input['robots'] ?? $meta['robots'] ?? '';
    $meta['base_url'] = $input['base_url'] ?? $meta['base_url'] ?? '';
    $meta['keywords'] = $input['keywords'] ?? $meta['keywords'] ?? '';
    $meta['viewport'] = $input['viewport'] ?? $meta['viewport'] ?? '';
    $meta['description'] = $input['description'] ?? $meta['description'] ?? '';

    // Proses og-image jika ada
    if (!empty($input['og-image']) && str_starts_with($input['og-image'], 'data:image/')) {
        // Hapus og-image lama jika ada
        if (!empty($meta['og-image'])) {
            $oldOgPath = '../assets/img/' . basename($meta['og-image']);
            if (file_exists($oldOgPath)) {
                @unlink($oldOgPath);
            }
        }
        // Simpan og-image baru
        $ogImageResult = saveBase64Image($input['og-image']);
        if (isset($ogImageResult['error'])) {
            sendResponse(['status' => 'error', 'message' => 'og-image: ' . $ogImageResult['error']], 422);
        }
        $meta['og-image'] = $ogImageResult['filename'];
    }

    // Proses logo-image jika ada
    if (!empty($input['logo-image']) && str_starts_with($input['logo-image'], 'data:image/')) {
        // Hapus logo-image lama jika ada
        if (!empty($meta['logo-image'])) {
            $oldLogoPath = '../assets/img/' . basename($meta['logo-image']);
            if (file_exists($oldLogoPath)) {
                @unlink($oldLogoPath);
            }
        }
        // Simpan logo-image baru
        $logoImageResult = saveBase64Image($input['logo-image']);
        if (isset($logoImageResult['error'])) {
            sendResponse(['status' => 'error', 'message' => 'logo-image: ' . $logoImageResult['error']], 422);
        }
        $meta['logo-image'] = $logoImageResult['filename'];
    }

    // Update ke database
    try {
        $jsonValue = json_encode($settingValue, JSON_UNESCAPED_UNICODE);
        $stmt = $Conn->prepare("UPDATE setting SET setting_value = :val WHERE setting_parameter = 'layout_static'");
        $stmt->bindParam(':val', $jsonValue);
        $stmt->execute();

        sendResponse(['status' => 'success', 'message' => 'Meta tag berhasil diperbarui']);
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
    }
?>