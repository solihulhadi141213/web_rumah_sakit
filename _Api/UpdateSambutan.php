<?php
    header("Content-Type: application/json");
    require_once "../_Config/Connection.php";
    require_once "../_Config/Function.php";

    function sendResponse($data, $status = 200) {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validasi metode
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus PUT'], 405);
    }

    // Validasi token
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (!$token) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan'], 401);
    }

    $Conn = (new Database())->getConnection();
    if (validasi_x_token($Conn, $token) !== 'Valid') {
        sendResponse(['status' => 'error', 'message' => 'Token tidak valid'], 403);
    }

    // Ambil input JSON
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input || !is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Ambil dan validasi field
    $fotoBase64 = trim($input['foto'] ?? '');
    $name = trim($input['name'] ?? '');
    $title = trim($input['title'] ?? '');
    $opening = trim($input['opening'] ?? '');
    $sambutan = trim($input['sambutan'] ?? '');
    $sub_title = trim($input['sub_title'] ?? '');

    if ($name === '' || $title === '' || $opening === '' || $sub_title === '') {
        sendResponse(['status' => 'error', 'message' => 'Field name, title, opening, dan sub_title wajib diisi'], 422);
    }

    // Ambil data layout_static
    $stmt = $Conn->prepare("SELECT * FROM setting WHERE setting_parameter='layout_static' LIMIT 1");
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) {
        sendResponse(['status' => 'error', 'message' => 'Data layout_static tidak ditemukan'], 404);
    }

    $jsonData = json_decode($data['setting_value'], true);
    $sambutanLama = $jsonData['sambutan_direktur'] ?? [];

    // Proses gambar jika ada
    $namaFileBaru = $sambutanLama['foto'] ?? null;
    if ($fotoBase64 !== '' && strpos($fotoBase64, 'data:image') === 0) {
        $matches = [];
        if (preg_match('/^data:image\/(png|jpg|jpeg);base64,/', $fotoBase64, $matches)) {
            $ext = $matches[1];
            $base64Data = preg_replace('/^data:image\/(png|jpg|jpeg);base64,/', '', $fotoBase64);
            $base64Data = str_replace(' ', '+', $base64Data);
            $binaryData = base64_decode($base64Data);

            if (strlen($binaryData) > 2 * 1024 * 1024) {
                sendResponse(['status' => 'error', 'message' => 'Ukuran gambar tidak boleh lebih dari 2MB'], 422);
            }

            $randName = bin2hex(random_bytes(18)) . '.' . $ext;
            $savePath = '../assets/img/_Direktur/' . $randName;
            if (!file_put_contents($savePath, $binaryData)) {
                sendResponse(['status' => 'error', 'message' => 'Gagal menyimpan gambar'], 500);
            }

            // Hapus file lama jika ada
            if (!empty($sambutanLama['foto'])) {
                $lamaPath = '../assets/img/_Direktur/' . $sambutanLama['foto'];
                if (file_exists($lamaPath)) {
                    @unlink($lamaPath);
                }
            }

            $namaFileBaru = $randName;
        } else {
            sendResponse(['status' => 'error', 'message' => 'Format gambar tidak valid'], 422);
        }
    }

    // Update sambutan direktur
    $jsonData['sambutan_direktur'] = [
        'foto' => $namaFileBaru,
        'name' => $name,
        'title' => $title,
        'opening' => $opening,
        'sambutan' => $sambutan,
        'sub_title' => $sub_title
    ];

    // Simpan perubahan
    $update = $Conn->prepare("UPDATE setting SET setting_value = :val WHERE id_setting = :id");
    $update->bindValue(':val', json_encode($jsonData, JSON_UNESCAPED_UNICODE));
    $update->bindValue(':id', $data['id_setting']);
    $update->execute();

    sendResponse([
        'status' => 'success',
        'message' => 'Sambutan direktur berhasil diperbarui',
        'sambutan_direktur' => $jsonData['sambutan_direktur']
    ]);
?>