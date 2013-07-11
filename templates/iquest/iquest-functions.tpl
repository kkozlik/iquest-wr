

{function iquestRenderFile file=0}
    {if $file.content_type == "text/plain"}
        <tr>
            <td colspan="2"><pre class="plaintext">{$file.content}</pre>
            </td>
        </tr>
    {elseif $file.content_type|truncate:6:"" == "image/"}
        <tr>
            <td colspan="2" class="text-center fileimg">
                <img src="{$file.file_url|escape}" />
                <div>Velikost: <span class="img_size"></span></div>
            </td>
        </tr>
    {elseif $file.content_type|truncate:6:"" == "audio/"}
        <tr>
            <td colspan="2">
                <div class="text-center">{$lang_str.iquest_file_audio_content}</div>
            </td>
        </tr>
    {elseif $file.content_type|truncate:6:"" == "video/"}
        <tr>
            <td colspan="2" class="text-center">
                <div class="text-center">{$lang_str.iquest_file_video_content}</div>
            </td>
        </tr>
    {else}
        <tr>
            <td colspan="2" class="text-center">
                <div class="text-center">{$lang_str.iquest_file_general_content}</div>
            </td>
        </tr>
    {/if}
{/function}


{function iquestPager}
    <div class="pagination pagination-right"><ul><li>
        {pager page=$pager 
               class_text='' 
               class_num='' 
               class_numon='active' 
               txt_first='&laquo;&laquo;' 
               txt_prev='&laquo;' 
               txt_next='&raquo;' 
               txt_last='&raquo;&raquo;' 
               display='always' 
               separator='</li><li>'}
    </li></ul></div>
{/function}
