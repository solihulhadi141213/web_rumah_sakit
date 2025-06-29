<?php
    // Aktifkan error reporting untuk debugging
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    header('Content-Type: application/json');

    // Fungsi respon JSON
    function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validasi metode request harus DELETE
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus DELETE'], 405);
    }

    // Load konfigurasi
    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';
    require_once '../_Config/log_visitor.php';

    // Koneksi ke database
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
    $validasi_token = validasi_x_token($Conn, $token);
    if ($validasi_token !== "Valid") {
        sendResponse(['status' => 'error', 'message' => $validasi_token], 401);
    }

    // Ambil input JSON
    $rawInput = file_get_contents("php://input");
    $input = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Validasi data input
    $id_blog = trim($input['id_blog'] ?? '');
    $order_id = (int)($input['order_id'] ?? 0);
    if ($id_blog === '') sendResponse(['status' => 'error', 'message' => 'ID Blog tidak boleh kosong'], 400);
    if ($order_id <= 0) sendResponse(['status' => 'error', 'message' => 'Order ID tidak valid'], 400);

    // Ambil data content_blog dari database
    $stmt = $Conn->prepare("SELECT content_blog FROM blog WHERE id_blog = :id_blog LIMIT 1");
    $stmt->bindParam(':id_blog', $id_blog);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        sendResponse(['status' => 'error', 'message' => 'Blog tidak ditemukan'], 404);
    }
    $content_blog_json = $row['content_blog'] ?? '';
    $content_array = [];

    if ($content_blog_json !== '') {
        $content_array = json_decode($content_blog_json, true);
        if (!is_array($content_array)) {
            sendResponse(['status' => 'error', 'message' => 'Data content_blog tidak valid'], 500);
        }
    }

    // Cari dan hapus konten dengan order_id
    $found = false;
    $folder = __DIR__ . '/../../assets/img/_Artikel/';
    foreach ($content_array as $index => $item) {
        if ($item['order_id'] == $order_id) {
            $found = true;

            // Hapus file gambar jika type image
            if (
                isset($item['type']) && $item['type'] === 'image' &&
                isset($item['content']) &&
                file_exists($folder . $item['content'])
            ) {
                unlink($folder . $item['content']);
            }

            // Hapus dari array
            unset($content_array[$index]);
            break;
        }
    }
    if (!$found) {
        sendResponse(['status' => 'error', 'message' => 'Konten dengan order_id tidak ditemukan'], 404);
    }

    // Urutkan ulang order_id
    usort($content_array, function($a, $b) {
        return $a['order_id'] <=> $b['order_id'];
    });
    $order = 1;
    foreach ($content_array as &$item) {
        $item['order_id'] = $order++;
    }
    unset($item);

    // Encode kembali ke JSON
    $new_json = json_encode(array_values($content_array), JSON_UNESCAPED_UNICODE);
    if ($new_json === false) {
        sendResponse(['status' => 'error', 'message' => 'Gagal mengubah data ke JSON'], 500);
    }

    // Simpan perubahan ke DB
    try {
        $Conn->beginTransaction();
        $stmt = $Conn->prepare("UPDATE blog SET content_blog = :content_blog WHERE id_blog = :id_blog");
        $stmt->bindParam(':content_blog', $new_json);
        $stmt->bindParam(':id_blog', $id_blog);
        $stmt->execute();
        $Conn->commit();

        sendResponse([
            'status' => 'success',
            'message' => 'Konten berhasil dihapus',
            'data' => [
                'id_blog' => $id_blog,
                'content_blog' => $new_json
            ]
        ]);
    } catch (PDOException $e) {
        $Conn->rollBack();
        sendResponse(['status' => 'error', 'message' => 'Gagal menghapus konten: ' . $e->getMessage()], 500);
    }
?>