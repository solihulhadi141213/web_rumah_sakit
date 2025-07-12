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

    // Ambil input
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input) respond(['status' => 'error', 'message' => 'Input JSON tidak valid'], 400);

    $title = trim($input['title'] ?? '');
    $bg_image = trim($input['bg_image'] ?? '');
    $list_content = $input['list_content'] ?? [];

    if ($title === '') respond(['status' => 'error', 'message' => 'Title tidak boleh kosong'], 422);
    if (!is_array($list_content)) respond(['status' => 'error', 'message' => 'list_content harus berupa array'], 422);

    // Validasi struktur list_content
    foreach ($list_content as $item) {
        if (!isset($item['icon'], $item['name'], $item['count'])) {
            respond(['status' => 'error', 'message' => 'Setiap item harus memiliki icon, name, dan count'], 422);
        }
    }

    $stmt = $Conn->prepare("SELECT * FROM setting WHERE setting_parameter='layout_static' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        respond(['status' => 'error', 'message' => 'Data setting tidak ditemukan'], 404);
    }

    $data = json_decode($row['setting_value'], true);
    $oldImage = $data['info_grafis']['bg_image'] ?? null;
    $newImageName = $oldImage;

    // Proses gambar jika base64 terisi
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

    // Update struktur data
    $data['info_grafis'] = [
        'title' => $title,
        'bg_image' => $newImageName,
        'list_content' => $list_content
    ];

    // Simpan ke database
    $update = $Conn->prepare("UPDATE setting SET setting_value=:val WHERE id_setting=:id");
    $update->bindValue(":val", json_encode($data, JSON_UNESCAPED_UNICODE));
    $update->bindValue(":id", $row['id_setting']);
    $update->execute();

    respond([
        'status' => 'success',
        'message' => 'Info grafis berhasil diperbarui',
        'info_grafis' => $data['info_grafis']
    ]);
?>