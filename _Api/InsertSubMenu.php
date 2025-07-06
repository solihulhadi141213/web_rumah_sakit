<?php
    header('Content-Type: application/json');
    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';

    function sendResponse($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Cek metode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus POST'], 405);
    }

    // Token validasi
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

    $order = $input['order'] ?? null;
    $label = trim($input['label'] ?? '');
    $url = trim($input['url'] ?? '');

    if ($order === null || !is_numeric($order)) {
        sendResponse(['status' => 'error', 'message' => 'Order parent menu wajib berupa angka'], 400);
    }
    if ($label === '') {
        sendResponse(['status' => 'error', 'message' => 'Label submenu wajib diisi'], 400);
    }
    if (strlen($label) > 255) {
        sendResponse(['status' => 'error', 'message' => 'Label tidak boleh lebih dari 255 karakter'], 400);
    }

    $label = strip_tags($label);
    $url = strip_tags($url);

    // Ambil layout_static
    $stmt = $Conn->prepare("SELECT * FROM setting WHERE setting_parameter = 'layout_static' LIMIT 1");
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) {
        sendResponse(['status' => 'error', 'message' => 'Data layout_static tidak ditemukan'], 404);
    }
    $setting_value = json_decode($data['setting_value'], true);
    if (!is_array($setting_value)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 500);
    }

    $menu = $setting_value['menu'] ?? [];
    $found = false;

    foreach ($menu as &$item) {
        if (isset($item['order']) && (int)$item['order'] === (int)$order) {
            $found = true;
            $submenu = $item['submenu'] ?? [];
            $lastOrder = 0;
            foreach ($submenu as $s) {
                if (isset($s['order']) && is_numeric($s['order']) && $s['order'] > $lastOrder) {
                    $lastOrder = $s['order'];
                }
            }
            $submenu[] = [
                'order' => $lastOrder + 1,
                'label' => $label,
                'url' => $url
            ];
            $item['submenu'] = $submenu;
            break;
        }
    }

    if (!$found) {
        sendResponse(['status' => 'error', 'message' => 'Parent menu dengan order tersebut tidak ditemukan'], 404);
    }

    // Update kembali setting_value
    $setting_value['menu'] = $menu;
    $newJson = json_encode($setting_value, JSON_UNESCAPED_UNICODE);
    $update = $Conn->prepare("UPDATE setting SET setting_value = :val WHERE id_setting = :id");
    $update->bindValue(':val', $newJson);
    $update->bindValue(':id', $data['id_setting']);
    $update->execute();

    sendResponse([
        'status' => 'success',
        'message' => 'Submenu berhasil ditambahkan',
        'submenu' => $submenu
    ]);
?>