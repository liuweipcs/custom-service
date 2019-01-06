<?php
    use yii\helpers\Url;
    use kartik\select2\Select2;
?>


<div id="container1" style="max-width:800px;height:270px"></div>

<script>
    // JS 代码
    var chart = Highcharts.chart('container1', {
        title: {
            text: '<?php echo $data['year']?>'+'年各平台退款率统计报表'
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
            categories: <?php echo $data['ploatform']['categories'];?>
        },
        tooltip: {
            shared: true,
            crosshairs: true
        },
        legend: {
            layout: 'horizontal',//图列布局  horizontal:水平布局  vertical:垂直布局
            align: 'center',
            verticalAlign: 'bottom'
        },
        plotOptions: {
            series: {
                label: {
                    connectorAllowed: false
                },
//                pointStart: 2010
            }
        },
        series: <?php echo $data['ploatform']['series'];?>,
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