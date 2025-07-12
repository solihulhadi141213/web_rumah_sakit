<?php
    header('Content-Type: application/json');
    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';

    function sendResponse($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validasi metode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus POST'], 405);
    }

    // Validasi token
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (empty($token)) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan'], 401);
    }

    $Conn = (new Database())->getConnection();
    $valid = validasi_x_token($Conn, $token);
    if ($valid !== 'Valid') {
        sendResponse(['status' => 'error', 'message' => $valid], 401);
    }

    // Ambil input JSON
    $raw = file_get_contents("php://input");
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Validasi company
    $company = trim($input['company'] ?? '');
    if ($company === '') {
        sendResponse(['status' => 'error', 'message' => 'Nama perusahaan wajib diisi'], 400);
    }
    $company = strip_tags($company);

    // Validasi logo (base64)
    $logo_base64 = $input['logo'] ?? '';
    if (empty($logo_base64)) {
        sendResponse(['status' => 'error', 'message' => 'Logo (base64) wajib diisi'], 400);
    }
    if (!preg_match('/^data:image\/(\w+);base64,/', $logo_base64, $type)) {
        sendResponse(['status' => 'error', 'message' => 'Format logo base64 tidak valid'], 400);
    }

    $image_type = strtolower($type[1]); // jpg, png, etc
    $logo_data = substr($logo_base64, strpos($logo_base64, ',') + 1);
    $decoded_logo = base64_decode($logo_data, true);
    if ($decoded_logo === false) {
        sendResponse(['status' => 'error', 'message' => 'Gagal mendekode logo'], 400);
    }

    // Cek ukuran maksimal 2MB
    if (strlen($decoded_logo) > 2 * 1024 * 1024) {
        sendResponse(['status' => 'error', 'message' => 'Ukuran logo tidak boleh lebih dari 2MB'], 400);
    }

    // Simpan file logo
    $uploadDir = '../assets/img/_Partnership/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $filename = uniqid() . '.' . $image_type;
    file_put_contents($uploadDir . $filename, $decoded_logo);

    // Ambil setting layout_static
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

    // Siapkan data partnership baru
    $partnership = $setting_value['partnership'] ?? [];
    $lastOrder = 0;
    foreach ($partnership as $p) {
        if (isset($p['order']) && is_numeric($p['order']) && $p['order'] > $lastOrder) {
            $lastOrder = $p['order'];
        }
    }
    $newOrder = $lastOrder + 1;

    $partnership[] = [
        'logo' => $filename,
        'company' => $company,
        'order' => $newOrder
    ];

    $setting_value['partnership'] = $partnership;
    $newJson = json_encode($setting_value, JSON_UNESCAPED_UNICODE);

    $stmtUpdate = $Conn->prepare("UPDATE setting SET setting_value = :val WHERE id_setting = :id");
    $stmtUpdate->bindValue(':val', $newJson);
    $stmtUpdate->bindValue(':id', $data['id_setting']);
    $stmtUpdate->execute();

    sendResponse([
        'status' => 'success',
        'message' => 'Partnership berhasil ditambahkan',
        'partnership' => $partnership
    ]);
?>