

{function iquestRenderFile file=0}
    {if $file.content_type == "text/plain"}
        <tr>
            <td colspan="2"><span class="">{$file.content}</span></td>
        </tr>
    {elseif $file.content_type|truncate:6:"" == "image/"}
        <tr>
            <td colspan="2"><img src="{$file.file_url|escape}" /></td>
        </tr>
    {/if}
{/function}
