$(document).ready(function() {
    // Tangkap event saat tombol "Lihat Detail" diklik
    $('#ModalDetailDokter').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // Tombol yang diklik
        var id_dokter = button.data('id'); // Ambil data-id dari tombol

        // Tampilkan indikator loading
        $('#ShowDetailDokter').html('<div class="text-center p-4"><div class="spinner-border text-success" role="status"></div><p class="mt-2">Memuat data jadwal dokter...</p></div>');

        // Kirim permintaan AJAX untuk ambil detail jadwal dokter
        $.ajax({
            url     : '_Page/Home/DetailJadwalDokter.php',
            type    : 'POST',
            data    : { id_dokter: id_dokter },
            success: function(response) {
                $('#ShowDetailDokter').html(response);
            },
            error: function(xhr, status, error) {
                $('#ShowDetailDokter').html('<div class="alert alert-danger">Gagal memuat data jadwal dokter. Silakan coba lagi.</div>');
                console.error('Error:', error);
            }
        });
    });

    //Baca selengkapnya Blog
    $('.baca_selengkapnya').on('click', function() {
        var targetUrl = $(this).attr('target-link');
        if (targetUrl) {
            window.location.href = targetUrl;
        }
    });
});