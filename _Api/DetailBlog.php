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

    // Validasi parameter id
    $id_blog = $_GET['id'] ?? '';
    $id_blog = trim($id_blog);

    if ($id_blog === '') {
        sendResponse(['status' => 'error', 'message' => 'Parameter id_blog tidak ditemukan'], 400);
    }

    // Koneksi dan fungsi
    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';
    require_once '../_Config/log_visitor.php';

    // Validasi koneksi DB
    try {
        $Conn = (new Database())->getConnection();
    } catch (Exception $e) {
        sendResponse(['status' => 'error', 'message' => 'Koneksi DB gagal: ' . $e->getMessage()], 500);
    }

    // Ambil token dari header
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (empty($token)) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan.'], 401);
    }

    // Validasi token
    $validasi_token = validasi_x_token($Conn, $token);
    if ($validasi_token !== "Valid") {
        sendResponse(['status' => 'error', 'message' => $validasi_token], 401);
    }

    // Ambil data dari tabel blog
    try {
        $stmt = $Conn->prepare("SELECT * FROM blog WHERE id_blog = :id_blog LIMIT 1");
        $stmt->bindParam(':id_blog', $id_blog);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            sendResponse(['status' => 'error', 'message' => 'Data blog tidak ditemukan'], 404);
        }

        // Decode content_blog (jika ada)
        $content_blog = [];
        if (!empty($row['content_blog'])) {
            $content_blog = json_decode($row['content_blog'], true);
            if (!is_array($content_blog)) {
                $content_blog = [];
            }
            // Sort berdasarkan order_id
            usort($content_blog, function($a, $b) {
                return $a['order_id'] <=> $b['order_id'];
            });
        }

        // Ambil tag dari tabel blog_tag
        $tag_list = [];
        try {
            $stmtTag = $Conn->prepare("SELECT tag FROM blog_tag WHERE id_blog = :id_blog");
            $stmtTag->bindParam(':id_blog', $id_blog);
            $stmtTag->execute();
            while ($tagRow = $stmtTag->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($tagRow['tag'])) {
                    $tag_list[] = $tagRow['tag'];
                }
            }
        } catch (PDOException $e) {
            $tag_list = [];
        }

        // Siapkan data response
        $data = [
            'id_blog'         => $row['id_blog'],
            'title_blog'      => $row['title_blog'],
            'deskripsi'       => $row['deskripsi'],
            'cover'           => $row['cover'],
            'datetime_creat'  => $row['datetime_creat'],
            'datetime_update' => $row['datetime_update'],
            'author_blog'     => $row['author_blog'],
            'publish'         => (int) $row['publish'],
            'content_blog'    => $content_blog,
            'blog_tag'        => $tag_list
        ];

        sendResponse([
            'status' => 'success',
            'message' => 'Detail blog ditemukan',
            'data' => $data
        ]);
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => 'Query gagal: ' . $e->getMessage()], 500);
    }
?>