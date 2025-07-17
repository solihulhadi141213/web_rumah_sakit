<?php
    header("Content-Type: application/json");
    require_once "../_Config/Connection.php";
    require_once "../_Config/Function.php";

    function respond($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        respond(['status' => 'error', 'message' => 'Metode harus PUT'], 405);
    }

    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (!$token) respond(['status' => 'error', 'message' => 'Token tidak ditemukan'], 401);

    $Conn = (new Database())->getConnection();
    if (validasi_x_token($Conn, $token) !== 'Valid') {
        respond(['status' => 'error', 'message' => 'Token tidak valid'], 403);
    }

    $input = json_decode(file_get_contents("php://input"), true);

    $id_blog = trim($input['id_blog'] ?? '');
    $order_id = (int)($input['order_id'] ?? 0);
    $order_id_new = (int)($input['order_id_new'] ?? 0);
    $type = trim($input['type'] ?? '');
    $content = trim($input['content'] ?? '');
    $position = $input['position'] ?? '';
    $width = $input['width'] ?? '';
    $unit = $input['unit'] ?? '';
    $caption = $input['caption'] ?? '';

    if ($id_blog === '' || $order_id <= 0 || $order_id_new <= 0 || $type === '' || $content === '') {
        respond(['status' => 'error', 'message' => 'Parameter tidak lengkap'], 422);
    }

    $stmt = $Conn->prepare("SELECT content_blog FROM blog WHERE id_blog = :id_blog LIMIT 1");
    $stmt->execute([':id_blog' => $id_blog]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) respond(['status' => 'error', 'message' => 'Blog tidak ditemukan'], 404);

    $content_array = json_decode($row['content_blog'], true);
    if (!is_array($content_array)) $content_array = [];

    $old_filename = '';
    $found = false;
    foreach ($content_array as $index => $item) {
        if ($item['order_id'] == $order_id) {
            $found = true;
            if (isset($item['type']) && $item['type'] === 'image' && isset($item['content'])) {
                $path = __DIR__ . '/../../assets/img/_Artikel/' . $item['content'];
                if (file_exists($path)) {
                    unlink($path);
                }
            }
            unset($content_array[$index]);
            break;
        }
    }

    if (!$found) respond(['status' => 'error', 'message' => 'Konten dengan order_id tidak ditemukan'], 404);

    if ($type === 'image') {
        if (!preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $content)) {
            respond(['status' => 'error', 'message' => 'Konten gambar tidak valid (base64)'], 400);
        }

        $data = explode(',', $content);
        $imageData = base64_decode($data[1]);
        if (!$imageData || strlen($imageData) > 2 * 1024 * 1024) {
            respond(['status' => 'error', 'message' => 'Gagal decoding atau ukuran melebihi 2MB'], 400);
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($imageData);
        if ($mime === 'image/png') {
            $ext = '.png';
        } elseif ($mime === 'image/jpeg') {
            $ext = '.jpg';
        } else {
            respond(['status' => 'error', 'message' => 'Format gambar tidak didukung'], 400);
        }

        $filename = uniqid('img_', true) . $ext;
        $folder = __DIR__ . '/../../assets/img/_Artikel/';
        if (!is_dir($folder)) mkdir($folder, 0755, true);
        $path = $folder . $filename;

        if (!file_put_contents($path, $imageData)) {
            respond(['status' => 'error', 'message' => 'Gagal menyimpan gambar'], 500);
        }

        $content = $filename;
    }

    $new_item = [
        'order_id' => $order_id_new,
        'type' => $type,
        'content' => $content
    ];

    if ($type === 'image') {
        $new_item['position'] = $position;
        $new_item['width'] = $width;
        $new_item['unit'] = $unit;
        $new_item['caption'] = $caption;
    }

    $content_array[] = $new_item;
    usort($content_array, fn($a, $b) => $a['order_id'] <=> $b['order_id']);

    foreach ($content_array as $i => &$item) {
        $item['order_id'] = $i + 1;
    }
    unset($item);

    $json = json_encode($content_array, JSON_UNESCAPED_UNICODE);
    if (!$json) respond(['status' => 'error', 'message' => 'Gagal encode JSON'], 500);

    $update = $Conn->prepare("UPDATE blog SET content_blog = :val WHERE id_blog = :id");
    $update->execute([':val' => $json, ':id' => $id_blog]);

    respond([
        'status' => 'success',
        'message' => 'Konten berhasil diperbarui',
        'content_blog' => $content_array
    ]);
?>
