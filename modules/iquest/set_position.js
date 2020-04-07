
function SetLocationCtl(){

    // this.last_location = {};
    this.marker = null;

    this.map = null;
    this.timer = null;

    this.get_position_url = null;
    this.set_position_url = null;
    this.mapCanvasId = null;
    this.inpDevId = null;
    this.inpTeam = null;
    this.updateTimeEl = null;
    this.zoneEl = null;

    this.storage = new SessionStorage();

    this.updateInterval = 5000; // [ms]
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

                self.get_position();
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

        this.get_position();
        this.timer = window.setInterval(function(){
            self.get_position();
        }, this.updateInterval);

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

                self.get_position();
            }
        });
    },

    get_color: function(age){
        if (age < 3*60) return "text-success";  // less than 3 min
        if (age < 10*60) return "text-warning"; // less than 10 min
        return "text-danger";
    },

    get_position: function(){
        var self = this;

        var devId = $(this.inpDevId).val();
        if (!devId) return;

        if (!this.get_position_url){
            console.error("get_position_url is not set.");
            return;
        }

        $.ajax({
            url: this.get_position_url,
            data: {
                devId: devId
            },
            success: function(data, status){

                if (data.errors){
                    clear_errors();
                    $.each(data.errors, function(index, err){
                        show_error(err);
                    });
                }

                self.render_zones(data.zones);

                var location = data.location;

                if (!location.lat || !location.lon) {
                    if (self.updateTimeEl){
                        self.updateTimeEl.text("-----");
                    }
                    return;
                };

                if (!self.marker){
                    self.marker = L.marker([location.lat, location.lon]);
                    self.marker.addTo(self.map);

                    self.map.panTo([location.lat, location.lon]);
                }
                else {
                    var marker_location = self.marker.getLatLng();
                    if (location.lat != marker_location.lat || location.lon != marker_location.lng){

                        self.marker.remove();
                        self.marker = L.marker([location.lat, location.lon]);
                        self.marker.addTo(self.map);

                        self.map.panTo([location.lat, location.lon]);
                    }
                }

                if (self.updateTimeEl){
                    var timeStr = $('<span>')
                                    .addClass(self.get_color(location.age))
                                    .text(location.lastupdate+" ("+location.lastupdate_ts+")");
                    self.updateTimeEl.html(timeStr);
                }

            }
        });
    },

    render_zones: function(zones){
        var self = this;
        this.zoneEl.empty();

        if (!zones) return;
        if (!zones.length) return;

        var wrapper=$("<div class='mt-md-4'>");

        $.each(zones, function(key, zone){

            var attrs = $('<span>');

            $.each(zone.attributes, function(attr_name, attr_val){
                var attr = $('<small>')
                    .append($('<span class="text-primary text-nowrap pr-1">').text(attr_name+":"))
                    .append($('<span>').text(attr_val));

                attrs.append(attr);
                attrs.append('<span>, </span>');
            });
            attrs.children().last().remove(); // remove the last comma

            var col = $('<div class="col">');
            col.append($("<small class='badge badge-info mr-1'>").text("Zona: "+zone.name));
            col.append(document.createTextNode("{"));
            col.append(attrs);
            col.append(document.createTextNode("}"));


            var div = $('<div class="row">').append(col);

            wrapper.append(div);
        });

        self.zoneEl.append(wrapper);

    }

}
