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

    // Ambil input
    $raw = file_get_contents("php://input");
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Validasi label
    $label = trim($input['label'] ?? '');
    if ($label === '') {
        sendResponse(['status' => 'error', 'message' => 'Label menu wajib diisi'], 400);
    }
    $label = strip_tags($label);
    if (strlen($label) > 255) {
        sendResponse(['status' => 'error', 'message' => 'Label tidak boleh lebih dari 255 karakter'], 400);
    }

    // Validasi url (boleh kosong)
    $url = trim($input['url'] ?? '');
    $url = strip_tags($url);

    // Ambil data layout_static
    $stmt = $Conn->prepare("SELECT * FROM setting WHERE setting_parameter = 'layout_static' LIMIT 1");
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) {
        sendResponse(['status' => 'error', 'message' => 'Data layout_static tidak ditemukan'], 404);
    }

    // Decode JSON
    $setting_value = json_decode($data['setting_value'], true);
    if (!is_array($setting_value)) {
        sendResponse(['status' => 'error', 'message' => 'Format setting_value tidak valid'], 500);
    }

    $menu = $setting_value['menu'] ?? [];
    $lastOrder = 0;
    foreach ($menu as $m) {
        if (isset($m['order']) && is_numeric($m['order']) && $m['order'] > $lastOrder) {
            $lastOrder = $m['order'];
        }
    }
    $newOrder = $lastOrder + 1;

    // Tambahkan menu baru
    $menu[] = [
        'order' => $newOrder,
        'label' => $label,
        'url'   => $url,
        'submenu' => []
    ];

    // Simpan kembali
    $setting_value['menu'] = $menu;
    $newJson = json_encode($setting_value, JSON_UNESCAPED_UNICODE);
    $stmtUpdate = $Conn->prepare("UPDATE setting SET setting_value = :val WHERE id_setting = :id");
    $stmtUpdate->bindValue(':val', $newJson);
    $stmtUpdate->bindValue(':id', $data['id_setting']);
    $stmtUpdate->execute();

    sendResponse([
        'status' => 'success',
        'message' => 'Menu berhasil ditambahkan',
        'menu' => $menu
    ]);
?>