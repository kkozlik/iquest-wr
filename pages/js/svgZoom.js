// JavaScript object for zooming svg images embeded in <object> tag
//
// Usage: svgZoom.init('object');
//
// Limitation: As of now just one zoomable svg object on a page is supported


var svgZoom = {
    container : null,
    svgObj : null,
    svgWidth : null,
    svgHeight : null,
    svgMinWidth : null,
    svgMinHeight : null,
    zoomCoef : 1.1,
    lastMousePos : null,
    
    init : function( container ){
        
        this.container = $(container);
        this.svgObj = this.container.contents().find("svg");

        this.svgWidth  = parseInt($(this.svgObj).attr('width'));
        this.svgHeight = parseInt($(this.svgObj).attr('height'));

        this.svgMinWidth  = this.svgWidth  / 10;
        this.svgMinHeight = this.svgHeight / 10;
        
        $(this.svgObj).bind('mousewheel DOMMouseScroll', $.proxy(this.onMouseWheel, this));
        $(this.svgObj).on('mousedown', $.proxy(this.onMouseDown, this));
        $(this.svgObj).on('mouseup', $.proxy(this.onMouseUp, this));
    },
    
    onMouseWheel : function (e){

        var scrollUp = (e.originalEvent.wheelDelta > 0 || e.originalEvent.detail < 0); 
        var viewBoxParts = this.getViewBoxParts();

        var width1  = viewBoxParts[2];
        var height1 = viewBoxParts[3];

        if (scrollUp){
            viewBoxParts[2] = viewBoxParts[2] / this.zoomCoef;
            viewBoxParts[3] = viewBoxParts[3] / this.zoomCoef;

            if (viewBoxParts[2] < this.svgMinWidth)  viewBoxParts[2] = this.svgMinWidth;
            if (viewBoxParts[3] < this.svgMinHeight) viewBoxParts[3] = this.svgMinHeight;
        }
        else{
            viewBoxParts[2] = viewBoxParts[2] * this.zoomCoef;
            viewBoxParts[3] = viewBoxParts[3] * this.zoomCoef;
            
            if (viewBoxParts[2] > this.svgWidth)  viewBoxParts[2] = this.svgWidth;
            if (viewBoxParts[3] > this.svgHeight) viewBoxParts[3] = this.svgHeight;
        }

        viewBoxParts[0] +=  (width1 - viewBoxParts[2])  / 2;
        viewBoxParts[1] +=  (height1 - viewBoxParts[3]) / 2;

        viewBoxParts = this.checkViewBoxBoundaries(viewBoxParts);

        $(this.svgObj).attr('viewBox', viewBoxParts.join(' '));
    },

    onMouseDown : function (e){
        $(this.svgObj).on("mousemove", $.proxy(function(e){
        
            var p1 = { x: e.pageX, y: e.pageY };
            var p0 = this.lastMousePos || p1;
            
            this.lastMousePos = p1;

            var dx = p1.x - p0.x;
            var dy = p1.y - p0.y;

            var viewBoxParts = this.getViewBoxParts();

            var scaleX = (viewBoxParts[2] / this.container.width());
            var scaleY = (viewBoxParts[3] / this.container.height());

            dx = dx * scaleX;
            dy = dy * scaleY;

            viewBoxParts[0] = viewBoxParts[0] - dx;
            viewBoxParts[1] = viewBoxParts[1] - dy;

            viewBoxParts = this.checkViewBoxBoundaries(viewBoxParts);

            $(this.svgObj).attr('viewBox', viewBoxParts.join(' '));
        }, this ));
    },

    onMouseUp : function (e){
        $(this.svgObj).off("mousemove");
        this.lastMousePos = null;
    },

    getViewBoxParts : function( ){
        var viewBox = $(this.svgObj).attr('viewBox');
        var viewBoxParts = viewBox.split(" ");

        viewBoxParts[0] = Number(viewBoxParts[0]);  // offset x
        viewBoxParts[1] = Number(viewBoxParts[1]);  // offset y
        viewBoxParts[2] = Number(viewBoxParts[2]);  // view Box Width
        viewBoxParts[3] = Number(viewBoxParts[3]);  // view Box Height

        return viewBoxParts;
    },

    checkViewBoxBoundaries : function( viewBoxParts ){
    
        if (viewBoxParts[0] < 0) viewBoxParts[0] = 0;
        if (viewBoxParts[1] < 0) viewBoxParts[1] = 0;

        if (viewBoxParts[0] + viewBoxParts[2] > this.svgWidth)  viewBoxParts[0] = this.svgWidth - viewBoxParts[2];
        if (viewBoxParts[1] + viewBoxParts[3] > this.svgHeight) viewBoxParts[1] = this.svgHeight - viewBoxParts[3];
    
        return viewBoxParts;
    }
}

