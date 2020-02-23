{* Smarty *}
{*
 * Smarty template displaying status messages
 *
 *}
{strip}

{capture name='infoTplStart'}
    <div class="alert alert-success">
    <button class="close" data-dismiss="alert" type="button">×</button>
    <ul class="mb-0">
{/capture}

{capture name='infoTplEnd'}
    </ul></div>
{/capture}

{capture name='infoItemTpl'}
    <li><span class="msgItemText"></span></li>
{/capture}

{capture name='infoCoinTpl'}
    <li><span class="msgItemText"></span><span class="coin-animated"></span></li>
{/capture}

<div id="infoPlaceHolder"
     data-template="{$smarty.capture.infoTplStart|escape}{$smarty.capture.infoTplEnd|escape}"
     data-item-template="{$smarty.capture.infoItemTpl|escape}"
     data-coin-template="{$smarty.capture.infoCoinTpl|escape}"
>
    {foreach $message as $row}
    {if $row@first}{$smarty.capture.infoTplStart}{/if}
        <li><span class="msgItemText">{$row.long|escape}</span>{if "coin"==$row.type|default:false}<span class="coin-animated"></span>{/if}</li>
    {if $row@last}{$smarty.capture.infoTplEnd}{/if}
    {/foreach}
</div>

{/strip}
