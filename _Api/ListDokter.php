<?php
    // Aktifkan error reporting untuk debugging
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    header('Content-Type: application/json');

    // Fungsi response JSON
    function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validasi metode POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus POST'], 405);
    }

    // Koneksi dan fungsi tambahan
    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';
    require_once '../_Config/log_visitor.php';

    // Validasi koneksi
    try {
        $Conn = (new Database())->getConnection();
    } catch (Exception $e) {
        sendResponse(['status' => 'error', 'message' => 'Koneksi DB gagal: ' . $e->getMessage()], 500);
    }

    // Ambil token dari header
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
    if (empty($token)) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan.'], 401);
    }

    // Validasi token
    $validasi_token = validasi_x_token($Conn, $token);
    if ($validasi_token !== "Valid") {
        sendResponse(['status' => 'error', 'message' => $validasi_token], 401);
    }

    // Ambil data JSON dari body
    $rawInput = file_get_contents("php://input");
    $input = json_decode($rawInput, true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Ambil parameter request
    $order_by = in_array($input['order_by'] ?? '', ['id_dokter','kode','nama','spesialis','last_update']) ? $input['order_by'] : 'id_dokter';
    $short_by = strtoupper($input['short_by'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
    $keyword_by = in_array($input['keyword_by'] ?? '', ['kode','nama','spesialis']) ? $input['keyword_by'] : '';
    $keyword = trim($input['keyword'] ?? '');
    $limit = max(1, (int)($input['limit'] ?? 10));
    $page = max(1, (int)($input['page'] ?? 1));
    $offset = ($page - 1) * $limit;

    // Filter pencarian
    $where = "";
    $params = [];
    if ($keyword_by !== '' && $keyword !== '') {
        $where = "WHERE $keyword_by LIKE :keyword";
        $params[':keyword'] = '%' . $keyword . '%';
    }

    // Hitung total data
    $count_query = "SELECT COUNT(*) as total FROM dokter $where";
    $count_stmt = $Conn->prepare($count_query);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total_data = (int)$count_stmt->fetchColumn();
    $total_pages = (int)ceil($total_data / $limit);

    // Ambil data dokter
    $data_query = "SELECT * FROM dokter $where ORDER BY $order_by $short_by LIMIT :limit OFFSET :offset";
    $data_stmt = $Conn->prepare($data_query);
    foreach ($params as $key => $value) {
        $data_stmt->bindValue($key, $value);
    }
    $data_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $data_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    try {
        $data_stmt->execute();
        $results = $data_stmt->fetchAll(PDO::FETCH_ASSOC);

        $final = [];
        foreach ($results as $dokter) {
            $jadwal_stmt = $Conn->prepare("
                SELECT jd.id_jadwal, jd.hari, jd.jam, jd.kuota_non_jkn, jd.kuota_jkn, jd.time_max, jd.last_update,
                    p.poliklinik
                FROM jadwal_dokter jd
                LEFT JOIN poliklinik p ON jd.id_poliklinik = p.id_poliklinik
                WHERE jd.id_dokter = :id_dokter
                ORDER BY jd.hari ASC, jd.jam ASC
            ");
            $jadwal_stmt->bindParam(':id_dokter', $dokter['id_dokter'], PDO::PARAM_INT);
            $jadwal_stmt->execute();
            $jadwal = $jadwal_stmt->fetchAll(PDO::FETCH_ASSOC);

            $final[] = [
                'id_dokter' => (int)$dokter['id_dokter'],
                'kode' => $dokter['kode'],
                'nama' => $dokter['nama'],
                'spesialis' => $dokter['spesialis'],
                'foto' => $dokter['foto'],
                'last_update' => $dokter['last_update'],
                'jadwal' => $jadwal
            ];
        }

        sendResponse([
            'status' => 'success',
            'message' => 'Daftar dokter ditemukan',
            'page' => $page,
            'limit' => $limit,
            'total_data' => $total_data,
            'total_pages' => $total_pages,
            'data' => $final
        ]);
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => 'Query gagal: ' . $e->getMessage()], 500);
    }
?>