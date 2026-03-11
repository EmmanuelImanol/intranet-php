$(document).ready(function(){

    $('.ir-arriba').click(function(){
        $('body, html').animate({
            scrollTop: '0px'
        });
    });

    $(window).scroll(function(){
        if( $(this).scrollTop() > 0 ){
            $('.ir-arriba').slideDown(1000);
        } else {
            $('.ir-arriba').slideUp(500);
        }
    });
    
});

