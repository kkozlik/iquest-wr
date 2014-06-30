{* Smarty *}

{include file='_head.tpl'}

{include file="iquest/iquest-functions.tpl"}

<div id="team-ranks" style="width:100%;"></div>
<br>
{include file='_tail.tpl'}

<script type="text/javascript">
$(function () {
        $('#team-ranks').highcharts({
            chart: {
                type: 'spline',
                zoomType: 'x'
            },
            title: {
                text: '{$lang_str.iquest_rank_title|escape:javascript}'
            },
            xAxis: {
                type: 'datetime',
                title: {
                    text: '{$lang_str.iquest_rank_x_axes|escape:javascript}'
                }
            },
            yAxis: {
                title: {
                    text: '{$lang_str.iquest_rank_y_axes|escape:javascript}'
                },
                reversed: true,
                min: 1,
                max: {$ranks|count} 
            },
            tooltip: {
                headerFormat: '{ldelim}point.y{rdelim}. <b>{ldelim}series.name{rdelim}</b><br/>',
                pointFormat: '{ldelim}point.x:%H:%M:%S{rdelim}'
            },

            series: [
            {foreach $ranks as $team_rank}  
                {
                    name: '{$team_rank.name|escape:javascript}',
                    // Define the data points. All series have a dummy year
                    // of 1970/71 in order to be compared on the same x axis. Note
                    // that in JavaScript, months start at 0 for January, 1 for February etc.
                    data: [
                        {foreach $team_rank.data as $rank_data}  
                            [{$rank_data.timestamp * 1000}, {$rank_data.rank}]{if !$rank_data@last},{/if}
                        {/foreach}  
                    ]
                }{if !$team_rank@last},{/if}
            {/foreach}  
            ]
        });
    });    
</script>
