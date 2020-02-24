/**
 * Show given string as error message on the screen
 * @param string err
 */
function show_error(err){
    show_msg(err, null, "#errPlaceHolder");
}

/**
 * Show given string as info message on the screen
 * @param string msg
 */
function show_info(msg, type){
    show_msg(msg, type, "#infoPlaceHolder");
}

/**
 *  clear all errors displayed
 */
function clear_errors(){
    clear_msg("#errPlaceHolder");
}

/**
 *  clear all info messages displayed
 */
function clear_info(){
    clear_msg("#infoPlaceHolder");
}

/**
 * Common function to display a message within given parentElement
 *
 * @param string msg            message to be displayed
 * @param string type           the template to choose
 * @param string parentElement  parent element for the message
 */
function show_msg(msg, type, parentElement){
    parentElement = $(parentElement);

    if (!type) type = "item";

    var item = $(parentElement.data(type+'Template'));
    item.find('.msgItemText').text(msg);

    var msgEl = parentElement.find('ul');
    if (!msgEl.length){
        parentElement.html(parentElement.data('template'));
    }

    parentElement.find('ul').append(item);
}

/**
 * Common function to delete all messages within given parentElement
 *
 * @param string parentElement  parent element for the message
 */
function clear_msg(parentElement){
    $(parentElement).html("");
}

function LocationCtl(){

    this.last_location = {};
    this.marker = null;
    this.timer = null;

    this.map = null;

    this.get_location_url = null;
    this.check_location_url = null;
    this.mapCanvasId = null;
    this.mapPopup = null;
    this.openPopupBtn = null;
    this.checkLocationBtn = null;

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

        this.checkLocationBtn.on('click', function(e){
            self.check_location();
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
                // @TODO: display errors returned from server

                if (data.lat != self.last_location.lat || data.lon != self.last_location.lon){
                    self.map.panTo([data.lat, data.lon]);

                    if (self.marker) self.marker.remove();
                    if ($.isEmptyObject(data)) return;

                    self.marker = L.marker([data.lat, data.lon]);
                    self.marker.addTo(self.map);
                }

                self.mapPopup.find(".updateTime").text(data.lastupdate+" ("+data.lastupdate_ts+")");
                self.last_location = data;
            }
        });
    },

    check_location: function(){
        var self = this;

        if (!this.check_location_url){
            console.error("check_location_url is not set.");
            return;
        }

        clear_errors();
        clear_info();

        $.ajax({
            url: this.check_location_url,
            success: function(data, status){

                if (data.errors){
                    data.errors.forEach(function(msg){show_error(msg);});
                }

            }
        });
    }

}
