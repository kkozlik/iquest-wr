

{function iquestRenderFile file=0}
    {if $file.content_type == "text/plain"}
        <tr>
            <td colspan="2"><span class="plaintext">{$file.content}</span>
            </td>
        </tr>
    {elseif $file.content_type|truncate:6:"" == "image/"}
        <tr>
            <td colspan="2" class="text-center fileimg">
                <img src="{$file.file_url|escape}" />
                <div>Velikost: <span class="img_size"></span></div>
            </td>
        </tr>
    {/if}
{/function}
