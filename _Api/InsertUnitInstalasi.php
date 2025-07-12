<?php
    header("Content-Type: application/json");
    require_once "../_Config/Connection.php";
    require_once "../_Config/Function.php";

    function respond($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validasi metode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['status' => 'error', 'message' => 'Metode harus POST'], 405);
    }

    // Validasi token
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (!$token) respond(['status' => 'error', 'message' => 'Token tidak ditemukan'], 401);

    $Conn = (new Database())->getConnection();
    if (validasi_x_token($Conn, $token) !== 'Valid') {
        respond(['status' => 'error', 'message' => 'Token tidak valid'], 403);
    }

    // Ambil data JSON
    $input = json_decode(file_get_contents("php://input"), true);
    $icon = trim($input['icon'] ?? '');
    $name = trim($input['name'] ?? '');

    // Validasi data
    if ($name === '') respond(['status' => 'error', 'message' => 'Name tidak boleh kosong'], 422);
    if ($icon === '') respond(['status' => 'error', 'message' => 'Icon tidak boleh kosong'], 422);

    // Ambil data layout_static
    $stmt = $Conn->prepare("SELECT * FROM setting WHERE setting_parameter='layout_static' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        respond(['status' => 'error', 'message' => 'Data layout_static tidak ditemukan'], 404);
    }

    $data = json_decode($row['setting_value'], true);
    $list = $data['unit_instalasi']['data_list'] ?? [];

    // Generate random ID 36 karakter
    function generateId($length = 36) {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $id = '';
        for ($i = 0; $i < $length; $i++) {
            $id .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $id;
    }

    $newId = generateId();

    $list[] = [
        'id' => $newId,
        'icon' => $icon,
        'name' => $name,
        'description' => []
    ];

    $data['unit_instalasi']['data_list'] = $list;

    // Simpan kembali ke DB
    $update = $Conn->prepare("UPDATE setting SET setting_value=:val WHERE id_setting=:id");
    $update->bindValue(":val", json_encode($data, JSON_UNESCAPED_UNICODE));
    $update->bindValue(":id", $row['id_setting']);
    $update->execute();

    // Respons berhasil
    respond([
        'status' => 'success',
        'message' => 'Item unit instalasi berhasil ditambahkan',
        'data' => end($list)
    ]);
?>
