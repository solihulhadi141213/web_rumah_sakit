<?php
// Aktifkan error display untuk debug (disable di production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Fungsi response
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Validasi metode request harus POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['status' => 'error', 'message' => 'Metode harus POST'], 405);
}

// Load konfigurasi
require_once '../_Config/Connection.php';
require_once '../_Config/Function.php';
require_once '../_Config/log_visitor.php';

// Koneksi DB
try {
    $Conn = (new Database())->getConnection();
} catch (Exception $e) {
    sendResponse(['status' => 'error', 'message' => 'Koneksi DB gagal: ' . $e->getMessage()], 500);
}

// Ambil token
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

// Ambil body
$rawInput = file_get_contents("php://input");
if (empty($rawInput)) {
    sendResponse(['status' => 'error', 'message' => 'Body kosong, tidak ada data dikirim'], 400);
}

// Decode JSON
$input = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
    sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
}

// Validasi input
$id_blog = trim($input['id_blog'] ?? '');
$order_id = (int)($input['order_id'] ?? 0);
$type = trim($input['type'] ?? '');
$content = trim($input['content'] ?? '');

if ($id_blog === '') sendResponse(['status' => 'error', 'message' => 'ID Blog tidak boleh kosong'], 400);
if ($order_id <= 0) sendResponse(['status' => 'error', 'message' => 'Order ID tidak boleh kosong atau 0'], 400);
if ($type === '') sendResponse(['status' => 'error', 'message' => 'Type Konten tidak boleh kosong'], 400);
if ($content === '') sendResponse(['status' => 'error', 'message' => 'Isi Konten tidak boleh kosong'], 400);

// Pastikan id_blog ada di database
try {
    $stmt = $Conn->prepare("SELECT content_blog FROM blog WHERE id_blog = :id_blog LIMIT 1");
    $stmt->bindParam(':id_blog', $id_blog);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        sendResponse(['status' => 'error', 'message' => 'ID Blog tidak ditemukan'], 404);
    }
    $content_blog = $row['content_blog'] ?? '';
} catch (PDOException $e) {
    sendResponse(['status' => 'error', 'message' => 'Gagal mengambil data blog: ' . $e->getMessage()], 500);
}

$content_blog_arry = [];
if ($content_blog !== '') {
    $content_blog_arry = json_decode($content_blog, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($content_blog_arry)) {
        sendResponse(['status' => 'error', 'message' => 'Data content_blog rusak / bukan JSON valid'], 500);
    }
}

// Jika type adalah image, validasi base64 & simpan file
if ($type === 'image') {
    if (!preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $content)) {
        sendResponse(['status' => 'error', 'message' => 'Format base64 image tidak valid'], 400);
    }

    $data = explode(',', $content);
    $decoded = base64_decode($data[1], true);
    if ($decoded === false) {
        sendResponse(['status' => 'error', 'message' => 'Base64 tidak dapat didekode'], 400);
    }

    // Validasi ukuran maksimal 2MB
    if (strlen($decoded) > 2 * 1024 * 1024) {
        sendResponse(['status' => 'error', 'message' => 'Ukuran gambar melebihi 2MB'], 400);
    }

    // Validasi MIME asli
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($decoded);
    $allowedTypes = ['image/png', 'image/jpeg'];
    if (!in_array($mimeType, $allowedTypes)) {
        sendResponse([
            'status' => 'error',
            'message' => 'Base64 bukan gambar yang valid. MIME: ' . $mimeType
        ], 400);
    }

    // Ekstensi berdasarkan MIME
    $ext = $mimeType === 'image/png' ? '.png' : '.jpg';

    // Simpan file
    $filename = uniqid('img_', true) . $ext;
    $path = __DIR__ . '/../assets/img/_Artikel/' . $filename;
    if (!file_put_contents($path, $decoded)) {
        sendResponse(['status' => 'error', 'message' => 'Gagal menyimpan file gambar'], 500);
    }

    $content = $filename;
}

// Urutkan ulang jika order_id sudah ada
foreach ($content_blog_arry as &$item) {
    if ($item['order_id'] >= $order_id) {
        $item['order_id'] += 1;
    }
}
unset($item);

$content_blog_arry[] = [
    'order_id' => $order_id,
    'type' => $type,
    'content' => $content
];

// Urutkan berdasarkan order_id
usort($content_blog_arry, function($a, $b) {
    return $a['order_id'] <=> $b['order_id'];
});

$content_blog_json = json_encode($content_blog_arry, JSON_UNESCAPED_UNICODE);
if ($content_blog_json === false) {
    sendResponse(['status' => 'error', 'message' => 'Gagal encode data content_blog'], 500);
}

// Update DB
try {
    $Conn->beginTransaction();
    $stmt = $Conn->prepare("UPDATE blog SET content_blog = :content_blog WHERE id_blog = :id_blog");
    $stmt->bindParam(':content_blog', $content_blog_json);
    $stmt->bindParam(':id_blog', $id_blog);
    $stmt->execute();
    $Conn->commit();

    sendResponse([
        'status' => 'success',
        'message' => 'Blog berhasil diupdate',
        'data' => [
            'id_blog' => $id_blog,
            'content_blog' => $content_blog_json
        ]
    ]);
} catch (PDOException $e) {
    $Conn->rollBack();
    sendResponse(['status' => 'error', 'message' => 'Gagal update: ' . $e->getMessage()], 500);
}
?>