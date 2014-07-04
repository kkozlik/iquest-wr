{* Smarty *}
{* 
 *	Smarty template displaying status messages
 *
 *}

{foreach $message as $row}
{if $row@first}
   	<div class="alert alert-block alert-success fade in">
    <button class="close" data-dismiss="alert" type="button">×</button>
	<ul class="unstyled">
{/if}
	<li>{$row.long|escape}{if "coin"==$row.type|default:false}<span class="coin-animated"></span>{/if}</li>
{if $row@last}
	</ul>
	</div>
{/if}
{/foreach}

