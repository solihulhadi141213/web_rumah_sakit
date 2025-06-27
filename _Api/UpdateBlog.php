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

    // Validasi Token
    if (empty($token)) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan.'], 401);
    }

    $validasi_token = validasi_x_token($Conn, $token);
    if ($validasi_token !== "Valid") {
        sendResponse(['status' => 'error', 'message' => $validasi_token], 401);
    }

    // Tangkap input JSON
    $input = json_decode(file_get_contents("php://input"), true);

    // Validasi JSON
    if (json_last_error() !== JSON_ERROR_NONE || $input === null) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Validasi ID Blog
    $id_blog = isset($input['id_blog']) ? trim($input['id_blog']) : "";
    if (empty($id_blog)) {
        sendResponse(['status' => 'error', 'message' => 'ID Blog tidak boleh kosong'], 400);
    }

    // Cek apakah blog ada di database
    $checkBlog = $Conn->prepare("SELECT cover FROM blog WHERE id_blog = :id_blog");
    $checkBlog->bindParam(':id_blog', $id_blog);
    $checkBlog->execute();

    if ($checkBlog->rowCount() === 0) {
        sendResponse(['status' => 'error', 'message' => 'Blog dengan ID '.$id_blog.' tidak ditemukan'], 404);
    }

    $blogData = $checkBlog->fetch(PDO::FETCH_ASSOC);
    $oldCover = $blogData['cover'];

    // Ambil data input
    $title_blog = isset($input['title_blog']) ? trim($input['title_blog']) : "";
    $deskripsi = isset($input['deskripsi']) ? trim($input['deskripsi']) : "";
    $author_blog = isset($input['author_blog']) ? trim($input['author_blog']) : "";
    $publish = isset($input['publish']) ? (int)$input['publish'] : 0;
    $cover = isset($input['cover']) ? $input['cover'] : "";
    $tags = isset($input['tag']) ? $input['tag'] : [];

    // Validasi data
    if (empty($title_blog)) sendResponse(['status' => 'error', 'message' => 'Judul Blog Tidak Boleh Kosong'], 400);
    if (strlen($title_blog) > 100) sendResponse(['status' => 'error', 'message' => 'Judul Blog maksimal 100 karakter'], 400);
    if (empty($deskripsi)) sendResponse(['status' => 'error', 'message' => 'Deskripsi Blog Tidak Boleh Kosong'], 400);
    if (strlen($deskripsi) > 255) sendResponse(['status' => 'error', 'message' => 'Deskripsi Blog maksimal 255 karakter'], 400);
    if (empty($author_blog)) sendResponse(['status' => 'error', 'message' => 'Author Blog Tidak Boleh Kosong'], 400);
    if (empty($tags)) sendResponse(['status' => 'error', 'message' => 'Tag tidak boleh kosong'], 400);

    foreach ($tags as $tag) {
        if (empty(trim($tag))) {
            sendResponse(['status' => 'error', 'message' => 'Setiap tag tidak boleh kosong'], 400);
        }
        if (strlen(trim($tag)) > 30) {
            sendResponse(['status' => 'error', 'message' => 'Setiap tag maksimal 30 karakter'], 400);
        }
    }

    // Proses Cover
    $filename = null;
    if (!empty($cover)) {
        // Validasi base64
        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $cover)) {
            sendResponse(['status' => 'error', 'message' => 'Format cover tidak valid'], 400);
        }
        
        // Generate nama file baru
        $filename = bin2hex(random_bytes(18)) . '.png';
        $cover_path = '../assets/img/_Artikel/' . $filename;
        
        // Decode dan simpan gambar
        $cover_data = base64_decode($cover);
        if ($cover_data === false) {
            sendResponse(['status' => 'error', 'message' => 'Gagal decode gambar cover'], 400);
        }
        
        if (file_put_contents($cover_path, $cover_data) === false) {
            sendResponse(['status' => 'error', 'message' => 'Gagal menyimpan gambar cover'], 500);
        }
        
        // Hapus file lama jika ada
        if (!empty($oldCover)) {
            $oldCoverPath = '../assets/img/_Artikel/' . $oldCover;
            if (file_exists($oldCoverPath)) {
                unlink($oldCoverPath);
            }
        }
    }

    // Waktu update
    $now = date('Y-m-d H:i:s');

    try {
        $Conn->beginTransaction();
        
        // Update data blog
        $updateBlog = $Conn->prepare("UPDATE blog SET 
            title_blog = :title_blog,
            deskripsi = :deskripsi,
            cover = COALESCE(:cover, cover),
            datetime_update = :datetime_update,
            author_blog = :author_blog,
            publish = :publish
            WHERE id_blog = :id_blog");
        
        $updateBlog->bindParam(':title_blog', $title_blog);
        $updateBlog->bindParam(':deskripsi', $deskripsi);
        $updateBlog->bindParam(':cover', $filename);
        $updateBlog->bindParam(':datetime_update', $now);
        $updateBlog->bindParam(':author_blog', $author_blog);
        $updateBlog->bindParam(':publish', $publish, PDO::PARAM_INT);
        $updateBlog->bindParam(':id_blog', $id_blog);
        
        if (!$updateBlog->execute()) {
            throw new PDOException("Gagal update blog");
        }
        
        // Hapus tag lama
        $deleteTags = $Conn->prepare("DELETE FROM blog_tag WHERE id_blog = :id_blog");
        $deleteTags->bindParam(':id_blog', $id_blog);
        if (!$deleteTags->execute()) {
            throw new PDOException("Gagal menghapus tag lama");
        }
        
        // Insert tag baru
        if (!empty($tags)) {
            $insertTag = $Conn->prepare("INSERT INTO blog_tag (id_blog_tag, id_blog, blog_tag) VALUES (:id_blog_tag, :id_blog, :blog_tag)");
            
            foreach ($tags as $tag) {
                $id_blog_tag = bin2hex(random_bytes(18));
                $cleanTag = trim($tag);
                
                $insertTag->bindParam(':id_blog_tag', $id_blog_tag);
                $insertTag->bindParam(':id_blog', $id_blog);
                $insertTag->bindParam(':blog_tag', $cleanTag);
                
                if (!$insertTag->execute()) {
                    throw new PDOException("Gagal menyimpan tag");
                }
            }
        }
        
        $Conn->commit();
        
        // Response
        $responseData = [
            'id_blog' => $id_blog,
            'title_blog' => $title_blog,
            'deskripsi' => $deskripsi,
            'cover' => !empty($filename) ? BASE_URL . '/assets/img/_Artikel/' . $filename : BASE_URL . '/assets/img/_Artikel/' . $oldCover,
            'author_blog' => $author_blog,
            'publish' => $publish,
            'datetime_update' => $now,
            'tags' => $tags
        ];
        
        sendResponse([
            'status' => 'success',
            'message' => 'Blog berhasil diupdate',
            'data' => $responseData
        ], 200);
        
    } catch (PDOException $e) {
        $Conn->rollBack();
        
        // Hapus file cover baru jika gagal
        if (!empty($filename) && file_exists('../assets/img/_Artikel/' . $filename)) {
            unlink('../assets/img/_Artikel/' . $filename);
        }
        
        sendResponse(['status' => 'error', 'message' => 'Gagal mengupdate blog: ' . $e->getMessage()], 500);
    }
?>