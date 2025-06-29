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

    // Cek metode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus POST'], 405);
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

    // Ambil body dan decode JSON
    $rawInput = file_get_contents("php://input");
    $input = json_decode($rawInput, true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Ambil nilai input
    $kode = trim($input['kode'] ?? '');
    $nama = trim($input['nama'] ?? '');
    $spesialis = trim($input['spesialis'] ?? '');
    $fotoBase64 = trim($input['foto'] ?? '');

    // Validasi wajib isi
    if ($kode === '' || $nama === '' || $spesialis === '') {
        sendResponse(['status' => 'error', 'message' => 'Kode, Nama, dan Spesialis tidak boleh kosong'], 400);
    }

    // Validasi duplikat kode
    $stmtCheck = $Conn->prepare("SELECT COUNT(*) FROM dokter WHERE kode = :kode");
    $stmtCheck->bindParam(':kode', $kode);
    $stmtCheck->execute();
    if ((int)$stmtCheck->fetchColumn() > 0) {
        sendResponse(['status' => 'error', 'message' => 'Kode dokter sudah digunakan'], 409);
    }

    // Proses simpan foto jika ada
    $namaFileFoto = null;
    if (!empty($fotoBase64)) {
        if (!preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $fotoBase64)) {
            sendResponse(['status' => 'error', 'message' => 'Format foto tidak valid (harus image base64)'], 400);
        }

        // Pisahkan metadata dan data base64
        [$typeMeta, $base64Data] = explode(',', $fotoBase64);
        $mime = explode('/', explode(';', $typeMeta)[0])[1];
        $fotoDecoded = base64_decode($base64Data, true);

        if ($fotoDecoded === false) {
            sendResponse(['status' => 'error', 'message' => 'Base64 foto tidak valid'], 400);
        }

        if (strlen($fotoDecoded) > 2 * 1024 * 1024) {
            sendResponse(['status' => 'error', 'message' => 'Ukuran file foto melebihi 2MB'], 400);
        }

        // Pastikan direktori tersedia
        $folderPath = realpath(__DIR__ . '/../assets/img/_Dokter/');
        if ($folderPath === false) {
            $folderPath = __DIR__ . '/../assets/img/_Dokter/';
            if (!is_dir($folderPath)) {
                if (!mkdir($folderPath, 0755, true)) {
                    sendResponse(['status' => 'error', 'message' => 'Gagal membuat folder penyimpanan foto'], 500);
                }
            }
        }

        // Simpan file
        $namaFileFoto = 'dokter_' . uniqid() . '.' . $mime;
        $filePath = $folderPath . '/' . $namaFileFoto;
        if (!file_put_contents($filePath, $fotoDecoded)) {
            sendResponse(['status' => 'error', 'message' => 'Gagal menyimpan file foto'], 500);
        }
    }

    // Simpan data ke database
    try {
        $stmt = $Conn->prepare("INSERT INTO dokter (kode, nama, spesialis, foto, last_update) VALUES (:kode, :nama, :spesialis, :foto, NOW())");
        $stmt->bindParam(':kode', $kode);
        $stmt->bindParam(':nama', $nama);
        $stmt->bindParam(':spesialis', $spesialis);
        $stmt->bindParam(':foto', $namaFileFoto);
        $stmt->execute();

        sendResponse([
            'status' => 'success',
            'message' => 'Dokter berhasil ditambahkan',
            'data' => [
                'id_dokter' => $Conn->lastInsertId(),
                'kode' => $kode,
                'nama' => $nama,
                'spesialis' => $spesialis,
                'foto' => $namaFileFoto
            ]
        ]);
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $e->getMessage()], 500);
    }
?>