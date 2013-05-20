{* 
 *	Smarty template displaying heading of pages
 *}

<div id="swContent">

<div id="errPlaceHolder">
{include file='_errors.tpl' errors=$parameters.errors}
</div>

{include file='_message.tpl' message=$parameters.message}

{if $parameters.errors or $parameters.message}
    <br />
{/if}

