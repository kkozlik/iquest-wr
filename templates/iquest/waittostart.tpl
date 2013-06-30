{include file='_head.tpl'}

{$search=['<date>','<time>']}
{$replacements=[$start_time|my_date_format:"j.n.Y",
                $start_time|my_date_format:"H:i:s"]}



<div class="alert alert-block alert-info fade in text-center">
    <h1>{$lang_str.iquest_txt_contest_begins_at|replace:$search:$replacements}</h1>
    <h2>{$lang_str.iquest_txt_time_remaining}: <span id="start_coundown">{$time_remaining}</span></h2>
</div>

<br>
{include file='_tail.tpl'}
