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

    // Hanya izinkan metode PUT
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        sendResponse(['status' => 'error', 'message' => 'Metode request tidak diizinkan. Gunakan PUT'], 405);
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

    // Ambil data dari input
    $id_laman = $input['id_laman'];
    $judul_laman = isset($input['judul_laman']) ? trim($input['judul_laman']) : "";
    $kategori_laman = isset($input['kategori_laman']) ? trim($input['kategori_laman']) : "";
    $cover = isset($input['cover']) ? $input['cover'] : null;
    $deskripsi = isset($input['deskripsi']) ? trim($input['deskripsi']) : null;
    $author = isset($input['author']) ? trim($input['author']) : "";

    // Validasi field wajib
    if (empty($judul_laman)) {
        sendResponse(['status' => 'error', 'message' => 'Judul laman tidak boleh kosong'], 400);
    }

    if (empty($kategori_laman)) {
        sendResponse(['status' => 'error', 'message' => 'Kategori laman tidak boleh kosong'], 400);
    }

    if (empty($author)) {
        sendResponse(['status' => 'error', 'message' => 'Author tidak boleh kosong'], 400);
    }

    // Validasi panjang field
    if (strlen($judul_laman) > 255) {
        sendResponse(['status' => 'error', 'message' => 'Judul laman maksimal 255 karakter'], 400);
    }

    if (strlen($kategori_laman) > 255) {
        sendResponse(['status' => 'error', 'message' => 'Kategori laman maksimal 255 karakter'], 400);
    }

    // Cek apakah laman dengan ID tersebut ada
    $check_stmt = $Conn->prepare("SELECT cover FROM laman WHERE id_laman = :id_laman");
    $check_stmt->bindParam(':id_laman', $id_laman);
    $check_stmt->execute();

    if ($check_stmt->rowCount() === 0) {
        sendResponse(['status' => 'error', 'message' => 'Laman tidak ditemukan'], 404);
    }

    $existing_laman = $check_stmt->fetch(PDO::FETCH_ASSOC);
    $old_cover = $existing_laman['cover'];

    // Proses cover jika ada
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
        
        // Hapus file cover lama jika ada
        if (!empty($old_cover)) {
            $old_cover_path = '../assets/img/_Laman/' . $old_cover;
            if (file_exists($old_cover_path)) {
                unlink($old_cover_path);
            }
        }
    }

    try {
        // Persiapkan query update
        $query = "UPDATE laman SET 
                    judul_laman = :judul_laman,
                    kategori_laman = :kategori_laman,
                    deskripsi = :deskripsi,
                    author = :author";
        
        // Tambahkan cover ke query jika ada
        if (!empty($filename)) {
            $query .= ", cover = :cover";
        }
        
        $query .= " WHERE id_laman = :id_laman";
        
        $stmt = $Conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':judul_laman', $judul_laman);
        $stmt->bindParam(':kategori_laman', $kategori_laman);
        $stmt->bindParam(':deskripsi', $deskripsi);
        $stmt->bindParam(':author', $author);
        $stmt->bindParam(':id_laman', $id_laman);
        
        if (!empty($filename)) {
            $stmt->bindParam(':cover', $filename);
        }
        
        // Eksekusi query
        $stmt->execute();
        
        // Ambil data laman yang sudah diupdate
        $updated_stmt = $Conn->prepare("SELECT * FROM laman WHERE id_laman = :id_laman");
        $updated_stmt->bindParam(':id_laman', $id_laman);
        $updated_stmt->execute();
        $updated_laman = $updated_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Format URL cover jika ada
        if (!empty($updated_laman['cover'])) {
            $updated_laman['cover_url'] = BASE_URL . '/assets/img/_Laman/' . $updated_laman['cover'];
        } else {
            $updated_laman['cover_url'] = null;
        }
        unset($updated_laman['cover']);
        
        // Kirim response
        sendResponse([
            'status' => 'success',
            'message' => 'Laman berhasil diperbarui',
            'data' => $updated_laman
        ]);

    } catch (PDOException $e) {
        // Hapus file cover baru jika gagal menyimpan ke database
        if (!empty($filename) && file_exists('../assets/img/_Laman/' . $filename)) {
            unlink('../assets/img/_Laman/' . $filename);
        }
        sendResponse(['status' => 'error', 'message' => 'Gagal memperbarui laman: ' . $e->getMessage()], 500);
    }
?>