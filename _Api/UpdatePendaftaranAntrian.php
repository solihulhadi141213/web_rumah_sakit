<?php
    header("Content-Type: application/json");
    require_once "../_Config/Connection.php";
    require_once "../_Config/Function.php";

    // Fungsi respon cepat
    function respond($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validasi metode PUT
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        respond(['status' => 'error', 'message' => 'Metode harus PUT'], 405);
    }

    // Ambil token dari header
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (!$token) respond(['status' => 'error', 'message' => 'Token tidak ditemukan'], 401);

    // Cek validasi token
    $Conn = (new Database())->getConnection();
    if (validasi_x_token($Conn, $token) !== 'Valid') {
        respond(['status' => 'error', 'message' => 'Token tidak valid'], 403);
    }

    // Ambil dan decode input JSON
    $input = json_decode(file_get_contents("php://input"), true);

    // Ambil field
    $title = trim($input['title'] ?? '');
    $subtitle = trim($input['subtitle'] ?? '');
    $icon = trim($input['icon'] ?? '');
    $label = trim($input['label'] ?? '');
    $url = trim($input['url'] ?? '');

    // Validasi title wajib
    if ($title === '') {
        respond(['status' => 'error', 'message' => 'Title tidak boleh kosong'], 422);
    }

    // Sanitasi (secara dasar, bisa ditingkatkan sesuai kebutuhan)
    function sanitize($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }

    // Bangun array baru setelah disanitasi
    $pendaftaran_antrian = [
        'title' => sanitize($title),
        'subtitle' => $subtitle, // subtitle boleh mengandung HTML
        'icon' => sanitize($icon),
        'label' => sanitize($label),
        'url' => filter_var($url, FILTER_VALIDATE_URL) ? $url : ''
    ];

    // Ambil JSON setting lama dari DB
    $query = $Conn->prepare("SELECT id_setting, setting_value FROM setting WHERE setting_parameter = 'layout_static' LIMIT 1");
    $query->execute();
    $data = $query->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        respond(['status' => 'error', 'message' => 'Setting layout_static tidak ditemukan'], 404);
    }

    $id_setting = $data['id_setting'];
    $layout = json_decode($data['setting_value'], true);

    // Update bagian pendaftaran_antrian
    $layout['pendaftaran_antrian'] = $pendaftaran_antrian;

    // Encode ulang dan simpan ke database
    $new_json = json_encode($layout, JSON_UNESCAPED_UNICODE);
    $update = $Conn->prepare("UPDATE setting SET setting_value = :val WHERE id_setting = :id");
    $update->execute([
        ':val' => $new_json,
        ':id' => $id_setting
    ]);

    // Respon sukses
    respond([
        'status' => 'success',
        'message' => 'Konten pendaftaran antrian berhasil diperbarui',
        'pendaftaran_antrian' => $pendaftaran_antrian
    ]);
?>