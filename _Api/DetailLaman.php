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

    // Tentukan base URL dinamis
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $full_path = dirname($_SERVER['SCRIPT_NAME']);
    $base_path = substr($full_path, 0, strpos($full_path, '/_Api'));
    $base_url = $protocol . $host . $base_path;
    define('BASE_URL', $base_url);

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

    // Hanya izinkan metode POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(['status' => 'error', 'message' => 'Metode request tidak diizinkan. Gunakan POST'], 405);
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
        // Query untuk mendapatkan detail laman
        $stmt = $Conn->prepare("SELECT 
                                id_laman, 
                                judul_laman, 
                                kategori_laman, 
                                datetime_laman, 
                                cover, 
                                deskripsi, 
                                author, 
                                konten
                            FROM laman 
                            WHERE id_laman = :id_laman");
        
        $stmt->bindParam(':id_laman', $id_laman);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            sendResponse(['status' => 'error', 'message' => 'Laman tidak ditemukan'], 404);
        }

        $laman = $stmt->fetch(PDO::FETCH_ASSOC);

        // Format URL cover jika ada
        if (!empty($laman['cover'])) {
            $laman['cover_url'] =$laman['cover'];
        } else {
            $laman['cover_url'] = null;
        }
        
        // Decode konten JSON jika ada
        if (!empty($laman['konten'])) {
            $laman['konten'] = json_decode($laman['konten'], true);
        } else {
            $laman['konten'] = [];
        }
        
        // Hapus field cover asli dari response
        unset($laman['cover']);

        // Kirim response
        sendResponse([
            'status' => 'success',
            'data' => $laman
        ]);

    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => 'Gagal mengambil detail laman: ' . $e->getMessage()], 500);
    }
?>