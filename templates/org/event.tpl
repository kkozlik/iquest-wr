{* Smarty *}

{include file='_head.tpl'}

{include file="iquest/iquest-functions.tpl"}


    <div class="well form-search form-filter">
    {$filter_form.start}
    <label for="team_id"><div>{$lang_str.iquest_event_team}:</div>{$filter_form.team_id}</label>

    <label for="type"><div>{$lang_str.iquest_event_type}:</div>{$filter_form.type}</label>
          
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
    {$filter_form.okey}{$filter_form.f_clear}
    {$filter_form.finish}
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
    <tr class="{if $event.success}success{else}error{/if}">
        <td >{$event.timestamp}</td>
        <td >{$event.team_name}</td>
        <td >{$event.type}</td>
        <td >{$event.data}</td>
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
</script>
