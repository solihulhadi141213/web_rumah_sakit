<?php
    // Aktifkan error reporting untuk debugging
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    header('Content-Type: application/json');

    // Fungsi response JSON
    function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Koneksi dan fungsi
    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';
    require_once '../_Config/log_visitor.php';

    // Validasi koneksi DB
    try {
        $Conn = (new Database())->getConnection();
    } catch (Exception $e) {
        sendResponse(['status' => 'error', 'message' => 'Koneksi DB gagal: ' . $e->getMessage()], 500);
    }

    // Ambil token dari header
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (empty($token)) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan.'], 401);
    }

    // Validasi token
    $validasi_token = validasi_x_token($Conn, $token);
    if ($validasi_token !== "Valid") {
        sendResponse(['status' => 'error', 'message' => $validasi_token], 401);
    }

    // Ambil data dari tabel setting
    $setting_parameter="layout_static";
    try {
        $stmt = $Conn->prepare("SELECT * FROM setting WHERE setting_parameter = :setting_parameter");
        $stmt->bindParam(':setting_parameter', $setting_parameter);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            sendResponse(['status' => 'error', 'message' => 'Data layout tidak ditemukan'], 404);
        }

        // Validasi setting_value
        if (empty($row['setting_value'])) {
            sendResponse(['status' => 'error', 'message' => 'Data layout tidak ditemukan'], 404);
        }

        $setting_value = json_decode($row['setting_value'], true);

        sendResponse([
            'status' => 'success',
            'message' => 'Detail layout ditemukan',
            'layout_static' => $setting_value
        ]);
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => 'Query gagal: ' . $e->getMessage()], 500);
    }
?>