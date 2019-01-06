<?php

use yii\helpers\Url;
use kartik\select2\Select2;
?>


<div id="container4" style="max-width:1650px;height:270px"></div>

<script>
    // JS 代码
    var chart = Highcharts.chart('container4', {
        title: {
            text: '<?php echo $data['year']?>'+'年各原因退款率统计报表'
        },
        subtitle: {
            text: '数据来源：'+'http://kefu.yibainetwork.com'
        },
        yAxis: {
            title: {
                text: '退款率'
            }
        },
        xAxis: {
            categories: <?php echo $data['reason']['categories'];?>
        },
        tooltip: {
            shared: true,
            crosshairs: true
        },
        legend: {
            layout: 'vertical',
            align: 'right',
            verticalAlign: 'middle'
        },
        plotOptions: {
            series: {
                label: {
                    connectorAllowed: false
                },
//                pointStart: 2010
            }
        },
        series: <?php echo $data['reason']['series'];?>,
        responsive: {
            rules: [{
                    condition: {
                        maxWidth: 500
                    },
                    chartOptions: {
                        legend: {
                            layout: 'horizontal',
                            align: 'center',
                            verticalAlign: 'bottom'
                        }
                    }
                }]
        }
    });
</script>