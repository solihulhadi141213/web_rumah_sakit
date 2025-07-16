<?php
    header("Content-Type: application/json");
    require_once "../_Config/Connection.php";
    require_once "../_Config/Function.php";

    function respond($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 1. Validasi metode POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['status' => 'error', 'message' => 'Metode harus POST'], 405);
    }

    // 2. Validasi x-token
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (!$token) respond(['status' => 'error', 'message' => 'Token tidak ditemukan'], 401);

    $Conn = (new Database())->getConnection();
    if (validasi_x_token($Conn, $token) !== 'Valid') {
        respond(['status' => 'error', 'message' => 'Token tidak valid'], 403);
    }

    // 3. Validasi struktur JSON
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        respond(['status' => 'error', 'message' => 'Format JSON harus berupa array'], 422);
    }

    // 4. Validasi masing-masing item
    $requiredKeys = ['ruang_rawat', 'kelas', 'kode_kelas', 'kapasitas', 'terisi', 'tersedia'];
    foreach ($input as $i => $item) {
        foreach ($requiredKeys as $key) {
            if (!isset($item[$key])) {
                respond(['status' => 'error', 'message' => "Data pada index $i tidak memiliki field '$key'"], 422);
            }
        }
    }

    // 5. Hapus semua data lama
    $Conn->exec("DELETE FROM ruang_rawat");

    // 6. Insert data baru
    $datetime = date('Y-m-d H:i:s');
    $stmt = $Conn->prepare("
        INSERT INTO ruang_rawat 
        (id_ruang_rawat, ruang_rawat, kelas, kode_kelas, kapasitas, terisi, tersedia, datetime_update)
        VALUES 
        (:id, :ruang_rawat, :kelas, :kode_kelas, :kapasitas, :terisi, :tersedia, :datetime)
    ");

    foreach ($input as $item) {
        $stmt->execute([
            ':id' => bin2hex(random_bytes(18)), // 36 karakter unik
            ':ruang_rawat' => $item['ruang_rawat'],
            ':kelas' => $item['kelas'],
            ':kode_kelas' => $item['kode_kelas'],
            ':kapasitas' => (int)$item['kapasitas'],
            ':terisi' => (int)$item['terisi'],
            ':tersedia' => (int)$item['tersedia'],
            ':datetime' => $datetime
        ]);
    }

    // 7. Ambil dan tampilkan data terbaru
    $dataBaru = $Conn->query("SELECT * FROM ruang_rawat ORDER BY ruang_rawat ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 8. Respon berhasil
    respond([
        'status' => 'success',
        'message' => 'Data ruang rawat berhasil diperbarui',
        'data' => $dataBaru
    ]);
?>