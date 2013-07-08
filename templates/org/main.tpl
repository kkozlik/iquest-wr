{* Smarty *}

{include file='_head.tpl'}

{include file="iquest/iquest-functions.tpl"}


     
    <table class="table table-bordered">
    <tr>
    <th>&nbsp;</th>
    {foreach $clue_groups as $group}
    <th ><a href="#">{$group.name|escape}</a></th>
    {/foreach}
    </tr>

    {foreach $teams as $team}
    <tr>
    <th>{$team.name|escape}</th>
        {foreach $clue_groups as $group}
        <td >
        {$cgrp_team[$group.id][$team.id]}
        </td>
        {/foreach}
    </tr>
    {/foreach}

    </table>


<br>
{include file='_tail.tpl'}

