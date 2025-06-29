<?php
    // Aktifkan error reporting untuk debugging
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    header('Content-Type: application/json');

    // Fungsi untuk merespons JSON
    function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validasi metode harus DELETE
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus DELETE'], 405);
    }

    // Load koneksi dan fungsi
    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';
    require_once '../_Config/log_visitor.php';

    // Validasi koneksi DB
    try {
        $Conn = (new Database())->getConnection();
    } catch (Exception $e) {
        sendResponse(['status' => 'error', 'message' => 'Koneksi DB gagal: ' . $e->getMessage()], 500);
    }

    // Validasi x-token
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (empty($token)) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan.'], 401);
    }
    $validasi_token = validasi_x_token($Conn, $token);
    if ($validasi_token !== "Valid") {
        sendResponse(['status' => 'error', 'message' => $validasi_token], 401);
    }

    // Ambil body JSON
    $rawInput = file_get_contents("php://input");
    $input = json_decode($rawInput, true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    $id_dokter = (int)($input['id_dokter'] ?? 0);
    if ($id_dokter <= 0) {
        sendResponse(['status' => 'error', 'message' => 'ID Dokter tidak valid'], 400);
    }

    // Cek data dokter di database
    try {
        $stmt = $Conn->prepare("SELECT * FROM dokter WHERE id_dokter = :id_dokter LIMIT 1");
        $stmt->bindParam(':id_dokter', $id_dokter);
        $stmt->execute();
        $dokter = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dokter) {
            sendResponse(['status' => 'error', 'message' => 'Data dokter tidak ditemukan'], 404);
        }

        // Hapus foto jika ada
        if (!empty($dokter['foto'])) {
            $fotoPath = realpath(__DIR__ . '/../assets/img/_Dokter/' . $dokter['foto']);
            if ($fotoPath && file_exists($fotoPath)) {
                @unlink($fotoPath); // Gunakan @ untuk suppress error jika file tidak bisa dihapus
            }
        }

        // Hapus data dokter
        $stmtDelete = $Conn->prepare("DELETE FROM dokter WHERE id_dokter = :id_dokter");
        $stmtDelete->bindParam(':id_dokter', $id_dokter);
        $stmtDelete->execute();

        sendResponse([
            'status' => 'success',
            'message' => 'Data dokter berhasil dihapus',
            'deleted_id' => $id_dokter
        ]);
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => 'Gagal menghapus data: ' . $e->getMessage()], 500);
    }
?>