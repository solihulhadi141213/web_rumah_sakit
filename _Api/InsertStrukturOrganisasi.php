<?php
    // Konfigurasi awal
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

    function generateRandomFilename($ext) {
        return bin2hex(random_bytes(18)) . '.' . $ext;
    }

    function getMimeTypeFromBase64($base64) {
        if (preg_match('/^data:(image\/[a-zA-Z]+);base64,/', $base64, $matches)) {
            return $matches[1];
        }
        return null;
    }

    // Validasi metode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus POST'], 405);
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

    // Ambil dan decode input JSON
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Ambil input
    $boss      = isset($input['boss']) ? intval($input['boss']) : null;
    $nama      = trim($input['nama'] ?? '');
    $job_title = trim($input['job_title'] ?? '');
    $NIP       = trim($input['NIP'] ?? '');
    $foto_base64 = $input['foto'] ?? null;

    // Validasi wajib
    $errors = [];

    if (strlen($nama) == 0 || strlen($nama) > 255) {
        $errors[] = 'Nama wajib diisi dan tidak boleh lebih dari 255 karakter.';
    }
    if (strlen($job_title) == 0 || strlen($job_title) > 255) {
        $errors[] = 'Jabatan wajib diisi dan tidak boleh lebih dari 255 karakter.';
    }
    if (strlen($NIP) == 0 || strlen($NIP) > 30) {
        $errors[] = 'NIP wajib diisi dan tidak boleh lebih dari 30 karakter.';
    }

    // Validasi boss jika diisi
    if (!is_null($boss)) {
        $stmt = $Conn->prepare("SELECT COUNT(*) FROM struktur_organisasi WHERE id_struktur_organisasi = :id");
        $stmt->bindParam(':id', $boss, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $errors[] = 'ID boss tidak ditemukan dalam struktur organisasi.';
        }
    }

    // Validasi dan simpan foto
    $filename = null;
    if (!empty($foto_base64)) {
        // Validasi MIME
        $mime = getMimeTypeFromBase64($foto_base64);
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        if (!in_array($mime, $allowed_mimes)) {
            $errors[] = 'Tipe gambar tidak valid. Hanya JPG, PNG, WEBP yang diperbolehkan.';
        }

        // Ukuran file
        $data = explode(',', $foto_base64);
        if (!isset($data[1])) {
            $errors[] = 'Format foto base64 tidak valid.';
        } else {
            $decoded = base64_decode($data[1]);
            if (strlen($decoded) > 5 * 1024 * 1024) { // 5MB
                $errors[] = 'Ukuran file foto maksimal 5MB.';
            }
        }

        // Simpan file jika tidak ada error sejauh ini
        if (empty($errors)) {
            $ext = explode('/', $mime)[1];
            $filename = generateRandomFilename($ext);
            $path = "../assets/img/_Struktur_Organisasi/" . $filename;
            file_put_contents($path, $decoded);
        }
    }

    // Jika ada error validasi
    if (!empty($errors)) {
        sendResponse(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }

    // Insert data
    try {
        $stmt = $Conn->prepare("INSERT INTO struktur_organisasi (boss, nama, job_title, NIP, foto) VALUES (:boss, :nama, :job_title, :NIP, :foto)");
        $stmt->bindValue(':boss', $boss, PDO::PARAM_INT);
        $stmt->bindValue(':nama', $nama);
        $stmt->bindValue(':job_title', $job_title);
        $stmt->bindValue(':NIP', $NIP);
        $stmt->bindValue(':foto', $filename);
        $stmt->execute();

        sendResponse([
            'status' => 'success',
            'message' => 'Data struktur organisasi berhasil ditambahkan.'
        ]);
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $e->getMessage()], 500);
    }
?>