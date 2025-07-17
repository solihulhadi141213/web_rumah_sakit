<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    header('Content-Type: application/json');

    function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validasi metode request harus POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus POST'], 405);
    }

    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';
    require_once '../_Config/log_visitor.php';

    try {
        $Conn = (new Database())->getConnection();
    } catch (Exception $e) {
        sendResponse(['status' => 'error', 'message' => 'Koneksi DB gagal: ' . $e->getMessage()], 500);
    }

    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (empty($token)) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan.'], 401);
    }

    $validasi_token = validasi_x_token($Conn, $token);
    if ($validasi_token !== "Valid") {
        sendResponse(['status' => 'error', 'message' => $validasi_token], 401);
    }

    $rawInput = file_get_contents("php://input");
    if (empty($rawInput)) {
        sendResponse(['status' => 'error', 'message' => 'Body kosong, tidak ada data dikirim'], 400);
    }

    $input = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Ambil dan validasi input utama
    $id_blog = trim($input['id_blog'] ?? '');
    $order_id = (int)($input['order_id'] ?? 0);
    $type = trim($input['type'] ?? '');
    $content = trim($input['content'] ?? '');

    if ($id_blog === '') sendResponse(['status' => 'error', 'message' => 'ID Blog tidak boleh kosong'], 400);
    if ($order_id <= 0) sendResponse(['status' => 'error', 'message' => 'Order ID tidak valid'], 400);
    if ($type === '') sendResponse(['status' => 'error', 'message' => 'Tipe konten tidak boleh kosong'], 400);
    if ($content === '') sendResponse(['status' => 'error', 'message' => 'Konten tidak boleh kosong'], 400);

    // Ambil konten lama
    $stmt = $Conn->prepare("SELECT content_blog FROM blog WHERE id_blog = :id_blog LIMIT 1");
    $stmt->bindParam(':id_blog', $id_blog);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        sendResponse(['status' => 'error', 'message' => 'ID Blog tidak ditemukan'], 404);
    }
    $content_blog = $row['content_blog'] ?? '';
    $content_blog_arry = [];
    if ($content_blog !== '') {
        $content_blog_arry = json_decode($content_blog, true);
        if (!is_array($content_blog_arry)) {
            sendResponse(['status' => 'error', 'message' => 'Data content_blog rusak / bukan JSON valid'], 500);
        }
    }

    // Validasi & proses konten image
    $imageExtra = [];
    if ($type === 'image') {
        if (!preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $content)) {
            sendResponse(['status' => 'error', 'message' => 'Format base64 image tidak valid'], 400);
        }

        $data = explode(',', $content);
        $decoded = base64_decode($data[1], true);
        if ($decoded === false) {
            sendResponse(['status' => 'error', 'message' => 'Base64 tidak dapat didekode'], 400);
        }

        if (strlen($decoded) > 2 * 1024 * 1024) {
            sendResponse(['status' => 'error', 'message' => 'Ukuran gambar melebihi 2MB'], 400);
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($decoded);
        $allowedTypes = ['image/png', 'image/jpeg'];
        if (!in_array($mimeType, $allowedTypes)) {
            sendResponse(['status' => 'error', 'message' => 'MIME gambar tidak valid: ' . $mimeType], 400);
        }

        $ext = $mimeType === 'image/png' ? '.png' : '.jpg';
        $filename = uniqid('img_', true) . $ext;
        $path = __DIR__ . '/../assets/img/_Artikel/' . $filename;
        if (!file_put_contents($path, $decoded)) {
            sendResponse(['status' => 'error', 'message' => 'Gagal menyimpan file gambar'], 500);
        }

        // Tambahan atribut image
        $imageExtra = [
            'position' => trim($input['position'] ?? 'center'),
            'width'    => trim($input['width'] ?? '100'),
            'unit'     => trim($input['unit'] ?? '%'),
            'caption'  => trim($input['caption'] ?? '')
        ];

        $content = $filename;
    }

    // Reorder data lama jika perlu
    foreach ($content_blog_arry as &$item) {
        if ($item['order_id'] >= $order_id) {
            $item['order_id'] += 1;
        }
    }
    unset($item);

    // Tambahkan konten baru
    $newItem = [
        'order_id' => $order_id,
        'type'     => $type,
        'content'  => $content
    ];

    if ($type === 'image') {
        $newItem = array_merge($newItem, $imageExtra);
    }

    $content_blog_arry[] = $newItem;

    // Urutkan berdasarkan order_id
    usort($content_blog_arry, function($a, $b) {
        return $a['order_id'] <=> $b['order_id'];
    });

    $content_blog_json = json_encode($content_blog_arry, JSON_UNESCAPED_UNICODE);
    if ($content_blog_json === false) {
        sendResponse(['status' => 'error', 'message' => 'Gagal encode data content_blog'], 500);
    }

    // Simpan ke database
    try {
        $Conn->beginTransaction();
        $stmt = $Conn->prepare("UPDATE blog SET content_blog = :content_blog WHERE id_blog = :id_blog");
        $stmt->bindParam(':content_blog', $content_blog_json);
        $stmt->bindParam(':id_blog', $id_blog);
        $stmt->execute();
        $Conn->commit();

        sendResponse([
            'status' => 'success',
            'message' => 'Konten blog berhasil ditambahkan',
            'data' => [
                'id_blog' => $id_blog,
                'content_blog' => json_decode($content_blog_json)
            ]
        ]);
    } catch (PDOException $e) {
        $Conn->rollBack();
        sendResponse(['status' => 'error', 'message' => 'Gagal update: ' . $e->getMessage()], 500);
    }
?>
