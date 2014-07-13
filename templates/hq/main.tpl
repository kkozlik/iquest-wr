{* Smarty *}

{include file='_head.tpl'}

{include file="iquest/iquest-functions.tpl"}


{if $action=='view_grp'}

    <ul class="breadcrumb">
    <li><a href="{$main_url|escape}">{$lang_str.iquest_l_back}</a></li>
    </ul>

    {foreach $clues as $clue}
    <div class="datatable clue">
    <table class="table table-bordered">
    <tr>
        <th class="filename">{$clue.filename}
            {if $clue.type=="coin"}<span class="coin"></span>{else}<span class="finder"></span>{/if}
        </th>
        <th class="link"><a href="{$clue.file_url|escape}" class="btn"><i class="icon-download-alt"></i> {$lang_str.iquest_download}</a></th>
    </tr>
    {call iquestRenderFile file=$clue}
    </table>
    </div>

    {foreach $clue.hints as $hint}
    <div class="datatable hint">
    <table class="table table-bordered">
    <tr >
        <th class="filename">{$lang_str.iquest_hint}: {$hint.filename} (timeout: {$hint.timeout}, price: {$hint.price} {$lang_str.iquest_txt_coin_symbol})</th>
        <th class="link"><a href="{$hint.file_url|escape}" class="btn"><i class="icon-download-alt"></i> {$lang_str.iquest_download}</a></th>
    </tr>
    {call iquestRenderFile file=$hint}
    </table>
    </div>
    {/foreach}
    {/foreach}

    <ul class="breadcrumb">
    <li><a href="{$main_url|escape}">{$lang_str.iquest_l_back}</a></li>
    </ul>

{else}

    <div class="horizontal-scrollbar" id="iscroll">     
    <table class="table table-bordered summary">
    <tr>
    <th rowspan="2">&nbsp;</th>
    {foreach $clue_groups as $group}{$colspan=$group.solution_ids|count}{if !$colspan}{$colspan=1}{/if}
    <th colspan="{$colspan}" title="{$group.id|escape}"><a href="{$group.view_url|escape}">{$group.name|escape}</a></th>
    {/foreach}
    </tr>
    <tr>
    {foreach $clue_groups as $group}
        {foreach $group.solution_ids as $solution_id}
        <th title="{$solutions.$solution_id.id|escape}">{$solutions.$solution_id.name|escape}</th>
        {foreachelse}
        <th >&nbsp;</th>
        {/foreach}
    {/foreach}
    </tr>

    {foreach $teams as $team}
    <tr>
    <th {if !$team.active}class="deactivated"{/if} rowspan="2">
        <a href="{$team.graph_url|escape}" {if !$team.active}title="deactivated"{/if}>{$team.name|escape}</a><br />
        ({$team.wallet} {$lang_str.iquest_txt_coin_symbol})
    </th>
        {foreach $clue_groups as $group}{$colspan=$group.solution_ids|count}{if !$colspan}{$colspan=1}{/if}
        <td colspan="{$colspan}" {if $cgrp_team[$group.id][$team.id].gained}class="solved"{/if} title="{$group.id|escape}">
        {$cgrp_team[$group.id][$team.id].gained_at|escape}
        </td>
        {/foreach}
    </tr>
    <tr>
        {foreach $clue_groups as $group}
            {foreach $group.solution_ids as $solution_id}
            <td {if $solution_team[$solution_id][$team.id].solved}class="solved"{/if} title="{$solutions.$solution_id.id|escape}">
            {$solution_team[$solution_id][$team.id].solved_at|escape}
            </td>
            {foreachelse}
            <td >&nbsp;</td>
            {/foreach}
        {/foreach}
    </tr>
    {/foreach}

    </table>
    </div>

{/if}

<br>
{include file='_tail.tpl'}

{if $action=='default'}
{literal}
    <script type="text/javascript">

    var ua = navigator.userAgent.toLowerCase();
    var isAndroid = ua.indexOf("android") > -1; //&& ua.indexOf("mobile");
    var isIphone = ((ua.indexOf("iphone") > -1) || (ua.indexOf("ipod") > -1));

    if (isAndroid || isIphone){
        /*
        iscroll do nto work well on android. It's quite slow.
            
        var myScroll = new iScroll('iscroll', {
                                        momentum: false,
                                        bounce: false
                                        //hideScrollbar: true
                                });
        */

        // Instead of using iscroll remove the scrolling capability from
        // the elemenet on devices that do not support it. 
        $("#iscroll").attr('class', '');
    }
    </script>
{/literal}
{/if}
