{* Smarty *}

{include file='_head.tpl'}

{include file="iquest/iquest-functions.tpl"}

<div class="d-flex flex-column flex-md-row flex-grow-1 mb-2 mb-md-4">
    <div class="flex-md-grow-1 h-100">
        <div id="mapCanvas" class="w-100 h-100"></div>
    </div>
    <div class="ml-md-3 w-100 w-md-25">
    <div class="row mt-2 mt-md-0">
        <div class="form-group col-6 col-md-12">
            <label for="team_id">Tým</label>
            {$formobj->el('team_id')}
        </div>
        <div class="form-group col-6 col-md-12">
            <label for="tracker_id">ID trackeru</label>
            <input type="text" class="form-control" id="tracker_id" >
        </div>
    </div>
    </div>
</div>


{include file='_tail.tpl'}

<script type="text/javascript">
    $(".swMain").addClass("d-flex flex-column vh-100");
    $("#page_container").attr("class", "container-fluid d-flex flex-column flex-grow-1");

    $(document).ready(function () {

        var locCtl = new SetLocationCtl();

        locCtl.inpDevId = $('#tracker_id');
        locCtl.inpTeam = $('#team_id');
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
