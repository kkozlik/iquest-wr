{* Smarty *}

{include file='_head.tpl'}

{include file="iquest/iquest-functions.tpl"}


    <div class="filterForm">
    <div class="card bg-light">
        <div class="card-header py-2">
            <a class="align-middle" data-toggle="collapse" href="#filterFormCollapse" style="display: block;">Filtr</a>
        </div>
        <div id="filterFormCollapse" class="collapse">
            <div class="card-body p-3">
                <div class="form-search form-filter">
                    {$filter_form.start}
                    <label for="team_id"><div>{$lang_str.iquest_event_team}:</div>
                        {$filter_formobj->el('team_id')->ignore_default_class(true)}
                    </label>

                    <label for="type"><div>{$lang_str.iquest_event_type}:</div>
                        {$filter_formobj->el('type')->ignore_default_class(true)}
                    </label>

                    <label for="success"><div>{$lang_str.iquest_event_success}:</div>
                        {$filter_formobj->el('success')->ignore_default_class(true)}
                    </label>

                    <label for="date_from">
                    <div>{$lang_str.iquest_event_date_from}:</div>
                    <div id="datetimepicker1" class="input-append">
                    {$filter_form.date_from}<span class="add-on">
                    <i data-time-icon="icon-time" data-date-icon="icon-calendar">
                    </i>
                    </span>
                    </div>
                    </label>

                    <label for="date_to">
                    <div>{$lang_str.iquest_event_date_to}:</div>
                    <div id="datetimepicker2" class="input-append">
                    {$filter_form.date_to}<span class="add-on">
                    <i data-time-icon="icon-time" data-date-icon="icon-calendar">
                    </i>
                    </span>
                    </div>
                    </label>

                    <div class="row align-items-center">
                        <div class="col-4 col-md-2">
                            {$filter_form.okey}
                        </div>
                        <div class="col-4 col-md-2">
                            {$filter_form.f_clear}
                        </div>
                        <div class="col">
                            <div class="custom-control custom-checkbox float-right">
                                {$filter_form.raw_data}
                                <label class="custom-control-label" for="raw_data">{$lang_str.iquest_event_raw_data}</label>
                            </div>
                        </div>
                    </div>

                    {$filter_form.finish}
                </div>
            </div>
        </div>
    </div>

    </div>

    <div class="pull-left">
    <label class="checkbox">Pause autorefresh<input type="checkbox" class="pause-autorefresh"></label>
    </div>
    {call iquestPager pager=$pager}

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
  $(function() {
    $('#datetimepicker1').datetimepicker({
      format: 'dd-MM-yyyy hh:mm:ss',
      language: 'cz',
      pick12HourFormat: false
    });
    $('#datetimepicker2').datetimepicker({
      format: 'dd-MM-yyyy hh:mm:ss',
      language: 'cz',
      pick12HourFormat: false
    });
  });

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
            style: 'btn-outline-secondary'
        });

        $('#filter_form select[name="type[]"]').selectpicker({
            actionsBox: true,
            style: 'btn-outline-secondary'
        });

        $('#filter_form select[name="success"]').selectpicker({
            style: 'btn-outline-secondary'
        });

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
