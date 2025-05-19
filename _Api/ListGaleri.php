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
    $stmt = $Conn->prepare("SELECT * FROM api_session WHERE session_token = :token AND datetime_expired > UTC_TIMESTAMP() LIMIT 1");
    $stmt->execute([':token' => $token]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        sendResponse(['status' => 'error', 'message' => 'Token tidak valid atau kedaluwarsa.'], 401);
    }

    // Tangkap input JSON
    $input = json_decode(file_get_contents("php://input"), true);
    $limit = isset($input['limit']) ? (int)$input['limit'] : 10;
    $page = isset($input['page']) ? (int)$input['page'] : 1;

    // Validasi nilai default
    if ($limit <= 0) $limit = 10;
    if ($page <= 0) $page = 1;

    $offset = ($page - 1) * $limit;

    // Ambil data galeri dari database
    $stmtGaleri = $Conn->prepare("SELECT id_galeri, title, cover FROM galeri ORDER BY id_galeri DESC LIMIT :limit OFFSET :offset");
    $stmtGaleri->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmtGaleri->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmtGaleri->execute();
    $galeriList = $stmtGaleri->fetchAll(PDO::FETCH_ASSOC);

    // Hitung jumlah item untuk setiap galeri
    $data = [];
    foreach ($galeriList as $row) {
        $stmtItem = $Conn->prepare("SELECT COUNT(*) AS jumlah FROM galeri_item WHERE id_galeri = :id_galeri");
        $stmtItem->execute([':id_galeri' => $row['id_galeri']]);
        $itemCount = $stmtItem->fetch(PDO::FETCH_ASSOC)['jumlah'] ?? 0;

        $file_cover=$row['cover'];
        $proxy_cover="$base_url/image_proxy.php?segment=Galeri&image_name=$file_cover";
        $data[] = [
            'id_galeri' => $row['id_galeri'],
            'title' => $row['title'],
            'cover' => $proxy_cover,
            'item' => (int)$itemCount
        ];
    }

    // Kirim response
    sendResponse([
        'status' => 'success',
        'message' => 'Galeri berhasil ditampilkan.',
        'page' => $page,
        'limit' => $limit,
        'data' => $data
    ], 200);
