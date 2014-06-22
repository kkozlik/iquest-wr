// JavaScript Document

// adds .naturalWidth() and .naturalHeight() methods to jQuery
// for retreaving a normalized naturalWidth and naturalHeight.
(function($){
    var  props = ['Width', 'Height'], prop;
    
    while (prop = props.pop()) {
        (function (natural, prop) {
            $.fn[natural] = (natural in new Image()) ? 
                    function () {
                        return this[0][natural];
                    } : 

                    function () {
                        var node = this[0], img, value;

                        if (node.tagName.toLowerCase() === 'img') {
                            img = new Image();
                            img.src = node.src,
                            value = img[prop];
                        }
                        return value;
                    };
        }('natural' + prop, prop.toLowerCase()));
    }
}(jQuery));


function resize_images(){
    $('.fileimg img').each(function() {

    
        var ratio = 0;  // Used for aspect ratio
        var width = $(this).width();    // Current image width
        var height = $(this).height();  // Current image height
        var naturalwidth = $(this).naturalWidth();    // Natural image width
        var naturalheight = $(this).naturalHeight();    // Natural image height

        // Max width for the image
        var maxWidth = Math.min( $( "#page_container" ).width() - 18,
                                 naturalwidth);
    
        ratio = maxWidth / naturalwidth;   // get ratio for scaling image
        $(this).css("width", maxWidth); // Set new width
        $(this).css("height", naturalheight * ratio);  // Scale height based on ratio

//         height = height * ratio;    // Reset height to match scaled image
//         width = $(this).width();    // Current image width
        
        $(this).parent().find('span.img_size').html(naturalwidth+"×"+naturalheight);
    });
}

function reload(){
     location.reload();
}


function enable_countdown(selector, secs){
    $(selector).countdown({
                    until: '+'+secs, 
                    compact: true, 
                    description: '',
                    onExpiry: reload
                });
}

/**
 *  set clock showing server time
 */ 
function set_clock(selector, time){
    
    // First get difference between client and server time and store it into 
    // diff variable.
    var client_time = new Date();
    var server_time = new Date();
    server_time.setTime(time*1000);

    var diff = server_time.getTime() - client_time.getTime();

    setInterval(function() {
        // Every second get actual client time and shift it of time difference
        // so we will gain the server time.
        var current_time = new Date();
        current_time.setTime(current_time.getTime() + diff);
        
        // Format the time to string
        $(selector).text(current_time.toTimeString().replace(/.*(\d{2}:\d{2}:\d{2}).*/, "$1"));
    }, 1000);
}


$(document).ready(function() {

    /**
     *  Click handler for minimize buttons for clues/hints
     */         
    $(".shrinkable .minimize-btn").click(function(e) {
        e.preventDefault();
        
        var obj_id = $(this).attr("data-obj-id");

        $("#"+obj_id).slideUp(
             150,
             function() {
                $("#min"+obj_id).removeClass("minimized-hidden");
        });

        // let the server know about the change and make it persistent
        $.ajax({
            url: $(this).attr("data-url-hide"),
            cache: false
        });
    });


    /**
     *  Click handler for restore buttons for clues/hints
     */         
    $(".shrinked-grp .restore-btn").click(function(e) {
        e.preventDefault();
        
        var obj_id = $(this).attr("data-obj-id");
        $("#min"+obj_id).addClass("minimized-hidden");
        $("#"+obj_id).slideDown(150);

        // let the server know about the change and make it persistent
        $.ajax({
            url: $(this).attr("data-url-unhide"),
            cache: false
        });
    });

});


$(window).load(function() {
    resize_images();
});

$(window).resize(function() {
    resize_images();
});
