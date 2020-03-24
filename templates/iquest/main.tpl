{* Smarty *}

{include file='_head.tpl'}

{include file="iquest/iquest-functions.tpl"}


{**
 *  Print clues and hints of a clue group
 *  @param  clue_grp
 *}
{function print_clue_grp}
    {foreach $clue_grp.clues as $clue}
    <div class="datatable clue" id="{$clue.ref_id|escape}" {if $clue.hidden}style="display: none;"{/if}>
        <div class="card mb-3">{* Adding rounded corners to table *}
            <table class="table shrinkable mb-0">
            <tr>
                <th class="filename align-middle">{$clue.filename|escape}
                    {if $clue.type=="coin"}<span class="coin"></span>
                    {elseif $clue.type=="special"}<span class="questionmark"></span>
                    {else}<span class="finder"></span>{/if}
                </th>
                <th class="link align-middle"><a href="{$clue.download_file_url|escape}" class="btn btn-sm btn-outline-secondary"><i class="icon-download-alt"></i> {$lang_str.iquest_download}</a></th>
                <th class="buttons align-middle">
                    <a href="#" title="{$lang_str.iquest_minimize}" class="btn btn-outline-secondary btn-sm minimize-btn" data-obj-id="{$clue.ref_id|escape}" data-url-hide="{$clue.hide_url}"><i class="fas fa-chevron-down"></i></a>
                </th>
            </tr>
            {call iquestRenderFile file=$clue colspan=3}
            </table>
        </div>
    </div>

    {foreach $clue.hints as $hint}
    <div class="datatable hint" id="{$hint.ref_id|escape}" {if $hint.hidden}style="display: none;"{/if}>
        <div class="card mb-3">{* Adding rounded corners to table *}
            <table class="table shrinkable mb-0">
            <tr {if $hint.new}class="new"{/if}>
                <th class="filename align-middle">
                    {$lang_str.iquest_hint}: {$hint.filename|escape}
                    {if $hint.new}<span class="new"></span>{/if}
                </th>
                <th class="link align-middle"><a href="{$hint.download_file_url|escape}" class="btn btn-sm btn-outline-secondary"><i class="icon-download-alt"></i> {$lang_str.iquest_download}</a></th>
                <th class="buttons align-middle">
                    <a href="#" title="{$lang_str.iquest_minimize}" class="btn btn-outline-secondary btn-sm minimize-btn" data-obj-id="{$hint.ref_id|escape}" data-url-hide="{$hint.hide_url}"><i class="fas fa-chevron-down"></i></a>
                </th>
            </tr>
            {call iquestRenderFile file=$hint colspan=3}
            </table>
        </div>
    </div>
    {/foreach}
    {/foreach}
{/function}


{**
 *  Print title of a clue group
 *  @param  clue_grp
 *}
{function print_clue_grp_title}
    <div class="card mb-3 cgrp-title {if $clue_grp.new}new{/if}" id="{$clue_grp.ref_id}">
        <div class="card-body p-1">
            <div class="row align-items-center mx-0">
                <h5 class="col-auto mb-0">{$clue_grp.name|escape}</h5>
                <div class="col-auto">{call print_minimized_clues clue_grp=$clue_grp}</div>
                <div class="col my-auto text-center">{$lang_str.iquest_txt_gained_at}: {call print_clue_grp_gained_at clue_grp=$clue_grp}</div>
                {if $clue_grp.new}
                    <div class="col-auto px-0"><span class="new"></span></div>
                {/if}
                {if $clue_grp.hints_for_sale}
                    <div class="col-auto pl-2 pr-0"><a href="{$clue_grp.buy_url|escape}" class="btn btn-sm btn-outline-secondary" onclick="return linkConfirmation(this, '{$clue_grp.buy_confirmation|escape:js}')">{$lang_str.iquest_btn_buy_hint} {$clue_grp.hint_price|escape}</a></div>
                {/if}
            </div>
        </div>
    </div>
{/function}


{**
 *  Print the gained_at value of clue group.
 *  If the time value is from today, just the time is displayed. Otherwise date and time is displayed.
 *  @param  clue_grp
 *}
{function print_clue_grp_gained_at}
    {if $smarty.now|date_format:'%Y-%m-%d' == $clue_grp.gained_at_ts|date_format:'%Y-%m-%d'}
        {$clue_grp.gained_at_ts|date_format:'%H:%M:%S'|escape}
    {else}
        {$clue_grp.gained_at_ts|date_format:'%d.%m.%Y %H:%M:%S'|escape}
    {/if}
{/function}

{**
 *  Print minimized clues and hints (as part of clue grp title)
 *  @param  clue_grp
 *}
{function print_minimized_clues}
    <ul class="list-inline shrinked-grp mb-0">
    {foreach $clue_grp.clues as $clue}
    <li class="list-inline-item {if !$clue.hidden}minimized-hidden{/if}" id="min{$clue.ref_id|escape}">
        <a href="#" class="restore-btn" data-obj-id="{$clue.ref_id|escape}" data-url-unhide="{$clue.unhide_url}">{$clue.filename|escape}</a>
    </li>
    {foreach $clue.hints as $hint}
    <li class="list-inline-item {if !$hint.hidden}minimized-hidden{/if}" id="min{$hint.ref_id|escape}">
        <a href="#" class="restore-btn" data-obj-id="{$hint.ref_id|escape}" data-url-unhide="{$hint.unhide_url}">{$hint.filename|escape}</a>
    </li>
    {/foreach}
    {/foreach}
    </ul>
{/function}


{**
 *  Print the block containing input elements for entering key
 *}
{function print_key_input}
    <div class="card bg-light mb-4">
        <div class="card-body p-4">

    {$form.start}
    <div class="row align-items-center">
        <div class="col-12 col-sm-{($tracker_enabled) ? 6 : 12} col-md-3 col-xl-2 pr-md-0 align-self-start">
            <div class="row mb-1">
                <div class="col">
                    <a href="{$all_in_1_url|escape}" class="text-nowrap btn btn-block {if $action=='view_all'}btn-dark{else}btn-outline-secondary{/if}">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" name="allInOneChk" id="allInOneChk" {if $action=='view_all'}checked{/if}>
                            <label class="custom-control-label" for="allInOneChk">{$lang_str.iquest_all_in_1}</label>
                        </div>
                    </a>
                </div>
            </div>
            {if $graph_enabled}
            <div class="row mb-1 mb-md-0">
                <div class="col">
                    <a href="{$view_graph_url|escape}" class="text-nowrap btn btn-block btn-outline-secondary">{$lang_str.iquest_graph}</a>
                </div>
            </div>
            {/if}
        </div>
        {if $tracker_enabled}
        <div class="col-12 col-sm-6 col-md-3 col-xl-2 pl-md-0 order-md-last">
            <div class="row mb-1">
                <div class="col">
                    <a href="javascript:" class="text-nowrap btn btn-block btn-outline-secondary myLocationBtn">{$lang_str.iquest_show_position}</a>
                </div>
            </div>
            <div class="row mb-1 mb-md-0">
                <div class="col">
                    <a href="{$check_location_url|escape}" class="text-nowrap btn btn-block btn-primary checkLocationBtn">{$lang_str.iquest_verify_tracker}</a>
                </div>
            </div>
        </div>
        {/if}
        <div class="col mt-1 mt-md-0">
            <div class="form-inline justify-content-center">
                <label for="solution_key" class="mr-2">{$lang_str.iquest_solution_key}:</label>
                {$formobj->el('solution_key')->add_class('mr-sm-1 mb-1 mb-sm-0')}
                {$form.okey}
            </div>
        </div>
    </div>
    {$form.finish}
        </div>
    </div>

    <div id="myLocationPopup" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myLocationPopupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h6 class="modal-title" id="myLocationPopupModalLabel">{$lang_str.iquest_your_position}
                <br /><small>Pozice získána naposledy před <span class="updateTime"></span></small>
                <br /><small class="d-none text-danger tracker-warning">Zkontrolujte funkci trackeru!</small>
            </h6>
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true" aria-label="Close">×</button>
        </div>
        <div class="modal-body">
            <div class="errPlaceHolder"
                data-template="{$smarty.capture.errorTplStart|escape}{$smarty.capture.errorTplEnd|escape}"
                data-item-template="{$smarty.capture.errorItemTpl|escape}"
            ></div>

            <div id="myLocationPopupMapCanvas" style="min-height:300px;"></div>
        </div>
    </div>
    </div>
    </div>

    <script type="text/javascript">
        $(document).ready(function () {

            var locCtl = new LocationCtl();
            locCtl.get_location_url = {$get_location_url|json_encode};
            locCtl.check_location_url = {$check_location_url|json_encode};

            locCtl.mapCanvasId = 'myLocationPopupMapCanvas';
            locCtl.mapPopup = $("#myLocationPopup");
            locCtl.openPopupBtn = $("a.myLocationBtn");
            locCtl.checkLocationBtn = $("a.checkLocationBtn");

            locCtl.init();

            $("#solution_key").focus();
        });
    </script>
{/function}




{if $action=='view_grp'}

    <ul class="breadcrumb">
    <li><a href="{$back_url|escape}">{$lang_str.iquest_l_back}</a></li>
    </ul>

    {call print_clue_grp_title clue_grp=$clue_grp}
    {call print_clue_grp clue_grp=$clue_grp}

    <ul class="breadcrumb">
    <li><a href="{$back_url|escape}">{$lang_str.iquest_l_back}</a></li>
    </ul>

{elseif $action=='view_all'}

    {call print_key_input}

    {foreach $clue_groups as $group}
        {call print_clue_grp_title clue_grp=$group}
        {call print_clue_grp clue_grp=$group}
    {/foreach}

    {foreach $solutions as $solution}
        <div class="card mb-3 solution-title {if $solution.new}new{/if}" id="{$solution.ref_id}">
            <div class="card-body p-1">
                <div class="row align-items-center mx-0">
                    <h5 class="col mb-0">{$lang_str.iquest_solution}: {$solution.name|escape}</h5>

                    {if $solution.new}
                        <div class="col-auto px-0"><span class="new"></span></div>
                    {/if}
                    <div class="col-auto pl-2 pr-0"><a href="{$solution.detail_url|escape}" class="btn btn-sm btn-outline-secondary">{$lang_str.iquest_view}</a></div>
                </div>
            </div>
        </div>
    {/foreach}


{elseif $action=='view_solution'}

    <ul class="breadcrumb">
    <li><a href="{$back_url|escape}">{$lang_str.iquest_l_back}</a></li>
    </ul>

    <div class="datatable solution">
        <div class="card mb-3">{* Adding rounded corners to table *}
            <table class="table mb-0">
            <tr>
                <th class="filename">{$solutions.name}</th>
                <th class="link">&nbsp;</th>
            </tr>
            {call iquestRenderFile file=$solutions}
            </table>
        </div>
    </div>

    <ul class="breadcrumb">
    <li><a href="{$back_url|escape}">{$lang_str.iquest_l_back}</a></li>
    </ul>

{elseif $action=='view_graph'}

    <ul class="breadcrumb">
    <li><a href="{$back_url|escape}">{$lang_str.iquest_l_back}</a></li>
    </ul>

    <div class="card bg-light mb-3">
        <div class="card-body p-1">
            <object id="contestGraph" data="{$get_graph_url|escape}" type="image/svg+xml" width="100%"></object>
        </div>
    </div>

    <ul class="breadcrumb">
    <li><a href="{$back_url|escape}">{$lang_str.iquest_l_back}</a></li>
    </ul>

    <script type="text/javascript">

        // load svgZoom.js script
        $.getScript("{$cfg->js_src_path}svgZoom.js");

        $('#contestGraph').on('load', function(){
            svgZoom.init('#contestGraph');
        });
    </script>

{else}

    {call print_key_input}


    <div class="row">
    <div class="col-6">
    <table class="table table-bordered clue-list">
    <tr><th>{$lang_str.iquest_avail_tasks}</th></tr>
    {foreach $clue_groups as $group}
    <tr><td {if $group.new or $group.new_hints}class="new"{/if}>
        <a href="{$group.detail_url|escape}">{$group.name}</a>
        {if $group.hints_for_sale}<a href="{$group.buy_url|escape}" class="btn btn-sm btn-outline-secondary float-right" style="margin-top: 2px;" onclick="return linkConfirmation(this, '{$group.buy_confirmation|escape:js}')">{$lang_str.iquest_btn_buy_hint} {$group.hint_price|escape}</a>{/if}
        {if $group.new}<span class="new"></span>{/if}
        {if $group.new_hints}<span class="newhint"></span>{/if}
    </td></tr>
    {/foreach}
    </table>
    </div>

    {foreach $solutions as $solution}
        {if $solution@first}
        <div class="col-6">
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

