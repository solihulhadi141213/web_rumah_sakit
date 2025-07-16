<?php
    header("Content-Type: application/json");
    require_once "../_Config/Connection.php";
    require_once "../_Config/Function.php";

    function respond($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 1. Validasi metode harus PUT
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        respond(['status' => 'error', 'message' => 'Metode harus PUT'], 405);
    }

    // 2. Validasi token dari header
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (!$token) respond(['status' => 'error', 'message' => 'Token tidak ditemukan'], 401);

    $Conn = (new Database())->getConnection();
    if (validasi_x_token($Conn, $token) !== 'Valid') {
        respond(['status' => 'error', 'message' => 'Token tidak valid'], 403);
    }

    // 3. Ambil JSON body request
    $input = json_decode(file_get_contents("php://input"), true);

    // 4. Validasi input
    $title = trim($input['title'] ?? '');
    $subtitle = trim($input['subtitle'] ?? '');
    $limit = $input['limit'] ?? null;

    if ($title === '') {
        respond(['status' => 'error', 'message' => 'Title tidak boleh kosong'], 422);
    }

    if (!is_numeric($limit) || $limit < 5 || $limit > 100) {
        respond(['status' => 'error', 'message' => 'Limit harus berupa angka antara 5 sampai 100'], 422);
    }

    // 5. Ambil setting berdasarkan parameter
    $stmt = $Conn->prepare("SELECT id_setting, setting_value FROM setting WHERE setting_parameter = 'layout_static' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        respond(['status' => 'error', 'message' => 'Data setting tidak ditemukan'], 404);
    }

    $id_setting = $row['id_setting'];
    $json = json_decode($row['setting_value'], true);

    // 6. Update value pada jadwal_dokter
    $json['jadwal_dokter'] = [
        'title' => $title,
        'subtitle' => $subtitle,
        'limit' => (int)$limit
    ];

    // 7. Encode kembali dan simpan ke database
    $newValue = json_encode($json, JSON_UNESCAPED_UNICODE);
    $update = $Conn->prepare("UPDATE setting SET setting_value = :val WHERE id_setting = :id");
    $update->execute([
        ':val' => $newValue,
        ':id' => $id_setting
    ]);

    // 8. Respon sukses
    respond([
        'status' => 'success',
        'message' => 'Konten jadwal dokter berhasil diperbarui',
        'jadwal_dokter' => $json['jadwal_dokter']
    ]);
?>