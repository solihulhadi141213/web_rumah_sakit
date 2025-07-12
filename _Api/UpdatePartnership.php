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

    $order = intval($input['order'] ?? 0);
    $order_switch = intval($input['order_switch'] ?? 0);
    $company = trim($input['company'] ?? '');
    $logo_base64 = $input['logo'] ?? '';

    if ($order <= 0 || $order_switch <= 0) {
        sendResponse(['status' => 'error', 'message' => 'Order atau order_switch tidak valid'], 400);
    }

    // Ambil data layout_static
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

    $uploadDir = '../assets/img/_Partnership/';
    $partnership = $setting_value['partnership'] ?? [];
    $foundOrder = false;
    $foundSwitch = false;

    // Validasi dan temukan item
    foreach ($partnership as &$item) {
        if ($item['order'] == $order) {
            $foundOrder = &$item;
        }
        if ($item['order'] == $order_switch) {
            $foundSwitch = &$item;
        }
    }
    unset($item);

    if (!$foundOrder || !$foundSwitch) {
        sendResponse(['status' => 'error', 'message' => 'Order atau order_switch tidak ditemukan'], 404);
    }

    // Tukar order jika berbeda
    if ($order !== $order_switch) {
        $tmp = $foundOrder['order'];
        $foundOrder['order'] = $foundSwitch['order'];
        $foundSwitch['order'] = $tmp;
    }

    // Update nama perusahaan
    if ($company !== '') {
        $foundOrder['company'] = strip_tags($company);
    }

    // Jika logo dikirim, validasi dan simpan baru
    if (!empty($logo_base64)) {
        if (!preg_match('/^data:image\/(\w+);base64,/', $logo_base64, $type)) {
            sendResponse(['status' => 'error', 'message' => 'Format logo base64 tidak valid'], 400);
        }

        $image_type = strtolower($type[1]);
        $allowed_types = ['png', 'jpg', 'jpeg', 'webp'];
        if (!in_array($image_type, $allowed_types)) {
            sendResponse(['status' => 'error', 'message' => 'Tipe gambar tidak didukung'], 400);
        }

        $logo_data = substr($logo_base64, strpos($logo_base64, ',') + 1);
        $decoded_logo = base64_decode($logo_data, true);
        if ($decoded_logo === false) {
            sendResponse(['status' => 'error', 'message' => 'Gagal mendekode logo'], 400);
        }

        if (strlen($decoded_logo) > 2 * 1024 * 1024) {
            sendResponse(['status' => 'error', 'message' => 'Ukuran logo tidak boleh lebih dari 2MB'], 400);
        }

        // Hapus file lama jika ada
        $oldFile = $foundOrder['logo'];
        if ($oldFile && file_exists($uploadDir . $oldFile)) {
            @unlink($uploadDir . $oldFile);
        }

        // Simpan file baru
        $filename = uniqid() . '.' . $image_type;
        file_put_contents($uploadDir . $filename, $decoded_logo);
        $foundOrder['logo'] = $filename;
    }

    // Simpan perubahan
    $setting_value['partnership'] = $partnership;
    $newJson = json_encode($setting_value, JSON_UNESCAPED_UNICODE);

    $stmtUpdate = $Conn->prepare("UPDATE setting SET setting_value = :val WHERE id_setting = :id");
    $stmtUpdate->bindValue(':val', $newJson);
    $stmtUpdate->bindValue(':id', $data['id_setting']);
    $stmtUpdate->execute();

    sendResponse([
        'status' => 'success',
        'message' => 'Data partnership berhasil diupdate',
        'partnership' => $partnership
    ]);
?>