<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    header('Content-Type: application/json');

    // Fungsi bantu
    function sendResponse($data, $status = 200) {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validasi metode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus POST'], 405);
    }

    // Load konfigurasi
    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';
    require_once '../_Config/log_visitor.php';

    try {
        $Conn = (new Database())->getConnection();
    } catch (Exception $e) {
        sendResponse(['status' => 'error', 'message' => 'Koneksi DB gagal: ' . $e->getMessage()], 500);
    }

    // Ambil dan validasi token
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (empty($token)) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan.'], 401);
    }
    $validasi_token = validasi_x_token($Conn, $token);
    if ($validasi_token !== 'Valid') {
        sendResponse(['status' => 'error', 'message' => $validasi_token], 401);
    }

    // Ambil input JSON
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    $hari = trim($input['hari'] ?? '');
    if (empty($hari)) {
        sendResponse(['status' => 'error', 'message' => 'Nama hari wajib diisi.'], 422);
    }

    try {
        $sql = "
            SELECT 
                jd.id_jadwal,
                jd.hari,
                jd.jam,
                jd.kuota_jkn,
                jd.kuota_non_jkn,
                jd.time_max,
                d.id_dokter,
                d.nama AS nama_dokter,
                d.spesialis,
                d.foto AS foto_dokter,
                p.id_poliklinik,
                p.poliklinik AS nama_poliklinik,
                p.foto AS foto_poliklinik
            FROM jadwal_dokter jd
            JOIN dokter d ON jd.id_dokter = d.id_dokter
            JOIN poliklinik p ON jd.id_poliklinik = p.id_poliklinik
            WHERE jd.hari = :hari
            ORDER BY jd.jam ASC
        ";
        $stmt = $Conn->prepare($sql);
        $stmt->bindParam(':hari', $hari);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendResponse([
            'status' => 'success',
            'message' => 'Data jadwal dokter ditemukan.',
            'data' => $results
        ]);
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => 'Gagal mengambil data: ' . $e->getMessage()], 500);
    }
?>