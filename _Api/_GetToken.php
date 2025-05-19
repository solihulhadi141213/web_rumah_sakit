<?php
    header('Content-Type: application/json');

    // Fungsi bantu untuk kirim response dengan status code
    function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    // Koneksi Database
    require_once '../_Config/Connection.php';
    require_once '../_Config/log_visitor.php';

    // Fungsi untuk membuat token acak 36 karakter
    function generateToken($length = 36) {
        return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', $length)), 0, $length);
    }

    // Ambil JSON input dari body
    $input = json_decode(file_get_contents("php://input"), true);

    $user_key = $input['user_key'] ?? '';
    $access_key = $input['access_key'] ?? '';

    if (empty($user_key) || empty($access_key)) {
        sendResponse([
            'status' => 'error',
            'message' => 'user_key dan access_key wajib diisi.'
        ], 400);
    }

    $db = new Database();
    $Conn = $db->getConnection();

    // Ambil data setting dari database
    $sql = "SELECT setting_value FROM setting WHERE setting_parameter = 'api_access' LIMIT 1";
    $stmt = $Conn->prepare($sql);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        sendResponse([
            'status' => 'error',
            'message' => 'Konfigurasi akses API tidak ditemukan.'
        ], 500);
    }

    $config = json_decode($row['setting_value'], true);

    // Validasi user_key dan access_key
    if ($user_key !== $config['user_key'] || $access_key !== $config['access_key']) {
        sendResponse([
            'status' => 'error',
            'message' => 'user_key atau access_key tidak valid.'
        ], 401);
    }

    // Buat token dan waktu
    $token = generateToken();
    $datetime_creat = gmdate("Y-m-d H:i:s"); // UTC now
    $datetime_expired = gmdate("Y-m-d H:i:s", strtotime($datetime_creat . " +1 hour"));

    // Simpan token ke tabel api_session
    $sqlInsert = "INSERT INTO api_session (user_key, access_key, datetime_creat, datetime_expired, session_token)
                VALUES (:user_key, :access_key, :datetime_creat, :datetime_expired, :session_token)";
    $stmtInsert = $Conn->prepare($sqlInsert);
    $success = $stmtInsert->execute([
        ':user_key' => $user_key,
        ':access_key' => $access_key,
        ':datetime_creat' => $datetime_creat,
        ':datetime_expired' => $datetime_expired,
        ':session_token' => $token
    ]);

    if ($success) {
        sendResponse([
            'status' => 'success',
            'message' => 'Token berhasil dibuat.',
            'session_token' => $token,
            'expired_at' => $datetime_expired
        ], 200);
    } else {
        sendResponse([
            'status' => 'error',
            'message' => 'Gagal menyimpan sesi ke database.'
        ], 500);
    }
