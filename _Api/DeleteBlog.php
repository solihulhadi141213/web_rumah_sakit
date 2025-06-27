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

    try {
        $Conn->beginTransaction();

        // 1. Dapatkan informasi cover untuk dihapus nanti
        $getBlog = $Conn->prepare("SELECT cover FROM blog WHERE id_blog = :id_blog");
        $getBlog->bindParam(':id_blog', $id_blog);
        $getBlog->execute();
        
        if ($getBlog->rowCount() === 0) {
            sendResponse(['status' => 'error', 'message' => 'Blog dengan ID '.$id_blog.' tidak ditemukan'], 404);
        }
        
        $blogData = $getBlog->fetch(PDO::FETCH_ASSOC);
        $coverFile = $blogData['cover'];

        // 2. Hapus semua tag terkait blog
        $deleteTags = $Conn->prepare("DELETE FROM blog_tag WHERE id_blog = :id_blog");
        $deleteTags->bindParam(':id_blog', $id_blog);
        $deleteTags->execute();

        // 3. Hapus blog
        $deleteBlog = $Conn->prepare("DELETE FROM blog WHERE id_blog = :id_blog");
        $deleteBlog->bindParam(':id_blog', $id_blog);
        $deleteBlog->execute();

        // 4. Hapus file cover jika ada
        if (!empty($coverFile)) {
            $coverPath = '../assets/img/_Artikel/' . $coverFile;
            if (file_exists($coverPath)) {
                unlink($coverPath);
            }
        }

        $Conn->commit();

        sendResponse([
            'status' => 'success',
            'message' => 'Blog berhasil dihapus',
            'id_blog' => $id_blog,
            'deleted_cover' => !empty($coverFile) ? $coverFile : null
        ], 200);

    } catch (PDOException $e) {
        $Conn->rollBack();
        sendResponse(['status' => 'error', 'message' => 'Gagal menghapus blog: ' . $e->getMessage()], 500);
    }
?>