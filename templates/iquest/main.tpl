{* Smarty *}

{include file='_head.tpl'}

{include file="iquest/iquest-functions.tpl"}


{if $action=='view_grp'}

    <ul class="breadcrumb breadcrumb-btn">
    <li class="no-btn"><a href="{$main_url|escape}">{$lang_str.iquest_l_back}</a></li>
    {if $clue_grp.hints_for_sale}
    <li class="pull-right"><a href="{$clue_grp.buy_url|escape}" class="btn">{$lang_str.iquest_btn_buy_hint}</a></li>
    {/if}
    </ul>

    {foreach $clue_grp.clues as $clue}
    <div class="datatable clue">
    <table class="table table-bordered">
    <tr>
        <th class="filename">{$clue.filename}
            {if $clue.type=="coin"}<span class="coin"></span>{/if}
        </th>
        <th class="link"><a href="{$clue.file_url|escape}" class="btn"><i class="icon-download-alt"></i> {$lang_str.iquest_download}</a></th>
    </tr>
    {call iquestRenderFile file=$clue}
    </table>
    </div>

    {foreach $clue.hints as $hint}
    <div class="datatable hint">
    <table class="table table-bordered">
    <tr {if $hint.new}class="new"{/if}>
        <th class="filename">
            {$lang_str.iquest_hint}: {$hint.filename}
            {if $hint.new}<span class="new"></span>{/if}
        </th>
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

    <div class="form-inline well">
    {$form.start}
    <div class="text-center">
    <label for="solution_key" class="solution_label">{$lang_str.iquest_solution_key}:</label>
    {$form.solution_key}
    {$form.okey}
    </div>
    {$form.finish}
    </div>
    
    
    
    <div class="row">
    <div class="span6">
    <table class="table table-bordered clue-list">
    <tr><th>{$lang_str.iquest_avail_tasks}</th></tr>
    {foreach $clue_groups as $group}
    <tr><td {if $group.new or $group.new_hints}class="new"{/if}>
        <a href="{$group.detail_url|escape}">{$group.name}</a>
        {if $group.hints_for_sale}<a href="{$group.buy_url|escape}" class="btn pull-right">{$lang_str.iquest_btn_buy_hint}</a>{/if}
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

