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
	<li>{$row.long|escape}</li>
{if $row@last}
	</ul>
	</div>
{/if}
{/foreach}

