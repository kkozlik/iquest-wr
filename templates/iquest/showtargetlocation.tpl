{* Smarty *}

{include file='_head.tpl'}

{include file="iquest/iquest-functions.tpl"}

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
    {/foreach}

<br>
{include file='_tail.tpl'}
