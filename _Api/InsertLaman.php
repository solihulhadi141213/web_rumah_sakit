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

    // Tangkap input JSON
    $input = json_decode(file_get_contents("php://input"), true);

    // Validasi jika JSON tidak valid atau kosong
    if (json_last_error() !== JSON_ERROR_NONE || $input === null) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Ambil data dari input
    $judul_laman = isset($input['judul_laman']) ? trim($input['judul_laman']) : "";
    $kategori_laman = isset($input['kategori_laman']) ? trim($input['kategori_laman']) : "";
    $cover = isset($input['cover']) ? $input['cover'] : null;
    $deskripsi = isset($input['deskripsi']) ? trim($input['deskripsi']) : null;
    $author = isset($input['author']) ? trim($input['author']) : "";

    // Validasi judul_laman tidak boleh kosong
    if (empty($judul_laman)) {
        sendResponse(['status' => 'error', 'message' => 'Judul laman tidak boleh kosong'], 400);
    }

    // Validasi panjang judul_laman (maksimal 255 karakter)
    if (strlen($judul_laman) > 255) {
        sendResponse(['status' => 'error', 'message' => 'Judul laman maksimal 255 karakter'], 400);
    }

    // Validasi kategori_laman tidak boleh kosong
    if (empty($kategori_laman)) {
        sendResponse(['status' => 'error', 'message' => 'Kategori laman tidak boleh kosong'], 400);
    }

    // Validasi panjang kategori_laman (maksimal 255 karakter)
    if (strlen($kategori_laman) > 255) {
        sendResponse(['status' => 'error', 'message' => 'Kategori laman maksimal 255 karakter'], 400);
    }

    // Validasi author tidak boleh kosong
    if (empty($author)) {
        sendResponse(['status' => 'error', 'message' => 'Author tidak boleh kosong'], 400);
    }

    // Validasi cover jika ada (tidak wajib)
    $filename = null;
    if (!empty($cover)) {
        // Validasi base64 string
        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $cover)) {
            sendResponse(['status' => 'error', 'message' => 'Format cover tidak valid'], 400);
        }
        
        // Decode gambar untuk validasi
        $cover_data = base64_decode($cover);
        if ($cover_data === false) {
            sendResponse(['status' => 'error', 'message' => 'Gagal decode gambar cover'], 400);
        }
        
        // Validasi ukuran file (max 2MB)
        if (strlen($cover_data) > 2 * 1024 * 1024) {
            sendResponse(['status' => 'error', 'message' => 'Ukuran cover tidak boleh lebih dari 2MB'], 400);
        }
        
        // Validasi tipe file
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($cover_data);
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!in_array($mime, $allowed_mimes)) {
            sendResponse(['status' => 'error', 'message' => 'Tipe file cover tidak valid. Hanya diperbolehkan JPEG, PNG, atau GIF'], 400);
        }
        
        // Tentukan ekstensi file
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif'
        ];
        $extension = $extensions[$mime];
        
        // Generate nama file (36 karakter unik)
        $filename = bin2hex(random_bytes(18)) . '.' . $extension;
        $cover_path = '../assets/img/_Laman/' . $filename;
        
        // Simpan file gambar
        $save_result = file_put_contents($cover_path, $cover_data);
        if ($save_result === false) {
            sendResponse(['status' => 'error', 'message' => 'Gagal menyimpan gambar cover'], 500);
        }
    }

    // Generate UUID untuk id_laman (36 karakter)
    $id_laman = bin2hex(random_bytes(18));

    // Waktu sekarang
    $now = date('Y-m-d H:i:s');

    try {
        // Persiapkan query insert
        $stmt = $Conn->prepare("INSERT INTO laman (
            id_laman, 
            judul_laman, 
            kategori_laman, 
            datetime_laman, 
            cover, 
            deskripsi, 
            author, 
            konten
        ) VALUES (
            :id_laman, 
            :judul_laman, 
            :kategori_laman, 
            :datetime_laman, 
            :cover, 
            :deskripsi, 
            :author, 
            :konten
        )");

        // Bind parameter
        $stmt->bindParam(':id_laman', $id_laman);
        $stmt->bindParam(':judul_laman', $judul_laman);
        $stmt->bindParam(':kategori_laman', $kategori_laman);
        $stmt->bindParam(':datetime_laman', $now);
        $stmt->bindParam(':cover', $filename);
        $stmt->bindParam(':deskripsi', $deskripsi);
        $stmt->bindParam(':author', $author);
        $empty_content = json_encode([]);
        $stmt->bindParam(':konten', $empty_content);

        // Eksekusi query
        $stmt->execute();

        // Data untuk response
        $data = [
            'id_laman' => $id_laman,
            'judul_laman' => $judul_laman,
            'kategori_laman' => $kategori_laman,
            'datetime_laman' => $now,
            'cover' => "$filename",
            'deskripsi' => $deskripsi,
            'author' => $author
        ];

        // Kirim response
        sendResponse([
            'status' => 'success',
            'message' => 'Laman berhasil disimpan.',
            'data' => $data
        ], 201);

    } catch (PDOException $e) {
        // Hapus file cover jika gagal menyimpan ke database
        if (!empty($filename) && file_exists('../assets/img/_Laman/' . $filename)) {
            unlink('../assets/img/_Laman/' . $filename);
        }
        sendResponse(['status' => 'error', 'message' => 'Gagal menyimpan laman: ' . $e->getMessage()], 500);
    }
?>