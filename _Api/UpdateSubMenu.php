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
    if (validasi_x_token($Conn, $token) !== 'Valid') {
        sendResponse(['status' => 'error', 'message' => 'Token tidak valid'], 401);
    }

    // Ambil JSON input
    $raw = file_get_contents("php://input");
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Ambil dan validasi parameter
    $orderParent = $input['order-parent'] ?? null;
    $orderChild = $input['order-child'] ?? null;
    $label = trim($input['label'] ?? '');
    $url = trim($input['url'] ?? '');

    if (!is_numeric($orderParent) || !is_numeric($orderChild)) {
        sendResponse(['status' => 'error', 'message' => 'order-parent dan order-child wajib berupa angka'], 400);
    }
    if ($label === '') {
        sendResponse(['status' => 'error', 'message' => 'Label tidak boleh kosong'], 400);
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

    $found = false;
    foreach ($setting_value['menu'] as &$menu) {
        if ((int)$menu['order'] === (int)$orderParent) {
            if (!isset($menu['submenu']) || !is_array($menu['submenu'])) {
                sendResponse(['status' => 'error', 'message' => 'Submenu tidak ditemukan pada parent ini'], 404);
            }

            foreach ($menu['submenu'] as &$submenu) {
                if ((int)$submenu['order'] === (int)$orderChild) {
                    $submenu['label'] = $label;
                    $submenu['url'] = $url;
                    $found = true;
                    break 2;
                }
            }
        }
    }

    if (!$found) {
        sendResponse(['status' => 'error', 'message' => 'Submenu tidak ditemukan'], 404);
    }

    // Simpan perubahan
    $updatedJson = json_encode($setting_value, JSON_UNESCAPED_UNICODE);
    $update = $Conn->prepare("UPDATE setting SET setting_value = :val WHERE id_setting = :id");
    $update->bindValue(':val', $updatedJson);
    $update->bindValue(':id', $data['id_setting']);
    $update->execute();

    sendResponse([
        'status' => 'success',
        'message' => 'Submenu berhasil diperbarui',
        'data' => [
            'order-parent' => $orderParent,
            'order-child' => $orderChild,
            'label' => $label,
            'url' => $url
        ]
    ]);
?>