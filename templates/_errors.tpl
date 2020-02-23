{* Smarty *}
{*
 *  Smarty template displaying error messages
 *
 *}
{strip}

{capture name='errorTplStart'}
    <div class="alert alert-danger">
    <button class="close" data-dismiss="alert" type="button">×</button>
    <ul class="mb-0">
{/capture}

{capture name='errorTplEnd'}
    </ul></div>
{/capture}

{capture name='errorItemTpl'}
    <li><span class="msgItemText"></span></li>
{/capture}

<div id="errPlaceHolder"
     data-template="{$smarty.capture.errorTplStart|escape}{$smarty.capture.errorTplEnd|escape}"
     data-item-template="{$smarty.capture.errorItemTpl|escape}"
>
    {foreach $errors as $row}
    {if $row@first}{$smarty.capture.errorTplStart}{/if}
        <li><span class="msgItemText">{$row|escape|nl2br}</span></li>
    {if $row@last}{$smarty.capture.errorTplEnd}{/if}
    {/foreach}
</div>

{/strip}
