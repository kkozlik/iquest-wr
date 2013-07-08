{* Smarty *}

{include file='_head.tpl'}

{include file="iquest/iquest-functions.tpl"}



    <div class="horizontal-scrollbar">     
    <table class="table table-bordered summary">
    <tr>
    <th>&nbsp;</th>
    {foreach $clue_groups as $group}
    <th ><a href="#">{$group.name|escape}</a></th>
    {/foreach}
    </tr>

    {foreach $teams as $team}
    <tr>
    <th><a href="{$team.graph_url|escape}">{$team.name|escape}</a></th>
        {foreach $clue_groups as $group}
        <td {if $cgrp_team[$group.id][$team.id]}class="solved"{/if}>
        {$cgrp_team[$group.id][$team.id]}
        </td>
        {/foreach}
    </tr>
    {/foreach}

    </table>
    </div>

<br>
{include file='_tail.tpl'}

