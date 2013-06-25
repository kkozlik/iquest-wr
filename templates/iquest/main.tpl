{* Smarty *}

{include file='_head.tpl'}

{include file="iquest/iquest-functions.tpl"}

{if $contest_over}
    <div>
    <h1>{$lang_str.iquest_txt_contest_over}</h1>
    <a href="{$reveal_url|escape}">{$lang_str.iquest_txt_show_goal}</a>
    </div>
{/if}

{if $next_hint}
    <div>
    {$lang_str.iquest_txt_next_hint}: {$next_hint}
    </div>
{/if}

{if $next_solution}
    <div>
    {$lang_str.iquest_txt_next_solution}: {$next_solution}
    </div>
{/if}

{if $action=='view_grp'}

    {foreach $clues as $clue}
    <div class="datatable clue">
    <table>
    <tr>
        <th>{$clue.filename}</th>
        <th><a href="{$clue.file_url|escape}">{$lang_str.iquest_download}</a></th>
    </tr>
    {call iquestRenderFile file=$clue}
    </table>
    </div>

    {foreach $clue.hints as $hint}
    <div class="datatable hint">
    <table>
    <tr>
        <th>Hint: {$hint.filename}</th>
        <th><a href="{$hint.file_url|escape}">{$lang_str.iquest_download}</a></th>
    </tr>
    {call iquestRenderFile file=$hint}
    </table>
    </div>
    {/foreach}
    {/foreach}

    <a href="{$main_url|escape}">{$lang_str.iquest_l_back}</a>

{elseif $action=='view_solution'}

    <div class="datatable solution">
    <table>
    <tr>
        <th>{$solutions.name}</th>
        <th>&nbsp;</th>
    </tr>
    {call iquestRenderFile file=$solutions}
    </table>
    </div>

    <a href="{$main_url|escape}">{$lang_str.iquest_l_back}</a>

{else}
    
    {$form.start}
    <div>
    <label for="solution_key">{$lang_str.iquest_solution_key}:</label>
    {$form.solution_key}
    {$form.okey}
    </div>
    {$form.finish}
    
    
    
    <div class="datatable">
    <table>
    <tr><th>{$lang_str.iquest_avail_tasks}</th></tr>
    {foreach $clue_groups as $group}
    <tr><td><a href="{$group.detail_url|escape}">{$group.name}</a></td></tr>
    {/foreach}
    </table>
    </div>

    {foreach $solutions as $solution}
        {if $solution@first}
        <div class="datatable">
        <table>
        <tr><th>{$lang_str.iquest_avail_solutions}</th></tr>
        {/if}

        <tr><td><a href="{$solution.detail_url|escape}">{$solution.name}</a></td></tr>

        {if $solution@last}
        </table>
        </div>
        {/if}
    {/foreach}

{/if}
<br>
{include file='_tail.tpl'}

