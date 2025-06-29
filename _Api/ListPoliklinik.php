<?php
    // Aktifkan error reporting
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

    // Load konfigurasi dan fungsi
    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';
    require_once '../_Config/log_visitor.php';

    // Koneksi DB
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

    // Ambil body JSON
    $rawInput = file_get_contents("php://input");
    $input = json_decode($rawInput, true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Ambil parameter
    $order_by   = $input['order_by'] ?? 'id_poliklinik';
    $short_by   = strtoupper($input['short_by'] ?? 'DESC');
    $keyword_by = $input['keyword_by'] ?? '';
    $keyword    = trim($input['keyword'] ?? '');
    $limit      = (int)($input['limit'] ?? 10);
    $page       = (int)($input['page'] ?? 1);
    $offset     = ($page - 1) * $limit;

    // Validasi nilai dasar
    $allowed_order = ['id_poliklinik', 'nama', 'kode'];
    if (!in_array($order_by, $allowed_order)) {
        $order_by = 'id_poliklinik';
    }
    if (!in_array($short_by, ['ASC', 'DESC'])) {
        $short_by = 'DESC';
    }

    // Bangun query
    $where = "WHERE 1=1";
    $params = [];

    if (!empty($keyword) && !empty($keyword_by)) {
        $where .= " AND $keyword_by LIKE :keyword";
        $params[':keyword'] = "%$keyword%";
    }

    try {
        // Hitung total data
        $stmtTotal = $Conn->prepare("SELECT COUNT(*) FROM poliklinik $where");
        $stmtTotal->execute($params);
        $total_data = $stmtTotal->fetchColumn();
        $total_page = ceil($total_data / $limit);

        // Ambil data
        $stmt = $Conn->prepare("SELECT * FROM poliklinik $where ORDER BY $order_by $short_by LIMIT :offset, :limit");
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendResponse([
            'status' => 'success',
            'message' => 'Data poliklinik ditemukan',
            'data' => $results,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total_data' => $total_data,
                'total_page' => $total_page
            ]
        ]);
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => 'Query gagal: ' . $e->getMessage()], 500);
    }
?>