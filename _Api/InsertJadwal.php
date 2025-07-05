<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    header('Content-Type: application/json');

    // Fungsi respons
    function sendResponse($data, $status = 200) {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validasi metode POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus POST'], 405);
    }

    // Include konfigurasi
    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';
    require_once '../_Config/log_visitor.php';

    try {
        $Conn = (new Database())->getConnection();
    } catch (Exception $e) {
        sendResponse(['status' => 'error', 'message' => 'Koneksi DB gagal: ' . $e->getMessage()], 500);
    }

    // Validasi token
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (empty($token)) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan.'], 401);
    }
    $validasi_token = validasi_x_token($Conn, $token);
    if ($validasi_token !== 'Valid') {
        sendResponse(['status' => 'error', 'message' => $validasi_token], 401);
    }

    // Ambil dan validasi input
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    $id_dokter      = $input['id_dokter'] ?? null;
    $id_poliklinik  = $input['id_poliklinik'] ?? null;
    $hari           = trim($input['hari'] ?? '');
    $jam            = trim($input['jam'] ?? '');
    $kuota_non_jkn  = $input['kuota_non_jkn'] ?? null;
    $kuota_jkn      = $input['kuota_jkn'] ?? null;
    $time_max       = $input['time_max'] ?? null;

    $errors = [];

    // Validasi ID dokter
    if (empty($id_dokter)) {
        $errors[] = "ID dokter wajib diisi.";
    } else {
        $stmt = $Conn->prepare("SELECT COUNT(*) FROM dokter WHERE id_dokter = :id");
        $stmt->bindParam(':id', $id_dokter, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $errors[] = "ID dokter tidak terdaftar.";
        }
    }

    // Validasi ID poliklinik
    if (empty($id_poliklinik)) {
        $errors[] = "ID poliklinik wajib diisi.";
    } else {
        $stmt = $Conn->prepare("SELECT COUNT(*) FROM poliklinik WHERE id_poliklinik = :id");
        $stmt->bindParam(':id', $id_poliklinik, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $errors[] = "ID poliklinik tidak terdaftar.";
        }
    }

    // Validasi hari
    $allowed_days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    if (empty($hari) || !in_array($hari, $allowed_days)) {
        $errors[] = "Hari wajib diisi dan harus berupa nama hari dalam Bahasa Indonesia.";
    }

    // Validasi jam (format HH:MM)
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $jam)) {
        $errors[] = "Jam wajib diisi dengan format valid, contoh: 08:00 atau 20:00.";
    }

    // Validasi kuota
    if (!is_numeric($kuota_non_jkn)) {
        $errors[] = "Kuota Non-JKN wajib diisi dan berupa angka.";
    }
    if (!is_numeric($kuota_jkn)) {
        $errors[] = "Kuota JKN wajib diisi dan berupa angka.";
    }

    // Validasi time_max
    if (!is_numeric($time_max) || intval($time_max) <= 0) {
        $errors[] = "Time max wajib diisi dengan angka satuan menit.";
    }

    if (!empty($errors)) {
        sendResponse(['status' => 'error', 'message' => implode(" ", $errors)], 422);
    }

    // Proses insert
    try {
        $stmt = $Conn->prepare("
            INSERT INTO jadwal_dokter (
                id_dokter, id_poliklinik, hari, jam, kuota_non_jkn, kuota_jkn, time_max, last_update
            ) VALUES (
                :id_dokter, :id_poliklinik, :hari, :jam, :kuota_non_jkn, :kuota_jkn, :time_max, NOW()
            )
        ");
        $stmt->bindParam(':id_dokter', $id_dokter, PDO::PARAM_INT);
        $stmt->bindParam(':id_poliklinik', $id_poliklinik, PDO::PARAM_INT);
        $stmt->bindParam(':hari', $hari);
        $stmt->bindParam(':jam', $jam);
        $stmt->bindParam(':kuota_non_jkn', $kuota_non_jkn, PDO::PARAM_INT);
        $stmt->bindParam(':kuota_jkn', $kuota_jkn, PDO::PARAM_INT);
        $stmt->bindParam(':time_max', $time_max, PDO::PARAM_INT);
        $stmt->execute();

        sendResponse(['status' => 'success', 'message' => 'Jadwal dokter berhasil ditambahkan.']);
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => 'Gagal menyimpan jadwal: ' . $e->getMessage()], 500);
    }
?>