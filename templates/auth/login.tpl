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


<table border="0" cellspacing="0" cellpadding="0" align="center">
<tr>
<td><label for="uname">{$lang_str.auth_username}:</label></td>
<td>{$form.uname}</td>
</tr>
<tr>
<td><label for="passw">{$lang_str.auth_password}:</label></td>
<td>{$form.passw}</td>
</tr>
</table>
<div class="loginButton">{$form.okey}</div>
{$form.finish}
</div>

<br>
{include file='_tail.tpl'}

