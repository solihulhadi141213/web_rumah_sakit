<?php
include "_Config/Function.php";

if (!empty($_GET['segment']) && !empty($_GET['image_name'])) {
    $segment = validateAndSanitizeInput($_GET['segment']);
    $image_name = validateAndSanitizeInput($_GET['image_name']);

    // Logika folder
    if($segment == "Direktur"){
        $folder="_Direktur";
    }else{
        if($segment == "Dokter"){
            $folder="_Dokter";
        }else{
            $folder="_Error";
        }
    }
    $image_name = ($folder == "_Error") ? "no_image.png" : $image_name;

    // Path relatif dari root website
    $image_path = __DIR__ . '/assets/img/' . $folder . '/' . $image_name;

    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));

    if (in_array($ext, $allowed_ext) && file_exists($image_path)) {
        $mime_types = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg', 
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp'
        ];
        
        header('Content-Type: ' . $mime_types[$ext]);
        readfile($image_path);
        exit;
    }

    // Fallback
    $default_image = __DIR__ . '/assets/img/_Error/no_image.png';
    if (file_exists($default_image)) {
        header('Content-Type: image/png');
        readfile($default_image);
        exit;
    }
}

header("HTTP/1.0 404 Not Found");
echo "Image not found";