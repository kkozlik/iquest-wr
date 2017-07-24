{* Smarty *}

{include file='_head.tpl'}

{include file="iquest/iquest-functions.tpl"}


    <div class="filterForm accordion">
    <div class="accordion-group">
    <div class="accordion-heading">
        <a class="accordion-toggle" data-toggle="collapse" href="#filterFormCollapse">Filtr</a>
    </div>
    <div id="filterFormCollapse" class="accordion-body collapse">
    <div class="form-search form-filter accordion-inner">
        {$filter_form.start}
        <label for="team_id"><div>{$lang_str.iquest_event_team}:</div>{$filter_form.team_id}</label>
    
        <label for="type"><div>{$lang_str.iquest_event_type}:</div>{$filter_form.type}</label>
    
        <label for="success"><div>{$lang_str.iquest_event_success}:</div>{$filter_form.success}</label>
              
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
    
        <br />

        <div class="pull-right">
        <label for="raw_data" class="checkbox"> {$lang_str.iquest_event_raw_data}{$filter_form.raw_data}</label>
        </div>
        
        {$filter_form.okey}{$filter_form.f_clear}
        {$filter_form.finish}
    
    </div>
    </div>
    </div>
    
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
        
        poll: function(){
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
        $('#filter_form #team_id').multiselect({
            onInitialized : function ($aSelect, $aContainer) {
            
                var $dropdown = $aContainer.find('.btn');
                var offset = $dropdown.offset();
                
                $aContainer.find('.dropdown-menu').css({
                    position:   'fixed',
                    top:        (offset.top + $dropdown.outerHeight()),
                    left:       offset.left,
                })
            },
            buttonWidth: '220px',
            includeSelectAllOption: true
        });

{* Event Poller shall be enabled only if we are displaying the first 
 * page of the pager. Otherwise the poller would populate the other 
 * pages with the most recent events. *}
{if !$pager.pos}
        EventPoller.init();
{/if}
    });


</script>
