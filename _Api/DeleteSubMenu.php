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
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus DELETE'], 405);
    }

    // Ambil dan validasi token
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
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    $orderParent = $input['order-parent'] ?? null;
    $orderChild = $input['order-child'] ?? null;
    if (!is_numeric($orderParent) || !is_numeric($orderChild)) {
        sendResponse(['status' => 'error', 'message' => 'order-parent dan order-child harus berupa angka'], 400);
    }

    // Ambil data setting
    $stmt = $Conn->prepare("SELECT * FROM setting WHERE setting_parameter = 'layout_static' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        sendResponse(['status' => 'error', 'message' => 'Data layout_static tidak ditemukan'], 404);
    }

    $settingValue = json_decode($row['setting_value'], true);
    if (!isset($settingValue['menu']) || !is_array($settingValue['menu'])) {
        sendResponse(['status' => 'error', 'message' => 'Struktur menu tidak ditemukan'], 500);
    }

    // Temukan parent menu
    $menuUpdated = [];
    $parentFound = false;
    $childFound = false;

    foreach ($settingValue['menu'] as &$menu) {
        if ((int)$menu['order'] === (int)$orderParent) {
            $parentFound = true;
            if (!isset($menu['submenu']) || !is_array($menu['submenu'])) {
                break;
            }

            $beforeCount = count($menu['submenu']);
            $menu['submenu'] = array_values(array_filter($menu['submenu'], function($sub) use ($orderChild) {
                return (int)$sub['order'] !== (int)$orderChild;
            }));

            $afterCount = count($menu['submenu']);
            if ($beforeCount !== $afterCount) {
                $childFound = true;
            }
        }
        $menuUpdated[] = $menu;
    }

    // Validasi hasil pencarian
    if (!$parentFound) {
        sendResponse(['status' => 'error', 'message' => 'Menu induk tidak ditemukan'], 404);
    }
    if (!$childFound) {
        sendResponse(['status' => 'error', 'message' => 'Submenu tidak ditemukan'], 404);
    }

    // Simpan perubahan
    $settingValue['menu'] = $menuUpdated;
    $jsonUpdated = json_encode($settingValue, JSON_UNESCAPED_UNICODE);
    $update = $Conn->prepare("UPDATE setting SET setting_value = :val WHERE id_setting = :id");
    $update->bindValue(':val', $jsonUpdated);
    $update->bindValue(':id', $row['id_setting']);
    $update->execute();

    // Respon sukses
    sendResponse([
        'status' => 'success',
        'message' => 'Submenu berhasil dihapus',
        'order-parent' => (int)$orderParent,
        'order-child' => (int)$orderChild
    ]);

?>