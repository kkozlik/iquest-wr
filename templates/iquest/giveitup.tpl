{* Smarty *}

{include file='_head.tpl'}

{include file="iquest/iquest-functions.tpl"}

<ul class="breadcrumb">
<li><a href="{$main_url|escape}">{$lang_str.iquest_l_back}</a></li>
</ul>


<div class="card bg-light mb-3">
    <div class="card-body p-5">

        <h3>{$lang_str.iquest_txt_giveitup_heading}</h3>
        <p>{$lang_str.iquest_txt_giveitup_p1}</p>
        <p>{$lang_str.iquest_txt_giveitup_p2}</p>

        <div class="form-inline">
        {$form.start}
        {$form.passwd} {$form.okey}
        {$form.finish}
        </div>

    </div>
</div>


<ul class="breadcrumb">
<li><a href="{$main_url|escape}">{$lang_str.iquest_l_back}</a></li>
</ul>

<br>
{include file='_tail.tpl'}
