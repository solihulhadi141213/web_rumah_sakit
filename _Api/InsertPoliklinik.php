<?php
    // Aktifkan error reporting
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    header('Content-Type: application/json');

    // Fungsi response
    function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validasi metode POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus POST'], 405);
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

    // Validasi token
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (empty($token)) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan.'], 401);
    }
    $validasi_token = validasi_x_token($Conn, $token);
    if ($validasi_token !== "Valid") {
        sendResponse(['status' => 'error', 'message' => $validasi_token], 401);
    }

    // Ambil dan decode JSON input
    $rawInput = file_get_contents("php://input");
    $input = json_decode($rawInput, true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Ambil input
    $poliklinik = trim($input['poliklinik'] ?? '');
    $deskripsi = trim($input['deskripsi'] ?? '');
    $kode = trim($input['kode'] ?? '');
    $status = trim($input['status'] ?? '');
    $fotoBase64 = trim($input['foto'] ?? '');
    $fotoName = null;

    // Validasi input
    if ($poliklinik === '' || strlen($poliklinik) > 255) {
        sendResponse(['status' => 'error', 'message' => 'Nama poliklinik wajib diisi dan maksimal 255 karakter'], 400);
    }
    if ($kode === '' || strlen($kode) > 20) {
        sendResponse(['status' => 'error', 'message' => 'Kode wajib diisi dan maksimal 20 karakter'], 400);
    }
    if (!in_array($status, ['Aktif', 'Non Aktif'])) {
        sendResponse(['status' => 'error', 'message' => 'Status harus bernilai Aktif atau Non Aktif'], 400);
    }

    // Validasi duplikasi kode
    try {
        $stmt = $Conn->prepare("SELECT COUNT(*) FROM poliklinik WHERE kode = :kode");
        $stmt->bindParam(':kode', $kode);
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            sendResponse(['status' => 'error', 'message' => 'Kode sudah terdaftar'], 400);
        }
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => 'Gagal cek kode: ' . $e->getMessage()], 500);
    }

    // Proses simpan foto jika ada
    if (!empty($fotoBase64)) {
        if (!preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $fotoBase64)) {
            sendResponse(['status' => 'error', 'message' => 'Format gambar base64 tidak valid'], 400);
        }

        $data = explode(',', $fotoBase64);
        $imageData = base64_decode($data[1], true);
        if ($imageData === false) {
            sendResponse(['status' => 'error', 'message' => 'Base64 tidak dapat didekode'], 400);
        }

        // Validasi ukuran maksimal 2MB
        if (strlen($imageData) > 2 * 1024 * 1024) {
            sendResponse(['status' => 'error', 'message' => 'Ukuran gambar melebihi 2MB'], 400);
        }

        // Validasi dan buat direktori
        $folder = realpath(__DIR__ . '/../assets/img/_Poliklinik/');
        if (!is_dir($folder)) {
            if (!mkdir($folder, 0755, true)) {
                sendResponse(['status' => 'error', 'message' => 'Gagal membuat direktori upload'], 500);
            }
        }

        $fotoName = substr(uniqid('poliklinik_', true), 0, 40) . '.png';
        $filePath = $folder . $fotoName;
        if (!file_put_contents($filePath, $imageData)) {
            sendResponse(['status' => 'error', 'message' => 'Gagal menyimpan foto'], 500);
        }
    }

    // Simpan data ke database
    try {
        $stmt = $Conn->prepare("INSERT INTO poliklinik (poliklinik, deskripsi, kode, status, foto, last_update) VALUES (:poliklinik, :deskripsi, :kode, :status, :foto, NOW())");
        $stmt->bindParam(':poliklinik', $poliklinik);
        $stmt->bindParam(':deskripsi', $deskripsi);
        $stmt->bindParam(':kode', $kode);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':foto', $fotoName);
        $stmt->execute();

        sendResponse([
            'status' => 'success',
            'message' => 'Data poliklinik berhasil ditambahkan',
            'data' => [
                'id_poliklinik' => $Conn->lastInsertId(),
                'poliklinik' => $poliklinik,
                'deskripsi' => $deskripsi,
                'kode' => $kode,
                'status' => $status,
                'foto' => $fotoName
            ]
        ]);
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $e->getMessage()], 500);
    }

?>