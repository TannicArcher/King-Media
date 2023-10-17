function sticky_relocate() {
    var window_top = $(window).scrollTop();
    var div_top = $('#sticky').offset().top;
    if (window_top > div_top) {
        $('.king-sidebar').addClass('fixed');
    } else {
        $('.king-sidebar').removeClass('fixed');
    }
}

$(function () {
    $(window).scroll(sticky_relocate);
    sticky_relocate();
});