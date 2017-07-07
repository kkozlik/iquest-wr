
// Immediately-Invoked Function - this expression is used in order to hide local
// variables inside the function and do not create the global ones.
//
// The function just load dependencies - the bootstrap-multiselect library:
// https://github.com/davidstutz/bootstrap-multiselect
 
(function ( $ ) {
    // get URL path to this script
    var _myPath = $('script[src$="bootstrap-multiselect.js"]').attr('src').replace('bootstrap-multiselect.js','');
    
    // load bootstrap-table.min.js script
    $.ajax({
        url: _myPath+"bootstrap-multiselect/bootstrap-multiselect.js",
        dataType: 'script',
        async: false
    });
    
    // load bootstrap-table.min.css styles
    $("<link/>", {
       rel: "stylesheet",
       type: "text/css",
       href: _myPath+"bootstrap-multiselect/bootstrap-multiselect.css"
    }).appendTo("head");
 
}( jQuery ));

