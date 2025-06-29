<?php
    // Aktifkan error reporting untuk debugging
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    header('Content-Type: application/json');

    // Fungsi kirim response JSON
    function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Cek metode harus DELETE
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

    // Ambil dan decode input
    $rawInput = file_get_contents("php://input");
    $input = json_decode($rawInput, true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Ambil id_poliklinik
    $id_poliklinik = (int)($input['id_poliklinik'] ?? 0);
    if ($id_poliklinik <= 0) {
        sendResponse(['status' => 'error', 'message' => 'ID Poliklinik tidak valid'], 400);
    }

    // Cek apakah data ada
    try {
        $stmt = $Conn->prepare("SELECT foto FROM poliklinik WHERE id_poliklinik = :id");
        $stmt->bindParam(':id', $id_poliklinik);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            sendResponse(['status' => 'error', 'message' => 'Poliklinik tidak ditemukan'], 404);
        }

        $fotoLama = $row['foto'];

        // Hapus file foto jika ada
        if (!empty($fotoLama)) {
            $pathFoto = realpath(__DIR__ . '/../assets/img/_Poliklinik/' . $fotoLama);
            if ($pathFoto && file_exists($pathFoto)) {
                unlink($pathFoto);
            }
        }

        // Hapus data dari DB
        $stmtDelete = $Conn->prepare("DELETE FROM poliklinik WHERE id_poliklinik = :id");
        $stmtDelete->bindParam(':id', $id_poliklinik);
        $stmtDelete->execute();

        sendResponse([
            'status' => 'success',
            'message' => 'Data poliklinik berhasil dihapus'
        ]);
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => 'Gagal menghapus: ' . $e->getMessage()], 500);
    }
?>
