{* Smarty *}

{include file='_head.tpl'}

{include file="iquest/iquest-functions.tpl"}


    <div class="card bg-light mb-3">
        <div class="card-header py-2">
            <a class="align-middle" data-toggle="collapse" href="#filterFormCollapse" style="display: block;">Filtr</a>
        </div>
        <div id="filterFormCollapse" class="collapse">
            <div class="card-body p-3">
                {$filter_form.start}
                    <div class="form-row">

                        <div class="col-12 col-md-4 col-lg mb-3">
                            <label for="team_id">{$lang_str.iquest_event_team}:</label>
                            {$filter_formobj->el('team_id')->ignore_default_class(true)->add_class('form-control')}
                        </div>


                        <div class="col-12 col-md-4 col-lg mb-3">
                            <label for="type">{$lang_str.iquest_event_type}:</label>
                            {$filter_formobj->el('type')->ignore_default_class(true)->add_class('form-control')}
                        </div>

                        <div class="col-12 col-md-4 col-lg mb-3">
                            <label for="success">{$lang_str.iquest_event_success}:</label>
                            {$filter_formobj->el('success')->ignore_default_class(true)->add_class('form-control')}
                        </div>


                        <div class="col-12 col-md-4 col-lg mb-3">
                            <label for="date_from">{$lang_str.iquest_event_date_from}:</label>

                            <div class="input-group date" id="datetimepicker1" data-target-input="nearest">
                                {$filter_formobj->el('date_from')->add_class('datetimepicker-input')->add_extrahtml('data-target="#datetimepicker1"')}
                                <div class="input-group-append" data-target="#datetimepicker1" data-toggle="datetimepicker">
                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4 col-lg mb-3">
                            <label for="date_to">{$lang_str.iquest_event_date_to}:</label>

                            <div class="input-group date" id="datetimepicker2" data-target-input="nearest">
                                {$filter_formobj->el('date_to')->add_class('datetimepicker-input')->add_extrahtml('data-target="#datetimepicker2"')}
                                <div class="input-group-append" data-target="#datetimepicker2" data-toggle="datetimepicker">
                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row align-items-center flex-row-reverse">
                        <div class="col-12 col-sm mb-3 mb-sm-0">
                            <div class="custom-control custom-checkbox float-right">
                                {$filter_form.raw_data}
                                <label class="custom-control-label" for="raw_data">{$lang_str.iquest_event_raw_data}</label>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-md-2">
                            {$filter_form.f_clear}
                        </div>
                        <div class="col-6 col-sm-4 col-md-2">
                            {$filter_form.okey}
                        </div>
                    </div>

                {$filter_form.finish}
            </div>
        </div>
    </div>


    <div class="row align-items-center mb-3">
        <div class="col-auto">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="pause-autorefresh custom-control-input" id="pause_autorefresh">
                <label class="custom-control-label" for="pause_autorefresh">Pause autorefresh</label>
            </div>
        </div>
        <div class="col">
            {call iquestPager pager=$pager}
        </div>
    </div>

    <table class="table table-bordered" id="event-table">
    <thead>
    <tr>
    <th>{$lang_str.iquest_event_time}</th>
    <th>{$lang_str.iquest_event_team}</th>
    <th>{$lang_str.iquest_event_type}</th>
    <th>{$lang_str.iquest_event_data}</th>
    </tr>
    </thead>

    <tbody>
    {foreach $events as $event}
        {include file=$row_template
                event=$event
                raw_data=$filter_values.raw_data
        }
    {/foreach}
    </tbody>
    </table>

    {call iquestPager pager=$pager}

<br>
{include file='_tail.tpl'}

<script type="text/javascript">

    $(".event-data").popover({
        placement: "top",
        trigger: "hover",
        html: true,
        container: 'body'
    });

    var EventPoller = {
        last_id: {$last_event_id|json_encode},
        enabled: true,

        poll: function(){
            if (!EventPoller.enabled) return;

            $.getJSON(
                '{$my_url}',
                { last_id_ajax: EventPoller.last_id },
                function (response) {

                    // @TODO: check for 'redirect' in response - when user is not authorized

                    $.each(response.rows, function (i, item) {
                        $('#event-table tbody').prepend(item);
                    });
                    EventPoller.last_id = response.last_id;
                });
        },

        init: function(){
            window.setInterval(this.poll, 10000);
        }
    }


    $(document).ready(function() {
        $('.pagination .page-link.active').closest('li').addClass('active');

        $('#filter_form select[name="team_id[]"]').selectpicker({
            actionsBox: true,
            liveSearch: true,
            liveSearchNormalize: true,
            style: 'btn-outline-secondary',
            selectedTextFormat: 'count'
        });

        $('#filter_form select[name="type[]"]').selectpicker({
            actionsBox: true,
            style: 'btn-outline-secondary',
            selectedTextFormat: 'count'
        });

        $('#filter_form select[name="success"]').selectpicker({
            style: 'btn-outline-secondary'
        });

        var datetimepickerOpts = {
            format: 'DD-MM-YYYY HH:mm:ss',
            locale: 'cs',
            icons: {
                time: "fa fa-clock",
                clear: "fas fa-trash-alt",
                today: "fas fa-calendar-check"
            },
            buttons: {
                showToday: true,
                showClear: false,
                showClose: true
            }
        }

        $('#datetimepicker1').datetimepicker(datetimepickerOpts);
        $('#datetimepicker2').datetimepicker(datetimepickerOpts);

        $('.pause-autorefresh').on('click', function(){
            if (this.checked) EventPoller.enabled = false;
            else              EventPoller.enabled = true;
        });

{* Event Poller shall be enabled only if we are displaying the first
 * page of the pager. Otherwise the poller would populate the other
 * pages with the most recent events. *}
{if !$pager.pos}
        EventPoller.init();
{/if}
    });


</script>
