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

    // Hanya izinkan metode DELETE
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        sendResponse(['status' => 'error', 'message' => 'Metode request tidak diizinkan. Gunakan DELETE'], 405);
    }

    // Tangkap input JSON
    $input = json_decode(file_get_contents("php://input"), true);

    // Validasi format JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error_message = 'Format JSON tidak valid';
        switch (json_last_error()) {
            case JSON_ERROR_DEPTH:
                $error_message = 'Kedalaman JSON melebihi batas';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $error_message = 'JSON underflow atau mode mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $error_message = 'Karakter kontrol tidak terduga ditemukan';
                break;
            case JSON_ERROR_SYNTAX:
                $error_message = 'Syntax error, format JSON salah';
                break;
            case JSON_ERROR_UTF8:
                $error_message = 'Karakter UTF-8 tidak valid';
                break;
            default:
                $error_message = 'Error JSON tidak diketahui';
        }
        sendResponse(['status' => 'error', 'message' => $error_message], 400);
    }

    // Validasi jika JSON kosong
    if ($input === null) {
        sendResponse(['status' => 'error', 'message' => 'Request body tidak boleh kosong'], 400);
    }

    // Validasi field wajib
    $required_fields = ['id_laman', 'id_konten'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            sendResponse(['status' => 'error', 'message' => "Field $field harus diisi"], 400);
        }
    }

    $id_laman = $input['id_laman'];
    $id_konten = $input['id_konten'];

    try {
        // Mulai transaksi
        $Conn->beginTransaction();

        // 1. Ambil data laman
        $stmt = $Conn->prepare("SELECT konten FROM laman WHERE id_laman = :id_laman");
        $stmt->bindParam(':id_laman', $id_laman);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            sendResponse(['status' => 'error', 'message' => 'Laman tidak ditemukan'], 404);
        }

        $laman = $stmt->fetch(PDO::FETCH_ASSOC);
        $existing_content = json_decode($laman['konten'], true);

        // 2. Cari konten yang akan dihapus
        $content_to_delete = null;
        $content_index = null;
        $is_image = false;
        $image_file = null;

        foreach ($existing_content as $index => $item) {
            if (isset($item['id_konten']) && $item['id_konten'] === $id_konten) {
                $content_to_delete = $item;
                $content_index = $index;
                $is_image = (isset($item['type']) && $item['type'] === 'image');
                $image_file = $is_image && isset($item['content']) ? $item['content'] : null;
                break;
            }
        }

        if ($content_to_delete === null) {
            sendResponse(['status' => 'error', 'message' => 'Konten tidak ditemukan dalam laman ini'], 404);
        }

        // 3. Hapus konten dari array
        array_splice($existing_content, $content_index, 1);

        // 4. Konversi ke JSON untuk update
        $updated_konten = json_encode($existing_content, JSON_UNESCAPED_SLASHES);

        // Update konten di database
        $update_stmt = $Conn->prepare("UPDATE laman SET konten = :konten WHERE id_laman = :id_laman");
        $update_stmt->bindParam(':konten', $updated_konten);
        $update_stmt->bindParam(':id_laman', $id_laman);
        $update_stmt->execute();

        // 5. Hapus file gambar jika konten adalah image
        if ($is_image && $image_file) {
            $image_path = '../assets/img/_Laman/' . $image_file;
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        // Commit transaksi
        $Conn->commit();

        // Format response untuk gambar
        if ($is_image) {
            $content_to_delete['content_url'] = BASE_URL . '/assets/img/_Laman/' . $image_file;
        }

        // Kirim response
        sendResponse([
            'status' => 'success',
            'message' => 'Konten berhasil dihapus',
            'deleted_content' => $content_to_delete,
            'remaining_contents' => count($existing_content),
            'laman_id' => $id_laman
        ]);

    } catch (PDOException $e) {
        // Rollback transaksi jika terjadi error
        $Conn->rollBack();
        sendResponse(['status' => 'error', 'message' => 'Gagal menghapus konten: ' . $e->getMessage()], 500);
    } catch (Exception $e) {
        // Rollback transaksi jika terjadi error
        $Conn->rollBack();
        sendResponse(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
    }
?>