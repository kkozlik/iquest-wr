{* Smarty *}

{include file='_head.tpl'}

{include file="iquest/iquest-functions.tpl"}

<div id="team-ranks" style="width:100%;"></div>

{if $actual_order}
    <div class="row">
    <div class="span6 offset3">
        <h3>{$lang_str.iquest_rank_act_order_title}</h3>
        
        <table class="table table-bordered table-striped table-condensed">
        <thead>
        <tr>
        <th>{$lang_str.iquest_rank_y_axes}</th>
        <th>{$lang_str.iquest_rank_team}</th>
        </tr>
        </thead>
        
        {foreach $actual_order as $team_name => $rank}
        <tr>
        <td>{$rank}</td>
        <td>{$team_name}</td>
        </tr>
        {/foreach}
        </table>
    </div>
    </div>
{/if}


<br>
{include file='_tail.tpl'}

<script type="text/javascript">
    $(function () {
        Highcharts.setOptions({
            global : {
                useUTC : false
            },
            colors: [ "#7cb5ec", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1",  "#9100e1", "#00ff24", "#0000fe"]
        });
        

        Highcharts.stockChart('team-ranks', {
            chart: {
                type: 'spline',
                zoomType: 'x',
                height: 435
            },
            legend: {
                enabled: true
            },
            title: {
                text: '{$lang_str.iquest_rank_title|escape:javascript}'
            },
            rangeSelector:{
                enabled: false
            },
            scrollbar: {
                enabled: true,
                height: 5
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
                max: {$ranks|count},
                endOnTick: false,
                opposite: false,
                showFirstLabel: true,
                showLastLabel: true,
                labels: {
                    align: "right",
                    y:3
                }
            },
            tooltip: {
                headerFormat: '{ldelim}point.y{rdelim}. <b>{ldelim}series.name{rdelim}</b><br/>',
                pointFormat: '{ldelim}point.x:%H:%M:%S{rdelim}',
                shared: false
            },
            navigator:{
                margin:10,
                height:25,
                yAxis:{
                    reversed: true
                }
            },

            series: [
            {foreach $ranks as $team_rank}  
                {
                    name: '{$team_rank.name|escape:javascript}',
                    marker: {
                        enabled: null,
                        radius: 4
                    },
                    // Define the data points. All series have a dummy year
                    // of 1970/71 in order to be compared on the same x axis. Note
                    // that in JavaScript, months start at 0 for January, 1 for February etc.
                    data: [
                        {foreach $team_rank.data as $rank_data}  
                            {
                             {if $rank_data.origin}
                                marker: {
                                    fillColor: '#FFFFFF',
                                    lineColor: '#FF3333',
                                    lineWidth: 2,
                                    symbol:    'circle'
                                },
                             {else}
                                marker: {
                                    enabled: false
                                },
                             {/if}
                                x:{$rank_data.timestamp * 1000}, 
                                y:{$rank_data.rank} 
                            }{if !$rank_data@last},{/if}
                        {/foreach}  
                    ]
                }{if !$team_rank@last},{/if}
            {/foreach}  
            ]
        });

    });    
</script>
