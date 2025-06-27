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
    $full_path = dirname($_SERVER['SCRIPT_NAME']); // dapatkan path penuh
    $base_path = substr($full_path, 0, strpos($full_path, '/_Api')); // ambil bagian sebelum '/_Api'
    $base_url = $protocol . $host . $base_path;
    define('BASE_URL', $base_url);

    //Buat Koneksi
    $Conn = (new Database())->getConnection();

    // Tangkap token dari header
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';

    //Validasi Jika Token Kosong
    if (empty($token)) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan.'], 401);
    }

    // Validasi token dari tabel api_session
    $validasi_token=validasi_x_token($Conn,$token);

    if ($validasi_token!=="Valid") {
        sendResponse(['status' => 'error', 'message' => ''.$validasi_token.''], 401);
    }

    // Tangkap input JSON
    $input = json_decode(file_get_contents("php://input"), true);

    // Validasi jika JSON tidak valid atau kosong
    if (json_last_error() !== JSON_ERROR_NONE || $input === null) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    $title_blog = isset($input['title_blog']) ? trim($input['title_blog']) : "";
    $deskripsi = isset($input['deskripsi']) ? trim($input['deskripsi']) : "";
    $author_blog = isset($input['author_blog']) ? trim($input['author_blog']) : "";
    $publish = isset($input['publish']) ? (int)$input['publish'] : 0;
    $cover = isset($input['cover']) ? $input['cover'] : "";
    $tags = isset($input['tag']) ? $input['tag'] : [];
    // Validasi tag
    if (empty($tags)) {
        sendResponse(['status' => 'error', 'message' => 'Tag tidak boleh kosong'], 400);
    }

    // Validasi setiap tag
    foreach ($tags as $tag) {
        if (empty(trim($tag))) {
            sendResponse(['status' => 'error', 'message' => 'Setiap tag tidak boleh kosong'], 400);
        }
        if (strlen(trim($tag)) > 30) {
            sendResponse(['status' => 'error', 'message' => 'Setiap tag maksimal 30 karakter'], 400);
        }
    }

    //Validasi title_blog tidak boleh kosong
    if(empty($title_blog)){
        sendResponse(['status' => 'error', 'message' => 'Judul Blog Tidak Boleh Kosong'], 400);
    }

    // Validasi panjang title_blog (maksimal 100 karakter)
    if(strlen($title_blog) > 100){
        sendResponse(['status' => 'error', 'message' => 'Judul Blog maksimal 100 karakter'], 400);
    }

    //Validasi deskripsi tidak boleh kosong
    if(empty($deskripsi)){
        sendResponse(['status' => 'error', 'message' => 'Deskripsi Blog Tidak Boleh Kosong'], 400);
    }

    // Validasi panjang deskripsi (maksimal 255 karakter)
    if(strlen($deskripsi) > 255){
        sendResponse(['status' => 'error', 'message' => 'Deskripsi Blog maksimal 255 karakter'], 400);
    }

    //Validasi author_blog tidak boleh kosong
    if(empty($author_blog)){
        sendResponse(['status' => 'error', 'message' => 'Author Blog Tidak Boleh Kosong'], 400);
    }

    //Validasi cover tidak boleh kosong
    if(empty($cover)){
        sendResponse(['status' => 'error', 'message' => 'Cover Blog Tidak Boleh Kosong'], 400);
    }

    // Validasi base64 string
    if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $cover)) {
        sendResponse(['status' => 'error', 'message' => 'Format cover tidak valid'], 400);
    }

    // Generate nama file cover
    $filename = bin2hex(random_bytes(18)) . '.png';
    $cover_path = '../assets/img/_Artikel/' . $filename;

    // Decode dan simpan gambar
    $cover_data = base64_decode($cover);
    if ($cover_data === false) {
        sendResponse(['status' => 'error', 'message' => 'Gagal decode gambar cover'], 400);
    }

    // Simpan file gambar
    $save_result = file_put_contents($cover_path, $cover_data);
    if ($save_result === false) {
        sendResponse(['status' => 'error', 'message' => 'Gagal menyimpan gambar cover'], 500);
    }

    // Generate UUID untuk id_blog
    $id_blog = bin2hex(random_bytes(18));

    // Waktu sekarang
    $now = date('Y-m-d H:i:s');

    try {
        // Persiapkan query insert
        $stmt = $Conn->prepare("INSERT INTO blog (
            id_blog, 
            title_blog, 
            deskripsi, 
            cover, 
            datetime_creat, 
            datetime_update, 
            author_blog, 
            content_blog, 
            publish
        ) VALUES (
            :id_blog, 
            :title_blog, 
            :deskripsi, 
            :cover, 
            :datetime_creat, 
            :datetime_update, 
            :author_blog, 
            :content_blog, 
            :publish
        )");

        // Bind parameter
        $stmt->bindParam(':id_blog', $id_blog);
        $stmt->bindParam(':title_blog', $title_blog);
        $stmt->bindParam(':deskripsi', $deskripsi);
        $stmt->bindParam(':cover', $filename);
        $stmt->bindParam(':datetime_creat', $now);
        $stmt->bindParam(':datetime_update', $now);
        $stmt->bindParam(':author_blog', $author_blog);
        $empty_content = json_encode([]);
        $stmt->bindParam(':content_blog', $empty_content);
        $stmt->bindParam(':publish', $publish, PDO::PARAM_INT);

        // Eksekusi query
        $stmt->execute();

        // Simpan tag-tag ke tabel blog_tag
        if (!empty($tags)) {
            $tagStmt = $Conn->prepare("INSERT INTO blog_tag (id_blog_tag, id_blog, blog_tag) VALUES (:id_blog_tag, :id_blog, :blog_tag)");
            
            foreach ($tags as $tag) {
                $id_blog_tag = bin2hex(random_bytes(18));
                $cleanTag = trim($tag);
                
                $tagStmt->bindParam(':id_blog_tag', $id_blog_tag);
                $tagStmt->bindParam(':id_blog', $id_blog);
                $tagStmt->bindParam(':blog_tag', $cleanTag);
                
                if (!$tagStmt->execute()) {
                    throw new PDOException("Gagal menyimpan tag: " . implode(", ", $tagStmt->errorInfo()));
                }
            }
        }
        // Data untuk response
        $data = [
            'id_blog' => $id_blog,
            'title_blog' => $title_blog,
            'deskripsi' => $deskripsi,
            'cover' => BASE_URL . '/assets/img/_Artikel/' . $filename,
            'author_blog' => $author_blog,
            'publish' => $publish,
            'datetime_creat' => $now,
            'datetime_update' => $now,
            'tags' => $tags
        ];

        // Kirim response
        sendResponse([
            'status' => 'success',
            'message' => 'Blog berhasil disimpan.',
            'data' => $data
        ], 201);

    } catch (PDOException $e) {
        // Hapus file cover jika gagal menyimpan ke database
        if (file_exists($cover_path)) {
            unlink($cover_path);
        }
        sendResponse(['status' => 'error', 'message' => 'Gagal menyimpan blog: ' . $e->getMessage()], 500);
    }
?>