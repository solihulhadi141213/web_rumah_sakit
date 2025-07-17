$(document).ready(function () {
    //Fungsi Menampilkan Data Blog
    function ShowData() {
        var filter_blog = $('#filter_blog').serialize();
        $('#show_list_blog').html('<div class="row"><div class="col-md-12" id="show_list_blog"></div></div>');
        $.ajax({
            type    : 'POST',
            url     : '_Page/Blog/ListBlog.php',
            data    : filter_blog,
            success: function(data) {
                $('#show_list_blog').html(data);
            }
        });
    }

    //Menampilkan Data Pertama Kali
    ShowData();

    //Event ketika pencarian
    $("#filter_blog").on("submit", function (e) {
        //Reset Halaman
        $('#page').val(1);
        
        //Tampilkan Data
        ShowData();
    });

    //Ketika tampilan mobile untuk menampilkan widget
    $('#toggle_widget_blog_btn').on('click', function () {
        $('#widget_blog').slideToggle();
        var currentText = $(this).text();
        $(this).text(
            currentText === "Tampilkan Filter & Widget" ? "Sembunyikan Filter & Widget" : "Tampilkan Filter & Widget"
        );
    });
});