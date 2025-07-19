<section class="breadcrumb-section">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo "$setting_berita_artikel_title"; ?></li>
            </ol>
        </nav>
    </div>
    <div class="container">
        <div class="row mt-3 mb-3">
            <div class="col-12">
                <h2 class="h1 mb-4 title_segment text-light"><?php echo "$setting_berita_artikel_title"; ?></h2>
            </div>
        </div>
    </div>
</section>
<section class="section bg-white p-4">
    <div class="container">
        <div class="row mb-3 mt-4">
            <div class="col-md-8 mb-4" id="list_data_blog">
                <?php
                    if(empty($_GET['id'])){
                        $id="";
                        //Apabila ada pencarian
                        if(!empty($_GET['keyword'])){
                            echo '
                                <div class="row mb-3">
                                    <div class="col-md-12 mb-3">
                                        <div class="alert alert-info">
                                            Menampilkan Daftar Berdasarkan Pencarian <b>'.$_GET['keyword'].'</b>
                                        </div>
                                    </div>
                                </div>
                            ';
                        }
                        //Apabila ada tag
                        if(!empty($_GET['tag'])){
                            echo '
                                <div class="row mb-3">
                                    <div class="col-md-12 mb-3">
                                        <div class="alert alert-info">
                                            Menampilkan Daftar Berdasarkan Tag/Kategori <b>'.$_GET['tag'].'</b>
                                        </div>
                                    </div>
                                </div>
                            ';
                        }
                        echo '
                            <div class="row">
                                <div class="col-md-12" id="show_list_blog">
                                    <!-- List Blog Ditampilkan Disini -->
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-md-12 mb-4 text-center">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-primary" id="prev_button">
                                            <i class="bi bi-chevron-left"></i>
                                        </button>
                                        <button type="button" disabled class="btn btn-outline-primary" id="page_position">
                                            1/10
                                        </button>
                                        <button type="button" class="btn btn-primary" id="next_button">
                                            <i class="bi bi-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ';
                    }else{
                        $id=$_GET['id'];
                        // Ambil data tag/kategori dari tabel blog_tag
                        $sql_tags = "SELECT blog_tag FROM blog_tag WHERE id_blog = :id_blog";
                        $stmt_tags = $Conn->prepare($sql_tags);
                        $stmt_tags->bindParam(':id_blog', $id);
                        $stmt_tags->execute();
                        $tags = $stmt_tags->fetchAll(PDO::FETCH_COLUMN); // Ambil hanya kolom blog_tag

                        //Buka Detail Blog
                        $sql_blog = "SELECT * FROM blog WHERE id_blog='$id'";
                        $stmt_blog = $Conn->prepare($sql_blog);
                        $stmt_blog->execute();
                        $blog_list = $stmt_blog->fetchAll();
                        if (count($blog_list) > 0) {
                            foreach ($blog_list as $blog) {
                                $title_blog = htmlspecialchars($blog['title_blog']);
                                $date_time_creat_blog = date('d/m/Y H:i T', strtotime($blog['datetime_creat']));
                                $author_blog = $blog['author_blog'];
                                $content_blog = $blog['content_blog'];
                                $deskripsi_blog = $blog['deskripsi'];
                                $cover_blog = htmlspecialchars($blog['cover']);
                                $cover_blog = $base_url . 'image_proxy.php?segment=Artikel&image_name=' . $blog['cover'];
                                echo '
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <h2>'.$title_blog.'</h2>
                                            <small>'.$date_time_creat_blog.' - '.$author_blog.'</small>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <img src="'.$cover_blog.'" width="100%">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-12 border-1 mb-3">
                                            '.$deskripsi_blog.'
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <div class="col-md-12 border-1 border-bottom mb-3">
                                        </div>
                                    </div>
                                ';
                                //Decript Json
                                $content_blog_array = json_decode($content_blog, true);

                                if (is_array($content_blog_array)) {
                                    foreach ($content_blog_array as $item) {
                                        $type = $item['type'] ?? '';
                                        $order_id = $item['order_id'] ?? '';

                                        // Tampilkan konten HTML biasa
                                        if ($type === 'html') {
                                            echo '<div class="blog-html" style="margin-bottom: 1rem;">';
                                            echo $item['content']; // Sudah dalam format HTML
                                            echo '</div>';
                                        }

                                        // Tampilkan konten gambar
                                        elseif ($type === 'image') {
                                            $width = $item['width'] ?? '100';
                                            $unit = $item['unit'] === '%' ? '%' : 'px';
                                            $position = $item['position'] ?? 'left';
                                            $caption = $item['caption'] ?? '';
                                            $imageSrc = $base_url . 'image_proxy.php?segment=Artikel&image_name=' .htmlspecialchars($item['content']);

                                            if($position=="left"){
                                                $text_position="text-left";
                                            }else{
                                                if($position=="right"){
                                                    $text_position="text-right";
                                                }else{
                                                    if($position=="center"){
                                                        $text_position="text-center";
                                                    }else{
                                                        $text_position="text-left";
                                                    }
                                                }
                                            }
                                            echo '
                                                <div class="row mb-3">
                                                    <div class="col-md-12 mb-3 '.$text_position.'">
                                                        <img src="' . $imageSrc . '" width="' . $width . '' . $unit . '">
                                                        <div class="caption" style="font-size: 0.9rem; color: #666;">' . htmlspecialchars($caption) . '</div>
                                                    </div>
                                                </div>
                                            ';
                                        }
                                    }
                                } else {
                                    echo "Konten blog tidak valid.";
                                }
                            }
                        } else {
                            echo '<p class="text-muted">Data Tidak Ditemukan</p>';
                        }
                       echo '
                        <div class="row mb-3">
                            <div class="col-md-12 mb-3 border-1 border-top">
                                <!-- Konten lainnya -->
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12 mb-3">
                                <h4>Tag/Kategori:</h4>';
                                
                                if (empty($tags)) {
                                    echo '<em>Tidak ada</em>';
                                } else {
                                    foreach ($tags as $tag) {
                                        $tag_safe = htmlspecialchars($tag);
                                        echo '<a href="?tag=' . urlencode($tag) . '" class="btn btn-outline-primary btn-sm me-2 mb-2">' . $tag_safe . '</a>';
                                    }
                                }

                        echo '
                            </div>
                        </div>';
                    }
                ?>
            </div>
            <div class="col-md-1 mb-4">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <button id="toggle_widget_blog_btn" class="btn btn-outline-secondary">
                            Tampilkan Filter & Widget
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4" id="widget_blog">
                <!-- Anda bisa isi ini dengan berita terbaru atau kategori lainnya -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h4>Pencarian</h4>
                    </div>
                    <div class="col-12">
                        <form action="">
                            <div class="input-group">
                                <input type="text" name="keyword" class="form-control" placeholder="Judul/Deskripsi">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                        <form action="javascript:void(0);" id="filter_blog">
                            <div class="input-group">
                                <?php
                                    if(empty($_GET['id'])){
                                        $id="";
                                    }else{
                                        $id=$_GET['id'];
                                    }
                                    if(empty($_GET['tag'])){
                                        $tag="";
                                        $keyword_by="";
                                        if(empty($_GET['keyword'])){
                                            $keyword="";
                                        }else{
                                            $keyword=$_GET['keyword'];
                                        }
                                    }else{
                                        $keyword_by="blog_tag";
                                        $keyword=$_GET['tag'];
                                        $tag=$_GET['tag'];
                                    }
                                ?>
                                <input type="hidden" name="id" id="id" value="<?php echo $id; ?>">
                                <input type="hidden" name="page" id="page" value="1">
                                <input type="hidden" name="keyword_by" id="keyword_by" value="<?php echo $keyword_by; ?>">
                                <input type="hidden" name="batas" id="batas" value="10">
                                <input type="hidden" name="ShortBy" id="ShortBy" value="DESC">
                                <input type="hidden" name="OrderBy" id="OrderBy" value="datetime_creat">
                                <input type="hidden" name="keyword" id="keyword" class="form-control" value="<?php echo $keyword; ?>">
                            </div>
                        </form>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h4>Tag/Kategori</h4>
                    </div>
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-2">
                            <?php
                                //Jumlah Total Post
                                $sql_total_post = "SELECT COUNT(*) as total FROM blog";
                                $stmt_total_post = $Conn->prepare($sql_total_post);
                                $stmt_total_post->execute();
                                $row_total = $stmt_total_post->fetch(PDO::FETCH_ASSOC);
                                $jumlah_total = $row_total['total'];

                                //Menampilkan tag
                                $sql_tag = "SELECT blog_tag, COUNT(DISTINCT id_blog) AS jumlah 
                                            FROM blog_tag 
                                            GROUP BY blog_tag 
                                            ORDER BY jumlah DESC";
                                $stmt_tag = $Conn->prepare($sql_tag);
                                $stmt_tag->execute();
                                $tag_list = $stmt_tag->fetchAll();

                                if (count($tag_list) > 0) {
                                    foreach ($tag_list as $tag_item) {
                                        $nama_tag = htmlspecialchars($tag_item['blog_tag']);
                                        $jumlah = $tag_item['jumlah'];

                                        // Cek apakah ini tag yang sedang dipilih
                                        $is_active = ($nama_tag === $tag) ? 'bg-dark' : 'bg-primary';

                                        echo '<a href="?tag=' . urlencode($nama_tag) . '" class="badge ' . $is_active . ' text-white text-decoration-none p-2 me-1 mb-1">';
                                        echo $nama_tag . ' <span class="badge bg-light text-dark ms-1">' . $jumlah . '</span>';
                                        echo '</a>';
                                    }

                                    // Tombol "Semua"
                                    $semua_active = ($tag === "") ? 'bg-dark' : 'bg-primary';
                                    echo '<a href="Blog" class="badge ' . $semua_active . ' text-white text-decoration-none p-2 me-1 mb-1">';
                                    echo 'Semua <span class="badge bg-light text-dark ms-1">' . $jumlah_total . '</span>';
                                    echo '</a>';
                                } else {
                                    echo '<p class="text-muted">Belum ada tag yang tersedia.</p>';
                                }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="popular-posts">
                        <h5 class="mb-3">Popular Post</h5>
                        <?php
                            $sql_popular = "SELECT b.id_blog, b.title_blog, b.cover, COUNT(v.id_blog) AS jumlah_view
                                            FROM blog b
                                            LEFT JOIN blog_viewer v ON v.id_blog = b.id_blog
                                            WHERE b.publish = 1
                                            GROUP BY b.id_blog
                                            ORDER BY jumlah_view DESC, b.datetime_creat DESC
                                            LIMIT 5";
                            $stmt_popular = $Conn->prepare($sql_popular);
                            $stmt_popular->execute();
                            $popular_list = $stmt_popular->fetchAll();

                            if (count($popular_list) > 0) {
                                foreach ($popular_list as $row) {
                                    $title = htmlspecialchars($row['title_blog']);
                                    $cover = $base_url . 'image_proxy.php?segment=Artikel&image_name=' . $row['cover'];
                                    $id_blog = $row['id_blog'];
                                    $url = $base_url . '/Blog?id=' . $id_blog;
                                    ?>
                                    <div class="d-flex mb-3">
                                        <div style="flex: 0 0 80px;">
                                            <a href="<?= $url ?>">
                                                <img src="<?= $cover ?>" alt="cover" class="img-fluid" style="width: 80px; height: 80px; object-fit: cover; border-radius: 5px;">
                                            </a>
                                        </div>
                                        <div class="ps-3">
                                            <a href="<?= $url ?>" class="text-decoration-none text-dark fw-semibold">
                                                <?= $title ?>
                                            </a>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } else {
                                echo "<p class='text-muted'>Belum ada postingan populer.</p>";
                            }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


