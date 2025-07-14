<?php
    header("Content-Type: application/json");
    require_once "../_Config/Connection.php";
    require_once "../_Config/Function.php";

    function respond($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        respond(['status' => 'error', 'message' => 'Metode harus PUT'], 405);
    }

    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (!$token) respond(['status' => 'error', 'message' => 'Token tidak ditemukan'], 401);

    $Conn = (new Database())->getConnection();
    if (validasi_x_token($Conn, $token) !== 'Valid') {
        respond(['status' => 'error', 'message' => 'Token tidak valid'], 403);
    }

    $input = json_decode(file_get_contents("php://input"), true);

    function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    $src = sanitize($input['src'] ?? '');
    $width = sanitize($input['width'] ?? '');
    $height = sanitize($input['height'] ?? '');
    $style = sanitize($input['style'] ?? '');
    $allowfullscreen = sanitize($input['allowfullscreen'] ?? '');
    $loading = sanitize($input['loading'] ?? '');
    $referrerpolicy = sanitize($input['referrerpolicy'] ?? '');
    $class = sanitize($input['class'] ?? '');

    if ($src === '') respond(['status' => 'error', 'message' => 'Src tidak boleh kosong'], 422);
    if ($width === '') respond(['status' => 'error', 'message' => 'Width tidak boleh kosong'], 422);
    if ($height === '' || !is_numeric($height)) respond(['status' => 'error', 'message' => 'Height harus berupa angka dan tidak boleh kosong'], 422);
    if ($style === '') respond(['status' => 'error', 'message' => 'Style tidak boleh kosong'], 422);
    if (!in_array($allowfullscreen, ['', 'true', 'false'], true)) respond(['status' => 'error', 'message' => 'Nilai allowfullscreen tidak valid'], 422);
    if (!in_array($loading, ['', 'lazy', 'eager'], true)) respond(['status' => 'error', 'message' => 'Nilai loading tidak valid'], 422);

    $valid_referrer = [
        'no-referrer', 'no-referrer-when-downgrade', 'origin', 'origin-when-cross-origin',
        'same-origin', 'strict-origin', 'strict-origin-when-cross-origin', 'unsafe-url'
    ];
    if (!in_array($referrerpolicy, $valid_referrer, true)) {
        respond(['status' => 'error', 'message' => 'Nilai referrerpolicy tidak valid'], 422);
    }

    $stmt = $Conn->prepare("SELECT * FROM setting WHERE setting_parameter='layout_static' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        respond(['status' => 'error', 'message' => 'Data layout_static tidak ditemukan'], 404);
    }

    $data = json_decode($row['setting_value'], true);
    $data['google_map'] = [
        'src' => $src,
        'width' => $width,
        'height' => $height,
        'style' => $style,
        'allowfullscreen' => $allowfullscreen,
        'loading' => $loading,
        'referrerpolicy' => $referrerpolicy,
        'class' => $class
    ];

    $update = $Conn->prepare("UPDATE setting SET setting_value = :val WHERE id_setting = :id");
    $update->bindValue(":val", json_encode($data, JSON_UNESCAPED_UNICODE));
    $update->bindValue(":id", $row['id_setting']);
    $update->execute();

    respond(['status' => 'success', 'message' => 'Google Map berhasil diperbarui']);
?>