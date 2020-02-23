function LocationCtl(){

    this.last_location = {};
    this.marker = null;
    this.timer = null;

    this.map = null;

    this.get_location_url = null;
    this.mapCanvasId = null;
    this.mapPopup = null;
    this.openPopupBtn = null;

    this.updateInterval = 5000; // [ms]
}

LocationCtl.prototype = {
    constructor: LocationCtl,

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

        this.mapPopup.on('shown.bs.modal', function(){
            self.map.invalidateSize();
            last_location = {};

            self.get_location();
            self.timer = window.setInterval(function(){
                self.get_location();
            }, self.updateInterval);
        });

        this.mapPopup.on('hide.bs.modal', function(){
            if (self.timer) window.clearInterval(self.timer);
        });

        this.openPopupBtn.on('click', function(e){
            self.mapPopup.modal("show");
        });
    },

    get_location: function(){
        var self = this;

        if (!this.get_location_url){
            console.error("get_location_url is not set.");
            return;
        }

        $.ajax({
            url: this.get_location_url,
            success: function(data, status){
                if (data.lat != self.last_location.lat || data.lon != self.last_location.lon){
                    self.map.panTo([data.lat, data.lon]);

                    if (self.marker) self.marker.remove();
                    if ($.isEmptyObject(data)) return;

                    self.marker = L.marker([data.lat, data.lon]);
                    self.marker.addTo(self.map);
                }

                self.mapPopup.find(".updateTime").html(data.timestr);
                self.last_location = data;
            }
        });
    }

}
