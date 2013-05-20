{* Smarty *}

{include file='_head.tpl'}

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

<br>
{include file='_tail.tpl'}

