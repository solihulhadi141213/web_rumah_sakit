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
    $full_path = dirname($_SERVER['SCRIPT_NAME']); // dapatkan path penuh
    $base_path = substr($full_path, 0, strpos($full_path, '/_Api')); // ambil bagian sebelum '/_Api'
    $base_url = $protocol . $host . $base_path;
    define('BASE_URL', $base_url);

    //Buat Koneksi
    $Conn = (new Database())->getConnection();

    // Tangkap token dari header
    $headers = getallheaders();
    $token = $headers['x-token'] ?? $headers['X-Token'] ?? '';

    if (empty($token)) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan.'], 401);
    }

    // Validasi token dari tabel api_session
    $validasi_token=validasi_x_token($Conn,$token);

    if ($validasi_token!=="Valid") {
        sendResponse(['status' => 'error', 'message' => ''.$validasi_token.''], 401);
    }

    // Tangkap input JSON
    $input = json_decode(file_get_contents("php://input"), true);
    $order_by = isset($input['order_by']) ? $input['order_by'] : "datetime_creat";
    $short_by = isset($input['short_by']) ? $input['short_by'] : "DESC";
    $keyword_by = isset($input['keyword_by']) ? $input['keyword_by'] : "";
    $keyword = isset($input['keyword']) ? $input['keyword'] : "";
    $limit = isset($input['limit']) ? (int)$input['limit'] : 10;
    $page = isset($input['page']) ? (int)$input['page'] : 1;

    // Validasi nilai default
    if ($limit <= 0) $limit = 10;
    if ($page <= 0) $page = 1;

    $offset = ($page - 1) * $limit;

    // Ambil data blog dari database
    $stmtGaleri = $Conn->prepare("SELECT * FROM blog  ORDER BY $order_by $short_by LIMIT :limit OFFSET :offset");
    $stmtGaleri->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtGaleri->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtGaleri->execute();
    $blog_list = $stmtGaleri->fetchAll(PDO::FETCH_ASSOC);

    // Hitung jumlah item untuk setiap galeri
    $data = [];
    foreach ($blog_list as $row) {
        $content_blog=$row['content_blog'];
        $content_blog_arry=json_decode($content_blog, true);
        $data[] = [
            'id_blog' => $row['id_blog'],
            'title_blog' => $row['title_blog'],
            'deskripsi' => $row['deskripsi'],
            'cover' => $row['cover'],
            'datetime_creat' => $row['datetime_creat'],
            'datetime_update' => $row['datetime_update'],
            'author_blog' => $row['author_blog'],
            'content_blog' => $content_blog_arry,
            'publish' => $row['publish']
        ];
    }

    // Kirim response
    sendResponse([
        'status' => 'success',
        'message' => 'Blog berhasil ditampilkan.',
        'page' => $page,
        'limit' => $limit,
        'data' => $data
    ], 200);
