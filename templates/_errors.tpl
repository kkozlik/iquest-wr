{* Smarty *}
{* 
 *	Smarty template displaying error messages
 *
 *}

{foreach $errors as $row}
{if $row@first}
	<div class="alert alert-block alert-error fade in">
    <button class="close" data-dismiss="alert" type="button">×</button>
    <ul>
{/if}	
    <li>{$row|escape|nl2br}</li>
{if $row@last}
    </ul>
    </div>
{/if}	
{/foreach}
