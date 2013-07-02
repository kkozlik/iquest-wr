{* 
 *	Smarty template displaying heading of pages
 *}

{$next_hint=$next_hint|default:""}
{$next_solution=$next_solution|default:""}

{if !$login_screen|default:false}
<div class="navbar navbar-inverse navbar-static-top">
  <div class="navbar-inner">
    <div class="container">
        <span class="brand">I.Quest</span>

        <div class="nav"><span class="navbar-text team-name">{$team_name|default:""|escape}</span></div>

		<ul class="nav pull-right">
			<li><a href='#'>{$lang_str.iquest_l_give_it_up}</a></li>
			<li><a href='{$parameters.logout_url|escape}'>{$lang_str.iquest_l_logout}</a></li>
	  	</ul>

        <div class="navinfo {($next_hint and $next_solution)?"double":""}">
        {if $next_hint}<div>{$lang_str.iquest_txt_next_hint}: <span id="hint_countdown">{$next_hint}</span></div>{/if}
        {if $next_solution}<div>{$lang_str.iquest_txt_next_solution}: <span id="solution_countdown">{$next_solution}</span></div>{/if}
        </div>
    </div>
  </div>
</div>
<br />
{/if}

<div class="container" id="page_container">

{if $contest_over|default:false}
    <div class="alert alert-block alert-error fade in contest-over">
    <span>{$lang_str.iquest_txt_contest_over}</span>
    <a class="btn btn-danger pull-right" href="{$reveal_url|escape}">{$lang_str.iquest_txt_show_goal}</a>
    </div>
{/if}


<div id="errPlaceHolder">
{include file='_errors.tpl' errors=$parameters.errors}
</div>

{include file='_message.tpl' message=$parameters.message}


