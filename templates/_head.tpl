{* 
 *	Smarty template displaying heading of pages
 *}

{$next_hint=$next_hint|default:""}
{$next_solution=$next_solution|default:""}

{if !$login_screen|default:false}
<div class="navbar navbar-inverse navbar-static-top">
  <div class="navbar-inner">
    <div class="container">

        {* .btn-navbar is used as the toggle for collapsed navbar content *}
        <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        </a>

        <span class="brand logo"><img src="{$cfg->img_src_path}logo.png" alt="I.Quest"></span>

        <div class="navinfo {($next_hint and $next_solution)?"double":""}">
            <div class="navinfo-inner">
            {if $next_hint}<div>{$lang_str.iquest_txt_next_hint}: <span id="hint_countdown">{$next_hint}</span></div>{/if}
            {if $next_solution}<div>{$lang_str.iquest_txt_next_solution}: <span id="solution_countdown">{$next_solution}</span></div>{/if}
            </div>
        </div>

        {* Everything you want hidden at 940px or less, place within here *}
        <div class="nav-collapse collapse">
            <div class="nav"><span class="navbar-text team-name">{$team_name|default:""|escape}</span></div>
    
            {if $parameters.display_wallet|default:0 and $team|default:0}
            <div class="nav pull-right wallet">{$lang_str.iquest_txt_wallet_state}<br />{$team.wallet|string_format:"%.2f"} {$lang_str.iquest_txt_coin_symbol}</div>
            {/if}
    
            <div class="nav pull-right clock">{$lang_str.iquest_txt_current_time}<br /><span id="current_time">{$current_time}</span></div>
    
    		<ul class="nav pull-right">
                {if $parameters.overview_url|default:0}
    			<li><a href='{$parameters.overview_url|escape}'>{$lang_str.iquest_l_overview}</a></li>
                {/if}
                {if $parameters.team_rank_url|default:0}
    			<li><a href='{$parameters.team_rank_url|escape}'>{$lang_str.iquest_l_team_rank}</a></li>
                {/if}
                {if $parameters.events_url|default:0}
    			<li><a href='{$parameters.events_url|escape}'>{$lang_str.iquest_l_events}</a></li>
                {/if}
                {if $parameters.giveitup_url|default:0}
    			<li><a href='{$parameters.giveitup_url|escape}'>{$lang_str.iquest_l_give_it_up}</a></li>
                {/if}
    			<li><a href='{$parameters.logout_url|escape}'>{$lang_str.iquest_l_logout}</a></li>
    	  	</ul>
    
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


