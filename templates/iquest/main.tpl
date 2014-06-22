{* Smarty *}

{include file='_head.tpl'}

{include file="iquest/iquest-functions.tpl"}

{function print_clue_grp}
    {foreach $clue_grp.clues as $clue}
    <div class="datatable clue" id="{$clue.ref_id|escape}" {if $clue.hidden}style="display: none;"{/if}>
    <table class="table table-bordered shrinkable">
    <tr>
        <th class="filename">{$clue.filename|escape}
            {if $clue.type=="coin"}<span class="coin"></span>{/if}
        </th>
        <th class="link"><a href="{$clue.file_url|escape}" class="btn"><i class="icon-download-alt"></i> {$lang_str.iquest_download}</a></th>
        <th class="buttons">
            <a href="#" class="btn minimize-btn" data-obj-id="{$clue.ref_id|escape}" data-url-hide="{$clue.hide_url}"><i class="icon-chevron-down"></i></a>
        </th>
    </tr>
    {call iquestRenderFile file=$clue colspan=3}
    </table>
    </div>

    {foreach $clue.hints as $hint}
    <div class="datatable hint" id="{$hint.ref_id|escape}" {if $hint.hidden}style="display: none;"{/if}>
    <table class="table table-bordered shrinkable">
    <tr {if $hint.new}class="new"{/if}>
        <th class="filename">
            {$lang_str.iquest_hint}: {$hint.filename}
            {if $hint.new}<span class="new"></span>{/if}
        </th>
        <th class="link"><a href="{$hint.file_url|escape}" class="btn"><i class="icon-download-alt"></i> {$lang_str.iquest_download}</a></th>
        <th class="buttons">
            <a href="#" class="btn minimize-btn" data-obj-id="{$hint.ref_id|escape}" data-url-hide="{$hint.hide_url}"><i class="icon-chevron-down"></i></a>
        </th>
    </tr>
    {call iquestRenderFile file=$hint colspan=3}
    </table>
    </div>
    {/foreach}
    {/foreach}
{/function}

{function print_minimized_clues}
    <ul class="navbar-text inline shrinked-grp">
    {foreach $clue_grp.clues as $clue}
    <li id="min{$clue.ref_id|escape}" {if !$clue.hidden}class="minimized-hidden"{/if}>
        <a href="#" class="navbar-link restore-btn" data-obj-id="{$clue.ref_id|escape}" data-url-unhide="{$clue.unhide_url}">{$clue.filename|escape}</a>
    </li>
    {foreach $clue.hints as $hint}
    <li id="min{$hint.ref_id|escape}" {if !$hint.hidden}class="minimized-hidden"{/if}>
        <a href="#" class="navbar-link restore-btn" data-obj-id="{$hint.ref_id|escape}" data-url-unhide="{$hint.unhide_url}">{$hint.filename|escape}</a>
    </li>
    {/foreach}
    {/foreach}
    </ul>
{/function}


{function print_key_input}
    <div class="form-inline well">
    {$form.start}
    <div class="text-center">
    <label for="solution_key" class="solution_label">{$lang_str.iquest_solution_key}:</label>
    {$form.solution_key}
    {$form.okey}
    </div>
    {$form.finish}
    </div>
    <script type="text/javascript">
        $(document).ready(function () {
            $("#solution_key").focus();
        });
    </script>
{/function}

{if $action=='view_grp'}

    <ul class="breadcrumb breadcrumb-btn">
    <li class="no-btn"><a href="{$main_url|escape}">{$lang_str.iquest_l_back}</a></li>
    {if $clue_grp.hints_for_sale}
    <li class="pull-right"><a href="{$clue_grp.buy_url|escape}" class="btn" onclick="return linkConfirmation(this, '{$clue_grp.buy_confirmation|escape:js}')">{$lang_str.iquest_btn_buy_hint}</a></li>
    {/if}
    </ul>

    {call print_clue_grp clue_grp=$clue_grp}

    <ul class="breadcrumb">
    <li><a href="{$main_url|escape}">{$lang_str.iquest_l_back}</a></li>
    </ul>

{elseif $action=='view_all'}

    {call print_key_input}

    {foreach $clue_groups as $group}
        <div class="navbar" id="{$group.ref_id}">
            <div class="navbar-inner {if $group.new}new{/if}">
            <div class="brand">{$group.name|escape}</div>
            <div class="pull-left">{call print_minimized_clues clue_grp=$group}</div>
            {if $group.hints_for_sale}
            <div class="pull-right"><a href="{$group.buy_url|escape}" class="btn" onclick="return linkConfirmation(this, '{$clue_grp.buy_confirmation|escape:js}')">{$lang_str.iquest_btn_buy_hint}</a></div>
            {/if}
            {if $group.new}
            <div class="pull-right"><span class="new"></span></div>
            {/if}
            </div>
        </div>

        {call print_clue_grp clue_grp=$group}
    {/foreach}
    
{elseif $action=='view_solution'}

    <ul class="breadcrumb">
    <li><a href="{$main_url|escape}">{$lang_str.iquest_l_back}</a></li>
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
    <li><a href="{$main_url|escape}">{$lang_str.iquest_l_back}</a></li>
    </ul>

{else}

    {call print_key_input}
    
    
    <div class="row">
    <div class="span6">
    <table class="table table-bordered clue-list">
    <tr><th>{$lang_str.iquest_avail_tasks}</th></tr>
    {foreach $clue_groups as $group}
    <tr><td {if $group.new or $group.new_hints}class="new"{/if}>
        <a href="{$group.detail_url|escape}">{$group.name}</a>
        {if $group.hints_for_sale}<a href="{$group.buy_url|escape}" class="btn pull-right" onclick="return linkConfirmation(this, '{$group.buy_confirmation|escape:js}')">{$lang_str.iquest_btn_buy_hint}</a>{/if}
        {if $group.new}<span class="new"></span>{/if}
        {if $group.new_hints}<span class="newhint"></span>{/if}
    </td></tr>
    {/foreach}
    </table>
    </div>

    {foreach $solutions as $solution}
        {if $solution@first}
        <div class="span6">
        <table class="table table-bordered clue-list">
        <tr><th>{$lang_str.iquest_avail_solutions}</th></tr>
        {/if}

        <tr><td {if $solution.new}class="new"{/if}>
            <a href="{$solution.detail_url|escape}">{$solution.name}</a>
            {if $solution.new}<span class="new"></span>{/if}
        </td></tr>

        {if $solution@last}
        </table>
        </div>
        {/if}
    {/foreach}

    </div>
{/if}
<br>
{include file='_tail.tpl'}

