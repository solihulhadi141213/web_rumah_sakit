<?php
    header('Content-Type: application/json');

    // Fungsi bantu kirim response dengan status code
    function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    // Koneksi Database
    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';
    require_once '../_Config/log_visitor.php';

    // Buat Koneksi
    $Conn = (new Database())->getConnection();

    // Tangkap token dari header
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';

    // Validasi Jika Token Kosong
    if (empty($token)) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan.'], 401);
    }

    // Validasi token dari tabel api_session
    $validasi_token = validasi_x_token($Conn, $token);
    if ($validasi_token !== "Valid") {
        sendResponse(['status' => 'error', 'message' => $validasi_token], 401);
    }

    // Hanya izinkan metode DELETE
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        sendResponse(['status' => 'error', 'message' => 'Metode request tidak diizinkan. Gunakan DELETE'], 405);
    }

    // Tangkap input JSON
    $input = json_decode(file_get_contents("php://input"), true);

    // Validasi jika JSON tidak valid atau kosong
    if (json_last_error() !== JSON_ERROR_NONE || $input === null) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Validasi ID laman wajib ada
    if (empty($input['id_laman'])) {
        sendResponse(['status' => 'error', 'message' => 'ID laman harus diisi'], 400);
    }

    $id_laman = $input['id_laman'];

    try {
        // Mulai transaksi
        $Conn->beginTransaction();

        // 1. Ambil informasi cover sebelum menghapus
        $stmt = $Conn->prepare("SELECT cover FROM laman WHERE id_laman = :id_laman");
        $stmt->bindParam(':id_laman', $id_laman);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            sendResponse(['status' => 'error', 'message' => 'Laman tidak ditemukan'], 404);
        }

        $laman = $stmt->fetch(PDO::FETCH_ASSOC);
        $cover_file = $laman['cover'];

        // 2. Hapus data dari database
        $delete_stmt = $Conn->prepare("DELETE FROM laman WHERE id_laman = :id_laman");
        $delete_stmt->bindParam(':id_laman', $id_laman);
        $delete_result = $delete_stmt->execute();

        if (!$delete_result || $delete_stmt->rowCount() === 0) {
            throw new PDOException("Gagal menghapus data laman");
        }

        // 3. Hapus file cover jika ada
        if (!empty($cover_file)) {
            $cover_path = '../assets/img/_Laman/' . $cover_file;
            if (file_exists($cover_path)) {
                unlink($cover_path);
            }
        }

        // Commit transaksi jika semua operasi berhasil
        $Conn->commit();

        // Kirim response sukses
        sendResponse([
            'status' => 'success',
            'message' => 'Laman berhasil dihapus',
            'deleted_id' => $id_laman
        ]);

    } catch (PDOException $e) {
        // Rollback transaksi jika terjadi error
        $Conn->rollBack();
        sendResponse(['status' => 'error', 'message' => 'Gagal menghapus laman: ' . $e->getMessage()], 500);
    }
?>