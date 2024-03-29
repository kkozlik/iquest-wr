{*
 *  variables that should be set:
 *      - $event
 *      - $raw_data - indicate whether raw data of the event should be displayed
 *                    instead of the filtered nice ones
 *}

{$data_content="<pre>`$event.data_formated`</pre>"}
<tr class="{if $event.success}success{else}error{/if}">
    <td class="text-nowrap text-center">{$event.timestamp}
        {if $event.playtime and 0 != $event.time_shift|default:0}
            <br/><span class="text-xs">{$event.playtime}</span>
        {/if}
    </td>
    <td >{$event.team_name}</td>
    <td >{$event.type}</td>
    {if $raw_data}
    <td class="event-data" data-toggle="popover" data-content="{$data_content|escape}">{$event.data|json_encode|escape}</td>
    {else}
    <td >
        {foreach $event.data_filtered as $key=>$value}
            <span class="eventLogDataKey">{$key|escape}</span>:
            <span class="eventLogDataValue">
                {if $value.values|default:false}
                    {foreach $value.values as $key2=>$value2}
                        {if $value2.url|default:false}
                            <a href="{$value2.url|escape}">{$value2.text|default:'<null>'|escape}</a>
                        {else}
                            {$value2.text|default:'<null>'|escape}
                        {/if}
                        {if !$value2@last},{/if}
                    {/foreach}
                {elseif $value.url|default:false}
                    <a href="{$value.url|escape}">{$value.text|default:'<null>'|escape}</a>
                {else}
                    {$value.text|default:'<null>'|escape}
                {/if}
            </span>{if !$value@last},{/if}
        {/foreach}
    </td>
    {/if}
</tr>
