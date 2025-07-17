<!-- <section class="hero-section d-flex align-items-center text-white text-center">
    <div class="container">
        <h3 class="lead fs-2">Selamat Datang</h3>
        <h1 class="display-4 fw-bold title_segment">Di RSU El-Syifa Kuningan</h1>
        <h2 class="lead fs-2 mt-3">
            IGD 24 JAM : (0232) 876240 / +6289601154726
        </h2>
        <div class="social-icons mt-4 d-flex justify-content-center gap-3">
            <a href="https://wa.me/62xxxxxxxxxxx" target="_blank" class="btn-social">
                <i class="bi bi-whatsapp"></i>
            </a>
            <a href="https://www.instagram.com/rsuelsyifa" target="_blank" class="btn-social">
                <i class="bi bi-instagram"></i>
            </a>
            <a href="https://www.youtube.com/@rsuelsyifa" target="_blank" class="btn-social">
                <i class="bi bi-youtube"></i>
            </a>
            <a href="https://www.facebook.com/rsuelsyifa" target="_blank" class="btn-social">
                <i class="bi bi-facebook"></i>
            </a>
        </div>
    </div>
</section> -->


<div id="carouselHero" class="carousel slide" data-bs-ride="carousel">
    <!-- Indicators -->
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#carouselHero" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#carouselHero" data-bs-slide-to="1"></button>
        <button type="button" data-bs-target="#carouselHero" data-bs-slide-to="2"></button>
    </div>
    
    <!-- Slides -->
    <div class="carousel-inner">
        <?php
            // Menampilkan Hero Welcome
            echo '
                <div class="carousel-item active" data-bs-interval="5000">
                    <img src="assets/img/_component/'.$setting_hero_welcome['image'].'" class="d-block w-100" alt="Slide 1">
                    <div class="carousel-caption">
                        <h5>'.$setting_hero_welcome['title'].'</h5>
                        <h3>'.$setting_hero_welcome['sub_title'].'</p>
                    </div>
                </div>
            ';

            //Menampilkan Hero IGD
            echo '
                <div class="carousel-item" data-bs-interval="5000">
                    <img src="assets/img/_component/'.$setting_hero_igd['image'].'" class="d-block w-100" alt="Slide 2">
                    <div class="carousel-caption">
                        <h5>'.$setting_hero_igd['title'].'</h5>
                        <h3>'.$setting_hero_igd['sub_title'].'</p>
                    </div>
                </div>
            ';

            //Menampilkan Hero Media Sosial
            echo '<div class="carousel-item" data-bs-interval="5000">';
            echo '  <img src="assets/img/_component/'.$setting_hero_media_sosial['image'].'" class="d-block w-100" alt="Slide 3">';
            echo '  <div class="carousel-caption">';
            echo '      <h5>'.$setting_hero_media_sosial['title'].'</h5>';
            echo '      <p>';
            echo '          <div class="social-icons mt-4 d-flex justify-content-center gap-3">';
                                foreach ($setting_hero_media_sosial['sub_title'] as $list_media_sosial) {
                                    echo '
                                        <a href="'.$list_media_sosial['url'].'" target="_blank" class="btn-social">
                                            '.$list_media_sosial['icon'].'
                                        </a>
                                    ';
                                }   
            echo '          </div>';
            echo '      </p>';
            echo '  </div>';
            echo '</div>';
        ?>
    </div>
    
    <!-- Controls -->
    <button class="carousel-control-prev" type="button" data-bs-target="#carouselHero" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#carouselHero" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
</div>

<!-- SAMBUTAN -->
<div class="section bg-light">
    <div class="container my-5">
        <div class="row mb-3">
            <div class="col-12 text-center">
                <h2 class="h1 mb-4 title_segment_dark"><?php echo "$setting_title_sambutan"; ?></h2>
            </div>
        </div>
        <div class="row mb-5">
            <div class="col-md-4 text-center mb-3">
                <div class="foto-wrapper rounded-circle mx-auto mb-3" style="width: 80%; overflow: hidden;">
                    <img src="<?php echo $base_url; ?>image_proxy.php?segment=Direktur&image_name=<?php echo "$setting_foto_sambutan"; ?>" 
                        alt="<?php echo "$setting_title_sambutan"; ?>" 
                        class="foto_direktur w-100" />
                </div>
                <h4 class="h4 mb-1 text-decoration-underline"><?php echo "$setting_name_sambutan"; ?></h3>
                <?php echo "$setting_sub_title_sambutan"; ?>
            </div>
            <div class="col-md-8 mb-3">
                <div class="text-muted" id="preview_sambutan">
                    <?php echo "$setting_opening_sambutan"; ?>
                </div>
                <div class="text-muted" id="full_sambutan">
                    <?php echo "$setting_isi_sambutan"; ?>
                </div>
                <div>
                    <a href="#" id="toggle_sambutan">Baca Selengkapnya</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-muted">
                    
                </div>
            </div>
        </div>
    </div>
</div>

<!-- INFO GRAFIS -->
<div class="section infografis">
     <div class="info_grafis_bg_wrapper">
        <img src="<?php echo $base_url; ?>assets/img/_Ui/<?php echo "$setting_info_grafis_bg_image"; ?>" class="info_grafis_bg">
    </div>
    <div class="container my-5">
        <div class="row">
            <div class="col-12 text-center">
                <h2 class="h1 mb-4 title_segment text-light">
                    <?php echo "$setting_info_grafis_title"; ?>
                </h2>
            </div>
        </div>
        <div class="row">
            <?php
                foreach ($setting_info_grafis_list_content as $info_grafis_list) {
                    $count_info_grafis=$info_grafis_list['count'];
                    $count_info_grafis_format= "" . number_format($count_info_grafis, 0, ',', '.');
                    $formatted_count = formatShortNumber($count_info_grafis);
                    echo '
                        <div class="col-6 col-sm-6 col-md-3 mb-4">
                            <div class="info_grafis d-flex align-items-center p-3 rounded-4 shadow-sm show_transisi">
                                <div class="icon-circle me-3">
                                    '.$info_grafis_list['icon'].'
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-semibold">'.$info_grafis_list['name'].'</h6>
                                    <h4 class="mb-0 fw-bold">'.$formatted_count.'</h4>
                                </div>
                            </div>
                        </div> 
                    ';
                }
            ?>
        </div>
    </div>
</div>

<!-- VISI MISI -->
<div class="section bg-white">
    <div class="container my-5">
        <div class="row mb-3">
            <div class="col-12 text-center">
                <h2 class="h1 mb-4 title_segment_dark">
                    <?php echo "$setting_visi_misi_title"; ?>
                </h2>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4 show_transisi">
                <p>
                    <i>
                        <b>Visi : </b><br>
                        <?php echo "$setting_visi_misi_visi"; ?>
                    </i>
                </p>
            </div>
            <div class="col-md-4 show_transisi">
                <p>
                    <i>
                        <b>Misi :</b>
                        <?php echo "$setting_visi_misi_misi"; ?>
                    </i>
                </p>
            </div>
            <div class="col-md-4 show_transisi">
                <p>
                    <i>
                        <b>Motto :</b><br>
                        <?php echo "$setting_visi_misi_motto"; ?>
                    </i>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- JADWAL DOKTER -->
<div class="section">
    <div class="container my-5">
        <div class="row mb-3">
            <div class="col-12 text-center">
                <span class="h1 mb-4 title_segment_dark"><?php echo $setting_jadwal_dokter_title; ?></span>
                <p><i><?php echo $setting_jadwal_dokter_subtitle; ?></i></p>
            </div>
        </div>
        <!-- SWIPER SLIDER DOKTER -->
        <div class="row mb-3">
            <div class="col-md-12 position-relative">
                <div class="swiper mySwiper">
                    <div class="swiper-wrapper mb-4">
                        <?php
                            $limit_dokter = $setting_jadwal_dokter_limit;
                            $sql_dokter = "SELECT id_dokter, kode, nama, spesialis, foto FROM dokter ORDER BY id_dokter DESC LIMIT :limit";
                            $stmt_dokter = $Conn->prepare($sql_dokter);
                            $stmt_dokter->bindParam(':limit', $limit_dokter, PDO::PARAM_INT);
                            $stmt_dokter->execute();
                            $dokterList = $stmt_dokter->fetchAll();
                            if (count($dokterList) > 0) {
                                foreach ($dokterList as $dokter) {
                                    echo '
                                        <div class="swiper-slide">
                                            <div class="card border-0 shadow doctor-card show_transisi">
                                                <img src="'.$base_url.'image_proxy.php?segment=Dokter&image_name='.$dokter['foto'].'" class="card-img-top" alt="'.$dokter['nama'].'">
                                                <div class="card-body text-center">
                                                    <h5 class="card-title">'.$dokter['nama'].'</h5>
                                                    <p class="card-text text-muted">'.$dokter['spesialis'].'</p>
                                                    <a href="javascript:void(0);" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#ModalDetailDokter" data-id="'.$dokter['id_dokter'].'">
                                                        Lihat Detail <i class="bi bi-chevron-right"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    ';
                                }
                            }
                        ?>
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-12 text-center align-content-center">
                <button type="button" class="baca_selengkapnya">
                    Lihat Selengkapnya
                </button>
            </div>
        </div>

    </div>
</div>

<!-- PENDAFTARAN ANTRIAN (JKN MOBILE) -->
<div class="section pendaftaran_antrian">
    <div class="container my-5 py-4">
        <div class="row align-items-center">
            <div class="col-md-12 text-center mb-3">
                <h2 class="h1 mb-3 title_segment_dark">
                    <?php echo  $setting_pendaftaran_antrian_title; ?>
                </h2>
                <p class="lead text-dark fw-normal show_transisi">
                    <?= htmlspecialchars_decode($setting_pendaftaran_antrian_subtitle); ?>
                </p>
            </div>
            <div class="col-md-12 text-center mb-3">
                <a href="<?php echo  $setting_pendaftaran_antrian_url; ?>" class="btn-jkn mt-3 d-inline-block show_transisi">
                    <?= html_entity_decode($setting_pendaftaran_antrian_icon); ?>
                    <?php echo  "$setting_pendaftaran_antrian_label"; ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- UNIT DAN INSTALASI -->
<div class="section service">
     <div class="service_bg_wrapper">
        <img src="<?php echo $base_url; ?>assets/img/_Ui/<?php echo "$setting_unit_instalasi_bg_image"; ?>" class="service_bg">
    </div>
    <div class="container my-5">
        <div class="row">
            <div class="col-12 text-center">
                <h2 class="h1 mb-4 service_segment"><?php echo "$setting_unit_instalasi_title"; ?></h2>
            </div>
        </div>
        <div class="row mb-3">
            <?php
                if(!empty($setting_unit_instalasi_list_content)){
                    foreach ($setting_unit_instalasi_list_content as $setting_unit_instalasi_list_content_list) {
                        $unit_instalasi_id=$setting_unit_instalasi_list_content_list['id'];
                        $unit_instalasi_name=$setting_unit_instalasi_list_content_list['name'];
                        $unit_instalasi_icon=$setting_unit_instalasi_list_content_list['icon'];
                        echo '
                            <div class="col-12 col-sm-12 col-md-6 col-lg-4 mb-4">
                                <div class="service_item d-flex align-items-center p-3 rounded-4 shadow-sm show_transisi">
                                    <div class="icon-circle me-3">
                                        '.$unit_instalasi_icon.'
                                    </div>
                                    <div>
                                        <h6 class="mb-1 fw-semibold">'.$unit_instalasi_name.'</h6>
                                        <a href="" class="text-primary text-decoration-underline">Selengkapnya</i></a>
                                    </div>
                                </div>
                            </div>
                        ';
                    }
                }
            ?>
        </div>
        <div class="row">
            <div class="col-12 text-center align-content-center">
                <button type="button" class="baca_selengkapnya_green">
                    Lihat Selengkapnya
                </button>
            </div>
        </div>
    </div>
</div>

<!-- RUANG RAWAT INAP -->
<div class="section bg-light">
    <div class="container my-5">
        <div class="row mb-3">
            <div class="col-12 text-center">
                <span class="h1 mb-4 title_segment_dark">Ruang Rawat Inap</span>
                <p><i>Ketersediaan Ruang Rawat Inap 24 Jam</i></p>
            </div>
        </div>
        <div class="row mb-5 mt-3">
            <div class="col-12">
                <div class="swiper room_swiper">
                    <div class="swiper-wrapper mb-4">
                        <?php
                            $limit_rr = 10;
                            $sql_rr = "SELECT id_ruang_rawat, ruang_rawat, kelas, kode_kelas FROM ruang_rawat ORDER BY datetime_update DESC LIMIT :limit";
                            $stmt_ss = $Conn->prepare($sql_rr);
                            $stmt_ss->bindParam(':limit', $limit_rr, PDO::PARAM_INT);
                            $stmt_ss->execute();
                            $RuangRawatList = $stmt_ss->fetchAll();
                            if (count($RuangRawatList) > 0) {
                                foreach ($RuangRawatList as $ruang_rawat) {
                                    echo '
                                        <div class="swiper-slide">
                                            <div class="room-card show_transisi">
                                                <span class="room-icon">
                                                    <i class="bi bi-building-check"></i>
                                                </span>
                                                <h3 class="class-room-name">'.$ruang_rawat['ruang_rawat'].'<br>('.$ruang_rawat['kelas'].')</h3>
                                                <a href="" class="room-detail">Lihat Detail</a>
                                            </div>
                                        </div>
                                    ';
                                }
                            }
                        ?>
<!--                         
                        <div class="swiper-slide">
                            <div class="room-card show_transisi">
                                <span class="room-icon">
                                    <i class="bi bi-building-check"></i>
                                </span>
                                <h3 class="class-room-name">Kelas 1 Umum Ketersediaan Ruang Rawat Inap 24 Jam</h3>
                                <a href="" class="room-detail">Lihat Detail</a>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="room-card show_transisi">
                                <span class="room-icon">
                                    <i class="bi bi-building-check"></i>
                                </span>
                                <h3 class="class-room-name">Kelas 2 Umum Ketersediaan Ruang Rawat Inap 24 Jam</h3>
                                <a href="" class="room-detail">Lihat Detail</a>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="room-card show_transisi">
                                <span class="room-icon">
                                    <i class="bi bi-building-check"></i>
                                </span>
                                <h3 class="class-room-name">Kelas 3 Umum Ketersediaan Ruang Rawat Inap 24 Jam</h3>
                                <a href="" class="room-detail">Lihat Detail</a>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="room-card show_transisi">
                                <span class="room-icon">
                                    <i class="bi bi-building-check"></i>
                                </span>
                                <h3 class="class-room-name">Kelas 3 Umum Ketersediaan Ruang Rawat Inap 24 Jam</h3>
                                <a href="" class="room-detail">Lihat Detail</a>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="room-card show_transisi">
                                <span class="room-icon">
                                    <i class="bi bi-building-check"></i>
                                </span>
                                <h3 class="class-room-name">Kelas 3 Umum Ketersediaan Ruang Rawat Inap 24 Jam</h3>
                                <a href="" class="room-detail">Lihat Detail</a>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="room-card show_transisi">
                                <span class="room-icon">
                                    <i class="bi bi-building-check"></i>
                                </span>
                                <h3 class="class-room-name">Kelas 3 Umum Ketersediaan Ruang Rawat Inap 24 Jam</h3>
                                <a href="" class="room-detail">Lihat Detail</a>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="room-card show_transisi">
                                <span class="room-icon">
                                    <i class="bi bi-building-check"></i>
                                </span>
                                <h3 class="class-room-name">Kelas 3 Umum Ketersediaan Ruang Rawat Inap 24 Jam</h3>
                                <a href="" class="room-detail">Lihat Detail</a>
                            </div>
                        </div> -->
                    </div>
                    <div class="room-pagination"></div>
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-12 text-center align-content-center">
                <button type="button" class="baca_selengkapnya">
                    Lihat Selengkapnya
                </button>
            </div>
        </div>
    </div>
</div>

<!-- BERITA DAN ARTIKEL -->
<div class="section">
    <div class="container my-5">
        <div class="row mb-3">
            <div class="col-12 text-center">
                <span class="h1 mb-4 title_segment_dark">
                    <?php echo "$setting_berita_artikel_title"; ?>
                </span>
                <p><i><?php echo "$setting_berita_artikel_subtitle"; ?></i></p>
            </div>
        </div>
        <div class="row mb-5">
            <?php
                $limit_berita = $setting_berita_artikel_limit;
                $sql_berita = "SELECT * FROM  blog  ORDER BY datetime_creat DESC LIMIT :limit";
                $stmt_berita = $Conn->prepare($sql_berita);
                $stmt_berita->bindParam(':limit', $limit_berita, PDO::PARAM_INT);
                $stmt_berita->execute();
                $berita_list = $stmt_berita->fetchAll();
                if (count($berita_list) > 0) {
                    foreach ($berita_list as $berita_artikel) {
                        $date_time_creat_blog=date('d/m/Y',strtotime($berita_artikel['datetime_creat']));
                        echo '
                            <div class="col-6 col-sm-6 col-md-4 col-lg-3 mb-3">
                                <div class="card h-100 d-flex flex-column show_transisi" style="width: 100%;">
                                    <div class="img-square-wrapper">
                                        <img src="'.$base_url.'image_proxy.php?segment=Artikel&image_name='.$berita_artikel['cover'].'" class="card-img-top" alt="...">
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="'.$base_url.'/Blog?id='.$berita_artikel['id_blog'].'" class="text text-decoration-none">'.$berita_artikel['title_blog'].'</a>
                                        </h5>
                                        <p class="card-text">'.$date_time_creat_blog.'</p>
                                    </div>
                                </div>
                            </div>
                        ';
                    }
                }
            ?>
        </div>
        <div class="row mb-3">
            <div class="col-12 text-center align-content-center">
                <button type="button" class="baca_selengkapnya" target-link="<?php echo $base_url; ?>Blog">
                    Lihat Selengkapnya
                </button>
            </div>
        </div>
    </div>
</div>

<!-- GALERI FOTO -->
<div class="section bg-light">
    <div class="container my-5">
        <div class="row mb-3">
            <div class="col-12 text-center">
                <span class="h1 mb-4 title_segment_dark">Galeri & Aktivitas</span>
                <p>
                    <i>Arsip dan galeri kegiatan yang pernah dilaksanakan</i>
                </p>
            </div>
        </div>
        <div class="row mb-5">
            <div class="col-md-3 mb-3">
                <div class="achievement-item show_transisi">
                    <img src="<?php echo $base_url; ?>assets/img/_Galeri/galeri_1.png" alt="Achievement">
                    <div class="achievement-overlay">
                        <h4>
                            <a href="Galeri?id=1" class="text text-decoration-none text-white">Global Alumni Network</a>
                        </h4>
                        <p>Fusce consectetur, enim eget aliquet volutpat, lacus nulla semper velit, et luctus.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="achievement-item show_transisi">
                    <img src="<?php echo $base_url; ?>assets/img/_Galeri/galeri_2.png" alt="Achievement">
                    <div class="achievement-overlay">
                        <h4>Global Alumni Network</h4>
                        <p>Fusce consectetur, enim eget aliquet volutpat, lacus nulla semper velit, et luctus.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="achievement-item show_transisi">
                    <img src="<?php echo $base_url; ?>assets/img/_Galeri/galeri_3.png" alt="Achievement">
                    <div class="achievement-overlay">
                        <h4>Global Alumni Network</h4>
                        <p>Fusce consectetur, enim eget aliquet volutpat, lacus nulla semper velit, et luctus.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="achievement-item show_transisi">
                    <img src="<?php echo $base_url; ?>assets/img/_Galeri/galeri_4.png" alt="Achievement">
                    <div class="achievement-overlay">
                        <h4>Global Alumni Network</h4>
                        <p>Fusce consectetur, enim eget aliquet volutpat, lacus nulla semper velit, et luctus.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-12 text-center align-content-center">
                <button type="button" class="baca_selengkapnya">
                    Lihat Selengkapnya
                </button>
            </div>
        </div>
    </div>
</div>

<!-- PARTNERSHIP -->
<div class="section">
    <div class="container my-5">
        <div class="row mb-3">
            <div class="col-12 text-center">
                <h2 class="h1 mb-4 title_segment">Partnership</h2>
            </div>
        </div>
        <div class="row mb-5">
            <div class="col-md-12 position-relative">
                <div class="swiper partnerSwiper"> <!-- Ganti class menjadi partnerSwiper -->
                    <div class="swiper-wrapper">
                        <?php
                            if(!empty($arry_static['partnership'])){
                                $partnership=$arry_static['partnership'];
                                foreach ($partnership as $partnership_list) {
                                    echo '
                                         <div class="swiper-slide d-flex align-items-center justify-content-center">
                                            <img src="'.$base_url.'assets/img/_Partnership/'.$partnership_list['logo'].'" class="image_partnership" alt="'.$partnership_list['company'].'">
                                        </div>
                                    ';
                                }
                            }
                        ?>
                    </div>
                    <div class="partner-pagination swiper-pagination"></div> <!-- Tambahkan class khusus -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- GOOGLE MAP -->
 <?php
    if(!empty($arry_static['google_map'])){
        echo '
            <div class="container-fluid px-0 g-0 mb-0">
                <iframe src="'.$arry_static['google_map']['src'].'" 
                width="'.$arry_static['google_map']['width'].'" 
                height="'.$arry_static['google_map']['height'].'" 
                style="'.$arry_static['google_map']['style'].'" 
                allowfullscreen="'.$arry_static['google_map']['allowfullscreen'].'" 
                loading="'.$arry_static['google_map']['loading'].'" 
                referrerpolicy="'.$arry_static['google_map']['referrerpolicy'].'" class="'.$arry_static['google_map']['class'].'">
                </iframe>
            </div>
        ';
    }
 ?>
