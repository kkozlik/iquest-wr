{* Smarty *}

{include file='_head.tpl'}

{include file="iquest/iquest-functions.tpl"}

<div class="row flex-grow-1 mb-4">
    <div class="col h-100">
        <div id="mapCanvas" class="w-100 h-100"></div>
    </div>
    <div class="col-2">
          <div class="form-group">
            <label for="trackerID">ID trackeru</label>
            <input type="text" class="form-control" id="trackerID" >
        </div>
    </div>
</div>


{include file='_tail.tpl'}

<script type="text/javascript">
    $(".swMain").addClass("d-flex flex-column vh-100");
    $("#page_container").attr("class", "container-fluid d-flex flex-column flex-grow-1");

    $(document).ready(function () {

        var locCtl = new SetLocationCtl();

        locCtl.inpDevId = $('#trackerID');
        locCtl.mapCanvasId = 'mapCanvas';
        locCtl.set_position_url = {$ajax_set_position_url|json_encode};

        locCtl.init();
    });
</script>
<style type="text/css">
    .leaflet-grab {
        cursor: auto;
    }

    .leaflet-dragging .leaflet-grab{
        cursor: grab;
    }
</style>
