<?php
    header('Content-Type: application/json');

    // Fungsi bantu kirim response dengan status code
    function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    // Koneksi Database
    require_once '../_Config/Connection.php';
    require_once '../_Config/Function.php';
    require_once '../_Config/log_visitor.php';

    // Tentukan base URL dinamis
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $full_path = dirname($_SERVER['SCRIPT_NAME']);
    $base_path = substr($full_path, 0, strpos($full_path, '/_Api'));
    $base_url = $protocol . $host . $base_path;
    define('BASE_URL', $base_url);

    // Buat Koneksi
    $Conn = (new Database())->getConnection();

    // Tangkap token dari header
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';

    // Validasi Jika Token Kosong
    if (empty($token)) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan.'], 401);
    }

    // Validasi token dari tabel api_session
    $validasi_token = validasi_x_token($Conn, $token);
    if ($validasi_token !== "Valid") {
        sendResponse(['status' => 'error', 'message' => $validasi_token], 401);
    }

    // Tangkap input JSON
    $input = json_decode(file_get_contents("php://input"), true);

    // Validasi jika JSON tidak valid atau kosong
    if (json_last_error() !== JSON_ERROR_NONE || $input === null) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Ambil parameter dari input
    $order_by = isset($input['order_by']) ? trim($input['order_by']) : 'datetime_laman';
    $short_by = isset($input['short_by']) ? trim($input['short_by']) : 'DESC';
    $keyword_by = isset($input['keyword_by']) ? trim($input['keyword_by']) : '';
    $keyword = isset($input['keyword']) ? trim($input['keyword']) : '';
    $limit = isset($input['limit']) ? (int)$input['limit'] : 10;
    $page = isset($input['page']) ? (int)$input['page'] : 1;

    // Validasi parameter order_by (hanya kolom tertentu yang diperbolehkan)
    $allowed_order_columns = ['datetime_laman', 'judul_laman', 'kategori_laman'];
    if (!in_array($order_by, $allowed_order_columns)) {
        sendResponse(['status' => 'error', 'message' => 'Kolom order_by tidak valid'], 400);
    }

    // Validasi short_by (hanya ASC atau DESC)
    $short_by = strtoupper($short_by);
    if (!in_array($short_by, ['ASC', 'DESC'])) {
        sendResponse(['status' => 'error', 'message' => 'Nilai short_by harus ASC atau DESC'], 400);
    }

    // Validasi limit (minimal 1, maksimal 100)
    if ($limit < 1 || $limit > 100) {
        sendResponse(['status' => 'error', 'message' => 'Limit harus antara 1 sampai 100'], 400);
    }

    // Validasi page (minimal 1)
    if ($page < 1) {
        sendResponse(['status' => 'error', 'message' => 'Halaman tidak valid'], 400);
    }

    // Validasi keyword_by jika keyword tidak kosong
    if (!empty($keyword) && !empty($keyword_by)) {
        $allowed_search_columns = ['judul_laman', 'kategori_laman', 'deskripsi', 'author'];
        if (!in_array($keyword_by, $allowed_search_columns)) {
            sendResponse(['status' => 'error', 'message' => 'Kolom pencarian tidak valid'], 400);
        }
    }

    try {
        // Hitung offset untuk pagination
        $offset = ($page - 1) * $limit;

        // Query dasar
        $query = "SELECT 
                    id_laman, 
                    judul_laman, 
                    kategori_laman, 
                    datetime_laman, 
                    cover, 
                    deskripsi, 
                    author 
                FROM laman 
                WHERE 1=1";

        // Tambahkan kondisi pencarian jika ada keyword
        if (!empty($keyword) && !empty($keyword_by)) {
            $query .= " AND $keyword_by LIKE :keyword";
            $keyword_param = "%$keyword%";
        }

        // Tambahkan order by
        $query .= " ORDER BY $order_by $short_by";

        // Tambahkan limit dan offset
        $query .= " LIMIT :limit OFFSET :offset";

        // Persiapkan query
        $stmt = $Conn->prepare($query);

        // Bind parameter pencarian jika ada
        if (!empty($keyword) && !empty($keyword_by)) {
            $stmt->bindParam(':keyword', $keyword_param);
        }

        // Bind parameter limit dan offset
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

        // Eksekusi query
        $stmt->execute();

        // Ambil hasil query
        $laman_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Query untuk menghitung total data (untuk pagination)
        $count_query = "SELECT COUNT(*) as total FROM laman WHERE 1=1";
        
        if (!empty($keyword) && !empty($keyword_by)) {
            $count_query .= " AND $keyword_by LIKE :keyword";
        }

        $count_stmt = $Conn->prepare($count_query);
        
        if (!empty($keyword) && !empty($keyword_by)) {
            $count_stmt->bindParam(':keyword', $keyword_param);
        }
        
        $count_stmt->execute();
        $total_data = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $total_pages = ceil($total_data / $limit);

        // Format URL cover jika ada
        foreach ($laman_list as &$laman) {
            if (!empty($laman['cover'])) {
                $laman['cover_url'] = $laman['cover'];
            } else {
                $laman['cover_url'] = null;
            }
            unset($laman['cover']);
        }

        // Data untuk response
        $response = [
            'status' => 'success',
            'data' => $laman_list,
            'pagination' => [
                'total_data' => (int)$total_data,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'per_page' => $limit,
                'has_next_page' => $page < $total_pages,
                'has_previous_page' => $page > 1
            ]
        ];

        // Kirim response
        sendResponse($response);

    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => 'Gagal mengambil data laman: ' . $e->getMessage()], 500);
    }
?>