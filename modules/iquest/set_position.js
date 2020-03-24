
function SetLocationCtl(){

    // this.last_location = {};
    this.marker = null;

    this.map = null;

    this.set_position_url = null;
    this.mapCanvasId = null;
    this.inpDevId = null;
    this.inpTeam = null;

    this.storage = new SessionStorage();
}

SetLocationCtl.prototype = {
    constructor: SetLocationCtl,

    init: function(){
        var self = this;

        if (!this.mapCanvasId){
            console.error("mapCanvasId is not set.");
        }

        var mapCenter = this.storage.getItem('mapCenter');
        var mapZoom = this.storage.getItem('mapZoom');

        if (mapZoom == null) mapZoom = 15;
        if (mapCenter == null) mapCenter = [49.2975044, 14.1265233];


        this.map = L.map(this.mapCanvasId, {zoom: mapZoom});
        this.map.panTo(mapCenter);

        L.tileLayer('https://m{s}.mapserver.mapy.cz/turist-m/{z}-{x}-{y}', {
            attribution: '<img src="https://mapy.cz/img/logo-small.svg" style="height: 10px" />',
            subdomains: '1234',
            minZoom: 10,
        }).addTo(this.map);



        this.map.on('click', function(e) {

            if (self.marker) self.marker.remove();
            self.marker = L.marker(e.latlng);
            self.marker.addTo(self.map);

            self.set_position(e.latlng);
        });

        this.map.on("moveend", function () {
            self.storage.setItem('mapCenter', self.map.getCenter());
        });

        this.map.on("zoomend", function () {
            self.storage.setItem('mapZoom', self.map.getZoom());
        });

        this.inpTeam.on('change', function(e){
            var teamId = self.inpTeam.val();

            self.storage.setItem('teamId', teamId);

            if (teamId == ""){
                self.inpDevId.prop('disabled', false);
            }
            else{
                var devId = self.inpTeam.find('option[value='+teamId+']').data('trackerId');
                self.inpDevId.prop('disabled', true);
                self.inpDevId.val(devId);

                self.storage.setItem('devId', devId);
            }
        });

        this.inpDevId.on('keyup', function(e){
            self.storage.setItem('devId', $(this).val());
        });

        var teamId = this.storage.getItem('teamId');
        var devId = this.storage.getItem('devId');

        if (devId != null) {
            this.inpDevId.val(devId);
        }

        if (teamId != null) {
            this.inpTeam.val(teamId);
            this.inpTeam.change();
        }
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