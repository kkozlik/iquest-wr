
function SetLocationCtl(){

    // this.last_location = {};
    this.marker = null;

    this.map = null;

    this.set_position_url = null;
    this.mapCanvasId = null;
    this.inpDevId = null;

}

SetLocationCtl.prototype = {
    constructor: SetLocationCtl,

    init: function(){
        var self = this;

        if (!this.mapCanvasId){
            console.error("mapCanvasId is not set.");
        }

        this.map = L.map(this.mapCanvasId, {zoom: 15});

        L.tileLayer('https://m{s}.mapserver.mapy.cz/turist-m/{z}-{x}-{y}', {
            attribution: '<img src="https://mapy.cz/img/logo-small.svg" style="height: 10px" />',
            subdomains: '1234',
            minZoom: 10,
        }).addTo(this.map);


        this.map.panTo([49.2975044, 14.1265233]);   // @TODO: remember last position

        this.map.on('click', function(e) {

            if (self.marker) self.marker.remove();
            self.marker = L.marker(e.latlng);
            self.marker.addTo(self.map);

            self.set_position(e.latlng);
        });
    },

    set_position: function(latlng){
        var self = this;

        if (!this.set_position_url){
            console.error("set_position_url is not set.");
            return;
        }

        var devId = $(this.inpDevId).val();
        if (!devId){
            clear_errors();
            show_error('Není zadané ID trackeru');
            return;
        }

        $.ajax({
            url: this.set_position_url,
            data:{
                lat:latlng.lat,
                lon:latlng.lng,
                devId:devId
            },
            success: function(data, status){
                clear_info();
                clear_errors();
                if (data.errors){
                    $.each(data.errors, function(index, err){
                        show_error(err);
                    });
                }

                if (data.infomsg){
                    $.each(data.infomsg, function(index, msg){
                        show_info(msg);
                    });
                }
            }
        });
    },
}
