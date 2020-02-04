{* Smarty *}
{*
 *	Smarty template displaying error messages
 *
 *}

{foreach $errors as $row}
{if $row@first}
	<div class="alert alert-danger">
    <button class="close" data-dismiss="alert" type="button">×</button>
    <ul class="mb-0">
{/if}
    <li>{$row|escape|nl2br}</li>
{if $row@last}
    </ul>
    </div>
{/if}
{/foreach}
