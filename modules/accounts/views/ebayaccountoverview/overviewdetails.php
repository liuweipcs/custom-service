<?php

use app\modules\accounts\models\EbayAccountOverview;

?>
<style>
    p.line {
        line-height: 28px;
        font-size: 15px;
    }

    span.std {
        font-weight: bold;
        font-size: 18px;
        color: #52c41a;
    }

    span.notstd {
        font-weight: bold;
        font-size: 18px;
        color: #f5222d;
    }

    span.glyphicon-arrow-up {
        color: red;
    }

    span.glyphicon-arrow-down {
        color: green;
    }
</style>
<div class="popup-wrapper">
    <div class="popup-body">
        <?php if (!empty($data)) { ?>
            <?php if ($type == 'ltnp') { ?>
                <p class="line">
                    您
                    <?php echo $data['dft_lst_eval_beg_dt']; ?>
                    -
                    <?php echo $data['dft_lst_eval_end_dt']; ?>
                    期间的综合表现为：
                    <?php
                    $ltnpStatus = EbayAccountOverview::getLtnpStatus();
                    $program = array_key_exists($data['program_status_lst_eval'], $ltnpStatus) ? $ltnpStatus[$data['program_status_lst_eval']] : '';
                    ?>
                    <a href="#" class="status" data-status="<?php echo $program; ?>"><?php echo $program; ?></a>
                </p>
                <p class="line">
                    数据更新时间: <?php echo $data['refreshed_date']; ?>
                </p>
                <p class="line">
                    其中，不良交易率表现状态:
                    <?php
                    $lst = array_key_exists($data['status_lst_eval'], $ltnpStatus) ? $ltnpStatus[$data['status_lst_eval']] : '';
                    ?>
                    <a href="#" class="status" data-status="<?php echo $lst; ?>"><?php echo $lst; ?></a>
                </p>
                <table class="table table-bordered" style="table-layout:fixed;">
                    <tr>
                        <td>当前评价(下次评估时间:<?php echo $data['next_review_dt']; ?>)</td>
                        <td>标准值</td>
                        <td>当前值</td>
                        <td>状态</td>
                    </tr>
                    <tr>
                        <td>小于等于10美金12月不良交易率</td>
                        <td><?php echo $data['dft_rt_lt10_12m_th'] * 100; ?>%</td>
                        <td><?php echo $data['dft_rt_lt10_12m_lst_eval'] * 100; ?>%</td>
                        <td>
                            <?php
                            $lt10 = array_key_exists($data['status_lt10_lst_eval'], $ltnpStatus) ? $ltnpStatus[$data['status_lt10_lst_eval']] : '';
                            ?>
                            <?php if ($data['program_status_lst_eval'] == 4) { ?>
                                <a href="#" class="status" data-status="">-</a>
                            <?php } else { ?>
                                <a href="#" class="status" data-status="<?php echo $lt10; ?>"><?php echo $lt10; ?></a>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td>大于10美金12月不良交易率</td>
                        <td><?php echo $data['dft_rt_gt10_12m_th'] * 100; ?>%</td>
                        <td><?php echo $data['dft_rt_gt10_12m_lst_eval'] * 100; ?>%</td>
                        <td>
                            <?php
                            $gt10 = array_key_exists($data['status_gt10_lst_eval'], $ltnpStatus) ? $ltnpStatus[$data['status_gt10_lst_eval']] : '';
                            ?>
                            <?php if ($data['program_status_lst_eval'] == 4) { ?>
                                <a href="#" class="status" data-status="">-</a>
                            <?php } else { ?>
                                <a href="#" class="status" data-status="<?php echo $gt10; ?>"><?php echo $gt10; ?></a>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td>综合12月不良交易率</td>
                        <td><?php echo $data['adj_dft_rt_12m_th'] * 100; ?>%</td>
                        <td><?php echo $data['adj_dft_rt_12m_lst_eval'] * 100; ?>%</td>
                        <td>
                            <?php
                            $adj = array_key_exists($data['status_adj_lst_eval'], $ltnpStatus) ? $ltnpStatus[$data['status_adj_lst_eval']] : '';
                            ?>
                            <?php if ($data['program_status_lst_eval'] == 4) { ?>
                                <a href="#" class="status" data-status="">-</a>
                            <?php } else { ?>
                                <a href="#" class="status" data-status="<?php echo $adj; ?>"><?php echo $adj; ?></a>
                            <?php } ?>
                        </td>
                    </tr>
                </table>
                <p class="line">
                    纠纷表现状态：
                    <?php
                    $snad_lst = array_key_exists($data['snad_status_lst_eval'], $ltnpStatus) ? $ltnpStatus[$data['snad_status_lst_eval']] : '';
                    ?>
                    <a href="#" class="status" data-status="<?php echo $snad_lst; ?>"><?php echo $snad_lst; ?></a>
                </p>
                <p class="line">
                    下次评估时间：<?php echo $data['next_review_dt']; ?>
                    当前表现 (<?php echo $data['dft_wk_eval_beg_dt']; ?> - <?php echo $data['dft_wk_eval_end_dt']; ?>)
                    预期状态为：
                    <?php
                    $snad_wk = array_key_exists($data['snad_status_wk_eval'], $ltnpStatus) ? $ltnpStatus[$data['snad_status_wk_eval']] : '';
                    ?>
                    <a href="#" class="status" data-status="<?php echo $snad_wk; ?>"><?php echo $snad_wk; ?></a>
                </p>
            <?php } else if ($type == 'ship') { ?>
                <p class="line">
                    您 <?php echo $data['1to8']['review_start_date']; ?> - <?php echo $data['1to8']['review_end_date']; ?> 期间的货运表现为
                    <?php
                    $shipStatus = EbayAccountOverview::getShippingStatus();
                    $ship_status = array_key_exists($data['1to8']['result'], $shipStatus) ? $shipStatus[$data['1to8']['result']] : '';
                    ?>
                    <a href="#" class="status" data-status="<?php echo $ship_status; ?>"><?php echo $ship_status; ?></a>
                    ,
                    上周状态:
                    <?php
                    $pre_ship_status = '无';
                    if (!empty($preData['1to8'])) {
                        $pre_ship_status = array_key_exists($preData['1to8']['result'], $shipStatus) ? $shipStatus[$preData['1to8']['result']] : '';
                    }
                    ?>
                    <a href="#" class="status" data-status="<?php echo $pre_ship_status; ?>"><?php echo $pre_ship_status; ?></a>
                </p>
                <p class="line">
                    数据更新时间: <?php echo $data['1to8']['refreshed_date']; ?>
                </p>
                <p class="line">
                    超出标准货运问题交易率 <?php echo rtrim($data['1to8']['glb_shtm_de_rate_pre'], '%'); ?>%
                </p>
                <table class="table table-bordered" style="table-layout:fixed;">
                    <tr>
                        <td>指标名称</td>
                        <td>本周数据</td>
                        <td>上周数据</td>
                    </tr>
                    <tr>
                        <td>北美货运问题交易率</td>
                        <td>
                            <?php
                            $na_shtm_rate_pre = rtrim($data['1to8']['na_shtm_rate_pre'], '%') * 100;
                            echo $na_shtm_rate_pre, '%';

                            if (!empty($preData['1to8'])) {
                                $pre_na_shtm_rate_pre = rtrim($preData['1to8']['na_shtm_rate_pre'], '%') * 100;
                                if ($na_shtm_rate_pre > $pre_na_shtm_rate_pre) {
                                    echo '<span class="glyphicon glyphicon-arrow-up"></span>';
                                } else {
                                    echo '<span class="glyphicon glyphicon-arrow-down"></span>';
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($preData['1to8'])) {
                                echo rtrim($preData['1to8']['na_shtm_rate_pre'], '%') * 100, '%';
                            } else {
                                echo '无';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>英国货运问题交易率</td>
                        <td>
                            <?php
                            $uk_shtm_rate_pre = rtrim($data['1to8']['uk_shtm_rate_pre'], '%') * 100;
                            echo $uk_shtm_rate_pre, '%';

                            if (!empty($preData['1to8'])) {
                                $pre_uk_shtm_rate_pre = rtrim($preData['1to8']['uk_shtm_rate_pre'], '%') * 100;
                                if ($uk_shtm_rate_pre > $pre_uk_shtm_rate_pre) {
                                    echo '<span class="glyphicon glyphicon-arrow-up"></span>';
                                } else {
                                    echo '<span class="glyphicon glyphicon-arrow-down"></span>';
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($preData['1to8'])) {
                                echo rtrim($preData['1to8']['uk_shtm_rate_pre'], '%') * 100, '%';
                            } else {
                                echo '无';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>德国货运问题交易率</td>
                        <td>
                            <?php
                            $de_shtm_rate_pre = rtrim($data['1to8']['de_shtm_rate_pre'], '%') * 100;
                            echo $de_shtm_rate_pre, '%';

                            if (!empty($preData['1to8'])) {
                                $pre_de_shtm_rate_pre = rtrim($preData['1to8']['de_shtm_rate_pre'], '%') * 100;
                                if ($de_shtm_rate_pre > $pre_de_shtm_rate_pre) {
                                    echo '<span class="glyphicon glyphicon-arrow-up"></span>';
                                } else {
                                    echo '<span class="glyphicon glyphicon-arrow-down"></span>';
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($preData['1to8'])) {
                                echo rtrim($preData['1to8']['de_shtm_rate_pre'], '%') * 100, '%';
                            } else {
                                echo '无';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>澳大利亚货运问题交易率</td>
                        <td>
                            <?php
                            $au_shtm_rate_pre = rtrim($data['1to8']['au_shtm_rate_pre'], '%') * 100;
                            echo $au_shtm_rate_pre, '%';

                            if (!empty($preData['1to8'])) {
                                $pre_au_shtm_rate_pre = rtrim($preData['1to8']['au_shtm_rate_pre'], '%') * 100;
                                if ($au_shtm_rate_pre > $pre_au_shtm_rate_pre) {
                                    echo '<span class="glyphicon glyphicon-arrow-up"></span>';
                                } else {
                                    echo '<span class="glyphicon glyphicon-arrow-down"></span>';
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($preData['1to8'])) {
                                echo rtrim($preData['1to8']['au_shtm_rate_pre'], '%') * 100, '%';
                            } else {
                                echo '无';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>其他货运问题交易率</td>
                        <td>
                            <?php
                            $oth_shtm_rate_pre = rtrim($data['1to8']['oth_shtm_rate_pre'], '%') * 100;
                            echo $oth_shtm_rate_pre, '%';

                            if (!empty($preData['1to8'])) {
                                $pre_oth_shtm_rate_pre = rtrim($preData['1to8']['oth_shtm_rate_pre'], '%') * 100;
                                if ($oth_shtm_rate_pre > $pre_oth_shtm_rate_pre) {
                                    echo '<span class="glyphicon glyphicon-arrow-up"></span>';
                                } else {
                                    echo '<span class="glyphicon glyphicon-arrow-down"></span>';
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($preData['1to8'])) {
                                echo rtrim($preData['1to8']['oth_shtm_rate_pre'], '%') * 100, '%';
                            } else {
                                echo '无';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
                <p class="line">
                    您 <?php echo $data['5to12']['review_start_date']; ?> - <?php echo $data['5to12']['review_end_date']; ?> 期间的货运表现为
                    <?php
                    $ship_status = array_key_exists($data['5to12']['result'], $shipStatus) ? $shipStatus[$data['5to12']['result']] : '';
                    ?>
                    <a href="#" class="status" data-status="<?php echo $ship_status; ?>"><?php echo $ship_status; ?></a>
                    ,
                    上周状态:
                    <?php
                    $pre_ship_status = '无';
                    if (!empty($preData['5to12'])) {
                        $pre_ship_status = array_key_exists($preData['5to12']['result'], $shipStatus) ? $shipStatus[$preData['5to12']['result']] : '';
                    }
                    ?>
                    <a href="#" class="status" data-status="<?php echo $pre_ship_status; ?>"><?php echo $pre_ship_status; ?></a>
                </p>
                <p class="line">
                    数据更新时间: <?php echo $data['5to12']['refreshed_date']; ?>
                </p>
                <p class="line">
                    超出标准货运问题交易率 <?php echo rtrim($data['5to12']['glb_shtm_de_rate_pre'], '%'); ?>%
                </p>
                <table class="table table-bordered" style="table-layout:fixed;">
                    <tr>
                        <td>指标名称</td>
                        <td>本周数据</td>
                        <td>上周数据</td>
                    </tr>
                    <tr>
                        <td>北美货运问题交易率</td>
                        <td>
                            <?php
                            $na_shtm_rate_pre = rtrim($data['5to12']['na_shtm_rate_pre'], '%') * 100;
                            echo $na_shtm_rate_pre, '%';

                            if (!empty($preData['5to12'])) {
                                $pre_na_shtm_rate_pre = rtrim($preData['5to12']['na_shtm_rate_pre'], '%') * 100;
                                if ($na_shtm_rate_pre > $pre_na_shtm_rate_pre) {
                                    echo '<span class="glyphicon glyphicon-arrow-up"></span>';
                                } else {
                                    echo '<span class="glyphicon glyphicon-arrow-down"></span>';
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($preData['5to12'])) {
                                echo rtrim($preData['5to12']['na_shtm_rate_pre'], '%') * 100, '%';
                            } else {
                                echo '无';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>英国货运问题交易率</td>
                        <td>
                            <?php
                            $uk_shtm_rate_pre = rtrim($data['5to12']['uk_shtm_rate_pre'], '%') * 100;
                            echo $uk_shtm_rate_pre, '%';

                            if (!empty($preData['5to12'])) {
                                $pre_uk_shtm_rate_pre = rtrim($preData['5to12']['uk_shtm_rate_pre'], '%') * 100;
                                if ($uk_shtm_rate_pre > $pre_uk_shtm_rate_pre) {
                                    echo '<span class="glyphicon glyphicon-arrow-up"></span>';
                                } else {
                                    echo '<span class="glyphicon glyphicon-arrow-down"></span>';
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($preData['5to12'])) {
                                echo rtrim($preData['5to12']['uk_shtm_rate_pre'], '%') * 100, '%';
                            } else {
                                echo '无';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>德国货运问题交易率</td>
                        <td>
                            <?php
                            $de_shtm_rate_pre = rtrim($data['5to12']['de_shtm_rate_pre'], '%') * 100;
                            echo $de_shtm_rate_pre, '%';

                            if (!empty($preData['5to12'])) {
                                $pre_de_shtm_rate_pre = rtrim($preData['5to12']['de_shtm_rate_pre'], '%') * 100;
                                if ($de_shtm_rate_pre > $pre_de_shtm_rate_pre) {
                                    echo '<span class="glyphicon glyphicon-arrow-up"></span>';
                                } else {
                                    echo '<span class="glyphicon glyphicon-arrow-down"></span>';
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($preData['5to12'])) {
                                echo rtrim($preData['5to12']['de_shtm_rate_pre'], '%') * 100, '%';
                            } else {
                                echo '无';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>澳大利亚货运问题交易率</td>
                        <td>
                            <?php
                            $au_shtm_rate_pre = rtrim($data['5to12']['au_shtm_rate_pre'], '%') * 100;
                            echo $au_shtm_rate_pre, '%';

                            if (!empty($preData['5to12'])) {
                                $pre_au_shtm_rate_pre = rtrim($preData['5to12']['au_shtm_rate_pre'], '%') * 100;
                                if ($au_shtm_rate_pre > $pre_au_shtm_rate_pre) {
                                    echo '<span class="glyphicon glyphicon-arrow-up"></span>';
                                } else {
                                    echo '<span class="glyphicon glyphicon-arrow-down"></span>';
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($preData['5to12'])) {
                                echo rtrim($preData['5to12']['au_shtm_rate_pre'], '%') * 100, '%';
                            } else {
                                echo '无';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>其他货运问题交易率</td>
                        <td>
                            <?php
                            $oth_shtm_rate_pre = rtrim($data['5to12']['oth_shtm_rate_pre'], '%') * 100;
                            echo $oth_shtm_rate_pre, '%';

                            if (!empty($preData['5to12'])) {
                                $pre_oth_shtm_rate_pre = rtrim($preData['5to12']['oth_shtm_rate_pre'], '%') * 100;
                                if ($oth_shtm_rate_pre > $pre_oth_shtm_rate_pre) {
                                    echo '<span class="glyphicon glyphicon-arrow-up"></span>';
                                } else {
                                    echo '<span class="glyphicon glyphicon-arrow-down"></span>';
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($preData['5to12'])) {
                                echo rtrim($preData['5to12']['oth_shtm_rate_pre'], '%') * 100, '%';
                            } else {
                                echo '无';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            <?php } else if ($type == 'tci') { ?>
                <p class="line">
                    您 <?php echo $data['review_start_dt']; ?> - <?php echo $data['review_end_dt']; ?> 期间的非货运表现为
                    <?php
                    $nonShipStatus = EbayAccountOverview::getNonShippingStatus();
                    $nonship_status = array_key_exists($data['result'], $nonShipStatus) ? $nonShipStatus[$data['result']] : '';
                    ?>
                    <a href="#" class="status" data-status="<?php echo $nonship_status; ?>"><?php echo $nonship_status; ?></a>
                    ,
                    上周状态:
                    <?php
                    $pre_nonship_status = '无';
                    if (!empty($preData)) {
                        $pre_nonship_status = array_key_exists($preData['result'], $nonShipStatus) ? $nonShipStatus[$preData['result']] : '';
                    }
                    ?>
                    <a href="#" class="status" data-status="<?php echo $pre_nonship_status; ?>"><?php echo $pre_nonship_status; ?></a>
                </p>
                <p class="line">
                    数据更新时间: <?php echo $data['refreshed_date']; ?>
                </p>
                <p class="line">
                    超出标准非货运问题交易率 <?php echo rtrim($data['ns_defect_adj_rt8wk'], '%'); ?>%
                </p>
                <table class="table table-bordered" style="table-layout:fixed;">
                    <tr>
                        <td>指标名称</td>
                        <td>本周数据</td>
                        <td>上周数据</td>
                    </tr>
                    <tr>
                        <td>北美非货运问题交易率</td>
                        <td>
                            <?php
                            $na_ns_defect_adj_rt8wk = rtrim($data['na_ns_defect_adj_rt8wk'], '%');
                            echo $na_ns_defect_adj_rt8wk, '%';

                            if (!empty($preData)) {
                                $pre_na_ns_defect_adj_rt8wk = rtrim($preData['na_ns_defect_adj_rt8wk'], '%');
                                if ($na_ns_defect_adj_rt8wk > $pre_na_ns_defect_adj_rt8wk) {
                                    echo '<span class="glyphicon glyphicon-arrow-up"></span>';
                                } else {
                                    echo '<span class="glyphicon glyphicon-arrow-down"></span>';
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($preData)) {
                                echo rtrim($preData['na_ns_defect_adj_rt8wk'], '%'), '%';
                            } else {
                                echo '无';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>英国非货运问题交易率</td>
                        <td>
                            <?php
                            $uk_ns_defect_adj_rt8wk = rtrim($data['uk_ns_defect_adj_rt8wk'], '%');
                            echo $uk_ns_defect_adj_rt8wk, '%';

                            if (!empty($preData)) {
                                $pre_uk_ns_defect_adj_rt8wk = rtrim($preData['uk_ns_defect_adj_rt8wk'], '%');
                                if ($uk_ns_defect_adj_rt8wk > $pre_uk_ns_defect_adj_rt8wk) {
                                    echo '<span class="glyphicon glyphicon-arrow-up"></span>';
                                } else {
                                    echo '<span class="glyphicon glyphicon-arrow-down"></span>';
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($preData)) {
                                echo rtrim($preData['uk_ns_defect_adj_rt8wk'], '%'), '%';
                            } else {
                                echo '无';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>德国非货运问题交易率</td>
                        <td>
                            <?php
                            $de_ns_defect_adj_rt8wk = rtrim($data['de_ns_defect_adj_rt8wk'], '%');
                            echo $de_ns_defect_adj_rt8wk, '%';

                            if (!empty($preData)) {
                                $pre_de_ns_defect_adj_rt8wk = rtrim($preData['de_ns_defect_adj_rt8wk'], '%');
                                if ($de_ns_defect_adj_rt8wk > $pre_de_ns_defect_adj_rt8wk) {
                                    echo '<span class="glyphicon glyphicon-arrow-up"></span>';
                                } else {
                                    echo '<span class="glyphicon glyphicon-arrow-down"></span>';
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($preData)) {
                                echo rtrim($preData['de_ns_defect_adj_rt8wk'], '%'), '%';
                            } else {
                                echo '无';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>澳大利亚非货运问题交易率</td>
                        <td>
                            <?php
                            $au_ns_defect_adj_rt8wk = rtrim($data['au_ns_defect_adj_rt8wk'], '%');
                            echo $au_ns_defect_adj_rt8wk, '%';

                            if (!empty($preData)) {
                                $pre_au_ns_defect_adj_rt8wk = rtrim($preData['au_ns_defect_adj_rt8wk'], '%');
                                if ($au_ns_defect_adj_rt8wk > $pre_au_ns_defect_adj_rt8wk) {
                                    echo '<span class="glyphicon glyphicon-arrow-up"></span>';
                                } else {
                                    echo '<span class="glyphicon glyphicon-arrow-down"></span>';
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($preData)) {
                                echo rtrim($preData['au_ns_defect_adj_rt8wk'], '%'), '%';
                            } else {
                                echo '无';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>其他非货运问题交易率</td>
                        <td>
                            <?php
                            $gl_ns_defect_adj_rt8wk = rtrim($data['gl_ns_defect_adj_rt8wk'], '%');
                            echo $gl_ns_defect_adj_rt8wk, '%';

                            if (!empty($preData)) {
                                $pre_gl_ns_defect_adj_rt8wk = rtrim($preData['gl_ns_defect_adj_rt8wk'], '%');
                                if ($gl_ns_defect_adj_rt8wk > $pre_gl_ns_defect_adj_rt8wk) {
                                    echo '<span class="glyphicon glyphicon-arrow-up"></span>';
                                } else {
                                    echo '<span class="glyphicon glyphicon-arrow-down"></span>';
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if (!empty($preData)) {
                                echo rtrim($preData['gl_ns_defect_adj_rt8wk'], '%'), '%';
                            } else {
                                echo '无';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            <?php } else if ($type == 'eds_shipping_policy') { ?>
                <p class="line">
                    您 <?php echo $data['epacket']['review_start_date'] ?> - <?php echo $data['epacket']['review_end_date']; ?>期间：
                </p>
                <p class="line">
                    美国>$5交易全程跟踪物流的使用状态为
                    <?php
                    $epacketShippingStatus = EbayAccountOverview::getEpacketShippingStatus();
                    if (isset($data['epacket']['e_packet_status'])) {
                        $epacket_status = array_key_exists($data['epacket']['e_packet_status'], $epacketShippingStatus) ? $epacketShippingStatus[$data['epacket']['e_packet_status']] : '';
                    } else {
                        $epacket_status = '-';
                    }
                    ?>
                    <a href="#" class="status" data-status="<?php echo $epacket_status; ?>"><?php echo $epacket_status; ?></a>

                    , 数据更新时间: <?php echo $data['epacket']['refreshed_date']; ?>
                </p>
                <p class="line">
                    >$5交易中使用ePacket+带有效追踪物流的比例 <?php echo isset($data['epacket']['adoption']) ? rtrim($data['epacket']['adoption'], '%') : '-'; ?>%，标准是高于 <?php echo isset($data['epacket']['standard_value']) ? rtrim($data['epacket']['standard_value'], '%') : '-'; ?>%
                </p>
                <table class="table table-bordered">
                    <tr>
                        <td>评估总交易数</td>
                        <td><?php echo isset($data['epacket']['evaluated_tnx_cnt']) ? $data['epacket']['evaluated_tnx_cnt'] : '-'; ?> 笔</td>
                        <td>使用全程跟踪物流且揽收扫描满足时效要求的比例</td>
                        <td><?php echo isset($data['epacket']['adoption']) ? rtrim($data['epacket']['adoption'], '%') : '-'; ?>%</td>
                    </tr>
                    <tr>
                        <td>其中：跨境发货占</td>
                        <td><?php echo isset($data['epacket']['cbt_tnx_cnt']) ? $data['epacket']['cbt_tnx_cnt'] : '-'; ?> 笔</td>
                        <td>使用ePacket+且揽收扫描满足时效要求的比例</td>
                        <td><?php echo isset($data['epacket']['cbt_adoption']) ? rtrim($data['epacket']['cbt_adoption'], '%') : '-'; ?>%</td>
                    </tr>
                    <tr>
                        <td>其中：海外仓发货占</td>
                        <td><?php echo isset($data['epacket']['wh_tnx_cnt']) ? $data['epacket']['wh_tnx_cnt'] : '-'; ?> 笔</td>
                        <td>使用带有效追踪物流且揽收扫描满足时效要求的比例</td>
                        <td><?php echo isset($data['epacket']['wh_adoption']) ? rtrim($data['epacket']['wh_adoption'], '%') : '-'; ?>%</td>
                    </tr>
                </table>
                <p class="line">
                    小于5美金及其他25个主要国家的物流使用合规状态为
                    <?php
                    $edshippingStatus = EbayAccountOverview::getEdshippingStatus();
                    if (isset($data['eds']['eds_status'])) {
                        $eds_status = array_key_exists($data['eds']['eds_status'], $edshippingStatus) ? $edshippingStatus[$data['eds']['eds_status']] : '';
                    } else {
                        $eds_status = '-';
                    }
                    ?>
                    <a href="#" class="status" data-status="<?php echo $eds_status; ?>"><?php echo $eds_status; ?></a>
                    标准是
                    <?php echo isset($data['eds']['standard_value']) ? rtrim($data['eds']['standard_value'], '%') : '-'; ?>%

                    , 数据更新时间: <?php echo $data['eds']['refreshed_date']; ?>
                </p>
                <table class="table table-bordered">
                    <tr>
                        <td>评估总交易数</td>
                        <td><?php echo isset($data['eds']['add_trans_cnt']) ? $data['eds']['add_trans_cnt'] : '-'; ?> 笔</td>
                        <td>物流使用合规比例</td>
                        <td><?php echo isset($data['eds']['eds_comply_rate']) ? rtrim($data['eds']['eds_comply_rate'], '%') : '-'; ?>%</td>
                    </tr>
                    <tr>
                        <td>其中：买家选择使用标准型及以上物流占</td>
                        <td><?php echo isset($data['eds']['add_buyer_std_trans_cnt']) ? $data['eds']['add_buyer_std_trans_cnt'] : '-'; ?> 笔</td>
                        <td>使用全程追踪物流比例</td>
                        <td><?php echo isset($data['eds']['eds_std_comply_rate']) ? rtrim($data['eds']['eds_std_comply_rate'], '%') : '-'; ?>%</td>
                    </tr>
                    <tr>
                        <td>其中：买家选择使用经济型物流占</td>
                        <td><?php echo isset($data['eds']['add_buyer_econ_trans_cnt']) ? $data['eds']['add_buyer_econ_trans_cnt'] : '-'; ?> 笔</td>
                        <td>使用至少含揽收信息或全程跟踪物流比例</td>
                        <td><?php echo isset($data['eds']['eds_econ_comply_rate']) ? rtrim($data['eds']['eds_econ_comply_rate'], '%') : '-'; ?>%</td>
                    </tr>
                </table>
                <p class="line">
                    SpeedPAK物流管理方案及其他符合政策要求的物流服务使用状态为
                    <?php
                    $speedPakListStatus = EbayAccountOverview::getSpeedPakListStatus();
                    if (isset($data['speedpakList']['account_status'])) {
                        $speedPakList_status = array_key_exists($data['speedpakList']['account_status'], $speedPakListStatus) ? $speedPakListStatus[$data['speedpakList']['account_status']] : '';
                    } else {
                        $speedPakList_status = '-';
                    }
                    ?>
                    <a href="#" class="status" data-status="<?php echo $speedPakList_status; ?>"><?php echo $speedPakList_status; ?></a>

                    , 数据更新时间：<?php echo $data['speedpakList']['create_pst']; ?>
                </p>
                <table class="table table-bordered">
                    <tr>
                        <td></td>
                        <td>被评估交易数</td>
                        <td>SpeedPAK+使用比例</td>
                        <td>最低要求</td>
                    </tr>
                    <tr>
                        <td>美国>$5直邮交易</td>
                        <td><?php echo $data['speedpakList']['us_trans']; ?> 笔</td>
                        <td>
                            <?php
                            if (empty($data['speedpakList']['us_color'])) {
                                echo '<span style="color:green;">' . rtrim($data['speedpakList']['us_adoption'], '%') * 100 . '%</span>';
                            } else {
                                echo '<span style="color:red;">' . rtrim($data['speedpakList']['us_adoption'], '%') * 100 . '%</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo '>=' . rtrim($data['speedpakList']['us_requirement'], '%') * 100 . '%'; ?></td>
                    </tr>
                    <tr>
                        <td>英国>￡5直邮交易</td>
                        <td><?php echo $data['speedpakList']['uk_trans']; ?> 笔</td>
                        <td>
                            <?php
                            if (empty($data['speedpakList']['uk_color'])) {
                                echo '<span style="color:green;">' . rtrim($data['speedpakList']['uk_adoption'], '%') * 100 . '%</span>';
                            } else {
                                echo '<span style="color:red;">' . rtrim($data['speedpakList']['uk_adoption'], '%') * 100 . '%</span>';
                            }

                            ?>
                        </td>
                        <td><?php echo '>=' . rtrim($data['speedpakList']['uk_requirement'], '%') * 100 . '%'; ?></td>
                    </tr>
                    <tr>
                        <td>德国>€5直邮交易</td>
                        <td><?php echo $data['speedpakList']['de_trans']; ?> 笔</td>
                        <td>
                            <?php
                            if (empty($data['speedpakList']['de_color'])) {
                                echo '<span style="color:green;">' . rtrim($data['speedpakList']['de_adoption'], '%') * 100 . '%</span>';
                            } else {
                                echo '<span style="color:red;">' . rtrim($data['speedpakList']['de_adoption'], '%') * 100 . '%</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo '>=' . rtrim($data['speedpakList']['de_requirement'], '%') * 100 . '%'; ?></td>
                    </tr>
                </table>
                <p class="line">
                    对应时间：<?php echo $data['speedpakList']['start_date']; ?> - <?php echo $data['speedpakList']['end_date']; ?>
                    下次评估：<?php echo $data['speedpakList']['next_evaluation_date']; ?>
                </p>
                <p class="line">
                    卖家设置SpeedPAK物流选项与实际使用物流不符表现为
                    <?php
                    $speedPakMisuseStatus = EbayAccountOverview::getSpeedPakMisuseStatus();
                    if (isset($data['speedpakMisuse']['account_status'])) {
                        $speedPakMisuse_status = array_key_exists($data['speedpakMisuse']['account_status'], $speedPakMisuseStatus) ? $speedPakMisuseStatus[$data['speedpakMisuse']['account_status']] : '';
                    } else {
                        $speedPakMisuse_status = '-';
                    }
                    ?>
                    <a href="#" class="status" data-status="<?php echo $speedPakMisuse_status; ?>"><?php echo $speedPakMisuse_status; ?></a>

                    , 数据更新时间：<?php echo $data['speedpakMisuse']['create_pst']; ?>
                </p>
                <table class="table table-bordered">
                    <tr>
                        <td></td>
                        <td>被评估交易数</td>
                        <td>合规率</td>
                        <td>最低要求</td>
                    </tr>
                    <tr>
                        <td>卖家设置加快型SpeedPAK物流选项</td>
                        <td><?php echo $data['speedpakMisuse']['expedited_trans']; ?> 笔</td>
                        <td>
                            <?php
                            if (empty($data['speedpakMisuse']['expedited_color'])) {
                                echo '<span style="color:green;">' . rtrim($data['speedpakMisuse']['expedited_comply_rate'], '%') * 100 . '%</span>';
                            } else {
                                echo '<span style="color:red;">' . rtrim($data['speedpakMisuse']['expedited_comply_rate'], '%') * 100 . '%</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo '>=' . rtrim($data['speedpakMisuse']['expedited_required_rate'], '%') * 100 . '%'; ?></td>
                    </tr>
                    <tr>
                        <td>卖家设置标准型SpeedPAK物流选项</td>
                        <td><?php echo $data['speedpakMisuse']['standard_trans']; ?> 笔</td>
                        <td>
                            <?php
                            if (empty($data['speedpakMisuse']['standard_color'])) {
                                echo '<span style="color:green;">' . rtrim($data['speedpakMisuse']['standard_comply_rate'], '%') * 100 . '%</span>';
                            } else {
                                echo '<span style="color:red;">' . rtrim($data['speedpakMisuse']['standard_comply_rate'], '%') * 100 . '%</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo '>=' . rtrim($data['speedpakMisuse']['standard_required_rate'], '%') * 100 . '%'; ?></td>
                    </tr>
                    <tr>
                        <td>卖家设置经济型SpeedPAK物流选项</td>
                        <td><?php echo $data['speedpakMisuse']['economy_trans']; ?> 笔</td>
                        <td>
                            <?php
                            if (empty($data['speedpakMisuse']['economy_color'])) {
                                echo '<span style="color:green;">' . rtrim($data['speedpakMisuse']['economy_comply_rate'], '%') * 100 . '%</span>';
                            } else {
                                echo '<span style="color:red;">' . rtrim($data['speedpakMisuse']['economy_comply_rate'], '%') * 100 . '%</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo '>=' . rtrim($data['speedpakMisuse']['economy_required_rate'], '%') * 100 . '%'; ?></td>
                    </tr>
                </table>
                <p class="line">
                    另外，您还有 <?php echo $data['speedpakMisuse']['speedpak_trans']; ?> 笔交易已经使用SpeedPAK服务，但并未设置SpeedPAK物流选项，请做相应修改
                </p>
                <p class="line">
                    对应时间：<?php echo $data['speedpakMisuse']['start_date']; ?> - <?php echo $data['speedpakMisuse']['end_date']; ?>
                    下次评估：<?php echo $data['speedpakMisuse']['next_evaluation_date']; ?>
                </p>
            <?php } else if ($type == 'sd_warehouse') { ?>
                <p class="line">
                    您 <?php echo $data['review_start_date']; ?> - <?php echo $data['review_end_date']; ?>期间的海外仓表现状态为
                    <?php
                    $sdWareHouseStatus = EbayAccountOverview::getWareHouseStatus();
                    $sd_status = array_key_exists($data['warehouse_status'], $sdWareHouseStatus) ? $sdWareHouseStatus[$data['warehouse_status']] : '';
                    ?>
                    <a href="#" class="status" data-status="<?php echo $sd_status; ?>"><?php echo $sd_status; ?></a>
                </p>
                <p class="line">
                    数据更新时间: <?php echo $data['refreshed_date']; ?>
                </p>
                <table class="table table-bordered table-striped">
                    <tr>
                        <td></td>
                        <td colspan="2">物流不良交易比例</td>
                        <td colspan="2">非当地发货比例</td>
                    </tr>
                    <tr>
                        <td>海外仓</td>
                        <td>标准值</td>
                        <td>当前值</td>
                        <td>标准值</td>
                        <td>当前值</td>
                    </tr>
                    <tr>
                        <td>美国</td>
                        <td><?php echo rtrim($data['us_ship_defect_sd'], '%') * 100; ?>%</td>
                        <td><?php echo rtrim($data['us_wh_shipping_defect_rate'], '%') * 100; ?>%</td>
                        <td><?php echo rtrim($data['us_cbt_sd'], '%') * 100; ?>%</td>
                        <td><?php echo rtrim($data['us_wh_cbt_trans_rate'], '%') * 100; ?>%</td>
                    </tr>
                    <tr>
                        <td>英国</td>
                        <td><?php echo rtrim($data['uk_ship_defect_sd'], '%') * 100; ?>%</td>
                        <td><?php echo rtrim($data['uk_wh_shipping_defect_rate'], '%') * 100; ?>%</td>
                        <td><?php echo rtrim($data['uk_cbt_sd'], '%') * 100; ?>%</td>
                        <td><?php echo rtrim($data['uk_wh_cbt_trans_rate'], '%') * 100; ?>%</td>
                    </tr>
                    <tr>
                        <td>德国</td>
                        <td><?php echo rtrim($data['de_ship_defect_sd'], '%') * 100; ?>%</td>
                        <td><?php echo rtrim($data['de_wh_shipping_defect_rate'], '%') * 100; ?>%</td>
                        <td><?php echo rtrim($data['de_cbt_sd'], '%') * 100; ?>%</td>
                        <td><?php echo rtrim($data['de_wh_cbt_trans_rate'], '%') * 100; ?>%</td>
                    </tr>
                    <tr>
                        <td>澳大利亚</td>
                        <td><?php echo rtrim($data['au_ship_defect_sd'], '%') * 100; ?>%</td>
                        <td><?php echo rtrim($data['au_wh_shipping_defect_rate'], '%') * 100; ?>%</td>
                        <td><?php echo rtrim($data['au_cbt_sd'], '%') * 100; ?>%</td>
                        <td><?php echo rtrim($data['au_wh_cbt_trans_rate'], '%') * 100; ?>%</td>
                    </tr>
                    <tr>
                        <td>其他海外仓</td>
                        <td><?php echo rtrim($data['other_ship_defect_sd'], '%') * 100; ?>%</td>
                        <td><?php echo rtrim($data['other_wh_shipping_defect_rate'], '%') * 100; ?>%</td>
                        <td><?php echo rtrim($data['other_cbt_sd'], '%') * 100; ?>%</td>
                        <td><?php echo rtrim($data['other_wh_cbt_trans_rate'], '%') * 100; ?>%</td>
                    </tr>
                </table>
                <p class="line">
                    对应时间：<?php echo $data['review_start_date']; ?> ~ <?php echo $data['review_end_date']; ?> 下次评估：<?php echo $data['next_evaluation_date']; ?>
                </p>
            <?php } else if ($type == 'pgc_tracking') { ?>
                <p class="line">
                    数据更新时间 <?php echo $data['refreshed_date']; ?>, 期间的商业追踪计划表现状态为
                    <?php
                    $pgcTrackingStatus = EbayAccountOverview::getPgcTrackingStatus();
                    $pgc_status = array_key_exists($data['pgc_status'], $pgcTrackingStatus) ? $pgcTrackingStatus[$data['pgc_status']] : '';
                    ?>
                    <a href="#" class="status" data-status="<?php echo $pgc_status; ?>"><?php echo $pgc_status; ?></a>
                </p>
                <table class="table table-bordered table-striped" style="table-layout:fixed;">
                    <tr>
                        <td>按照您提交的商业计划，我们做了以下审核</td>
                        <td>标准值</td>
                        <td>状态(O是达标，X就是不达标)</td>
                    </tr>
                    <tr>
                        <td>账号累计营业额(美金)</td>
                        <td><?php echo $data['account_cmltv_std']; ?></td>
                        <td>
                            <?php if (empty($data['account_cmltv'])) { ?>
                                <span class="std">O</span>
                            <?php } else { ?>
                                <span class="notstd">X</span>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td>是否已被冻结</td>
                        <td><?php echo $data['suspension_std']; ?></td>
                        <td>
                            <?php if (empty($data['suspension_sts'])) { ?>
                                <span class="std">O</span>
                            <?php } else { ?>
                                <span class="notstd">X</span>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td>重复刊登违规</td>
                        <td><?php echo $data['duplicate_std']; ?></td>
                        <td>
                            <?php if (empty($data['duplicate_sts'])) { ?>
                                <span class="std">O</span>
                            <?php } else { ?>
                                <span class="notstd">X</span>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td>商业计划完成总体表现</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>&nbsp;&nbsp;&nbsp;&nbsp;目标站点完成情况</td>
                        <td><?php echo $data['cridr_as_std']; ?></td>
                        <td>
                            <?php if (empty($data['cridr_as_promised'])) { ?>
                                <span class="std">O</span>
                            <?php } else { ?>
                                <span class="notstd">X</span>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;&nbsp;&nbsp;&nbsp;目标品类完成情况</td>
                        <td><?php echo $data['cat_as_std']; ?></td>
                        <td>
                            <?php if (empty($data['cat_as_promised'])) { ?>
                                <span class="std">O</span>
                            <?php } else { ?>
                                <span class="notstd">X</span>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;&nbsp;&nbsp;&nbsp;目标平均单价完成情况</td>
                        <td><?php echo $data['asp_as_std']; ?></td>
                        <td>
                            <?php if (empty($data['asp_as_promised'])) { ?>
                                <span class="std">O</span>
                            <?php } else { ?>
                                <span class="notstd">X</span>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td>账号不良交易率</td>
                        <td><?php echo $data['dft_std']; ?></td>
                        <td>
                            <?php if (empty($data['dft_sts'])) { ?>
                                <span class="std">O</span>
                            <?php } else { ?>
                                <span class="notstd">X</span>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td>海外仓使用率</td>
                        <td><?php echo $data['wh_std']; ?></td>
                        <td>
                            <?php if (empty($data['wh_sts'])) { ?>
                                <span class="std">O</span>
                            <?php } else { ?>
                                <span class="notstd">X</span>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td>平均月销售额</td>
                        <td><?php echo $data['avg_gmv_std']; ?></td>
                        <td>
                            <?php if (empty($data['avg_gmv_sts'])) { ?>
                                <span class="std">O</span>
                            <?php } else { ?>
                                <span class="notstd">X</span>
                            <?php } ?>
                        </td>
                    </tr>
                </table>
                <table class="table table-bordered table-striped" style="table-layout:fixed;">
                    <tr>
                        <td>主要销售国家</td>
                        <td><?php echo $data['primary_corridor']; ?></td>
                    </tr>
                    <tr>
                        <td>次要销售国家</td>
                        <td><?php echo $data['secondary_corridor']; ?></td>
                    </tr>
                    <tr>
                        <td>主要产品所属一级分类</td>
                        <td><?php echo $data['primary_vertical']; ?></td>
                    </tr>
                    <tr>
                        <td>主要产品所属二级分类</td>
                        <td><?php echo $data['primary_category']; ?></td>
                    </tr>
                    <tr>
                        <td>次要产品所属一级分类</td>
                        <td><?php echo $data['secondary_vertical']; ?></td>
                    </tr>
                    <tr>
                        <td>次要产品所属二级分类</td>
                        <td><?php echo $data['secondary_category']; ?></td>
                    </tr>
                    <tr>
                        <td>申请账户主营产品预估平均单价(美金)</td>
                        <td><?php echo $data['estimated_item_asp_usd']; ?></td>
                    </tr>
                    <tr>
                        <td>仓储所在地</td>
                        <td><?php echo $data['location_of_warehouse']; ?></td>
                    </tr>
                    <tr>
                        <td>海外仓存货销量占比</td>
                        <td><?php echo $data['warehouse_adoption_rate']; ?></td>
                    </tr>
                </table>
            <?php } else if ($type == 'qclist') { ?>
                <p class="line">
                    数据更新时间 <?php echo $data[0]['refreshed_date']; ?>
                </p>
                <table class="table table-bordered table-striped" style="table-layout:fixed;">
                    <tr>
                        <td>itemId</td>
                        <td>到期时间</td>
                        <td>下线时间</td>
                        <td>刊登状态</td>
                        <td>交易额</td>
                        <td>交易量</td>
                        <td>问题交易量</td>
                        <td>比率(问题/总交易量)</td>
                    </tr>
                    <?php if (!empty($data)) { ?>
                        <?php foreach ($data as $item) { ?>
                            <tr>
                                <td><a href="http://www.ebay.com/itm/<?php echo $item['item_id']; ?>" target="_blank"><?php echo $item['item_id']; ?></a></td>
                                <td><?php echo $item['rm_dead_dt']; ?></td>
                                <td><?php echo $item['auct_end_dt']; ?></td>
                                <td>
                                    <?php
                                        if ($item['listing_status'] == 'Active') {
                                            echo '上线';
                                        } else {
                                            echo '下线';
                                        }
                                    ?>
                                </td>
                                <td><?php echo $item['gmv_usd']; ?></td>
                                <td><?php echo $item['total_trans']; ?></td>
                                <td><?php echo $item['bbe_trans']; ?></td>
                                <td><?php echo round($item['bbe_trans'] / $item['total_trans'], 2) * 100; ?>%</td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </table>
            <?php } else if ($type == 'bad_trade_rate') { ?>
                <p class="line">
                    您 <?php echo $data['metric_lookback_startdate']; ?> - <?php echo $data['metric_lookback_enddate']; ?>期间：
                </p>
                <p class="line">
                    评价日期: <?php echo $data['evaluation_date']; ?>
                </p>
                <?php
                $metricValue = json_decode($data['metric_value'], true);
                $upperBound = json_decode($data['metric_threshold_upper_bound'], true);
                ?>
                <p class="line">
                    共产生 <?php echo $metricValue['denominator']; ?>项交易，
                    不良交易 <?php echo $metricValue['numerator']; ?>项，
                    不良交易率为 <?php echo $metricValue['value']; ?>%，
                    标准是低于 <?php echo $upperBound['value']; ?>%
                </p>
            <?php } else if ($type == 'unresolve_dispute_rate') { ?>
                <p class="line">
                    您 <?php echo $data['metric_lookback_startdate']; ?> - <?php echo $data['metric_lookback_enddate']; ?>期间：
                </p>
                <p class="line">
                    评价日期: <?php echo $data['evaluation_date']; ?>
                </p>
                <?php
                $metricValue = json_decode($data['metric_value'], true);
                $upperBound = json_decode($data['metric_threshold_upper_bound'], true);
                ?>
                <p class="line">
                    共产生 <?php echo $metricValue['denominator']; ?>项交易，
                    未解决纠纷 <?php echo $metricValue['numerator']; ?>项，
                    未解决纠纷率为 <?php echo $metricValue['value']; ?>%，
                    标准是低于 <?php echo $upperBound['value']; ?>%
                </p>
            <?php } else if ($type == 'transport_delay_rate') { ?>
                <p class="line">
                    您 <?php echo $data['metric_lookback_startdate']; ?> - <?php echo $data['metric_lookback_enddate']; ?>期间：
                </p>
                <p class="line">
                    评价日期: <?php echo $data['evaluation_date']; ?>
                </p>
                <?php
                $metricValue = json_decode($data['metric_value'], true);
                $upperBound = json_decode($data['metric_threshold_upper_bound'], true);
                ?>
                <p class="line">
                    共产生 <?php echo $metricValue['denominator']; ?>项交易，
                    分子 <?php echo $metricValue['numerator']; ?>项，
                    运送延迟率 <?php echo $metricValue['value']; ?>%，
                    最低标准是低于
                    <?php
                    //运送延迟率的最低标准是英国德国9%，美国7%，全球10%
                    switch ($data['program']) {
                        case 'PROGRAM_DE':
                        case 'PROGRAM_UK':
                            echo '9%';
                            break;
                        case 'PROGRAM_US':
                            echo '7%';
                            break;
                        case 'PROGRAM_GLOBAL':
                            echo '10%';
                            break;
                    }
                    ?>
                </p>
            <?php } ?>
        <?php } else { ?>
            没有找到数据
        <?php } ?>
    </div>
    <div class="popup-footer"></div>
</div>
<script type="text/javascript">
    $(function () {
        //给状态不同的颜色
        function addStatusColor() {
            $("a.status").each(function () {
                var status = $(this).attr("data-status");
                if (status) {
                    if (status.indexOf("正常") != -1) {
                        $(this).css("color", "#52c41a");
                    } else if (status.indexOf("超标") != -1) {
                        $(this).css("color", "#fa541c");
                    } else if (status.indexOf("警告") != -1) {
                        $(this).css("color", "#faad14");
                    } else if (status.indexOf("限制") != -1) {
                        $(this).css("color", "#eb2f96");
                    } else if (status.indexOf("不考核") != -1) {
                        $(this).css("color", "#1890ff");
                    }

                    if (status.indexOf("最高评级") != -1) {
                        $(this).css("color", "#52c41a");
                    } else if (status.indexOf("低于标准") != -1) {
                        $(this).css("color", "#fa541c");
                    }
                }
            });
        }

        addStatusColor();
    });
</script>