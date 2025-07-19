<?php
    header('Content-Type: application/json');

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

    // Validasi Token
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (empty($token)) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan.'], 401);
    }

    $validasi_token = validasi_x_token($Conn, $token);
    if ($validasi_token !== "Valid") {
        sendResponse(['status' => 'error', 'message' => $validasi_token], 401);
    }

    // Hanya izinkan metode POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(['status' => 'error', 'message' => 'Metode request tidak diizinkan. Gunakan POST'], 405);
    }

    // Tangkap input JSON
    $input = json_decode(file_get_contents("php://input"), true);
    if (json_last_error() !== JSON_ERROR_NONE || $input === null) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Validasi field wajib
    if (empty($input['id_laman'])) {
        sendResponse(['status' => 'error', 'message' => 'ID laman harus diisi'], 400);
    }
    if (!isset($input['order']) || !is_numeric($input['order'])) {
        sendResponse(['status' => 'error', 'message' => 'Order harus berupa angka'], 400);
    }
    if (empty($input['type'])) {
        sendResponse(['status' => 'error', 'message' => 'Tipe konten harus diisi'], 400);
    }
    if (empty($input['content'])) {
        sendResponse(['status' => 'error', 'message' => 'Konten harus diisi'], 400);
    }

    $id_laman = $input['id_laman'];
    $order = (int)$input['order'];
    $type = $input['type'];
    $content = $input['content'];

    // Cek apakah laman ada
    $check_stmt = $Conn->prepare("SELECT konten FROM laman WHERE id_laman = :id_laman");
    $check_stmt->bindParam(':id_laman', $id_laman);
    $check_stmt->execute();

    if ($check_stmt->rowCount() === 0) {
        sendResponse(['status' => 'error', 'message' => 'Laman tidak ditemukan'], 404);
    }

    $laman = $check_stmt->fetch(PDO::FETCH_ASSOC);
    $existing_content = json_decode($laman['konten'], true) ?? [];

    // Validasi order tidak boleh duplikat
    foreach ($existing_content as $item) {
        if ($item['order'] === $order) {
            sendResponse([
                'status' => 'error', 
                'message' => 'Order sudah digunakan oleh konten lain',
                'existing_content' => $item
            ], 400);
        }
    }

    // Generate ID konten unik
    $id_konten = bin2hex(random_bytes(16));

    // Proses konten berdasarkan tipe
    $new_content_item = [
        'id_konten' => $id_konten,
        'order' => $order,
        'type' => $type,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    if ($type === 'html') {
        $new_content_item['content'] = $content;
    } 
    elseif ($type === 'image') {
        // Validasi field tambahan untuk image
        $required_fields = ['position', 'width', 'unit'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                sendResponse(['status' => 'error', 'message' => "Field $field harus diisi untuk konten gambar"], 400);
            }
        }

        // Validasi base64 image
        if (!preg_match('/^data:image\/(\w+);base64,/', $content, $matches)) {
            sendResponse(['status' => 'error', 'message' => 'Format gambar tidak valid. Gunakan data URI dengan format base64'], 400);
        }

        $image_data = substr($content, strpos($content, ',') + 1);
        $image_data = base64_decode($image_data);
        if ($image_data === false) {
            sendResponse(['status' => 'error', 'message' => 'Gagal decode gambar'], 400);
        }

        // Validasi ukuran gambar (max 2MB)
        if (strlen($image_data) > 2 * 1024 * 1024) {
            sendResponse(['status' => 'error', 'message' => 'Ukuran gambar tidak boleh lebih dari 2MB'], 400);
        }

        // Tentukan ekstensi file
        $image_type = strtolower($matches[1]);
        $allowed_types = ['jpeg' => 'jpg', 'png' => 'png', 'gif' => 'gif'];
        if (!array_key_exists($image_type, $allowed_types)) {
            sendResponse(['status' => 'error', 'message' => 'Tipe gambar tidak didukung. Gunakan JPEG, PNG, atau GIF'], 400);
        }

        // Generate nama file unik
        $filename = bin2hex(random_bytes(16)) . '.' . $allowed_types[$image_type];
        $image_path = '../assets/img/_Laman/' . $filename;

        // Simpan gambar
        if (!file_put_contents($image_path, $image_data)) {
            sendResponse(['status' => 'error', 'message' => 'Gagal menyimpan gambar'], 500);
        }

        // Tambahkan data gambar ke konten
        $new_content_item['content'] = $filename;
        $new_content_item['position'] = $input['position'];
        $new_content_item['width'] = $input['width'];
        $new_content_item['unit'] = $input['unit'];
        $new_content_item['caption'] = $input['caption'] ?? null;
    } 
    else {
        sendResponse(['status' => 'error', 'message' => 'Tipe konten tidak valid. Gunakan "html" atau "image"'], 400);
    }

    // Tambahkan konten baru ke array existing content
    $existing_content[] = $new_content_item;

    // Urutkan konten berdasarkan order
    usort($existing_content, function($a, $b) {
        return $a['order'] <=> $b['order'];
    });

    // Konversi ke JSON untuk update
    $konten_json = json_encode($existing_content, JSON_UNESCAPED_SLASHES);

    try {
        // Update konten di database
        $update_stmt = $Conn->prepare("UPDATE laman SET konten = :konten WHERE id_laman = :id_laman");
        $update_stmt->bindParam(':konten', $konten_json);
        $update_stmt->bindParam(':id_laman', $id_laman);
        $update_stmt->execute();

        // Format response untuk gambar
        if ($type === 'image') {
            $new_content_item['content_url'] = BASE_URL . '/assets/img/_Laman/' . $new_content_item['content'];
        }

        // Kirim response
        sendResponse([
            'status' => 'success',
            'message' => 'Konten berhasil ditambahkan',
            'content_item' => $new_content_item,
            'total_contents' => count($existing_content),
            'current_order' => $order
        ]);

    } catch (PDOException $e) {
        // Hapus gambar yang sudah disimpan jika terjadi error
        if ($type === 'image' && isset($filename) && file_exists($image_path)) {
            unlink($image_path);
        }
        sendResponse(['status' => 'error', 'message' => 'Gagal menyimpan konten: ' . $e->getMessage()], 500);
    }
?>