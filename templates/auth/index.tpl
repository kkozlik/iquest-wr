{* Smarty *}

{include file='_head.tpl'}

{literal}
<style type="text/css">
	#uname {width:97%;}
	#passw {width:97%;}
</style>	
{/literal}


<div class="swLPTitle">
<h1>{$lang_str.auth_userlogin}</h1>
<p>{$lang_str.auth_enter_username_and_passw}:</p>
</div>

<div class="swForm swLoginForm">

{$form.start}
<table border="0" cellspacing="0" cellpadding="0" align="center">
<tr>
<td width="50%"><label for="uname">{$lang_str.auth_username}:</label></td>
<td>{$form.uname}</td>
</tr>
<tr>
<td width="50%"><label for="passw">{$lang_str.auth_password}:</label></td>
<td>{$form.passw}</td>
</tr>
</table>
<div class="loginButton">{$form.okey}</div>
{$form.finish}
</div>

<br>
{include file='_tail.tpl'}

