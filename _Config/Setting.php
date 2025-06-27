<?php
    
    //Buka Setting Dengan Prepared Statment
    $sql_static= "SELECT setting_value FROM setting WHERE setting_parameter = :param_static";

    // Mempersiapkan statement
    $stmt_static = $Conn->prepare($sql_static);

    // Parameter yang akan diikat
    $parameter_static = 'layout_static';

    // Mengikat parameter dan mengeksekusi query
    $stmt_static->execute([':param_static' => $parameter_static]);

    // Mengambil hasil
    $result_static = $stmt_static->fetch();

    //Hasil Meta Tag
    if ($result_static) {
       $pengaturan_static=$result_static['setting_value'];
       $arry_static=json_decode($pengaturan_static,true);
      
    } else {
       $pengaturan_static="";
       $arry_static="";
    }

    
    if(!empty($arry_static)){
        //Variabel Meta Tag
        if(!empty($arry_static['meta_tag'])){
            $setting_type=$arry_static['meta_tag']['type'];
            $setting_title=$arry_static['meta_tag']['title'];
            $setting_author=$arry_static['meta_tag']['author'];
            $setting_robots=$arry_static['meta_tag']['robots'];
            $setting_base_url=$arry_static['meta_tag']['base_url'];
            $setting_keywords=$arry_static['meta_tag']['keywords'];
            $setting_og_image=$arry_static['meta_tag']['og-image'];
            $setting_viewport=$arry_static['meta_tag']['viewport'];
            $setting_logo_image=$arry_static['meta_tag']['logo-image'];
            $setting_description=$arry_static['meta_tag']['description'];
        }else{
            //Empty Meta Tag
            $setting_type="";
            $setting_title="";
            $setting_author="";
            $setting_robots="";
            $setting_base_url="";
            $setting_keywords="";
            $setting_og_image="";
            $setting_viewport="";
            $setting_logo_image="";
            $setting_description=""; 
        }

        //Variabel Favicon
        if(!empty($arry_static['favicon'])){
            $setting_180x180=$arry_static['favicon']['180x180'];
            $setting_32x32=$arry_static['favicon']['32x32'];
            $setting_16x16=$arry_static['favicon']['16x16'];
            $setting_manifest=$arry_static['favicon']['manifest'];
        }else{
            //Empty Favicon
            $setting_180x180="";
            $setting_32x32="";
            $setting_16x16="";
            $setting_manifest="";
        }

        //Variabel Navbar
        if(!empty($arry_static['navbar'])){
            $setting_logo_image_navbar=$arry_static['navbar']['logo-image'];
            $setting_title_navbar=$arry_static['navbar']['title'];
        }else{
            //Empty Navbar
            $setting_logo_image_navbar="";
            $setting_title_navbar="";
        }

        //Variabel Sambutan
        if(!empty($arry_static['sambutan_direktur'])){
            $setting_title_sambutan=$arry_static['sambutan_direktur']['title'];
            $setting_sub_title_sambutan=$arry_static['sambutan_direktur']['sub_title'];
            $setting_name_sambutan=$arry_static['sambutan_direktur']['name'];
            $setting_opening_sambutan=$arry_static['sambutan_direktur']['opening'];
            $setting_isi_sambutan=$arry_static['sambutan_direktur']['sambutan'];
            $setting_foto_sambutan=$arry_static['sambutan_direktur']['foto'];
        }else{
            //Empty Sambutan
            $setting_title_sambutan="";
            $setting_sub_title_sambutan="";
            $setting_name_sambutan="";
            $setting_opening_sambutan="";
            $setting_isi_sambutan="";
            $setting_foto_sambutan="";
        }

        //Variabel Hero
        if(!empty($arry_static['hero'])){
            $setting_hero_welcome=$arry_static['hero']['hero_welcome'];
            $setting_hero_igd=$arry_static['hero']['hero_igd'];
            $setting_hero_media_sosial=$arry_static['hero']['hero_media_sosial'];
        }else{
            //Empty Hero
            $setting_hero_welcome="";
            $setting_hero_igd="";
            $setting_hero_media_sosial="";
        }

        //Variabel Visi Misi
        if(!empty($arry_static['visi_misi'])){
            $setting_visi_misi_title=$arry_static['visi_misi']['title'];
            $setting_visi_misi_visi=$arry_static['visi_misi']['visi'];
            $setting_visi_misi_misi=$arry_static['visi_misi']['misi'];
            $setting_visi_misi_motto=$arry_static['visi_misi']['motto'];
        }else{
            //Empty Visi Misi
            $setting_visi_misi_title="";
            $setting_visi_misi_visi="";
            $setting_visi_misi_misi="";
            $setting_visi_misi_motto="";
        }

        //Variabel Info Grafis
        if(!empty($arry_static['info_grafis'])){
            $setting_info_grafis_title=$arry_static['info_grafis']['title'];
            $setting_info_grafis_bg_image=$arry_static['info_grafis']['bg_image'];
            $setting_info_grafis_list_content=$arry_static['info_grafis']['list_content'];
        }else{
            //Empty Info Grafis
            $setting_info_grafis_title="";
            $setting_info_grafis_bg_image="";
            $setting_info_grafis_list_content="";
        }

        //Variabel Unit Instalasi
        if(!empty($arry_static['unit_instalasi'])){
            $setting_unit_instalasi_title=$arry_static['unit_instalasi']['title'];
            $setting_unit_instalasi_bg_image=$arry_static['unit_instalasi']['bg_image'];
            $setting_unit_instalasi_list_content=$arry_static['unit_instalasi']['data_list'];
        }else{
            //Empty Unit Instalasi
            $setting_unit_instalasi_title="";
            $setting_unit_instalasi_bg_image="";
            $setting_unit_instalasi_list_content="";
        }

    }else{
        //Empty Meta Tag
        $setting_type="";
        $setting_title="";
        $setting_author="";
        $setting_robots="";
        $setting_base_url="";
        $setting_keywords="";
        $setting_og_image="";
        $setting_viewport="";
        $setting_logo_image="";
        $setting_description="";

        //Empty Favicon
        $setting_180x180="";
        $setting_32x32="";
        $setting_16x16="";
        $setting_manifest="";

        //Empty Navbar
        $setting_logo_image_navbar="";
        $setting_title_navbar="";

        //Empty Sambutan
        $setting_title_sambutan="";
        $setting_sub_title_sambutan="";
        $setting_name_sambutan="";
        $setting_opening_sambutan="";
        $setting_isi_sambutan="";
        $setting_foto_sambutan="";

        //Empty Hero
        $setting_hero_welcome="";
        $setting_hero_igd="";
        $setting_hero_media_sosial="";

        //Empty Visi Misi
        $setting_visi_misi_title="";
        $setting_visi_misi_visi="";
        $setting_visi_misi_misi="";
        $setting_visi_misi_motto="";

        //Empty Info Grafis
        $setting_info_grafis_title="";
        $setting_info_grafis_bg_image="";
        $setting_info_grafis_list_content="";

        //Empty Unit Instalasi
        $setting_unit_instalasi_title="";
        $setting_unit_instalasi_bg_image="";
        $setting_unit_instalasi_list_content="";
    }

?>