<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="<?php echo "$setting_viewport"; ?>">
    <meta name="description" content="<?php echo "$setting_description"; ?>">
    <meta name="keywords" content="<?php echo "$setting_keywords"; ?>">
    <meta name="author" content="<?php echo "$setting_author"; ?>">
    <meta name="robots" content="<?php echo "$setting_robots"; ?>">
    <title><?php echo "$setting_title"; ?></title>

    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo "$setting_title"; ?>">
    <meta property="og:description" content="<?php echo "$setting_description"; ?>">
    <meta property="og:image" content="<?php echo $setting_base_url; ?>/assets/img/<?php echo $setting_og_image; ?>">
    <meta property="og:url" content="<?php echo $setting_base_url; ?>">
    <meta property="og:type" content="<?php echo $setting_type; ?>">

    <!-- Open Graph untuk Social Media (Facebook, LinkedIn, dll) -->
    <meta property="og:title" content="<?php echo $setting_title; ?>">
    <meta property="og:description" content="<?php echo $setting_description; ?>">
    <meta property="og:type" content="<?php echo $setting_type; ?>">
    <meta property="og:url" content="<?php echo $setting_base_url; ?>">
    <meta property="og:image" content="<?php echo $setting_base_url; ?>/assets/img/<?php echo $setting_32x32; ?>"> 
    <!-- Ganti jika punya gambar banner OG khusus -->

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $setting_title; ?>">
    <meta name="twitter:description" content="<?php echo $setting_description; ?>">
    <meta name="twitter:image" content="<?php echo $setting_base_url; ?>/assets/img/<?php echo $setting_32x32; ?>"> 
    <!-- Ganti jika punya gambar banner Twitter khusus -->

    <!-- Favicon & Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $setting_base_url; ?>/assets/img/<?php echo $setting_180x180; ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $setting_base_url; ?>/assets/img/<?php echo $setting_32x32; ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $setting_base_url; ?>/assets/img/<?php echo $setting_16x16; ?>">
    <link rel="manifest" href="<?php echo $setting_base_url; ?>/assets/img/<?php echo $setting_manifest; ?>">
    
    <!-- Canonical URL (SEO untuk mencegah duplikat) -->
    <link rel="canonical" href="<?php echo $setting_base_url; ?>">

    <!-- Bootstrap -->
    <link href="<?php echo $setting_base_url; ?>/node_modules\bootstrap\dist\css\bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <script src="<?php echo $setting_base_url; ?>/node_modules\bootstrap\dist\js\bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>

    <!-- Bootstrap Icon -->
    <link rel="stylesheet" href="<?php echo $setting_base_url; ?>/node_modules/bootstrap-icons/font/bootstrap-icons.min.css">

    <!-- Google Fonts Roboto -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap">

    <!-- Swiper -->
    <link rel="stylesheet" href="<?php echo $setting_base_url; ?>/node_modules/swiper/swiper-bundle.min.css"/>
    <script src="<?php echo $setting_base_url; ?>/node_modules/swiper/swiper-bundle.min.js"></script>

    <!-- Go JS -->
    <script src="<?php echo $setting_base_url; ?>/assets/GoJs/release/go.js"></script>

    <!-- Custome CSS -->
    <link rel="stylesheet" href="<?php echo $setting_base_url; ?>/assets/css/custome.css?v=<?php echo date('YmdHis'); ?>">
</head>