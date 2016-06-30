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

    <table class="table table-bordered">
    <tr>
    <th>{$lang_str.iquest_event_time}</th>
    <th>{$lang_str.iquest_event_team}</th>
    <th>{$lang_str.iquest_event_type}</th>
    <th>{$lang_str.iquest_event_data}</th>
    </tr>

    {foreach $events as $event}
    {$data_content="<pre>`$event.data_formated`</pre>"}
    <tr class="{if $event.success}success{else}error{/if}">
        <td class="nowrap">{$event.timestamp}</td>
        <td >{$event.team_name}</td>
        <td >{$event.type}</td>
        {if $filter_values.raw_data}
        <td class="event-data" data-toggle="popover" data-content="{$data_content|escape}">{$event.data|json_encode|escape}</td>
        {else}
        <td >
            {foreach $event.data_filtered as $key=>$value}
                <span class="eventLogDataKey">{$key|escape}</span>:
                <span class="eventLogDataValue">
                    {if $value.url|default:false}
                        <a href="{$value.url|escape}">{$value.text|escape}</a>
                    {else}
                        {$value.text|escape}
                    {/if}
                </span>{if !$value@last},{/if}
            {/foreach}
        </td>
        {/if}
    </tr>
    {/foreach}

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
        html: true
    });
</script>
