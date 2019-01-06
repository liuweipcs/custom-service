<style>
    .stepInfo {
        position: relative;
        background: #f2f2f2;
        margin: 40px auto 0 auto;
        width: auto;
    }

    ul, ol {
        list-style: none;
        margin-bottom: -3px;
    }

    .stepInfo li {
        width: auto;
        height: 0.15em;
        background: #bbb;
    }

    .stepIco {
        background: #bbb none repeat;
        border-radius: 1em;
        color: #fff;
        float: left;
        height: 1.4em;
        line-height: 1.5em;
        margin-left: 60px;
        margin-top: -10px;
        padding: 0.03em;
        text-align: center;
        width: 1.4em;
        z-index: 999;
    }

    .stepText {
        color: #666;
        margin-top: 0.2em;
        width: 6em;
        text-align: center;
        margin-left: -2.2em;
    }

    .step {
        background: rgb(79, 182, 64) none repeat scroll 0px 0px;
    }

    .step_text {
        color: rgb(79, 182, 64);
    }
</style>
<div class="panel panel-primary">
    <div class="container" style="height:120px;width:100%;">
        <div class="stepInfo">
            <ul>
                <li></li>
            </ul>

            <?php
            $i = 1;
            if (isset($orderNodelist) && !empty($orderNodelist)) {
                foreach ($orderNodelist as $k => $v) { ?>
                    <?php if (($k == 15 && !empty($ondeList[$k])) || ($k == 30 && !empty($ondeList[$k])) || ($k != 15 && $k != 30)) { ?>
                        <div class="stepIco stepIco<?= $i; ?>"
                             <?php if (isset($ondeList[$k]) && ($k != 15 && $k != 30 && $info['payment_status'] == 1)){ ?>style="background: rgb(79, 182, 64) none repeat scroll 0px 0px;" <?php } ?>  <?php if ($k == 15 || $k == 30 || ($k == 5 && $info['payment_status'] != 1)) { ?> style="background: rgb(255,0,0) none repeat scroll 0px 0px;"<?php } ?>>
                            <?php echo $i; ?>
                            <div class="stepText" <?php if (isset($ondeList[$k]) && ($k != 15 && $k != 30 && $info['payment_status'] == 1)){ ?>style="color: rgb(79, 182, 64);"<?php } ?>
                                <?php if ($k == 15 || $k == 30 || ($k == 5 && $info['payment_status'] != 1)) { ?> style="color: rgb(255,0,0);"<?php } ?>>

                                <?php
                                if ($k == 5 && $info['payment_status'] != 1) {
                                    echo '未付款';
                                } else {
                                    echo $v;
                                }
                                ?>
                            </div>
                            <div class="stepText" <?php if (isset($ondeList[$k]) && ($k != 15 && $k != 30 && $info['payment_status'] == 1)){ ?>style="color: rgb(79, 182, 64);"<?php } ?>
                                <?php if ($k == 15 || $k == 30) { ?> style="color: rgb(255,0,0);"<?php } ?>>
                                <?php
                                if (isset($ondeList[$k]) && ((int)$ondeList[$k]['node_time'] > 0)) {
                                    echo $ondeList[$k]['node_time'];
                                }
                                ?>
                            </div>
                        </div>
                        <?php $i++;
                    } ?>
                <?php }
            } ?>


            <!--            <div class="stepIco stepIco1 step">1<div class="stepText step_text">订单生成</div>
                            <div class="stepText step_text">2018-01-19 16:58:03</div>
                        </div>
                        <div class="stepIco stepIco2" style="background: rgb(79, 182, 64) none repeat scroll 0px 0px;">
                            2                    <div class="stepText" style="color: rgb(79, 182, 64);">

                                付款时间                    </div>
                            <div class="stepText" style="color: rgb(79, 182, 64);">
                                2018-01-19 16:58:04                    </div>
                        </div>
                        <div class="stepIco stepIco3" style="background: rgb(79, 182, 64) none repeat scroll 0px 0px;">
                            3                    <div class="stepText" style="color: rgb(79, 182, 64);">

                                订单检查                    </div>
                            <div class="stepText" style="color: rgb(79, 182, 64);">
                                2018-01-19 17:52:55                    </div>
                        </div>
                        <div class="stepIco stepIco4" style="background: rgb(79, 182, 64) none repeat scroll 0px 0px;">
                            4                    <div class="stepText" style="color: rgb(79, 182, 64);">

                                推送到仓库                    </div>
                            <div class="stepText" style="color: rgb(79, 182, 64);">
                                2018-01-19 17:55:03                    </div>
                        </div>
                        <div class="stepIco stepIco5">
                            5                    <div class="stepText">

                                订单已配货                    </div>
                            <div class="stepText">
                            </div>
                        </div>
                        <div class="stepIco stepIco6">
                            6                    <div class="stepText">

                                上传到物流商                    </div>
                            <div class="stepText">
                            </div>
                        </div>
                        <div class="stepIco stepIco7">
                            7                    <div class="stepText">

                                仓库拉取订单                    </div>
                            <div class="stepText">
                            </div>
                        </div>
                        <div class="stepIco stepIco8">
                            8                    <div class="stepText">

                                仓库扫描打包                    </div>
                            <div class="stepText">
                            </div>
                        </div>
                        <div class="stepIco stepIco9">
                            9                    <div class="stepText">

                                仓库扫描出库                    </div>
                            <div class="stepText">
                            </div>
                        </div>
                        <div class="stepIco stepIco10">
                            10                    <div class="stepText">

                                物流商发货                    </div>
                            <div class="stepText">
                            </div>
                        </div>
                        <div class="stepIco stepIco11">
                            11                    <div class="stepText">

                                客户签收                    </div>
                            <div class="stepText">
                            </div>
                        </div>-->
        </div>
    </div>
</div>