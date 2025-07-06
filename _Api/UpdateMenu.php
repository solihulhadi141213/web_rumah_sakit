<?php
    header('Content-Type: application/json');
    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';

    function sendResponse($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Hanya izinkan metode PUT
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus PUT'], 405);
    }

    // Ambil token dari header
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

    // Ambil body input
    $raw = file_get_contents("php://input");
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Ambil parameter dan lakukan validasi
    $order = $input['order'] ?? null;
    $label = trim($input['label'] ?? '');
    $url = trim($input['url'] ?? '');

    if (!is_numeric($order)) {
        sendResponse(['status' => 'error', 'message' => 'Order wajib diisi dan berupa angka'], 400);
    }
    if ($label === '') {
        sendResponse(['status' => 'error', 'message' => 'Label wajib diisi'], 400);
    }
    if (strlen($label) > 255) {
        sendResponse(['status' => 'error', 'message' => 'Label tidak boleh lebih dari 255 karakter'], 400);
    }

    // Sanitasi
    $label = strip_tags($label);
    $url = strip_tags($url);

    // Ambil data layout_static
    $stmt = $Conn->prepare("SELECT * FROM setting WHERE setting_parameter = 'layout_static' LIMIT 1");
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) {
        sendResponse(['status' => 'error', 'message' => 'Data layout_static tidak ditemukan'], 404);
    }

    $setting_value = json_decode($data['setting_value'], true);
    if (!is_array($setting_value) || !isset($setting_value['menu'])) {
        sendResponse(['status' => 'error', 'message' => 'Struktur menu tidak ditemukan'], 500);
    }

    $menu = $setting_value['menu'];
    $found = false;

    foreach ($menu as &$item) {
        if (isset($item['order']) && (int)$item['order'] === (int)$order) {
            $item['label'] = $label;
            $item['url'] = $url;
            $found = true;
            break;
        }
    }

    if (!$found) {
        sendResponse(['status' => 'error', 'message' => 'Menu dengan order tersebut tidak ditemukan'], 404);
    }

    // Update ke database
    $setting_value['menu'] = $menu;
    $updatedJson = json_encode($setting_value, JSON_UNESCAPED_UNICODE);

    $update = $Conn->prepare("UPDATE setting SET setting_value = :val WHERE id_setting = :id");
    $update->bindValue(':val', $updatedJson);
    $update->bindValue(':id', $data['id_setting']);
    $update->execute();

    sendResponse([
        'status' => 'success',
        'message' => 'Menu berhasil diperbarui',
        'data' => $item
    ]);
?>