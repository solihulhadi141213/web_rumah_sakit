<?php
    // Koneksi Database
    require_once '../../_Config/Connection.php';

    // Inisialisasi koneksi database
    $db = new Database();
    $Conn = $db->getConnection();

    // Tangkap id_dokter
    if (empty($_POST['id_dokter'])) {
        echo '<div class="alert alert-danger">ID Dokter Tidak Boleh Kosong!</div>';
        exit;
    }

    $id_dokter = $_POST['id_dokter'];

    // Ambil detail dokter
    $sql_dokter = "SELECT * FROM dokter WHERE id_dokter = :id_dokter LIMIT 1";
    $stmt_dokter = $Conn->prepare($sql_dokter);
    $stmt_dokter->bindParam(':id_dokter', $id_dokter);
    $stmt_dokter->execute();
    $dokter = $stmt_dokter->fetch(PDO::FETCH_ASSOC);

    if (!$dokter) {
        echo '<div class="alert alert-warning">Data dokter tidak ditemukan.</div>';
        exit;
    }

    // Tampilkan detail dokter
    echo '
        <div class="row mb-4">
            <div class="col-md-3 text-center">
                <img src="image_proxy.php?segment=Dokter&image_name='.$dokter['foto'].'" class="img-fluid rounded-circle border" alt="'.$dokter['nama'].'" style="max-width:150px;">
            </div>
            <div class="col-md-9">
                <h4>'.$dokter['nama'].' ('.$dokter['id_dokter'].')</h4>
                <p class="mb-1"><strong>Spesialis:</strong> '.$dokter['spesialis'].'</p>
                <p class="text-muted"><i class="bi bi-clock-history"></i> Terakhir diperbarui: '.$dokter['last_update'].'</p>
            </div>
        </div>
    ';

    // Ambil data jadwal dokter
    $sql_jadwal = "SELECT * FROM jadwal_dokter WHERE id_dokter = :id_dokter ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')";
    $stmt_jadwal = $Conn->prepare($sql_jadwal);
    $stmt_jadwal->bindParam(':id_dokter', $id_dokter);
    $stmt_jadwal->execute();
    $jadwalList = $stmt_jadwal->fetchAll(PDO::FETCH_ASSOC);

    if (count($jadwalList) > 0) {
        echo '
            <h5 class="mb-3"><i class="bi bi-calendar-check"></i> Jadwal Praktek</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Hari</th>
                            <th>Jam</th>
                            <th>Poliklinik</th>
                            <th>Kuota Non-JKN</th>
                            <th>Kuota JKN</th>
                            <th>Batas Waktu Daftar</th>
                        </tr>
                    </thead>
                    <tbody>
        ';

        foreach ($jadwalList as $jadwal) {
            // Ambil nama poliklinik (jika perlu join atau query manual)
            $id_poliklinik = $jadwal['id_poliklinik'];
            $nama_poli = '-';
            $stmt_poli = $Conn->prepare("SELECT poliklinik FROM poliklinik WHERE id_poliklinik = :id_poliklinik");
            $stmt_poli->bindParam(':id_poliklinik', $id_poliklinik);
            $stmt_poli->execute();
            $poli = $stmt_poli->fetch(PDO::FETCH_ASSOC);
            if ($poli) {
                $nama_poli = $poli['poliklinik'];
            }

            echo '
                <tr>
                    <td>'.$jadwal['hari'].'</td>
                    <td>'.$jadwal['jam'].'</td>
                    <td>'.$nama_poli.'</td>
                    <td>'.$jadwal['kuota_non_jkn'].'</td>
                    <td>'.$jadwal['kuota_jkn'].'</td>
                    <td>'.$jadwal['time_max'].' menit sebelum</td>
                </tr>
            ';
        }

        echo '
                    </tbody>
                </table>
            </div>
        ';
    } else {
        echo '<div class="alert alert-info">Jadwal dokter belum tersedia.</div>';
    }
?>
