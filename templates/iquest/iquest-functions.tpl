

{function iquestRenderFile file=0 colspan=2}
    {if $file.content_type == "text/plain"}
        <tr>
            <td colspan="{$colspan}"><pre class="plaintext">{$file.content}</pre>
            </td>
        </tr>
    {elseif $file.content_type == "image/svg+xml"}
        <tr>
            <td colspan="{$colspan}" class="text-center fileimg">
                <object data="{$file.file_url|escape}" type="image/svg+xml" width="100%"></object>
            </td>
        </tr>
    {elseif $file.content_type|truncate:6:"" == "image/"}
        <tr>
            <td colspan="{$colspan}" class="text-center fileimg">
                <img src="{$file.file_url|escape}" />
                <div>Velikost: <span class="img_size"></span></div>
            </td>
        </tr>
    {elseif $file.content_type|truncate:6:"" == "audio/"}
        <tr>
            <td colspan="{$colspan}">
                <div class="text-center"><span class="badge badge-inverse">{$lang_str.iquest_file_audio_content}</span></div>
            </td>
        </tr>
    {elseif $file.content_type|truncate:6:"" == "video/"}
        <tr>
            <td colspan="{$colspan}" class="text-center">
                <div class="text-center"><span class="badge badge-inverse">{$lang_str.iquest_file_video_content}</span></div>
            </td>
        </tr>
    {elseif $file.content_type == "application/pdf"}
        <tr>
            <td colspan="{$colspan}" class="text-center fileimg">
                <embed src="{$file.file_url|escape}" width="100%" height="800px"/>
            </td>
        </tr>
    {else}
        <tr>
            <td colspan="{$colspan}" class="text-center">
                <div class="text-center"><span class="badge badge-inverse">{$lang_str.iquest_file_general_content}</span></div>
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
