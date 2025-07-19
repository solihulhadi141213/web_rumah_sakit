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

    // Hanya izinkan metode PUT
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        sendResponse(['status' => 'error', 'message' => 'Metode request tidak diizinkan. Gunakan PUT'], 405);
    }

    // Tangkap input JSON
    $input = json_decode(file_get_contents("php://input"), true);
    if (json_last_error() !== JSON_ERROR_NONE || $input === null) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Validasi field wajib
    $required_fields = ['id_laman', 'order', 'order_new', 'type', 'content'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            sendResponse(['status' => 'error', 'message' => "Field $field harus diisi"], 400);
        }
    }

    $id_laman = $input['id_laman'];
    $order = (int)$input['order'];
    $order_new = (int)$input['order_new'];
    $type = $input['type'];
    $content = $input['content'];

    // Cek apakah laman ada
    $stmt = $Conn->prepare("SELECT konten FROM laman WHERE id_laman = :id_laman");
    $stmt->bindParam(':id_laman', $id_laman);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        sendResponse(['status' => 'error', 'message' => 'Laman tidak ditemukan'], 404);
    }

    $laman = $stmt->fetch(PDO::FETCH_ASSOC);
    $existing_content = json_decode($laman['konten'], true) ?? [];

    // Cari konten yang akan diupdate
    $content_to_update = null;
    $content_index = null;
    foreach ($existing_content as $index => $item) {
        if ($item['order'] === $order) {
            $content_to_update = $item;
            $content_index = $index;
            break;
        }
    }

    if ($content_to_update === null) {
        sendResponse(['status' => 'error', 'message' => 'Konten dengan order tersebut tidak ditemukan'], 404);
    }

    // Validasi perubahan order
    if ($order_new !== $order) {
        // Cek apakah order_new sudah digunakan
        foreach ($existing_content as $item) {
            if ($item['order'] === $order_new && $item['id_konten'] !== $content_to_update['id_konten']) {
                // Tukar posisi konten
                $existing_content[$content_index]['order'] = $order_new;
                
                // Cari konten dengan order_new dan update order-nya
                foreach ($existing_content as $swap_index => $swap_item) {
                    if ($swap_item['order'] === $order_new && $swap_item['id_konten'] !== $content_to_update['id_konten']) {
                        $existing_content[$swap_index]['order'] = $order;
                        break;
                    }
                }
                break;
            }
        }
    }

    // Simpan informasi konten lama untuk cleanup
    $old_content_type = $content_to_update['type'];
    $old_content_file = ($old_content_type === 'image') ? $content_to_update['content'] : null;

    // Proses update konten berdasarkan tipe
    $updated_content = [
        'id_konten' => $content_to_update['id_konten'],
        'order' => $order_new,
        'type' => $type,
        'created_at' => $content_to_update['created_at'],
        'updated_at' => date('Y-m-d H:i:s')
    ];

    if ($type === 'html') {
        $updated_content['content'] = $content;
        
        // Jika sebelumnya image, hapus file gambar lama
        if ($old_content_type === 'image' && $old_content_file) {
            $old_file_path = '../assets/img/_Laman/' . $old_content_file;
            if (file_exists($old_file_path)) {
                unlink($old_file_path);
            }
        }
    } 
    elseif ($type === 'image') {
        // Validasi field tambahan untuk image
        $image_required_fields = ['position', 'width', 'unit'];
        foreach ($image_required_fields as $field) {
            if (empty($input[$field])) {
                sendResponse(['status' => 'error', 'message' => "Field $field harus diisi untuk konten gambar"], 400);
            }
        }

        // Validasi base64 image jika ada content baru
        if ($content !== 'keep-existing-image') {
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

            // Hapus file gambar lama jika ada
            if ($old_content_file && file_exists('../assets/img/_Laman/' . $old_content_file)) {
                unlink('../assets/img/_Laman/' . $old_content_file);
            }

            // Generate nama file unik
            $filename = bin2hex(random_bytes(16)) . '.' . $allowed_types[$image_type];
            $image_path = '../assets/img/_Laman/' . $filename;

            // Simpan gambar
            if (!file_put_contents($image_path, $image_data)) {
                sendResponse(['status' => 'error', 'message' => 'Gagal menyimpan gambar'], 500);
            }

            $updated_content['content'] = $filename;
        } else {
            // Gunakan file gambar yang sudah ada
            $updated_content['content'] = $old_content_file;
        }

        $updated_content['position'] = $input['position'];
        $updated_content['width'] = $input['width'];
        $updated_content['unit'] = $input['unit'];
        $updated_content['caption'] = $input['caption'] ?? null;
    } 
    else {
        sendResponse(['status' => 'error', 'message' => 'Tipe konten tidak valid. Gunakan "html" atau "image"'], 400);
    }

    // Update konten dalam array
    $existing_content[$content_index] = $updated_content;

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

        // Format response
        $response_data = [
            'status' => 'success',
            'message' => 'Konten berhasil diupdate',
            'content_item' => $updated_content,
            'total_contents' => count($existing_content)
        ];

        if ($type === 'image') {
            $response_data['content_item']['content_url'] = $updated_content['content'];
        }

        // Kirim response
        sendResponse($response_data);

    } catch (PDOException $e) {
        // Rollback perubahan file jika terjadi error
        if ($type === 'image' && isset($filename) && file_exists('../assets/img/_Laman/' . $filename)) {
            unlink('../assets/img/_Laman/' . $filename);
        }
        sendResponse(['status' => 'error', 'message' => 'Gagal mengupdate konten: ' . $e->getMessage()], 500);
    }
?>