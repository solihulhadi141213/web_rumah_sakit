<?php
    declare(strict_types=1);
    header('Content-Type: application/json');

    /**
     * Mengirim response JSON dengan status code
     * 
     * @param array $data Data yang akan dikirim sebagai response
     * @param int $statusCode HTTP status code
     */
    function sendResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
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

        // Validasi token dari tabel api_session
        $stmt = $conn->prepare("SELECT * FROM api_session WHERE session_token = :token AND datetime_expired > UTC_TIMESTAMP() LIMIT 1");
        $stmt->execute([':token' => $token]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            sendResponse(['status' => 'error', 'message' => 'Token tidak valid atau kedaluwarsa.'], 401);
        }

        // Validasi Input
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id_galeri = validateAndSanitizeInput($input['id_galeri'] ?? '');

        if (empty($id_galeri)) {
            sendResponse(['status' => 'error', 'message' => 'ID galeri tidak boleh kosong.'], 422);
        }

        // Mulai Transaction
        $conn->beginTransaction();

        try {
            // 1. Hapus file-file item galeri
            $stmtItems = $conn->prepare("SELECT file_item FROM galeri_item WHERE id_galeri = :id_galeri");
            $stmtItems->execute([':id_galeri' => $id_galeri]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $item) {
                $filePath = realpath('../assets/img/_Galeri/' . $item['file_item']);
                
                if ($filePath && is_file($filePath) && is_writable($filePath)) {
                    if (!unlink($filePath)) {
                        error_log("Failed to delete file: " . $filePath);
                    }
                }
            }

            // 2. Hapus record dari galeri_item
            $stmtDeleteItems = $conn->prepare("DELETE FROM galeri_item WHERE id_galeri = :id_galeri");
            $stmtDeleteItems->execute([':id_galeri' => $id_galeri]);

            // 3. Hapus file cover galeri
            $stmtGaleri = $conn->prepare("SELECT cover FROM galeri WHERE id_galeri = :id_galeri LIMIT 1");
            $stmtGaleri->execute([':id_galeri' => $id_galeri]);
            $galeri = $stmtGaleri->fetch(PDO::FETCH_ASSOC);

            if ($galeri && $galeri['cover'] !== 'default.png') {
                $coverPath = realpath('../assets/img/_Galeri/' . $galeri['cover']);
                
                if ($coverPath && is_file($coverPath) && is_writable($coverPath)) {
                    if (!unlink($coverPath)) {
                        error_log("Failed to delete cover: " . $coverPath);
                    }
                }
            }

            // 4. Hapus record galeri
            $stmtDeleteGaleri = $conn->prepare("DELETE FROM galeri WHERE id_galeri = :id_galeri");
            $stmtDeleteGaleri->execute([':id_galeri' => $id_galeri]);

            // Commit transaction jika semua berhasil
            $conn->commit();

            sendResponse([
                'status' => 'success',
                'message' => 'Galeri dan semua item berhasil dihapus.',
                'data' => [
                    'id_galeri' => $id_galeri,
                    'deleted_items' => count($items)
                ]
            ]);

        } catch (PDOException $e) {
            $conn->rollBack();
            sendResponse(['status' => 'error', 'message' => 'Gagal menghapus data: ' . $e->getMessage()], 500);
        }

    } catch (Throwable $e) {
        sendResponse(['status' => 'error', 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()], 500);
    }