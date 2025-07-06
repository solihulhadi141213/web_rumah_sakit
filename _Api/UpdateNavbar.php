<?php
    header('Content-Type: application/json');
    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';

    function sendResponse($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus PUT'], 405);
    }

    // Validasi token
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

    // Ambil input
    $raw = file_get_contents("php://input");
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Ambil data layout_static
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

    // Variabel
    $maxSize = 2 * 1024 * 1024;
    $allowedMime = ['image/png', 'image/jpeg'];
    $targetDir = '../assets/img/';
    $logoFile = $setting_value['navbar']['logo-image'] ?? null;

    // Update logo-image jika diberikan
    if (!empty($input['logo-image'])) {
        if (!preg_match('/^data:(image\/[a-zA-Z]+);base64,/', $input['logo-image'], $matches)) {
            sendResponse(['status' => 'error', 'message' => 'Format base64 tidak valid'], 400);
        }

        $mime = $matches[1];
        if (!in_array($mime, $allowedMime)) {
            sendResponse(['status' => 'error', 'message' => 'Hanya gambar PNG dan JPEG yang diperbolehkan'], 400);
        }

        $imageData = base64_decode(preg_replace('/^data:image\/[a-zA-Z]+;base64,/', '', $input['logo-image']));
        if (strlen($imageData) > $maxSize) {
            sendResponse(['status' => 'error', 'message' => 'Ukuran gambar melebihi 2MB'], 400);
        }

        // Hapus file lama jika ada
        if (!empty($logoFile)) {
            $oldPath = $targetDir . basename($logoFile);
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        // Simpan file baru
        $ext = ($mime === 'image/png') ? 'png' : 'jpg';
        $newFileName = bin2hex(random_bytes(18)) . '.' . $ext;
        $newPath = $targetDir . $newFileName;
        file_put_contents($newPath, $imageData);
        $setting_value['navbar']['logo-image'] = $newFileName;
    }

    // Update title Wajib terisi
    if (!isset($input['title']) || trim($input['title']) === '') {
        sendResponse(['status' => 'error', 'message' => 'Title wajib diisi'], 400);
    }
    $setting_value['navbar']['title'] = substr(trim($input['title']), 0, 255);

    // Simpan kembali ke database
    $newJson = json_encode($setting_value, JSON_UNESCAPED_UNICODE);
    $stmtUpdate = $Conn->prepare("UPDATE setting SET setting_value = :val WHERE id_setting = :id");
    $stmtUpdate->bindValue(':val', $newJson);
    $stmtUpdate->bindValue(':id', $data['id_setting']);
    $stmtUpdate->execute();

    sendResponse([
        'status' => 'success',
        'message' => 'Navbar berhasil diperbarui',
        'navbar' => $setting_value['navbar']
    ]);
?>