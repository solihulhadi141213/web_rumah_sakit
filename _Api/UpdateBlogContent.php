<?php
    // Aktifkan error reporting untuk debugging
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    header('Content-Type: application/json');

    function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validasi metode PUT
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus PUT'], 405);
    }

    // Load konfigurasi
    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';
    require_once '../_Config/log_visitor.php';

    // Inisialisasi koneksi
    try {
        $Conn = (new Database())->getConnection();
    } catch (Exception $e) {
        sendResponse(['status' => 'error', 'message' => 'Koneksi DB gagal: ' . $e->getMessage()], 500);
    }

    // Ambil token
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (empty($token)) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan.'], 401);
    }
    $validasi_token = validasi_x_token($Conn, $token);
    if ($validasi_token !== "Valid") {
        sendResponse(['status' => 'error', 'message' => $validasi_token], 401);
    }

    // Ambil body input
    $rawInput = file_get_contents("php://input");
    $input = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Validasi data input
    $id_blog = trim($input['id_blog'] ?? '');
    $order_id = (int)($input['order_id'] ?? 0);
    $order_id_new = (int)($input['order_id_new'] ?? 0);
    $type = trim($input['type'] ?? '');
    $content = trim($input['content'] ?? '');

    if ($id_blog === '') sendResponse(['status' => 'error', 'message' => 'ID Blog tidak boleh kosong'], 400);
    if ($order_id <= 0) sendResponse(['status' => 'error', 'message' => 'Order ID tidak valid'], 400);
    if ($order_id_new <= 0) sendResponse(['status' => 'error', 'message' => 'Order ID baru tidak valid'], 400);
    if ($type === '') sendResponse(['status' => 'error', 'message' => 'Type tidak boleh kosong'], 400);
    if ($content === '') sendResponse(['status' => 'error', 'message' => 'Konten tidak boleh kosong'], 400);

    // Ambil content lama dari DB
    $stmt = $Conn->prepare("SELECT content_blog FROM blog WHERE id_blog = :id_blog LIMIT 1");
    $stmt->bindParam(':id_blog', $id_blog);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $content_blog_json = $row['content_blog'] ?? '';
    $content_array = [];

    if ($content_blog_json !== '') {
        $content_array = json_decode($content_blog_json, true);
        if (!is_array($content_array)) {
            sendResponse(['status' => 'error', 'message' => 'Data content_blog tidak valid'], 500);
        }
    }

    // Hapus data lama berdasarkan order_id
    $found = false;
    $old_filename = '';
    foreach ($content_array as $index => $item) {
        if ($item['order_id'] == $order_id) {
            $found = true;

            // Cek jika sebelumnya image, dan file benar-benar ada
            if (
                isset($item['type']) && $item['type'] === 'image' &&
                isset($item['content']) &&
                file_exists(__DIR__ . '/../../assets/img/_Artikel/' . $item['content'])
            ) {
                $old_filename = $item['content'];
            }

            unset($content_array[$index]);
            break;
        }
    }
    if (!$found) {
        sendResponse(['status' => 'error', 'message' => 'Data dengan order_id tidak ditemukan'], 404);
    }

    // Jika type baru adalah image
    if ($type === 'image') {
        // Validasi format base64
        if (!preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $content)) {
            sendResponse(['status' => 'error', 'message' => 'Konten bukan base64 image valid'], 400);
        }

        $data = explode(',', $content);
        $imageData = base64_decode($data[1]);
        if ($imageData === false) {
            sendResponse(['status' => 'error', 'message' => 'Base64 gagal didecode'], 400);
        }

        // Validasi ukuran maksimum
        if (strlen($imageData) > 2 * 1024 * 1024) {
            sendResponse(['status' => 'error', 'message' => 'Ukuran gambar melebihi 2MB'], 400);
        }

        // Validasi folder
        $folder = __DIR__ . '/../../assets/img/_Artikel/';
        if (!is_dir($folder)) {
            if (!mkdir($folder, 0755, true)) {
                sendResponse(['status' => 'error', 'message' => 'Folder penyimpanan tidak tersedia dan gagal dibuat'], 500);
            }
        }

        // Simpan file baru
        $filename = uniqid('img_', true) . '.png';
        $filepath = $folder . $filename;
        if (!file_put_contents($filepath, $imageData)) {
            sendResponse(['status' => 'error', 'message' => 'Gagal menyimpan gambar'], 500);
        }

        // Hapus file lama jika ada
        if ($old_filename && file_exists($folder . $old_filename)) {
            unlink($folder . $old_filename);
        }

        // Ganti isi content menjadi nama file
        $content = $filename;
    }

    // Tambahkan data baru ke array
    $content_array[] = [
        'order_id' => $order_id_new,
        'type' => $type,
        'content' => $content
    ];

    // Urutkan berdasarkan order_id
    usort($content_array, function ($a, $b) {
        return $a['order_id'] <=> $b['order_id'];
    });

    // Normalisasi ulang order_id agar tidak duplikat
    $order = 1;
    foreach ($content_array as &$item) {
        $item['order_id'] = $order++;
    }
    unset($item);

    $new_json = json_encode($content_array, JSON_UNESCAPED_UNICODE);
    if ($new_json === false) {
        sendResponse(['status' => 'error', 'message' => 'Gagal encode JSON'], 500);
    }

    // Simpan ke database
    try {
        $Conn->beginTransaction();
        $stmt = $Conn->prepare("UPDATE blog SET content_blog = :content_blog WHERE id_blog = :id_blog");
        $stmt->bindParam(':content_blog', $new_json);
        $stmt->bindParam(':id_blog', $id_blog);
        $stmt->execute();
        $Conn->commit();

        sendResponse([
            'status' => 'success',
            'message' => 'Konten berhasil diupdate',
            'data' => [
                'id_blog' => $id_blog,
                'content_blog' => $new_json
            ]
        ]);
    } catch (PDOException $e) {
        $Conn->rollBack();
        sendResponse(['status' => 'error', 'message' => 'Gagal update: ' . $e->getMessage()], 500);
    }
?>