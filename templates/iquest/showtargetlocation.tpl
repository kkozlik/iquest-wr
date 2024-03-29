{* Smarty *}

{include file='_head.tpl'}

{include file="iquest/iquest-functions.tpl"}

    <ul class="breadcrumb mt-1 mt-lg-3">
    <li><a href="{$main_url|escape}">{$lang_str.iquest_l_back}</a></li>
    </ul>

    {foreach $clues as $clue}
    <div class="datatable clue">
        <div class="card mb-3">{* Adding rounded corners to table *}
            <table class="table mb-0">
            <tr>
                <th class="filename">{$clue.filename}</th>
                <th class="link"><a href="{$clue.download_file_url|escape}" class="btn btn-sm btn-outline-secondary text-nowrap"><i class="fas fa-download"></i> {$lang_str.iquest_download}</a></th>
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
