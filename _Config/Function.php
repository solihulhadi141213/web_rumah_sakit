<?php
    function GenerateCaptcha($length){
        $captcha = "";
        $codeAlphabet = "ABCDEFGHJKLMNPQRTUVWXYZ";
        $codeAlphabet.= "abcdefghijkmnpqrtuvwxyz";
        $codeAlphabet.= "2346789";
        $max = strlen($codeAlphabet);
        
        for ($i=0; $i < $length; $i++) {
            $captcha .= $codeAlphabet[random_int(0, $max-1)];
        }
        return $captcha;
    }
    function generate_uuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    function GenerateToken($length){
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet.= "0123456789";
        $max = strlen($codeAlphabet); // edited
        
        for ($i=0; $i < $length; $i++) {
            $token .= $codeAlphabet[random_int(0, $max-1)];
        }
        return $token;
    }
    function GenerateUuid($length) {
        if ($length <= 0) {
            throw new InvalidArgumentException("Length must be a positive integer.");
        }
    
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $uuid = '';
    
        for ($i = 0; $i < $length; $i++) {
            $uuid .= $characters[random_int(0, $charactersLength - 1)];
        }
    
        return $uuid;
    }
    function GenerateKodeObat($length){
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet.= "0123456789";
        $max = strlen($codeAlphabet); // edited
        
        for ($i=0; $i < $length; $i++) {
            $token .= $codeAlphabet[random_int(0, $max-1)];
        }
        return $token;
    }
    function GetDetailData($Conn, $Tabel, $Param, $Value, $Colom) {
        // Validasi input yang diperlukan
        if (empty($Conn)) {
            return "No Database Connection";
        }
        if (empty($Tabel)) {
            return "No Table Selected";
        }
        if (empty($Param)) {
            return "No Parameter Selected";
        }
        if (empty($Value)) {
            return "No Value Provided";
        }
        if (empty($Colom)) {
            return "No Column Selected";
        }
    
        // Escape table name and column name untuk mencegah SQL Injection
        $Tabel = mysqli_real_escape_string($Conn, $Tabel);
        $Param = mysqli_real_escape_string($Conn, $Param);
        $Colom = mysqli_real_escape_string($Conn, $Colom);
    
        // Menggunakan prepared statement
        $Qry = $Conn->prepare("SELECT $Colom FROM $Tabel WHERE $Param = ?");
        if ($Qry === false) {
            return "Query Preparation Failed: " . $Conn->error;
        }
    
        // Bind parameter
        $Qry->bind_param("s", $Value);
    
        // Eksekusi query
        if (!$Qry->execute()) {
            return "Query Execution Failed: " . $Qry->error;
        }
    
        // Mengambil hasil
        $Result = $Qry->get_result();
        $Data = $Result->fetch_assoc();
    
        // Menutup statement
        $Qry->close();
    
        // Mengembalikan hasil
        if (empty($Data[$Colom])) {
            return "";
        } else {
            return $Data[$Colom];
        }
    }
    
    function IjinAksesSaya($Conn,$SessionIdAkses,$KodeFitur){
        $QryParam = mysqli_query($Conn,"SELECT * FROM akses_ijin WHERE id_akses='$SessionIdAkses' AND kode='$KodeFitur'")or die(mysqli_error($Conn));
        $DataParam = mysqli_fetch_array($QryParam);
        if(empty($DataParam['id_akses'])){
            $Response="Tidak Ada";
        }else{
            $Response="Ada";
        }
        return $Response;
    }
    function CekFiturEntitias($Conn,$uuid_akses_entitas,$id_akses_fitur){
        $QryParam = mysqli_query($Conn,"SELECT * FROM akses_referensi WHERE uuid_akses_entitas='$uuid_akses_entitas' AND id_akses_fitur='$id_akses_fitur'")or die(mysqli_error($Conn));
        $DataParam = mysqli_fetch_array($QryParam);
        if(empty($DataParam['id_akses_referensi'])){
            $Response="Tidak Ada";
        }else{
            $Response="Ada";
        }
        return $Response;
    }
    function addLog($Conn, $id_akses, $datetime_log, $kategori_log, $deskripsi_log) {
        // Query SQL dengan placeholder
        $query = "INSERT INTO log (id_akses, datetime_log, kategori_log, deskripsi_log) VALUES (?, ?, ?, ?)";
    
        // Menggunakan prepared statement
        if ($stmt = $Conn->prepare($query)) {
            // Bind parameter
            $stmt->bind_param("isss", $id_akses, $datetime_log, $kategori_log, $deskripsi_log);
    
            // Eksekusi query
            if ($stmt->execute()) {
                $Response = "Success";
            } else {
                $Response = "Input Log Gagal: " . $stmt->error;
            }
    
            // Tutup statement
            $stmt->close();
        } else {
            $Response = "Prepared Statement Gagal: " . $Conn->error;
        }
    
        return $Response;
    }
    function CheckParameterOnJson($jsonString,$type,$parameter) {
        // Mengurai string JSON menjadi array PHP
        $data = json_decode($jsonString, true);
    
        // Pengecekan apakah $type ada dalam salah satu elemen array
        foreach ($data as $item) {
            if ($item[$parameter] === $type) {
                return true; // Jika ditemukan, kembalikan true
            }
        }
    
        return false; // Jika tidak ditemukan, kembalikan false
    }
    function validateAndSanitizeInput($input) {
        // Menghapus karakter yang tidak diinginkan
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input);
        $input = addslashes($input);
        return $input;
    }
    function validasi_format_tanggal($format, $inputDate) {
        // Mencoba membuat objek DateTime dari input
        $date = DateTime::createFromFormat($format, $inputDate);
    
        // Memeriksa apakah input valid sebagai tanggal dan sesuai format
        if ($date && $date->format($format) === $inputDate) {
            return true; // Input valid
        }
    
        return false; // Input tidak valid
    }
    function validateUploadedFile($file,$size) {
        // Tipe file yang diperbolehkan
        $allowedMimeTypes = [
            'image/jpeg', // Untuk file .jpg dan .jpeg
            'image/png',  // Untuk file .png
            'image/gif',  // Untuk file .gif
        ];
    
        // Maksimal ukuran file (5MB, misalnya)
        $maxFileSize = $size * 1024 * 1024; // 5MB dalam byte
    
        // Periksa apakah file diunggah tanpa error
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return "Terjadi kesalahan saat mengunggah file.";
        }
    
        // Periksa ukuran file
        if ($file['size'] > $maxFileSize) {
            return "Ukuran file terlalu besar. Maksimal 5MB.";
        }
    
        // Dapatkan MIME type file
        $fileMimeType = mime_content_type($file['tmp_name']);
    
        // Validasi tipe MIME
        if (!in_array($fileMimeType, $allowedMimeTypes)) {
            return "Tipe file $fileMimeType tidak valid. Hanya JPG, JPEG, PNG, dan GIF yang diperbolehkan.";
        }
    
        // Jika semua validasi lolos
        return true;
    }
    function validateUploadedFileIcon($file,$size) {
        // Tipe file yang diperbolehkan
        $allowedMimeTypes = [
            'image/jpeg', // Untuk file .jpg dan .jpeg
            'image/png',  // Untuk file .png
            'image/gif',  // Untuk file .gif
            'image/ico',  // Untuk file .ico
            'image/webp',  // Untuk file .webp
            'image/svg',  // Untuk file .svg
        ];
    
        // Maksimal ukuran file (5MB, misalnya)
        $maxFileSize = $size * 1024 * 1024; // 5MB dalam byte
    
        // Periksa apakah file diunggah tanpa error
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return "Terjadi kesalahan saat mengunggah file.";
        }
    
        // Periksa ukuran file
        if ($file['size'] > $maxFileSize) {
            return "Ukuran file terlalu besar. Maksimal 5MB.";
        }
    
        // Dapatkan MIME type file
        $fileMimeType = mime_content_type($file['tmp_name']);
    
        // Validasi tipe MIME
        if (!in_array($fileMimeType, $allowedMimeTypes)) {
            return "Tipe file tidak valid. Hanya JPG, JPEG, PNG, dan GIF yang diperbolehkan.";
        }
        
    
        // Jika semua validasi lolos
        return true;
    }
    // Fungsi untuk menghitung total tarif berdasarkan id_tarif_group
    function getTotalTarifByGroup($id_tarif_group, $Conn) {
        // Query untuk menjumlahkan tarif berdasarkan id_tarif_group
        $query = "
            SELECT SUM(tarif.tarif) AS total_tarif
            FROM tarif
            INNER JOIN tarif_ref ON tarif.id_tarif = tarif_ref.id_tarif
            INNER JOIN tarif_group ON tarif_ref.id_tarif_group = tarif_group.id_tarif_group
            WHERE tarif_group.id_tarif_group = ?
        ";

        // Persiapkan statement
        $stmt = $Conn->prepare($query);

        // Bind parameter
        $stmt->bind_param("i", $id_tarif_group);

        // Eksekusi query
        $stmt->execute();

        // Ambil hasil
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        // Kembalikan total tarif
        return $data['total_tarif'] ?? 0;
    }
    function encryptData($key, $data) {
        $iv = substr($key, 0, 16); // IV diambil dari 16 karakter pertama dari Encryption Key
        return base64_encode(openssl_encrypt(json_encode($data), 'AES-256-CBC', hex2bin($key), OPENSSL_RAW_DATA, $iv));
    }
    
    function decryptData($key, $encryptedData) {
        $iv = substr($key, 0, 16); // IV diambil dari 16 karakter pertama dari Encryption Key
        return json_decode(openssl_decrypt(base64_decode($encryptedData), 'AES-256-CBC', hex2bin($key), OPENSSL_RAW_DATA, $iv), true);
    }
    function sendRequest($url, $encryptedData) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['data' => $encryptedData]));
    
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
        curl_close($ch);
    
        if ($httpCode == 200) {
            return $response;
        } else {
            return false;
        }
    }
    // Encryption Function
    function inacbg_encrypt($data, $key) {
        /// make binary representasion of $key
        $key = hex2bin($key);
        /// check key length, must be 256 bit or 32 bytes
        if (mb_strlen($key, "8bit") !== 32) {
            throw new Exception("Needs a 256-bit key!");
        }
        /// create initialization vector
        $iv_size = openssl_cipher_iv_length("aes-256-cbc");
        $iv = openssl_random_pseudo_bytes($iv_size); // dengan catatan dibawah
        /// encrypt
        $encrypted = openssl_encrypt($data,"aes-256-cbc",$key,OPENSSL_RAW_DATA,$iv);
        /// create signature, against padding oracle attacks
        $signature = mb_substr(hash_hmac("sha256",$encrypted,$key,true),0,10,"8bit"); 
        /// combine all, encode, and format
        $encoded = chunk_split(base64_encode($signature.$iv.$encrypted));
        return $encoded;
    }
    
    // Decryption Function
    function inacbg_decrypt($str, $strkey){
        /// make binary representation of $key
        $key = hex2bin($strkey);
        /// check key length, must be 256 bit or 32 bytes
        if (mb_strlen($key, "8bit") !== 32) {
            throw new Exception("Needs a 256-bit key!");
        }
        /// calculate iv size
        $iv_size = openssl_cipher_iv_length("aes-256-cbc");
        /// breakdown parts
        $decoded = base64_decode($str);
        $signature = mb_substr($decoded,0,10,"8bit");
        $iv = mb_substr($decoded,10,$iv_size,"8bit");
        $encrypted = mb_substr($decoded,$iv_size+10,NULL,"8bit");
        /// check signature, against padding oracle attack
        $calc_signature = mb_substr(hash_hmac("sha256",$encrypted,$key,true),0,10,"8bit"); 
        if(!inacbg_compare($signature,$calc_signature)) {
            return "SIGNATURE_NOT_MATCH"; /// signature doesn't match
        }
        $decrypted = openssl_decrypt($encrypted,"aes-256-cbc",$key,OPENSSL_RAW_DATA,$iv);
        return $decrypted;
    }
    /// Compare Function
        function inacbg_compare($a, $b) {
        /// compare individually to prevent timing attacks
        
        /// compare length
        if (strlen($a) !== strlen($b)) return false;
        
        /// compare individual
        $result = 0;
        for($i = 0; $i < strlen($a); $i ++) {
        $result |= ord($a[$i]) ^ ord($b[$i]);
        }
        
        return $result == 0;
    }

    //GENERATE TOKEN SIMRS
    function GenerateTokenSimrs($url_simrs,$client_id,$client_key) {
        //Get Token
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => ''.$url_simrs.'get_token.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
                "client_id" : "'.$client_id.'",
                "client_key" : "'.$client_key.'"
            }',
        ));
        $response_koneksi = curl_exec($curl);
        return $response_koneksi;
    }
    function GetKunjungan($url_simrs,$client_id,$token,$page,$limit,$short_by,$order_by,$keyword_by,$keyword) {
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => ''.$url_simrs.'kunjungan.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
            "page":"'.$page.'",
            "limit":"'.$limit.'",
            "short_by":"'.$short_by.'",
            "order_by":"'.$order_by.'",
            "keyword_by":"'.$keyword_by.'",
            "keyword":"'.$keyword.'"
        }',
        CURLOPT_HTTPHEADER => array(
                'token: '.$token.'',
                'Content-Type: application/json',
                'client_id: '.$client_id.''
            ),
        ));
        
        $response = curl_exec($curl);
        
        curl_close($curl);
        return $response;
    }
    function GetDistinctKunjungan($url_simrs,$client_id,$token,$colom){
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => ''.$url_simrs.'distinct_kunjungan.php?colom='.$colom.'',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'token: '.$token.'',
            'Content-Type: application/json'
        ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    function GetDokter($url_simrs,$client_id,$token) {
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => ''.$url_simrs.'get_dokter.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'token: '.$token.'',
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        
        return $response;
    }

    function getNamaBulan($angkaBulan) {
        // Array dengan nama-nama bulan
        $namaBulan = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember'
        ];
    
        // Mengembalikan nama bulan berdasarkan angka
        return $namaBulan[$angkaBulan] ?? 'Bulan tidak valid';
    }

    //Fungsi Untuk Melakukan Resize
    function resizeImage($source_path, $target_path, $mime_type, $max_width = 800) {
        list($width, $height) = getimagesize($source_path);
    
        $ratio = $height / $width;
    
        // Jika lebih kecil, pakai ukuran asli
        $new_width = ($width > $max_width) ? $max_width : $width;
        $new_height = $new_width * $ratio;
    
        switch ($mime_type) {
            case 'image/jpeg':
                $src_image = imagecreatefromjpeg($source_path);
                break;
            case 'image/png':
                $src_image = imagecreatefrompng($source_path);
                break;
            case 'image/gif':
                $src_image = imagecreatefromgif($source_path);
                break;
            default:
                return false;
        }
    
        $dst_image = imagecreatetruecolor($new_width, $new_height);
        imagecopyresampled($dst_image, $src_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
        switch ($mime_type) {
            case 'image/jpeg':
                $save_result = imagejpeg($dst_image, $target_path, 90);
                break;
            case 'image/png':
                $save_result = imagepng($dst_image, $target_path);
                break;
            case 'image/gif':
                $save_result = imagegif($dst_image, $target_path);
                break;
        }
    
        imagedestroy($src_image);
        imagedestroy($dst_image);
    
        return $save_result;
    }
    
    function getUserIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP']; // IP dari shared internet
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]; // IP dari proxy
        } else {
            return $_SERVER['REMOTE_ADDR']; // IP langsung
        }
    }

    //Format Nilai 1K, 1 M dst
    function formatShortNumber($number) {
        if ($number >= 1000000) {
            // Format untuk jutaan (1M, 1,5M, dst)
            $formatted = number_format($number / 1000000, 1, ',', '') . 'M';
        } elseif ($number >= 1000) {
            // Format untuk ribuan (1K, 1,5K, dst)
            $formatted = number_format($number / 1000, 1, ',', '') . 'K';
        } else {
            // Angka di bawah 1000 ditampilkan biasa
            $formatted = $number;
        }
        
        // Menghilangkan ,0 jika tidak ada desimal (1,0K menjadi 1K)
        $formatted = str_replace(',0K', 'K', $formatted);
        $formatted = str_replace(',0M', 'M', $formatted);
        
        return $formatted;
    }

    //Validasi x-token
    function validasi_x_token($Conn,$token) {
        
        //Apabila token tidak ada
        if(empty($token)){
            $response="Token Tidak Boleh Kosong";
        }else{
            $stmt = $Conn->prepare("SELECT * FROM api_session WHERE session_token = :token AND datetime_expired > UTC_TIMESTAMP() LIMIT 1");
            $stmt->execute([':token' => $token]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$session) {
                $response="Token tidak valid atau kedaluwarsa.";
            }else{
                $response="Valid";
            }
        }
        
        return $response;
    }
?>