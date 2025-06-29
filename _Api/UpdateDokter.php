<?php
    // Aktifkan error reporting untuk debugging
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    header('Content-Type: application/json');

    // Fungsi respons JSON
    function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validasi metode harus PUT
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus PUT'], 405);
    }

    // Load koneksi dan fungsi
    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';
    require_once '../_Config/log_visitor.php';

    // Validasi koneksi database
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

    // Ambil nilai input
    $id_dokter = (int)($input['id_dokter'] ?? 0);
    $kode = trim($input['kode'] ?? '');
    $nama = trim($input['nama'] ?? '');
    $spesialis = trim($input['spesialis'] ?? '');
    $fotoBase64 = trim($input['foto'] ?? '');

    // Validasi input dasar
    if ($id_dokter <= 0) sendResponse(['status' => 'error', 'message' => 'ID Dokter tidak valid'], 400);
    if ($kode === '') sendResponse(['status' => 'error', 'message' => 'Kode tidak boleh kosong'], 400);
    if ($nama === '') sendResponse(['status' => 'error', 'message' => 'Nama tidak boleh kosong'], 400);
    if ($spesialis === '') sendResponse(['status' => 'error', 'message' => 'Spesialis tidak boleh kosong'], 400);

    // Cek apakah dokter tersedia
    $stmt = $Conn->prepare("SELECT * FROM dokter WHERE id_dokter = :id_dokter");
    $stmt->bindParam(':id_dokter', $id_dokter);
    $stmt->execute();
    $dokter = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$dokter) {
        sendResponse(['status' => 'error', 'message' => 'Data dokter tidak ditemukan'], 404);
    }

    $fotoLama = $dokter['foto'] ?? null;
    $namaFileFotoBaru = $fotoLama;

    // Jika ada data base64 baru untuk foto, lakukan update
    if (!empty($fotoBase64)) {
        if (!preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $fotoBase64)) {
            sendResponse(['status' => 'error', 'message' => 'Format foto tidak valid (harus base64 image)'], 400);
        }

        [$typeMeta, $base64Data] = explode(',', $fotoBase64);
        $mime = explode('/', explode(';', $typeMeta)[0])[1];
        $fotoDecoded = base64_decode($base64Data, true);

        if ($fotoDecoded === false) {
            sendResponse(['status' => 'error', 'message' => 'Base64 foto tidak valid'], 400);
        }

        if (strlen($fotoDecoded) > 2 * 1024 * 1024) {
            sendResponse(['status' => 'error', 'message' => 'Ukuran file foto melebihi 2MB'], 400);
        }

        // Hapus foto lama jika ada
        if (!empty($fotoLama)) {
            $pathLama = realpath(__DIR__ . '/../assets/img/_Dokter/' . $fotoLama);
            if ($pathLama && file_exists($pathLama)) {
                @unlink($pathLama);
            }
        }

        // Simpan foto baru
        $folderPath = realpath(__DIR__ . '/../assets/img/_Dokter/');
        if ($folderPath === false) {
            $folderPath = __DIR__ . '/../assets/img/_Dokter/';
            if (!is_dir($folderPath)) {
                if (!mkdir($folderPath, 0755, true)) {
                    sendResponse(['status' => 'error', 'message' => 'Gagal membuat folder foto'], 500);
                }
            }
        }

        $namaFileFotoBaru = 'dokter_' . uniqid() . '.' . $mime;
        $pathBaru = $folderPath . '/' . $namaFileFotoBaru;
        if (!file_put_contents($pathBaru, $fotoDecoded)) {
            sendResponse(['status' => 'error', 'message' => 'Gagal menyimpan foto baru'], 500);
        }
    }

    // Update data dokter
    try {
        $stmt = $Conn->prepare("UPDATE dokter SET kode = :kode, nama = :nama, spesialis = :spesialis, foto = :foto, last_update = NOW() WHERE id_dokter = :id_dokter");
        $stmt->bindParam(':kode', $kode);
        $stmt->bindParam(':nama', $nama);
        $stmt->bindParam(':spesialis', $spesialis);
        $stmt->bindParam(':foto', $namaFileFotoBaru);
        $stmt->bindParam(':id_dokter', $id_dokter);
        $stmt->execute();

        sendResponse([
            'status' => 'success',
            'message' => 'Data dokter berhasil diupdate',
            'data' => [
                'id_dokter' => $id_dokter,
                'kode' => $kode,
                'nama' => $nama,
                'spesialis' => $spesialis,
                'foto' => $namaFileFotoBaru
            ]
        ]);
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => 'Gagal update data: ' . $e->getMessage()], 500);
    }
?>