<?php
    declare(strict_types=1);
    header('Content-Type: application/json');

    /**
     * Mengirim response JSON
     * @param array $data
     * @param int $statusCode
     */
    function sendResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    // Validasi HTTP Method
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        sendResponse([
            'status' => 'error',
            'message' => 'Method not allowed. Only PUT requests are accepted'
        ], 405); // 405 Method Not Allowed
    }

    try {
        // Koneksi Database
        require_once '../_Config/Connection.php';
        require_once '../_Config/Function.php';
        require_once '../_Config/log_visitor.php';

        $database = new Database();
        $conn = $database->getConnection();

        // Validasi Token
        $headers = getallheaders();
        $token = $headers['x-token'] ?? $headers['X-Token'] ?? null;

        if (empty($token)) {
            sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan.'], 401);
        }

        // Validasi token
        $stmt = $conn->prepare("SELECT * FROM api_session WHERE session_token = :token AND datetime_expired > UTC_TIMESTAMP() LIMIT 1");
        $stmt->execute([':token' => $token]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            sendResponse(['status' => 'error', 'message' => 'Token tidak valid atau kedaluwarsa.'], 401);
        }

        // Baca input
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        // Validasi Input
        $id_galeri_item = validateAndSanitizeInput($input['id_galeri_item'] ?? '');
        $id_galeri = validateAndSanitizeInput($input['id_galeri'] ?? '');
        $title_item = validateAndSanitizeInput($input['title_item'] ?? '');
        $description_item = validateAndSanitizeInput($input['description_item'] ?? '');
        $file_item_base64 = $input['file_item_base64'] ?? null;

        // Validasi wajib
        $errors = [];
        if (empty($id_galeri)) $errors[] = 'ID galeri tidak boleh kosong';
        if (empty($id_galeri_item)) $errors[] = 'ID galeri item tidak boleh kosong';
        if (empty($title_item)) $errors[] = 'Title galeri item tidak boleh kosong';
        if (strlen($title_item) > 30) $errors[] = 'Title galeri item maksimal 30 karakter';

        if (!empty($errors)) {
            sendResponse(['status' => 'error', 'message' => implode(', ', $errors)], 422);
        }

        // Cek keberadaan data
        $stmtCheck = $conn->prepare("SELECT 1 FROM galeri WHERE id_galeri = :id_galeri LIMIT 1");
        $stmtCheck->execute([':id_galeri' => $id_galeri]);
        if (!$stmtCheck->fetch()) {
            sendResponse(['status' => 'error', 'message' => 'Data galeri tidak ditemukan.'], 404);
        }

        $stmtCheck2 = $conn->prepare("SELECT file_item FROM galeri_item WHERE id_galeri_item = :id_galeri_item LIMIT 1");
        $stmtCheck2->execute([':id_galeri_item' => $id_galeri_item]);
        $existingItem = $stmtCheck2->fetch(PDO::FETCH_ASSOC);

        if (!$existingItem) {
            sendResponse(['status' => 'error', 'message' => 'Data galeri item tidak ditemukan.'], 404);
        }

        $filename = $existingItem['file_item']; // Default file lama

        // Proses gambar baru jika ada
        if (!empty($file_item_base64)) {
            $decoded = base64_decode($file_item_base64, true);
            if (!$decoded) {
                sendResponse(['status' => 'error', 'message' => 'Gagal mendekode gambar base64.'], 422);
            }

            $imageInfo = @getimagesizefromstring($decoded);
            if ($imageInfo === false) {
                sendResponse(['status' => 'error', 'message' => 'Data base64 bukan gambar yang valid.'], 422);
            }

            // Generate nama file baru
            $filename = GenerateUuid(36) . '.png';
            $savePath = '../assets/img/_Galeri/' . $filename;

            if (!file_put_contents($savePath, $decoded)) {
                sendResponse(['status' => 'error', 'message' => 'Gagal menyimpan file gambar baru.'], 500);
            }

            // Hapus file lama
            $oldFilePath = '../assets/img/_Galeri/' . $existingItem['file_item'];
            if ($existingItem['file_item'] !== 'default.png' && file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        }

        // Update database
        $date_item = date('Y-m-d H:i:s');
        $stmtUpdate = $conn->prepare("UPDATE galeri_item SET 
            title_item = :title_item, 
            description_item = :description_item, 
            file_item = :file_item, 
            date_item = :date_item 
            WHERE id_galeri_item = :id_galeri_item");

        $success = $stmtUpdate->execute([
            ':id_galeri_item' => $id_galeri_item,
            ':title_item' => $title_item,
            ':description_item' => $description_item,
            ':file_item' => $filename,
            ':date_item' => $date_item
        ]);

        if ($success) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $base_url = $protocol . $_SERVER['HTTP_HOST'] . str_replace('/_Api', '', dirname($_SERVER['SCRIPT_NAME']));

            sendResponse([
                'status' => 'success',
                'message' => 'Galeri item berhasil diperbarui.',
                'data' => [
                    'id_galeri_item' => $id_galeri_item,
                    'id_galeri' => $id_galeri,
                    'title_item' => $title_item,
                    'description_item' => $description_item,
                    'file_item' => "$base_url/image_proxy.php?segment=Galeri&image_name=$filename",
                    'date_item' => $date_item
                ]
            ]);
        } else {
            sendResponse(['status' => 'error', 'message' => 'Gagal memperbarui data galeri.'], 500);
        }

    } catch (Throwable $e) {
        sendResponse([
            'status' => 'error',
            'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
        ], 500);
    }