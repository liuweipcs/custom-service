<?php
    use yii\helpers\Url;
    use kartik\select2\Select2;
?>


<div id="container2" style="max-width:800px;height:270px"></div>

<script>
    // JS 代码
    var chart = Highcharts.chart('container2', {
        title: {
            text: '<?php echo $data['year']?>'+'年各账号退款率统计报表'
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
            categories: <?php echo $data['account']['categories'];?>
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
                dataLabels: {
                    enabled: true,
                }
            }
        },
        chart: {
           // marginBottom: 120
//            width: 800
        },

        series: <?php echo $data['account']['series'];?>,
        responsive: {
            rules: [{
//                condition: {
//                    maxWidth: 700,
//                    maxHeight: 150
//                },
                chartOptions: {
                    legend: {
                        layout: 'horizontal',
                        align: 'bottom',
                        verticalAlign: 'bottom'
                    }
                }
            }]
        }
    });
</script>