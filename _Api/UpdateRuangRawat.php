<?php
// Aktifkan error reporting
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

// Validasi metode PUT (gunakan POST karena PUT tidak bisa diuji langsung di browser)
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    sendResponse(['status' => 'error', 'message' => 'Metode harus PUT'], 405);
}

// Load konfigurasi
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

// Ambil body PUT (via php://input)
$rawInput = file_get_contents("php://input");
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
}

// Ambil dan sanitasi input
$id_ruang_rawat = trim($input['id_ruang_rawat'] ?? '');
$ruang_rawat    = trim(htmlspecialchars($input['ruang_rawat'] ?? ''));
$kelas          = trim(htmlspecialchars($input['kelas'] ?? ''));
$kode_kelas     = trim(htmlspecialchars($input['kode_kelas'] ?? ''));
$kapasitas      = isset($input['kapasitas']) ? (int)$input['kapasitas'] : null;
$terisi         = isset($input['terisi']) ? (int)$input['terisi'] : 0;
$tersedia       = isset($input['tersedia']) ? (int)$input['tersedia'] : 0;
$datetime_update = date("Y-m-d H:i:s");

// Validasi
if (empty($id_ruang_rawat)) {
    sendResponse(['status' => 'error', 'message' => 'ID ruang rawat wajib diisi.']);
}
if (empty($ruang_rawat) || strlen($ruang_rawat) > 255) {
    sendResponse(['status' => 'error', 'message' => 'Nama ruang rawat tidak valid.']);
}
if (empty($kelas) || strlen($kelas) > 100) {
    sendResponse(['status' => 'error', 'message' => 'Kelas tidak valid.']);
}
if (empty($kode_kelas) || strlen($kode_kelas) > 50) {
    sendResponse(['status' => 'error', 'message' => 'Kode kelas tidak valid.']);
}
if (!is_numeric($kapasitas) || $kapasitas <= 0) {
    sendResponse(['status' => 'error', 'message' => 'Kapasitas harus lebih dari 0.']);
}
if ($terisi + $tersedia > $kapasitas) {
    sendResponse(['status' => 'error', 'message' => 'Terisi + tersedia melebihi kapasitas.']);
}

// Cek apakah ID tersedia di DB
$stmtCheck = $Conn->prepare("SELECT COUNT(*) FROM ruang_rawat WHERE id_ruang_rawat = :id");
$stmtCheck->bindParam(':id', $id_ruang_rawat);
$stmtCheck->execute();
if ($stmtCheck->fetchColumn() == 0) {
    sendResponse(['status' => 'error', 'message' => 'ID ruang rawat tidak ditemukan.']);
}

// Proses update
try {
    $stmt = $Conn->prepare("UPDATE ruang_rawat 
        SET ruang_rawat = :ruang_rawat,
            kelas = :kelas,
            kode_kelas = :kode_kelas,
            kapasitas = :kapasitas,
            terisi = :terisi,
            tersedia = :tersedia,
            datetime_update = :datetime_update
        WHERE id_ruang_rawat = :id");
    
    $stmt->bindParam(':ruang_rawat', $ruang_rawat);
    $stmt->bindParam(':kelas', $kelas);
    $stmt->bindParam(':kode_kelas', $kode_kelas);
    $stmt->bindParam(':kapasitas', $kapasitas, PDO::PARAM_INT);
    $stmt->bindParam(':terisi', $terisi, PDO::PARAM_INT);
    $stmt->bindParam(':tersedia', $tersedia, PDO::PARAM_INT);
    $stmt->bindParam(':datetime_update', $datetime_update);
    $stmt->bindParam(':id', $id_ruang_rawat);
    
    $stmt->execute();

    sendResponse([
        'status' => 'success',
        'message' => 'Ruang rawat berhasil diperbarui',
        'data' => [
            'id_ruang_rawat' => $id_ruang_rawat,
            'ruang_rawat' => $ruang_rawat,
            'kelas' => $kelas,
            'kode_kelas' => $kode_kelas,
            'kapasitas' => $kapasitas,
            'terisi' => $terisi,
            'tersedia' => $tersedia,
            'datetime_update' => $datetime_update
        ]
    ]);
} catch (PDOException $e) {
    sendResponse(['status' => 'error', 'message' => 'Gagal update: ' . $e->getMessage()], 500);
}
?>