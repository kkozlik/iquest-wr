{* Smarty *}

{include file='_head.tpl'}

{include file="iquest/iquest-functions.tpl"}


{if $action=='view_grp'}

    <ul class="breadcrumb">
    <li><a href="{$back_url|escape}">{$lang_str.iquest_l_back}</a></li>
    </ul>

    {foreach $clues as $clue}
    <div class="datatable clue">
    <table class="table table-bordered">
    <tr>
        <th class="filename">{$clue.filename}
            {if $clue.type=="coin"}<span class="coin"></span>
            {elseif $clue.type=="special"}<span class="questionmark"></span>
            {else}<span class="finder"></span>{/if}
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
    <li><a href="{$back_url|escape}">{$lang_str.iquest_l_back}</a></li>
    </ul>

{elseif $action=='view_solution'}

    <ul class="breadcrumb">
    <li><a href="{$back_url|escape}">{$lang_str.iquest_l_back}</a></li>
    </ul>

    <div class="datatable solution">
    <table class="table table-bordered">
    <tr>
        <th class="filename">{$solutions.name}</th>
        <th class="link">&nbsp;</th>
    </tr>
    {call iquestRenderFile file=$solutions}
    </table>
    </div>

    <ul class="breadcrumb">
    <li><a href="{$back_url|escape}">{$lang_str.iquest_l_back}</a></li>
    </ul>

{elseif $action=='view_hint'}

    <ul class="breadcrumb">
    <li><a href="{$back_url|escape}">{$lang_str.iquest_l_back}</a></li>
    </ul>

    <div class="datatable hint">
    <table class="table table-bordered">
    <tr>
        <th class="filename">{$lang_str.iquest_hint}: {$hint.filename} (timeout: {$hint.timeout}, price: {$hint.price} {$lang_str.iquest_txt_coin_symbol})</th>
        <th class="link"><a href="{$hint.file_url|escape}" class="btn"><i class="icon-download-alt"></i> {$lang_str.iquest_download}</a></th>
    </tr>
    {call iquestRenderFile file=$hint}
    </table>
    </div>

    <ul class="breadcrumb">
    <li><a href="{$back_url|escape}">{$lang_str.iquest_l_back}</a></li>
    </ul>

{else}

    <div class="horizontal-scrollbar" id="scroll-wrapper">
    <table class="table table-bordered summary" id="clueTable">
    <thead>
    <tr>
    <th rowspan="2">&nbsp;</th>
    {foreach $clue_groups as $group}{$colspan=$group.solution_ids|count}{if !$colspan}{$colspan=1}{/if}
    <th colspan="{$colspan}" title="{$group.id|escape}"><a href="{$group.view_url|escape}">{$group.name|escape}</a></th>
    {/foreach}
    </tr>
    <tr>
    {foreach $clue_groups as $group}
        {foreach $group.solution_ids as $solution_id}
        <th title="{$solutions.$solution_id.id|escape}">
        {if $solutions.$solution_id.view_url|default:0}<a href="{$solutions.$solution_id.view_url|escape}">{$solutions.$solution_id.name|escape}</a>
        {else}{$solutions.$solution_id.name|escape}
        {/if}</th>
        {foreachelse}
        <th >&nbsp;</th>
        {/foreach}
    {/foreach}
    </tr>
    </thead>

    {foreach $teams as $team}
    <tr class="first">
    <th {if !$team.active}class="deactivated"{/if} rowspan="2">
        <a href="{$team.graph_url|escape}" {if !$team.active}title="deactivated"{/if}>{$team.name|escape}</a><br />
        ({$team.wallet} {$lang_str.iquest_txt_coin_symbol})
    </th>
        {foreach $clue_groups as $group}{$colspan=$group.solution_ids|count}{if !$colspan}{$colspan=1}{/if}
        {$data_content="<strong>Tým: </strong>`$team.name|escape`<br /><strong>Úkol: </strong><a href='`$group.view_url|escape`'>`$group.name|escape`</a>"}
        <td colspan="{$colspan}" 
            class="time-field {if $cgrp_team[$group.id][$team.id].gained}solved{/if}" 
            title="{$group.id|escape}"
            data-toggle="popover" 
            data-content="{$data_content|escape}"
        >
        {$cgrp_team[$group.id][$team.id].gained_at|escape}
        </td>
        {/foreach}
    </tr>
    <tr class="second">
        {foreach $clue_groups as $group}
            {foreach $group.solution_ids as $solution_id}
            {if $solutions.$solution_id.view_url|default:0}
            {$data_content="<strong>Tým: </strong>`$team.name|escape`<br /><strong>Řešení: </strong><a href='`$solutions.$solution_id.view_url|escape`'>`$solutions.$solution_id.name|escape`</a>"}
            {else}
            {$data_content="<strong>Tým: </strong>`$team.name|escape`<br /><strong>Řešení: </strong>`$solutions.$solution_id.name|escape`"}
            {/if}
            <td class="time-field {if $solution_team[$solution_id][$team.id].solved}solved{/if}" 
                title="{$solutions.$solution_id.id|escape}"
                data-toggle="popover" 
                data-content="{$data_content|escape}"
            >
            {$solution_team[$solution_id][$team.id].solved_at|escape}
            </td>
            {foreachelse}
            <td class="unused">&nbsp;</td>
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

    if (!isAndroid && !isIphone){
        // floatThead do not work well on android. It's quite slow.
        // Moreover the table header consumes a lot of space on the small screen.
        // So enable it only on not mobile device.

        var $table = $('#clueTable');
        $table.floatThead();


        // Kinetic is not needed on android. Its functionality is already in
        // the mobile browser.
        // So enable it only on not mobile device too.
        
        $('#scroll-wrapper').kinetic();
        $('#scroll-wrapper').addClass('inselectable'); 
    }

    $(".time-field").popover({
        placement: "top",
        trigger: "click",
        html: true,
        container: 'body'
    });

    $('.time-field').on('shown.bs.popover', function () {
        var $pop = $(this);
        setTimeout(function () {
            $pop.popover('hide');
        }, 1400);
    });
    </script>
{/literal}
{/if}
