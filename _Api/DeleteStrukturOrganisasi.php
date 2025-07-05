<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    header('Content-Type: application/json');

    // Fungsi bantu
    function sendResponse($data, $status = 200) {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validasi metode DELETE
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus DELETE'], 405);
    }

    // Include konfigurasi
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

    // Ambil input JSON dari DELETE
    $rawInput = file_get_contents("php://input");
    $input = json_decode($rawInput, true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Validasi ID
    $id = $input['id_struktur_organisasi'] ?? null;
    if (empty($id)) {
        sendResponse(['status' => 'error', 'message' => 'ID struktur organisasi wajib diisi.'], 422);
    }

    // Cek apakah data ada
    $stmt = $Conn->prepare("SELECT * FROM struktur_organisasi WHERE id_struktur_organisasi = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        sendResponse(['status' => 'error', 'message' => 'Data tidak ditemukan.'], 404);
    }

    // Hapus file foto jika ada
    if (!empty($data['foto'])) {
        $path = "../assets/img/_Struktur_Organisasi/" . $data['foto'];
        if (file_exists($path)) {
            unlink($path);
        }
    }

    // Proses DELETE
    try {
        $stmt = $Conn->prepare("DELETE FROM struktur_organisasi WHERE id_struktur_organisasi = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        sendResponse(['status' => 'success', 'message' => 'Data struktur organisasi berhasil dihapus.']);
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => 'Gagal menghapus data: ' . $e->getMessage()], 500);
    }
?>