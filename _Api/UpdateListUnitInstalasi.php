<?php
    header("Content-Type: application/json");
    require_once "../_Config/Connection.php";
    require_once "../_Config/Function.php";

    // Fungsi respon standar
    function respond($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validasi metode PUT
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        respond(['status' => 'error', 'message' => 'Metode harus PUT'], 405);
    }

    // Validasi x-token dari header
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (!$token) respond(['status' => 'error', 'message' => 'Token tidak ditemukan'], 401);

    $Conn = (new Database())->getConnection();
    if (validasi_x_token($Conn, $token) !== 'Valid') {
        respond(['status' => 'error', 'message' => 'Token tidak valid'], 403);
    }

    // Ambil data JSON dari request body
    $input = json_decode(file_get_contents("php://input"), true);
    $id   = trim($input['id'] ?? '');
    $icon = trim($input['icon'] ?? '');
    $name = trim($input['name'] ?? '');

    // Validasi input
    if ($id === '') {
        respond(['status' => 'error', 'message' => 'ID tidak boleh kosong'], 422);
    }
    if ($name === '') {
        respond(['status' => 'error', 'message' => 'Name tidak boleh kosong'], 422);
    }
    if ($icon === '') {
        respond(['status' => 'error', 'message' => 'Icon tidak boleh kosong'], 422);
    }

    // Ambil data layout_static dari database
    $stmt = $Conn->prepare("SELECT * FROM setting WHERE setting_parameter='layout_static' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        respond(['status' => 'error', 'message' => 'Data layout_static tidak ditemukan'], 404);
    }

    $data = json_decode($row['setting_value'], true);
    $list = $data['unit_instalasi']['data_list'] ?? [];

    $found = false;
    foreach ($list as &$item) {
        if ($item['id'] === $id) {
            $item['icon'] = $icon;
            $item['name'] = $name;
            $found = true;
            break;
        }
    }

    if (!$found) {
        respond(['status' => 'error', 'message' => 'ID unit tidak ditemukan'], 404);
    }

    // Simpan perubahan ke database
    $data['unit_instalasi']['data_list'] = $list;
    $update = $Conn->prepare("UPDATE setting SET setting_value=:val WHERE id_setting=:id");
    $update->bindValue(":val", json_encode($data, JSON_UNESCAPED_UNICODE));
    $update->bindValue(":id", $row['id_setting']);
    $update->execute();

    respond([
        'status' => 'success',
        'message' => 'Unit instalasi berhasil diperbarui',
        'data' => $item
    ]);
?>
