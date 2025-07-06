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

    // Ambil body JSON
    $raw = file_get_contents("php://input");
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    $order = $input['order'] ?? null;
    if (!is_numeric($order)) {
        sendResponse(['status' => 'error', 'message' => 'Order harus berupa angka dan tidak boleh kosong'], 400);
    }

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

    // Hapus menu berdasarkan order
    $menu_awal = $setting_value['menu'];
    $setting_value['menu'] = array_values(array_filter($menu_awal, function ($m) use ($order) {
        return (int)$m['order'] !== (int)$order;
    }));

    if (count($menu_awal) === count($setting_value['menu'])) {
        sendResponse(['status' => 'error', 'message' => 'Menu dengan order tersebut tidak ditemukan'], 404);
    }

    // Simpan perubahan
    $updatedJson = json_encode($setting_value, JSON_UNESCAPED_UNICODE);
    $update = $Conn->prepare("UPDATE setting SET setting_value = :val WHERE id_setting = :id");
    $update->bindValue(':val', $updatedJson);
    $update->bindValue(':id', $data['id_setting']);
    $update->execute();

    sendResponse([
        'status' => 'success',
        'message' => 'Menu berhasil dihapus',
        'deleted_order' => (int)$order
    ]);
?>