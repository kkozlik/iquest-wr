{* Smarty *}
{*
 * Smarty template displaying status messages
 *
 *}

{foreach $message as $row}
{if $row@first}
    <div class="alert alert-success">
    <button class="close" data-dismiss="alert" type="button">×</button>
    <ul class="mb-0">
{/if}
    <li>{$row.long|escape}{if "coin"==$row.type|default:false}<span class="coin-animated"></span>{/if}</li>
{if $row@last}
    </ul>
    </div>
{/if}
{/foreach}

