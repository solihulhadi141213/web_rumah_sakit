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

    function generateRandomFilename($ext) {
        return bin2hex(random_bytes(18)) . '.' . $ext;
    }

    function getMimeTypeFromBase64($base64) {
        if (preg_match('/^data:(image\/[a-zA-Z]+);base64,/', $base64, $matches)) {
            return $matches[1];
        }
        return null;
    }

    // Validasi metode
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        sendResponse(['status' => 'error', 'message' => 'Metode harus PUT'], 405);
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

    // Ambil dan decode JSON
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        sendResponse(['status' => 'error', 'message' => 'Format JSON tidak valid'], 400);
    }

    // Ambil input
    $id     = $input['id_struktur_organisasi'] ?? null;
    $boss   = isset($input['boss']) ? intval($input['boss']) : null;
    $nama   = trim($input['nama'] ?? '');
    $job    = trim($input['job_title'] ?? '');
    $nip    = trim($input['NIP'] ?? '');
    $foto_b64 = $input['foto'] ?? null;

    $errors = [];

    // Validasi ID utama
    if (empty($id)) {
        $errors[] = "ID struktur organisasi tidak boleh kosong.";
    } else {
        $stmt = $Conn->prepare("SELECT * FROM struktur_organisasi WHERE id_struktur_organisasi = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$existing) {
            $errors[] = "Data dengan ID tersebut tidak ditemukan.";
        }
    }

    // Validasi boss (jika diisi)
    if (!is_null($boss)) {
        $stmt = $Conn->prepare("SELECT COUNT(*) FROM struktur_organisasi WHERE id_struktur_organisasi = :boss_id");
        $stmt->bindParam(':boss_id', $boss, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $errors[] = "ID boss tidak ditemukan.";
        }
    }

    // Validasi nama dan jabatan
    if (strlen($nama) == 0 || strlen($nama) > 255) {
        $errors[] = "Nama wajib diisi dan tidak boleh lebih dari 255 karakter.";
    }
    if (strlen($job) == 0 || strlen($job) > 255) {
        $errors[] = "Jabatan wajib diisi dan tidak boleh lebih dari 255 karakter.";
    }

    // Validasi NIP
    if (strlen($nip) == 0 || strlen($nip) > 30) {
        $errors[] = "NIP wajib diisi dan tidak boleh lebih dari 30 karakter.";
    }

    // Proses foto
    $filename = $existing['foto'] ?? null;
    if (!empty($foto_b64)) {
        $mime = getMimeTypeFromBase64($foto_b64);
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        if (!in_array($mime, $allowed_mimes)) {
            $errors[] = 'Tipe gambar tidak valid. Hanya JPG, PNG, WEBP.';
        }

        $data = explode(',', $foto_b64);
        if (!isset($data[1])) {
            $errors[] = 'Format foto base64 tidak valid.';
        } else {
            $decoded = base64_decode($data[1]);
            if (strlen($decoded) > 5 * 1024 * 1024) {
                $errors[] = 'Ukuran gambar tidak boleh lebih dari 5MB.';
            }
        }

        // Simpan gambar jika valid
        if (empty($errors)) {
            $ext = explode('/', $mime)[1];
            $new_filename = generateRandomFilename($ext);
            $target_path = "../assets/img/_Struktur_Organisasi/" . $new_filename;
            if (file_put_contents($target_path, $decoded)) {
                // Hapus foto lama jika ada
                if (!empty($existing['foto'])) {
                    $old_path = "../../assets/img/_Struktur_Organisasi/" . $existing['foto'];
                    if (file_exists($old_path)) {
                        unlink($old_path);
                    }
                }
                $filename = $new_filename;
            } else {
                $errors[] = 'Gagal menyimpan gambar.';
            }
        }
    }

    if (!empty($errors)) {
        sendResponse(['status' => 'error', 'message' => implode(" ", $errors)], 422);
    }

    // Update data
    try {
        $stmt = $Conn->prepare("UPDATE struktur_organisasi SET boss = :boss, nama = :nama, job_title = :job, NIP = :nip, foto = :foto WHERE id_struktur_organisasi = :id");
        $stmt->bindValue(':boss', $boss, PDO::PARAM_INT);
        $stmt->bindValue(':nama', $nama);
        $stmt->bindValue(':job', $job);
        $stmt->bindValue(':nip', $nip);
        $stmt->bindValue(':foto', $filename);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        sendResponse([
            'status' => 'success',
            'message' => 'Data struktur organisasi berhasil diperbarui.'
        ]);
    } catch (PDOException $e) {
        sendResponse(['status' => 'error', 'message' => 'Gagal update data: ' . $e->getMessage()], 500);
    }
?>