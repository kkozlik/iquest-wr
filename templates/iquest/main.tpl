{* Smarty *}

{include file='_head.tpl'}

{include file="iquest/iquest-functions.tpl"}

{if $action=='view_grp'}

    {foreach $clues as $clue}
    <div class="datatable">
    <table>
    <tr>
        <th>{$clue.filename}</th>
        <th><a href="{$clue.file_url|escape}">{$lang_str.iquest_download}</a></th>
    </tr>
    {call iquestRenderFile file=$clue}
    </table>
    </div>
    {/foreach}

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

{/if}
<br>
{include file='_tail.tpl'}

