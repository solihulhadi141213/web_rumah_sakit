<?php
header('Content-Type: application/json');
require_once '../_Config/Connection.php';
require_once '../_Config/Function.php';

function sendResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Validasi metode
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    sendResponse(['status' => 'error', 'message' => 'Metode harus DELETE'], 405);
}

// Validasi token
$headers = getallheaders();
$token = $headers['x-token'] ?? $headers['X-Token'] ?? '';
if (empty($token)) {
    sendResponse(['status' => 'error', 'message' => 'Token tidak ditemukan'], 401);
}

$Conn = (new Database())->getConnection();
$valid = validasi_x_token($Conn, $token);
if ($valid !== 'Valid') {
    sendResponse(['status' => 'error', 'message' => $valid], 401);
}

// Ambil input JSON
$raw = file_get_contents("php://input");
$input = json_decode($raw, true);
if (!is_array($input)) {
    sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
}

$orderToDelete = intval($input['order'] ?? 0);
if ($orderToDelete <= 0) {
    sendResponse(['status' => 'error', 'message' => 'Order tidak valid'], 400);
}

// Lokasi folder logo
$uploadDir = '../assets/img/_Partnership/';

// Ambil data layout_static
$stmt = $Conn->prepare("SELECT * FROM setting WHERE setting_parameter = 'layout_static' LIMIT 1");
$stmt->execute();
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) {
    sendResponse(['status' => 'error', 'message' => 'Data layout_static tidak ditemukan'], 404);
}

$setting_value = json_decode($data['setting_value'], true);
if (!is_array($setting_value)) {
    sendResponse(['status' => 'error', 'message' => 'Format setting_value tidak valid'], 500);
}

$partnership = $setting_value['partnership'] ?? [];
$found = false;
$updated = [];
$deletedFile = null;

// Cari dan hapus data partnership dengan order tertentu
foreach ($partnership as $item) {
    if (intval($item['order']) === $orderToDelete) {
        $found = true;
        $deletedFile = $item['logo'];
        continue; // Skip item ini (hapus)
    }
    $updated[] = $item; // Simpan yang lain
}

if (!$found) {
    sendResponse(['status' => 'error', 'message' => 'Data partnership dengan order tersebut tidak ditemukan'], 404);
}

// Hapus file logo jika ada
if ($deletedFile && file_exists($uploadDir . $deletedFile)) {
    @unlink($uploadDir . $deletedFile);
}

// Susun ulang order (dimulai dari 1)
foreach ($updated as $i => &$item) {
    $item['order'] = $i + 1;
}
unset($item);

$setting_value['partnership'] = $updated;
$newJson = json_encode($setting_value, JSON_UNESCAPED_UNICODE);

$stmtUpdate = $Conn->prepare("UPDATE setting SET setting_value = :val WHERE id_setting = :id");
$stmtUpdate->bindValue(':val', $newJson);
$stmtUpdate->bindValue(':id', $data['id_setting']);
$stmtUpdate->execute();

sendResponse([
    'status' => 'success',
    'message' => 'Partnership berhasil dihapus',
    'partnership' => $updated
]);
?>