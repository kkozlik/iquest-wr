{* 
 *  variables that should be set:
 *      - $event
 *      - $raw_data - indicate whether raw data of the event should be displayed
 *                    instead of the filtered nice ones
 *}

{$data_content="<pre>`$event.data_formated`</pre>"}
<tr class="{if $event.success}success{else}error{/if}">
    <td class="nowrap">{$event.timestamp}</td>
    <td >{$event.team_name}</td>
    <td >{$event.type}</td>
    {if $raw_data}
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
