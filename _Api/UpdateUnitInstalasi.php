<?php
    header("Content-Type: application/json");
    require_once "../_Config/Connection.php";
    require_once "../_Config/Function.php";

    function respond($data, $status = 200) {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Pastikan metode PUT
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
    if (!$input) respond(['status' => 'error', 'message' => 'Input tidak valid'], 400);

    $title = trim($input['title'] ?? '');
    $bg_image = trim($input['bg_image'] ?? '');

    if ($title === '') {
        respond(['status' => 'error', 'message' => 'Title tidak boleh kosong'], 422);
    }

    // Ambil data layout_static
    $stmt = $Conn->prepare("SELECT * FROM setting WHERE setting_parameter='layout_static' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        respond(['status' => 'error', 'message' => 'Data layout_static tidak ditemukan'], 404);
    }

    $data = json_decode($row['setting_value'], true);
    $oldImage = $data['unit_instalasi']['bg_image'] ?? null;
    $newImageName = $oldImage;

    // Jika bg_image diisi base64, lakukan penyimpanan
    if ($bg_image && strpos($bg_image, 'data:image') === 0) {
        if (preg_match('/^data:image\/(png|jpg|jpeg);base64,/', $bg_image, $matches)) {
            $ext = $matches[1];
            $base64 = preg_replace('/^data:image\/(png|jpg|jpeg);base64,/', '', $bg_image);
            $binary = base64_decode($base64);

            if (strlen($binary) > 2 * 1024 * 1024) {
                respond(['status' => 'error', 'message' => 'Ukuran gambar tidak boleh lebih dari 2MB'], 422);
            }

            $newImageName = bin2hex(random_bytes(18)) . '.' . $ext;
            $path = "../assets/img/_Ui/" . $newImageName;

            if (!file_put_contents($path, $binary)) {
                respond(['status' => 'error', 'message' => 'Gagal menyimpan gambar'], 500);
            }

            if ($oldImage && file_exists("../assets/img/_Ui/" . $oldImage)) {
                @unlink("../assets/img/_Ui/" . $oldImage);
            }
        } else {
            respond(['status' => 'error', 'message' => 'Format gambar tidak valid'], 422);
        }
    }

    // Update data
    $data['unit_instalasi']['title'] = $title;
    $data['unit_instalasi']['bg_image'] = $newImageName;

    // Simpan ke DB
    $update = $Conn->prepare("UPDATE setting SET setting_value=:val WHERE id_setting=:id");
    $update->bindValue(":val", json_encode($data, JSON_UNESCAPED_UNICODE));
    $update->bindValue(":id", $row['id_setting']);
    $update->execute();

    respond([
        'status' => 'success',
        'message' => 'Data unit & instalasi berhasil diperbarui',
        'unit_instalasi' => $data['unit_instalasi']
    ]);
?>