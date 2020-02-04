{* Smarty *}

{include file='_head.tpl'}

{include file="iquest/iquest-functions.tpl"}

    <ul class="breadcrumb">
    <li><a href="{$main_url|escape}">{$lang_str.iquest_l_back}</a></li>
    </ul>

    {foreach $clues as $clue}
    <div class="datatable clue">
        <div class="card mb-3">{* Adding rounded corners to table *}
            <table class="table mb-0">
            <tr>
                <th class="filename">{$clue.filename}</th>
                <th class="link"><a href="{$clue.download_file_url|escape}" class="btn btn-sm btn-outline-secondary"><i class="icon-download-alt"></i> {$lang_str.iquest_download}</a></th>
            </tr>
            {call iquestRenderFile file=$clue}
            </table>
        </div>
    </div>
    {/foreach}

    <ul class="breadcrumb">
    <li><a href="{$main_url|escape}">{$lang_str.iquest_l_back}</a></li>
    </ul>

<br>
{include file='_tail.tpl'}
