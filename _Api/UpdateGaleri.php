<?php
    header('Content-Type: application/json');

    // Fungsi bantu untuk kirim response dengan status code
    function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    // Koneksi Database
    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';
    require_once '../_Config/log_visitor.php';

    $Conn = (new Database())->getConnection();

    // Tangkap token dari header
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';

    if (empty($token)) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan.'], 401);
    }

    // Validasi token dari tabel api_session
    $stmt = $Conn->prepare("SELECT * FROM api_session WHERE session_token = :token AND datetime_expired > UTC_TIMESTAMP() LIMIT 1");
    $stmt->execute([':token' => $token]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak valid atau kedaluwarsa.'], 401);
    }

    // Tangkap input JSON
    $input = json_decode(file_get_contents("php://input"), true);
    $id_galeri = validateAndSanitizeInput($input['id_galeri'] ?? '');
    $title = validateAndSanitizeInput($input['title'] ?? '');
    $cover_base64 = $input['cover_base64'] ?? null; // Boleh null karena update bisa tanpa ganti gambar

    // Validasi id_galeri
    if (empty($id_galeri)) {
        sendResponse(['status' => 'error', 'message' => 'ID galeri tidak boleh kosong.'], 422);
    }

    // Validasi title
    if (empty($title)) {
        sendResponse(['status' => 'error', 'message' => 'Judul galeri tidak boleh kosong.'], 422);
    }

    if (strlen($title) > 30) {
        sendResponse(['status' => 'error', 'message' => 'Judul galeri maksimal 30 karakter.'], 422);
    }

    // Cek apakah data dengan id_galeri tersebut ada
    $stmtCheck = $Conn->prepare("SELECT * FROM galeri WHERE id_galeri = :id_galeri LIMIT 1");
    $stmtCheck->execute([':id_galeri' => $id_galeri]);
    $existingData = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$existingData) {
        sendResponse(['status' => 'error', 'message' => 'Data galeri tidak ditemukan.'], 404);
    }

    $filename = $existingData['cover']; // Default: gunakan file lama

    // Proses gambar baru jika ada
    if (!empty($cover_base64)) {
        $decoded = base64_decode($cover_base64, true);
        if (!$decoded) {
            sendResponse(['status' => 'error', 'message' => 'Gagal mendekode gambar base64.'], 422);
        }

        // Cek apakah hasil decode valid image
        $imageInfo = @getimagesizefromstring($decoded);
        if ($imageInfo === false) {
            sendResponse(['status' => 'error', 'message' => 'Data base64 bukan gambar yang valid.'], 422);
        }

        // Generate nama file baru
        $filename = GenerateUuid(36) . '.png';
        $savePath = '../assets/img/_Galeri/' . $filename;

        // Simpan file baru
        if (!file_put_contents($savePath, $decoded)) {
            sendResponse(['status' => 'error', 'message' => 'Gagal menyimpan file gambar baru.'], 500);
        }

        // Hapus file lama jika ada dan bukan default
        $oldFilePath = '../assets/img/_Galeri/' . $existingData['cover'];
        if (file_exists($oldFilePath) && $existingData['cover'] != 'default.png') {
            unlink($oldFilePath);
        }
    }

    // Update data di database
    $stmtUpdate = $Conn->prepare("UPDATE galeri SET title = :title, cover = :cover WHERE id_galeri = :id_galeri");
    $success = $stmtUpdate->execute([
        ':id_galeri' => $id_galeri,
        ':title' => $title,
        ':cover' => $filename
    ]);

    if ($success) {
        // Tentukan base URL dinamis
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $full_path = dirname($_SERVER['SCRIPT_NAME']); // dapatkan path penuh
        $base_path = substr($full_path, 0, strpos($full_path, '/_Api')); // ambil bagian sebelum '/_Api'
        $base_url = $protocol . $host . $base_path;
        define('BASE_URL', $base_url);

        sendResponse([
            'status' => 'success',
            'message' => 'Galeri berhasil diperbarui.',
            'data' => [
                'id_galeri' => $id_galeri,
                'title' => $title,
                'cover' => "$base_url/image_proxy.php?segment=Galeri&image_name=$filename"
            ]
        ]);
    } else {
        sendResponse(['status' => 'error', 'message' => 'Gagal memperbarui data galeri.'], 500);
    }