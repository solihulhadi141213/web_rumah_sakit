<?php
    header("Content-Type: application/json");
    require_once "../_Config/Connection.php";
    require_once "../_Config/Function.php";

    function respond($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    function generateRandomString($length = 36) {
        return bin2hex(random_bytes($length / 2));
    }

    function simpanGambarBase64($base64, $folder, $prefix = "img_") {
        $exploded = explode(',', $base64);
        if (count($exploded) != 2) return null;

        $decoded = base64_decode($exploded[1]);
        if (!$decoded) return null;

        if (strlen($decoded) > 2 * 1024 * 1024) return "File gambar tidak boleh lebih dari 2 MB";

        preg_match('/^data:image\/(\w+);base64$/', $exploded[0], $type);
        if (!isset($type[1])) return "Format gambar tidak valid";

        $ext = strtolower($type[1]);
        $filename = $prefix . generateRandomString() . '.' . $ext;
        $path = $folder . '/' . $filename;

        if (!file_put_contents($path, $decoded)) return "Gagal menyimpan file gambar";

        return $filename;
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
    $id = trim($input['id'] ?? '');
    $description = $input['description'] ?? [];
    if ($id === '' || !is_array($description)) {
        respond(['status' => 'error', 'message' => 'ID dan description wajib diisi'], 422);
    }

    $stmt = $Conn->prepare("SELECT * FROM setting WHERE setting_parameter='layout_static' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) respond(['status' => 'error', 'message' => 'Data layout_static tidak ditemukan'], 404);

    $data = json_decode($row['setting_value'], true);
    $list = $data['unit_instalasi']['data_list'] ?? [];
    $found = false;

    foreach ($list as &$unit) {
        if ($unit['id'] === $id) {
            $found = true;
            $existing = $unit['description'] ?? [];
            $new_desc = [];
            $folder = "../assets/img/_UnitInstalasi";
            if (!is_dir($folder)) mkdir($folder, 0755, true);

            foreach ($description as $desc) {
                $rowNum = (int) ($desc['row'] ?? 0);
                $type = trim($desc['type'] ?? '');
                $content = trim($desc['content'] ?? '');

                if ($type === 'image' && preg_match('/^data:image\/(\w+);base64,/', $content)) {
                    // Hapus file lama jika ada pada row yang sama
                    foreach ($existing as $old) {
                        if ($old['row'] === $rowNum && $old['type'] === 'image') {
                            $oldPath = $folder . '/' . $old['content'];
                            if (file_exists($oldPath)) unlink($oldPath);
                        }
                    }
                    $save = simpanGambarBase64($content, $folder);
                    if (is_string($save) && str_starts_with($save, 'File')) {
                        respond(['status' => 'error', 'message' => $save], 422);
                    }
                    $content = $save;
                }

                $new_desc[] = [
                    'row' => $rowNum,
                    'type' => $type,
                    'content' => $content
                ];
            }

            $unit['description'] = $new_desc;
            break;
        }
    }

    if (!$found) respond(['status' => 'error', 'message' => 'ID unit tidak ditemukan'], 404);

    $data['unit_instalasi']['data_list'] = $list;
    $update = $Conn->prepare("UPDATE setting SET setting_value=:val WHERE id_setting=:id");
    $update->bindValue(":val", json_encode($data, JSON_UNESCAPED_UNICODE));
    $update->bindValue(":id", $row['id_setting']);
    $update->execute();

    respond(['status' => 'success', 'message' => 'Deskripsi berhasil diperbarui']);
?>