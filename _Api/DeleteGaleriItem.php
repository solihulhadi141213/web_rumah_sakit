<?php
    declare(strict_types=1);
    header('Content-Type: application/json');

    /**
     * Send JSON response with HTTP status code
     */
    function sendResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    // Validate HTTP Method
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        sendResponse([
            'status' => 'error',
            'message' => 'Method not allowed. Only DELETE requests are accepted'
        ], 405);
    }

    try {
        // Database Connection
        require_once '../_Config/Connection.php';
        require_once '../_Config/Function.php';
        require_once '../_Config/log_visitor.php';

        $database = new Database();
        $conn = $database->getConnection();

        // Token Validation
        $headers = getallheaders();
        $token = $headers['x-token'] ?? $headers['X-Token'] ?? null;

        if (empty($token)) {
            sendResponse(['status' => 'error', 'message' => 'Authorization token missing'], 401);
        }

        // Verify Token
        $stmt = $conn->prepare("SELECT 1 FROM api_session WHERE session_token = :token AND datetime_expired > UTC_TIMESTAMP() LIMIT 1");
        $stmt->execute([':token' => $token]);
        
        if (!$stmt->fetch()) {
            sendResponse(['status' => 'error', 'message' => 'Invalid or expired token'], 401);
        }

        // Get Input Data
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id_galeri = validateAndSanitizeInput($input['id_galeri'] ?? '');
        $id_galeri_item = validateAndSanitizeInput($input['id_galeri_item'] ?? '');

        // Input Validation
        $errors = [];
        if (empty($id_galeri)) $errors[] = 'Gallery ID is required';
        if (empty($id_galeri_item)) $errors[] = 'Gallery item ID is required';

        if (!empty($errors)) {
            sendResponse(['status' => 'error', 'message' => implode(', ', $errors)], 422);
        }

        // Start Transaction
        $conn->beginTransaction();

        try {
            // Get file info from database
            $stmt = $conn->prepare("SELECT file_item FROM galeri_item WHERE id_galeri_item = :id AND id_galeri = :gallery_id LIMIT 1");
            $stmt->execute([':id' => $id_galeri_item, ':gallery_id' => $id_galeri]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                throw new RuntimeException('Gallery item not found');
            }

            // Prepare file path
            $filePath = realpath('../assets/img/_Galeri/' . $item['file_item']);
            $fileDeleted = false;

            // Delete physical file if exists
            if ($filePath && is_file($filePath) && is_writable($filePath)) {
                $fileDeleted = unlink($filePath);
                
                if (!$fileDeleted) {
                    error_log("Failed to delete file: {$filePath}");
                    throw new RuntimeException('Failed to delete attached file');
                }
            }

            // Delete database record
            $stmt = $conn->prepare("DELETE FROM galeri_item WHERE id_galeri_item = :id");
            $stmt->execute([':id' => $id_galeri_item]);

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('No records deleted');
            }

            // Commit transaction
            $conn->commit();

            sendResponse([
                'status' => 'success',
                'message' => 'Gallery item deleted successfully',
                'data' => [
                    'gallery_id' => $id_galeri,
                    'item_id' => $id_galeri_item,
                    'file_deleted' => $fileDeleted,
                    'file_name' => $item['file_item']
                ]
            ]);

        } catch (Throwable $e) {
            $conn->rollBack();
            sendResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }

    } catch (Throwable $e) {
        sendResponse([
            'status' => 'error',
            'message' => 'System error: ' . $e->getMessage()
        ], 500);
    }