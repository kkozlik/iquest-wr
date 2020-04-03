{*
 *	Smarty template displaying heading of pages
 *}

{$next_hint=$next_hint|default:""}
{$next_solution=$next_solution|default:""}

{if !$login_screen|default:false}
<div class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <span class="navbar-brand">
        <img src="{$parameters.logo_url|escape}" alt="{$parameters.game_name|escape}">
    </span>


    <div class="collapse navbar-collapse">
        <ul class="navbar-nav">
            <li class="navbar-text team-name px-2">
                {$team_name|default:""|escape}
            </li>
            {if $show_place|default:false}
                <li class="navbar-text px-2">
                    {$lang_str.iquest_txt_your_place}: {$team_place|default:""|escape}
                </li>
            {/if}
        </ul>
    </div>

    <div class="navbar-text timeouts mx-auto">
        {if $next_hint}<div>{$lang_str.iquest_txt_next_hint}: <span id="hint_countdown">{$next_hint}</span></div>{/if}
        {if $next_solution}<div>{$lang_str.iquest_txt_next_solution}: <span id="solution_countdown">{$next_solution}</span></div>{/if}
    </div>

    <div class="collapse navbar-collapse">

        <ul class="navbar-nav ml-auto">
            {if $parameters.overview_url|default:0}
                <li class="nav-item">
                    <a class="nav-link" href='{$parameters.overview_url|escape}'>{$lang_str.iquest_l_overview}</a>
                </li>
            {/if}
            {if $parameters.team_rank_url|default:0}
                <li class="nav-item">
                    <a class="nav-link" href='{$parameters.team_rank_url|escape}'>{$lang_str.iquest_l_team_rank}</a>
                </li>
            {/if}
            {if $parameters.events_url|default:0}
                <li class="nav-item">
                    <a class="nav-link" href='{$parameters.events_url|escape}'>{$lang_str.iquest_l_events}</a>
                </li>
            {/if}
            {if $parameters.set_position_url|default:0}
                <li class="nav-item">
                    <a class="nav-link" href='{$parameters.set_position_url|escape}'>{$lang_str.iquest_l_set_position}</a>
                </li>
            {/if}
            {if $parameters.giveitup_url|default:0}
                <li class="nav-item">
                    <a class="nav-link" href='{$parameters.giveitup_url|escape}'>{$lang_str.iquest_l_give_it_up}</a>
                </li>
            {/if}
            <li class="nav-item">
                <a class="nav-link" href='{$parameters.logout_url|escape}'>{$lang_str.iquest_l_logout}</a>
            </li>

            <li class="navbar-text clock">
                {$lang_str.iquest_txt_current_time}<br /><span id="current_time">{$current_time}</span>
            </li>

            {if $parameters.display_wallet|default:0 and $team|default:0}
                <li class="navbar-text wallet">
                    {$lang_str.iquest_txt_wallet_state}<br />{$team.wallet|string_format:"%.2f"} {$lang_str.iquest_txt_coin_symbol}
                </li>
            {/if}
            {if $parameters.display_bomb|default:0 and $team|default:0}
                <li class="navbar-text bomb">
                    {if $team.bomb >= 10}
                        <span class="align-middle" title="Máte {$team.bomb|string_format:"%.2f"} bomb">{$team.bomb|floor|string_format:"%2d"}💣</span>
                    {elseif $team.bomb > 0}
                        <span class="fa-stack bomb-icon" title="Máte {$team.bomb|string_format:"%.2f"} bomb">
                            <i class="fas fa-bomb fa-stack-2x"></i>
                            <strong class="bomb-number fa-stack-1x">{$team.bomb|floor|string_format:"%1d"}</strong>
                        </span>
                    {/if}
                </li>
            {/if}
        </ul>
    </div>

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target=".navbar-collapse" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
</div>
<br />
{/if}

<div class="container" id="page_container">

{if $contest_over|default:false}
    <div class="alert alert-danger">
        <div class="row align-items-center">
            <h3 class="col mb-0">{$lang_str.iquest_txt_contest_over}</h3>
            <div class="col-auto">
                <a class="btn btn-danger " href="{$reveal_url|escape}">{$lang_str.iquest_txt_show_goal}</a>
            </div>
        </div>
    </div>
{/if}


{include file='_errors.tpl' errors=$parameters.errors}

{include file='_message.tpl' message=$parameters.message}


