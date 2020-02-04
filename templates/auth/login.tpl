{* Smarty *}

{include file='_head.tpl'}

{literal}
<style type="text/css">
    body {
        background-color: #F5F5F5;
        padding-bottom: 40px;
        padding-top: 40px;
    }
</style>
{/literal}


<div class="form-signin">

    {$form.start}
        <h2 class="form-signin-heading">{$lang_str.auth_userlogin}</h2>
        <p>{$lang_str.auth_enter_username_and_passw}:</p>

        <div class="row align-items-center">
            <div class="col-3">
                <label for="uname">{$lang_str.auth_username}:</label>
            </div>
            <div class="col">
                {$form.uname}
            </div>
        </div>

        <div class="row align-items-center">
            <div class="col-3">
                <label for="passw">{$lang_str.auth_password}:</label>
            </div>
            <div class="col">
                {$form.passw}
            </div>
        </div>

        {$form.okey}
    {$form.finish}
</div>

<br>
{include file='_tail.tpl'}

