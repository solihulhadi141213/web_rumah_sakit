<?php
    // Aktifkan error reporting
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    header('Content-Type: application/json');

    // Fungsi kirim response
    function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validasi metode DELETE
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus DELETE'], 405);
    }

    // Load konfigurasi dan fungsi
    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';
    require_once '../_Config/log_visitor.php';

    // Koneksi DB
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

    // Ambil JSON body
    $rawInput = file_get_contents("php://input");
    $input = json_decode($rawInput, true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Ambil dan validasi ID
    $id_ruang_rawat = trim($input['id_ruang_rawat'] ?? '');
    if (empty($id_ruang_rawat)) {
        sendResponse(['status' => 'error', 'message' => 'ID ruang rawat wajib diisi.']);
    }

    // Cek keberadaan data
    $stmtCheck = $Conn->prepare("SELECT * FROM ruang_rawat WHERE id_ruang_rawat = :id");
    $stmtCheck->bindParam(':id', $id_ruang_rawat);
    $stmtCheck->execute();
    $dataRuang = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$dataRuang) {
        sendResponse(['status' => 'error', 'message' => 'Data ruang rawat tidak ditemukan.']);
    }

    // Hapus data
    try {
        $stmtDelete = $Conn->prepare("DELETE FROM ruang_rawat WHERE id_ruang_rawat = :id");
        $stmtDelete->bindParam(':id', $id_ruang_rawat);
        $stmtDelete->execute();

        sendResponse([
            'status' => 'success',
            'message' => 'Ruang rawat berhasil dihapus',
            'deleted' => $dataRuang
        ]);
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => 'Gagal menghapus: ' . $e->getMessage()], 500);
    }
?>