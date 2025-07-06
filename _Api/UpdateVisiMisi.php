<?php
    header("Content-Type: application/json");
    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';

    function sendResponse($data, $status = 200) {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validasi metode PUT
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus PUT'], 405);
    }

    // Ambil token dari header
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (!$token) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan'], 401);
    }

    // Validasi token
    $Conn = (new Database())->getConnection();
    if (validasi_x_token($Conn, $token) !== 'Valid') {
        sendResponse(['status' => 'error', 'message' => 'Token tidak valid'], 401);
    }

    // Ambil input JSON
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Ambil dan validasi isi input
    $misi = trim($input['misi'] ?? '');
    $visi = trim($input['visi'] ?? '');
    $motto = trim($input['motto'] ?? '');
    $title = trim($input['title'] ?? '');

    if ($misi === '' || $visi === '' || $motto === '' || $title === '') {
        sendResponse(['status' => 'error', 'message' => 'Semua field (misi, visi, motto, title) wajib diisi'], 422);
    }

    // Ambil data setting layout_static
    $stmt = $Conn->prepare("SELECT * FROM setting WHERE setting_parameter = 'layout_static' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        sendResponse(['status' => 'error', 'message' => 'Data layout_static tidak ditemukan'], 404);
    }

    // Update data visi_misi
    $data = json_decode($row['setting_value'], true);
    $data['visi_misi'] = [
        'misi' => $misi,
        'visi' => $visi,
        'motto' => $motto,
        'title' => $title
    ];

    // Simpan ke database
    $settingJson = json_encode($data, JSON_UNESCAPED_UNICODE);
    $update = $Conn->prepare("UPDATE setting SET setting_value = :val WHERE id_setting = :id");
    $update->bindValue(':val', $settingJson);
    $update->bindValue(':id', $row['id_setting']);
    $update->execute();

    sendResponse([
        'status' => 'success',
        'message' => 'Visi misi berhasil diperbarui',
        'visi_misi' => $data['visi_misi']
    ]);

?>