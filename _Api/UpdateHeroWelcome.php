<?php
    header("Content-Type: application/json");
    require_once "../_Config/Connection.php";
    require_once "../_Config/Function.php";

    function respond($data, $status = 200) {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validasi metode
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        respond(['status' => 'error', 'message' => 'Metode harus PUT'], 405);
    }

    // Validasi token
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (!$token) respond(['status' => 'error', 'message' => 'Token tidak ditemukan'], 401);

    $Conn = (new Database())->getConnection();
    if (validasi_x_token($Conn, $token) !== 'Valid') {
        respond(['status' => 'error', 'message' => 'Token tidak valid'], 403);
    }

    // Ambil input JSON
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input || !is_array($input)) {
        respond(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Ambil dan validasi data
    $imageBase64 = trim($input['image'] ?? '');
    $title = trim($input['title'] ?? '');
    $sub_title = trim($input['sub_title'] ?? '');

    if ($title === '' || $sub_title === '') {
        respond(['status' => 'error', 'message' => 'Title dan sub_title wajib diisi'], 422);
    }

    // Ambil layout_static
    $stmt = $Conn->prepare("SELECT * FROM setting WHERE setting_parameter='layout_static' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        respond(['status' => 'error', 'message' => 'Data setting tidak ditemukan'], 404);
    }

    $data = json_decode($row['setting_value'], true);
    $oldImage = $data['hero']['hero_welcome']['image'] ?? null;
    $newImageName = $oldImage;

    // Proses image jika ada
    if ($imageBase64 && strpos($imageBase64, 'data:image') === 0) {
        if (preg_match('/^data:image\/(png|jpg|jpeg);base64,/', $imageBase64, $matches)) {
            $ext = $matches[1];
            $base64 = preg_replace('/^data:image\/(png|jpg|jpeg);base64,/', '', $imageBase64);
            $binary = base64_decode($base64);

            if (strlen($binary) > 2 * 1024 * 1024) {
                respond(['status' => 'error', 'message' => 'Ukuran gambar tidak boleh lebih dari 2MB'], 422);
            }

            $newImageName = bin2hex(random_bytes(18)) . '.' . $ext;
            $path = "../assets/img/_component/" . $newImageName;

            if (!file_put_contents($path, $binary)) {
                respond(['status' => 'error', 'message' => 'Gagal menyimpan gambar'], 500);
            }

            // Hapus gambar lama
            if ($oldImage && file_exists("../assets/img/_component/" . $oldImage)) {
                @unlink("../assets/img/_component/" . $oldImage);
            }
        } else {
            respond(['status' => 'error', 'message' => 'Format gambar tidak valid'], 422);
        }
    }

    // Update data
    $data['hero']['hero_welcome'] = [
        'image' => $newImageName,
        'title' => $title,
        'sub_title' => $sub_title
    ];

    // Simpan
    $update = $Conn->prepare("UPDATE setting SET setting_value=:val WHERE id_setting=:id");
    $update->bindValue(":val", json_encode($data, JSON_UNESCAPED_UNICODE));
    $update->bindValue(":id", $row['id_setting']);
    $update->execute();

    respond([
        'status' => 'success',
        'message' => 'Hero Welcome berhasil diperbarui',
        'hero_welcome' => $data['hero']['hero_welcome']
    ]);
?>