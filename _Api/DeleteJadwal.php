<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    header('Content-Type: application/json');

    function sendResponse($data, $status = 200) {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validasi metode
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus DELETE'], 405);
    }

    // Include koneksi
    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';
    require_once '../_Config/log_visitor.php';

    try {
        $Conn = (new Database())->getConnection();
    } catch (Exception $e) {
        sendResponse(['status' => 'error', 'message' => 'Koneksi DB gagal: ' . $e->getMessage()], 500);
    }

    // Validasi token
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (empty($token)) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan.'], 401);
    }
    $validasi_token = validasi_x_token($Conn, $token);
    if ($validasi_token !== 'Valid') {
        sendResponse(['status' => 'error', 'message' => $validasi_token], 401);
    }

    // Ambil input JSON
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid.'], 400);
    }

    $id_jadwal = $input['id_jadwal'] ?? null;
    if (empty($id_jadwal)) {
        sendResponse(['status' => 'error', 'message' => 'ID jadwal wajib diisi.'], 422);
    }

    // Cek apakah jadwal ada
    $stmt = $Conn->prepare("SELECT COUNT(*) FROM jadwal_dokter WHERE id_jadwal = :id");
    $stmt->bindParam(':id', $id_jadwal, PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        sendResponse(['status' => 'error', 'message' => 'Data jadwal tidak ditemukan.'], 404);
    }

    // Hapus data
    try {
        $stmt = $Conn->prepare("DELETE FROM jadwal_dokter WHERE id_jadwal = :id");
        $stmt->bindParam(':id', $id_jadwal, PDO::PARAM_INT);
        $stmt->execute();

        sendResponse(['status' => 'success', 'message' => 'Jadwal dokter berhasil dihapus.']);
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => 'Gagal menghapus jadwal: ' . $e->getMessage()], 500);
    }
?>