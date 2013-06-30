// JavaScript Document

function resize_images(){
    $('.fileimg img').each(function() {

    
        var ratio = 0;  // Used for aspect ratio
        var width = $(this).width();    // Current image width
        var height = $(this).height();  // Current image height
        var naturalwidth = this.naturalWidth;    // Natural image width
        var naturalheight = this.naturalHeight;    // Natural image height

        // Max width for the image
        var maxWidth = Math.min( $( "#page_container" ).width() - 18,
                                 naturalwidth);
    
        ratio = maxWidth / width;   // get ratio for scaling image
        $(this).css("width", maxWidth); // Set new width
        $(this).css("height", height * ratio);  // Scale height based on ratio
        height = height * ratio;    // Reset height to match scaled image

        width = $(this).width();    // Current image width
        
        $(this).parent().find('span.img_size').html(naturalwidth+"×"+naturalheight);
    });
}

$(document).ready(function() {
    resize_images();
});

$(window).resize(function() {
    resize_images();
});
