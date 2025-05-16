<?php
    //Koneksi
    require_once '_Config/Connection.php';
    require_once '_Config/log_visitor.php';

    // Inisialisasi koneksi database
    $db = new Database();
    $Conn = $db->getConnection();

    // Mulai logging
    $logger = new VisitorLogger($Conn);
    $logger->logVisit();

    // Tentukan base URL dinamis
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $base_url = $protocol . $host . $base_path . '/';
    define('BASE_URL', $base_url);

    // Tangkap URI dan hilangkan query string
    $request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Normalisasi URI dengan menghapus base_path dan index.php
    $relative_uri = preg_replace([
        '#^' . preg_quote($base_path, '#') . '#',  // Hapus base path
        '#/index\.php$#',                          // Hapus /index.php di akhir
        '#/index\.php/#',                          // Hapus /index.php/ di tengah
    ], '', $request_uri);

    // Hilangkan slash di depan/belakang dan kosongkan jika hanya ada slash
    $relative_uri = trim($relative_uri, '/');
    
    // Ambil segment pertama sebagai halaman
    $segments = explode('/', $relative_uri);
    $Page = !empty($segments[0]) ? $segments[0] : 'Home';
    
    // Contoh penggunaan:
    // - URL: example.com/index.php/contact → $Page = 'contact'
    // - URL: example.com/contact → $Page = 'contact'
    // - URL: example.com/ → $Page = 'home'

    //Simpan log pengunjung
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Rumah Sakit Umum El-Syifa Kuningan, Kabupaten Kuningan. Memberikan pelayanan kesehatan yang profesional dan terpercaya.">
        <meta name="keywords" content="RSU, RS, RSU El-Syifa, RSEl-Syifa, Elsyifa, RS Elsyifa, RS El-Syifa, Kuningan, Kabupaten Kuningan, Kab. Kuningan">
        <meta name="author" content="RSU El-Syifa Kuningan">
        <meta name="robots" content="index, follow">
        <title>Web | RSU EL-Syifa</title>

        <!-- Open Graph -->
        <meta property="og:title" content="RSU El-Syifa Kuningan">
        <meta property="og:description" content="Rumah Sakit Umum El-Syifa Kuningan, Kabupaten Kuningan. Memberikan pelayanan kesehatan yang profesional dan terpercaya.">
        <meta property="og:image" content="https://example.com/images/og-image.jpg">
        <meta property="og:url" content="https://el-syifa-kuningan.id">
        <meta property="og:type" content="website">

        <!-- Open Graph untuk Social Media (Facebook, LinkedIn, dll) -->
        <meta property="og:title" content="RSU El-Syifa Kuningan">
        <meta property="og:description" content="">
        <meta property="og:type" content="website">
        <meta property="og:url" content="<?php echo $base_url; ?>">
        <meta property="og:image" content="<?php echo $base_url; ?>/assets/img/favicon-32x32.png"> <!-- Ganti jika punya gambar banner OG khusus -->

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="RSU El-Syifa Kuningan">
        <meta name="twitter:description" content="Rumah Sakit Umum El-Syifa Kuningan, Kabupaten Kuningan. Memberikan pelayanan kesehatan yang profesional dan terpercaya.">
        <meta name="twitter:image" content="<?php echo $base_url; ?>/assets/img/favicon-32x32.png"> <!-- Ganti jika punya gambar banner Twitter khusus -->

        <!-- Favicon & Icons -->
        <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $base_url; ?>/assets/img/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base_url; ?>/assets/img/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $base_url; ?>/assets/img/favicon-16x16.png">
        <link rel="manifest" href="<?php echo $base_url; ?>/assets/img/site.webmanifest">
        
        <!-- Canonical URL (SEO untuk mencegah duplikat) -->
        <link rel="canonical" href="<?php echo $base_url; ?>">

        <!-- Bootstrap -->
        <link href="<?php echo $base_url; ?>/node_modules\bootstrap\dist\css\bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
        <script src="<?php echo $base_url; ?>/node_modules\bootstrap\dist\js\bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>

        <!-- Bootstrap Icon -->
        <link rel="stylesheet" href="<?php echo $base_url; ?>/node_modules/bootstrap-icons/font/bootstrap-icons.min.css">

        <!-- Google Fonts Roboto -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap">

        <!-- Swiper -->
        <link rel="stylesheet" href="<?php echo $base_url; ?>/node_modules/swiper/swiper-bundle.min.css"/>
        <script src="<?php echo $base_url; ?>/node_modules/swiper/swiper-bundle.min.js"></script>

        <!-- Go JS -->
        <script src="<?php echo $base_url; ?>/assets/GoJs/release/go.js"></script>

        <!-- Custome CSS -->
        <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/custome.css?v=<?php echo date('YmdHis'); ?>">
    </head>
    <div class="preloader">
        <div class="loader">
            <!-- Anda bisa gunakan spinner, logo, atau animasi SVG -->
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3">Memuat...</p>
        </div>
    </div>
    <body>
        <!-- HEADER -->
        <header class="container-fluid py-2 px-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <!-- Logo dan Judul -->
                <div class="d-flex align-items-center gap-2">
                    <img src="<?php echo $base_url; ?>/assets/img/67718ce144463.png" alt="Logo RSU El-Syifa" style="height: 40px;">
                    <h4 class="mb-0">
                        <a href="" class="web-name text-decoration-none">RSU El-Syifa Kuningan</a>
                    </h4>
                </div>

                <!-- Tombol Offcanvas (untuk mobile) -->
                <button class="btn btn-outline-light d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu" aria-controls="offcanvasMenu">
                    <i class="bi bi-list fs-4"></i>
                </button>

                <!-- Menu (Offcanvas) -->
                <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasMenu" aria-labelledby="offcanvasMenuLabel">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title text-light" id="offcanvasMenuLabel">Menu</h5>
                        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body">
                        <ul class="navbar-nav ms-auto">
                        <li class="nav-item"><a class="nav-link" href="#">Beranda</a></li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#submenuTentang" role="button" aria-expanded="false" aria-controls="submenuTentang">
                                Tentang Kami <i class="bi bi-chevron-down float-end"></i>
                            </a>
                            <div class="collapse ps-3" id="submenuTentang">
                                <a class="nav-link" href="#">Sejarah</a>
                                <a class="nav-link" href="#">Struktur Organisasi</a>
                                <a class="nav-link" href="#">Sarana Prasarana</a>
                                <a class="nav-link" href="#">Sumber Daya Manusia</a>
                                <a class="nav-link" href="#">Kontak Dan Lokasi</a>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="collapse" href="#submenuLayanan" role="button" aria-expanded="false" aria-controls="submenuLayanan">
                                Layanan <i class="bi bi-chevron-down float-end"></i>
                            </a>
                            <div class="collapse ps-3" id="submenuLayanan">
                                <a class="nav-link" href="#">Rawat Inap</a>
                                <a class="nav-link" href="#">Rawat Jalan</a>
                                <a class="nav-link" href="#">UGD</a>
                            </div>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="#">Dokter</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Kontak</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Login</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Menu biasa di desktop -->
                <nav class="d-none d-lg-block">
                    <ul class="navbar-nav flex-row gap-3">
                        <li class="nav-item"><a class="nav-link" href="<?php echo $base_url; ?>Home">Beranda</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Tentang Kami</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Sejarah</a></li>
                                <li><a class="dropdown-item" href="<?php echo $base_url; ?>Struktur-Organisasi">Struktur Organisasi</a></li>
                                <li><a class="dropdown-item" href="#">Sarana Prasarana</a></li>
                                <li><a class="dropdown-item" href="#">Sumber Daya Manusia</a></li>
                                <li><a class="dropdown-item" href="#">Kontak Dan Lokasi</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Layanan</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Rawat Inap</a></li>
                                <li><a class="dropdown-item" href="#">Rawat Jalan</a></li>
                                <li><a class="dropdown-item" href="#">UGD</a></li>
                            </ul>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $base_url; ?>Dokter">Dokter</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $base_url; ?>Kontak">Kontak</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $base_url; ?>Login">Login</a></li>
                    </ul>
                </nav>
            </div>
        </header>
        <?php
            // Routing manual
            // echo '<h1>'.$Page.'</h1>';
            if($Page=="Home"||$Page==""){
                include "_Page/Home/Home.php";
            }elseif($Page=="Contact"){
                include "_Page/Contact/Contact.php";
            }elseif($Page=="Struktur-Organisasi"){
                include "_Page/Struktur-Organisasi/Struktur-Organisasi.php";
            }else{
                include "_Page/Error/page-not-found.php";
            }
        ?>
        
        <button id="backToTopBtn" class="btn btn-success rounded-circle shadow" style="position: fixed; bottom: 20px; right: 20px; width: 50px; height: 50px; display: none;">
            <i class="bi bi-arrow-up"></i>
        </button>
        <!-- FOOTER -->
        <footer class="footer py-4">
            <div class="container">
                <div class="row text-white">
                    <!-- Kontak -->
                    <div class="col-md-3 mb-3">
                        <h5 class="text-decoration-underline">Tentang Kami</h5>
                        <ul class="">
                            <li><a href="https://kemkes.go.id" class="footer-link" target="_blank">Sejarah</a></li>
                            <li><a href="https://kemkes.go.id" class="footer-link" target="_blank">Struktur Organisasi</a></li>
                            <li><a href="https://kemkes.go.id" class="footer-link" target="_blank">Sarana Prasarana</a></li>
                            <li><a href="https://kemkes.go.id" class="footer-link" target="_blank">Sumber Daya Manusia</a></li>
                            <li><a href="https://kemkes.go.id" class="footer-link" target="_blank">Kontak Dan Lokasi</a></li>
                        </ul>
                    </div>
                    <!-- Link Eksternal -->
                    <div class="col-md-3 mb-3">
                        <h5 class="text-decoration-underline">Tautan Lainnya</h5>
                        <ul class="">
                            <li><a href="https://kemkes.go.id" class="footer-link" target="_blank">Hubungi Kami</a></li>
                            <li><a href="https://kemkes.go.id" class="footer-link" target="_blank">FAQ</a></li>
                            <li><a href="https://kemkes.go.id" class="footer-link" target="_blank">Syarat & Ketentuan</a></li>
                            <li><a href="https://kemkes.go.id" class="footer-link" target="_blank">Kebijakan Privasi</a></li>
                            <li><a href="https://kemkes.go.id" class="footer-link" target="_blank">Peta Situs</a></li>
                        </ul>
                    </div>
                    <!-- Alamat -->
                    <div class="col-md-3 mb-3">
                        <h5 class="text-decoration-underline">Alamat</h5>
                        <p>Jalan RE Martadinata No 128 Kelurahan Ancaran, Kecamatan Kuningan, Kabupaten Kuningan, Jawa Barat.</p>
                        
                        <!-- Tambahan Kontak -->
                        <div class="contact-info mt-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-telephone me-2"></i>
                                <span>(0232) 1234567</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-whatsapp me-2"></i>
                                <span>0812 3456 7890</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-envelope me-2"></i>
                                <span>info@rsuelsyifa.co.id</span>
                            </div>
                        </div>
                    </div>
                    <!-- Media Sosial -->
                    <div class="col-md-3 mb-3">
                        <h5 class="text-decoration-underline">Media Sosial</h5>
                        <div class="social-media">
                            <a href="#" class="social-circle"><i class="bi bi-facebook"></i></a>
                            <a href="#" class="social-circle"><i class="bi bi-twitter-x"></i></a>
                            <a href="#" class="social-circle"><i class="bi bi-instagram"></i></a>
                            <a href="#" class="social-circle"><i class="bi bi-youtube"></i></a>
                        </div>
                    </div>
                </div>
                <hr class="border-light">
                <div class="text-center text-white small">&copy; 2025 RSU El-Syifa Kuningan. All rights reserved.</div>
            </div>
        </footer>

    </body>
    <script type="text/javascript" src="<?php echo $base_url; ?>/node_modules/jquery/dist/jquery.min.js?v=<?php echo date('YmdHis'); ?>"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const preloader = document.querySelector('.preloader');
            const startTime = Date.now();
            const minimumDisplayTime = 2000; // 2 detik dalam milidetik

            // Fungsi untuk menyembunyikan preloader
            function hidePreloader() {
                const elapsedTime = Date.now() - startTime;
                const remainingTime = Math.max(0, minimumDisplayTime - elapsedTime);

                setTimeout(() => {
                    preloader.style.opacity = '0';
                    setTimeout(() => {
                        preloader.style.display = 'none';
                    }, 500); // Waktu untuk transition opacity
                }, remainingTime);
            }

            // Pastikan semua aset (gambar, dll) sudah dimuat
            window.addEventListener('load', hidePreloader);

            // Fallback jika event load tidak terpicu
            setTimeout(hidePreloader, minimumDisplayTime + 1000);
        });

        $(document).ready(function(){
            // Fungsi untuk mengecek ketika elemen muncul di viewport
            function tampilkanDenganTransisi() {
                const elements = document.querySelectorAll('.show_transisi');
                
                elements.forEach(element => {
                    const elementPosition = element.getBoundingClientRect().top;
                    const screenPosition = window.innerHeight / 1.2;
                    
                    if(elementPosition < screenPosition) {
                        element.classList.add('muncul');
                    }
                });
            }

            // Jalankan saat load dan scroll
            window.addEventListener('load', tampilkanDenganTransisi);
            window.addEventListener('scroll', tampilkanDenganTransisi);

            function revealOnScroll() {
                $('.title_segment').each(function () {
                    var top_of_element = $(this).offset().top;
                    var bottom_of_screen = $(window).scrollTop() + $(window).height();

                    if (bottom_of_screen > top_of_element + 30) {
                        $(this).addClass('show');
                    }
                });
                $('.title_segment_dark').each(function () {
                    var top_of_element = $(this).offset().top;
                    var bottom_of_screen = $(window).scrollTop() + $(window).height();

                    if (bottom_of_screen > top_of_element + 30) {
                        $(this).addClass('show');
                    }
                });
                 $('.service_segment').each(function () {
                    var top_of_element = $(this).offset().top;
                    var bottom_of_screen = $(window).scrollTop() + $(window).height();

                    if (bottom_of_screen > top_of_element + 30) {
                        $(this).addClass('show');
                    }
                });
            }
            $('#klik').on('click', function(){
                alert('Berhasil! jQuery aktif.');
            });
            // Sembunyikan full_sambutan saat halaman dimuat
            $('#full_sambutan').hide();

            // Saat link diklik, tampilkan full_sambutan dengan animasi
            let isVisible = false;
            $('#toggle_sambutan').on('click', function(e) {
                e.preventDefault();
                if (!isVisible) {
                    $('#full_sambutan').slideDown('slow');
                    $(this).text('Sembunyikan');
                    isVisible = true;
                } else {
                    $('#full_sambutan').slideUp('slow');
                    $(this).text('Baca Selengkapnya');
                    isVisible = false;
                }
            });

            

            // Jalankan saat load dan scroll
            $(window).on('scroll', revealOnScroll);
            revealOnScroll();
            

            // Swiper
            const swiper = new Swiper('.mySwiper', {
                slidesPerView: 1.2,
                spaceBetween: 16,
                loop: false,
                grabCursor: true,
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true, // biar bisa diklik
                    type: 'bullets', // tipe bullet (titik-titik)
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                breakpoints: {
                    576: { slidesPerView: 2 },
                    768: { slidesPerView: 3 },
                    992: { slidesPerView: 4 },
                },
            });

            //Infografis
            const counters = document.querySelectorAll('.info_grafis h4');
            const speed = 200;

            counters.forEach(counter => {
                const target = +counter.innerText;
                const count = +counter.innerText;
                const increment = target / speed;
                
                if(count < target) {
                    counter.innerText = Math.ceil(count + increment);
                    setTimeout(updateCount, 1);
                } else {
                    counter.innerText = target;
                }
            });
            // Cek saat load
            tampilkanDenganTransisi();
            
            // Cek saat scroll
            $(window).scroll(function() {
                tampilkanDenganTransisi();
            });
            
            function tampilkanDenganTransisi() {
                $('.show_transisi').each(function() {
                    const elementPosition = $(this).offset().top;
                    const screenPosition = $(window).scrollTop() + $(window).height()/1.2;
                    
                    if(elementPosition < screenPosition) {
                        $(this).addClass('muncul');
                    }
                });
            }

            //back To top
            $(window).scroll(function() {
                if ($(this).scrollTop() > 200) {
                    $('#backToTopBtn').fadeIn();
                } else {
                    $('#backToTopBtn').fadeOut();
                }
            });

            $('#backToTopBtn').click(function() {
                $('html, body').animate({scrollTop: 0}, 'smooth');
                return false;
            });

            //PARTNER SWIPER
            // Inisialisasi untuk partner swiper
            const partnerSwiper = new Swiper('.partnerSwiper', {
                loop: true,
                autoplay: {
                    delay: 2500,
                    disableOnInteraction: false,
                },
                slidesPerView: 2,
                spaceBetween: 20,
                centeredSlides: true,
                grabCursor: true,
                
                breakpoints: {
                    576: {
                        slidesPerView: 3,
                        spaceBetween: 20
                    },
                    768: {
                        slidesPerView: 4,
                        spaceBetween: 30
                    },
                    992: {
                        slidesPerView: 5,
                        spaceBetween: 40
                    }
                },
                
                pagination: {
                    el: '.partner-pagination',
                    clickable: true,
                    dynamicBullets: true
                },
            });

            //ROOM SWIPER
            new Swiper(".room_swiper", {
                slidesPerView: 1.2,
                spaceBetween: 2, // atau kurangi jadi 8 jika terlalu renggang
                breakpoints: {
                    576: {
                        slidesPerView: 1,
                        spaceBetween: 16
                    },
                    768: {
                        slidesPerView: 2,
                        spaceBetween: 20
                    },
                    992: {
                        slidesPerView: 4,
                        spaceBetween: 24
                    }
                },
                pagination: {
                    el: ".room-pagination",
                    clickable: true,
                },
                navigation: {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev",
                },
            });

        });
    </script>
</html>