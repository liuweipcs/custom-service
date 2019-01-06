<?php

use yii\helpers\Url;
use app\modules\mails\models\MailTemplate;
use app\modules\accounts\models\Platform;
use yii\helpers\Html;
use app\modules\mails\models\MailTemplateCategory;
use app\modules\orders\models\Logistic;
use app\modules\systems\models\Country;
use app\modules\systems\models\ReminderMsgRule;

$this->title = '速卖通消息详情';
?>
<style>
    li {
        list-style: none;
    }

    .hear-title, .search-box ul {
        overflow: hidden;
    }

    .hear-title p:nth-child(1) span:nth-child(1), .hear-title p:nth-child(2) span:nth-child(1) {
        display: inline-block;
        width: 30%
    }

    .item-list li {
        border-bottom: 1px solid #ddd;
        padding: 5px 10px
    }

    .item-list li span {
        display: inline-block;
        width: 25%
    }

    .search-box ul li {
        float: left;
        padding: 0 10px 10px 0
    }

    .search-box textarea {
        display: block;
        margin-top: 10px;
        width: 100%
    }

    .info-box .det-info {
        width: 100%;
        height: 200px;
        border: 2px solid #ddd;
    }

    /*.well span{padding: 6%}*/
    .well {
        width: 100%;
        margin-bottom: 20px;
        background-color: none;
        overflow: hidden;
    }

    .well p {
        text-align: left
    }

    .well_content {
        width: 50%;
        min-height: 20px;
        padding: 19px;
        background-color: #f5f5f5;
        border: 1px solid #e3e3e3;
        border-radius: 4px;
        box-shadow: inset 0 1px 1px rgba(0, 0, 0, .05);
    }

    .language {
        width: 720px;
        float: left;
        height: auto;
        max-height: 250px;
        overflow-y: scroll;
    }

    .language li {
        width: 16%;
        float: left;
    }

    .language li a {
        font-size: 10px;
        text-align: left;
        cursor: pointer;
    }

    .col-sm-5 {
        width: auto;
    }

    .tr_q .dropdown-menu {
        left: -136px;
    }

    .tr_h .dropdown-menu {
        left: -392px;
    }

    .panel {
        overflow: auto;
    }

    .first_one {
        background-color: #f5222d;
        border-color: #f5222d;
        color: white;
        padding: 10px 15px;
    }

    fieldset {
        padding: .35em .625em .75em;
        margin: 0 2px;
        border: 1px solid silver;
    }

    legend {
        padding: .5em;
        border: 0;
        width: auto;
        margin-bottom: 0;
        font-size: 16px;
    }

    .mail_template_area a {
        display: inline-block;
        margin: 2px 5px;
    }
</style>
<div>
    <div id="page-wrapper-inbox">
        <p>
            <a href='/mails/aliexpress/index' style='text-decoration:none;'>
                <button type="button" class="btn btn-primary btn-lg btn-block">返回列表</button>
            </a>
        </p>
        <!--    左边信息-->
        <div class="panelLeft" style="width:59%;height:100%;float: left;">
            <input type="hidden" name="current_id" value="<?php echo Yii::$app->getRequest()->getQueryParam('id'); ?>">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <ul class="list-inline" id="ulul">
                        <?php
                        if (!empty($tags_data)) {
                            foreach ($tags_data as $key => $value) {
                                ?>
                                <li style="margin-right: 20px;" class="btn btn-default"
                                    id="tags_value<?php echo $key; ?>">
                                    <span use_data="<?php echo $key; ?>"><?php echo $value; ?></span>&nbsp;<a
                                        class="btn btn-warning" href="javascript:void(0)"
                                        onclick="removetags(this);">x</a></li>
                                    <?php
                                }
                            }
                            ?>
                    </ul>
                </h3>
            </div>
            <div class="panel panel-success" style="width:100%;float: left;max-height:600px;overflow-y:scroll;">
                <div class="panel-body">
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <div class="hear-title">
                                <p><span>查看状态：<?php echo $model::getReadStat($model->read_stat); ?></span><span
                                        id="dear">处理状态：<?php echo $model::getDealStat($model->deal_stat); ?></span><span
                                        style="margin-left:450px;">店铺简称：<?php echo $accountName; ?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($memberRemark)) { ?>
                        <div class="panel panel-default" style="padding-top: 15px;height: 60px;">
                            <span style="margin-left: 10px;"></span>
                            <?php if (!empty((implode('', array_values($memberRemark['list']))))) { ?>
                                <?php foreach ($memberRemark["list"] as $k => $value) { ?>
                                    <?php if ($value) { ?>
                                        <span id="remark_<?php echo $k; ?>">
                                            <li class="tag label btn-info md ion-close-circled" style=""
                                                data-id="<?php echo $k; ?>">
                                                <span style="cursor: pointer;word-break:normal;white-space:pre-wrap;"
                                                      class="remark" data="<?php echo $k; ?>"
                                                      data1=""><?php echo isset($value) ? $value : ''; ?></span>
                                                <a href="javascript:void(0)" class="remove_remark"
                                                   data-id="<?php echo $k; ?>">x</a>
                                            </li>
                                        </span>
                                        <?php
                                    } else {
                                        continue;
                                    }
                                    ?>

                                <?php } ?>
                            <?php } else { ?>
                                <?php $inboxId = array_keys($memberRemark["list"])[0]; ?>
                                <span id="remark_<?php echo $inboxId; ?>">
                                    <i class="fa remark fa-pencil remark" style="cursor: pointer;"
                                       data="<?php echo $inboxId; ?>"></i>
                                </span>
                            <?php } ?>
                        </div>
                    <?php } ?>

                    <p id="replyList">
                        <?php
                        $wx = 1;
                        $i = 0;
                        foreach ($replyList as $value) {
                            $i++;
                            $wx += 1;
                            ?>

                            <?php
                            /* 一个信息多次回复时获取到的信息是没有reply_from的，是一个二维数组 */
                            if (!empty($value['reply_from'])) {
                                ?>

                                <input type="hidden" class="type_id" value="<?php echo $value['type_id'] ?>">
                                <input type="hidden" class="message_type" value="<?php echo $value['message_type'] ?>">
                            <div class="well">
                                <div class="well_content"
                                     <?php if (!empty($value['reply_from']) && $value['reply_from'] == 2) { ?>style="background:#F8F8FF;float:left;word-break:break-all;"
                                     <?php } else { ?>style="background:#90EE90;float:right;" <?php } ?> >
                                    <p style="border-bottom: 2px solid #EEEEE0">
                                        <span>发送人：<?php
                                            if (!empty($value['reply_by'])) {
                                                echo!empty($value['reply_by']) ? $value['reply_by'] : '';
                                            } else {
                                                echo!empty($value['create_by']) ? $value['create_by'] : '';
                                            }
                                            ?></span>
                                        <span>日期：<?php
                                            if (!empty($value['gmt_create'])) {
                                                echo!empty($value['gmt_create']) ? $value['gmt_create'] : '';
                                            } else {
                                                echo!empty($value['create_time']) ? $value['create_time'] : '';
                                            }
                                            ?></span>
                                    </p>
                                    <p class="pcontent_<?php echo $wx; ?>">
                                        <span id="text_<?php echo $wx; ?>"
                                              data-content="<?php echo!empty($value['reply_content']) ? rawurlencode(strip_tags(trim($value['reply_content']))) : ''; ?>">
                                                  <?php echo!empty($value['reply_content']) ? $value['reply_content'] : ''; ?>
                                                  <?php echo!empty($value['reply_content_en']) ? htmlentities($value['reply_content_en']) : '' ?>
                                        </span>
                                        <?php if (!empty($value['reply_content']) && !empty($value['reply_from']) && $value['reply_from'] == 2) { ?>
                                            <a style="cursor: pointer;" data1="<?php echo $wx; ?>"
                                               class="transClik">&nbsp;&nbsp;点击翻译</a>
                                           <?php } ?>
                                        <br/>
                                        <?php
                                        $a_url = isset($value['fileBImg']) ? '<a href="' . $value['fileBImg'] . '" target="_blank">' : "";
                                        $img = isset($value['fileImg']) ? '<img src="' . $value['fileImg'] . '">' : "";
                                        $a_end = isset($value['fileBImg']) ? '</a>' : "";
                                        echo $a_url . $img . $a_end;
                                        ?>

                                        <?php if (!empty($value['summary']['product_name'])) { ?>
                                            <?php if (\app\common\VHelper::fileExists($value['summary']['product_image_url'])) { ?>
                                                <img src="<?php echo!empty($value['summary']['product_image_url']) ? $value['summary']['product_image_url'] : ''; ?>">
                                            <?php } ?>
                                        <?php } ?>
                                    </p>
                                </div>
                            </div>

                        <?php } else { ?>
                            <div class="panel panel-primary">
                                <div class="<?php
                                if ($i == 1) {
                                    echo 'first_one';
                                } else {
                                    echo 'panel-heading';
                                };
                                ?>">
                                    <input type="hidden" class="type_id" value="<?php echo $value[0]['type_id'] ?>">
                                    <input type="hidden" class="message_type" value="<?php echo $value[0]['message_type'] ?>">
                                    <?php
                                    if (!empty($value[0]['message_type']) && $value[0]['message_type'] == 'order') {
                                        ?>
                                        <h3 class="panel-title">关于该订单<br>
                                            订单号：<a class="edit-button" _width="90%" _height="90%" href="<?php
                                            echo Url::toRoute(['/orders/order/orderdetails',
                                                'order_id' => !empty($value[0]['type_id']) ? $value[0]['type_id'] : '',
                                                'platform' => Platform::PLATFORM_CODE_ALI,
                                            ]);
                                            ?>"
                                                   target="_blank"><?php echo!empty($value[0]['type_id']) ? $value[0]['type_id'] : ''; ?></a>
                                            <button type="button" class="btn btn-success"
                                                    onclick="informationSource('<?php echo $value[0]['message_type']; ?>',<?php echo $value[0]['type_id']; ?>, '关于该订单:<br/><?php echo!empty($value[0]['type_id']) ? $value[0]['type_id'] : ''; ?>');">
                                                回复
                                            </button>
                                            <span id="remark_<?php echo $value[0]['id']; ?>">
                                                <?php
                                                if (/* isset($value[0]['remark']) && */
                                                        empty($value[0]['remark'])) {
                                                    ?>
                                                    <i class="fa remark fa-pencil remark" style="cursor: pointer;"
                                                       data="<?php echo $value[0]['id']; ?>" data1=""></i>
                                                   <?php } else { ?>
                                                    <li class="tag label btn-info md ion-close-circled"
                                                        data-id="<?php echo $value[0]['id']; ?>">
                                                        <span style="cursor: pointer;word-break:normal;white-space:pre-wrap;" pointer;" class="remark" data="<?php echo $value[0]['id']; ?>
                                                              " data1=""><?php echo isset($value[0]['remark']) ? $value[0]['remark'] : ''; ?></span>
                                                        <a href="javascript:void(0)" class="remove_remark"
                                                           data-id="<?php echo $value[0]['id']; ?>">x</a>
                                                    </li>
                                                <?php } ?>
                                            </span><br/>
                                            <span>
                                                <?php if (is_array($sku)) { ?>
                                                    相关sku：
                                                    <?php foreach ($sku as $val) { ?>
                                                        <a href="http://120.24.249.36/product/index/sku/<?php echo $val; ?>"
                                                           style="color:white" target='_blank'><?php echo $val; ?>
                                                            ——</a>
                                                    <?php } ?>
                                                    <br/>
                                                <?php } else { ?>
                                                    相关sku：<?php echo $sku; ?><br/>
                                                <?php } ?>
                                            </span>

                                        </h3>
                                    <?php } elseif (!empty($value[0]['message_type']) && $value[0]['message_type'] == 'product') { ?>
                                        <h3 class="panel-title">关于该产品<br>
                                            <a href="<?php echo!empty($value[0]['summary']['product_detail_url']) ? $value[0]['summary']['product_detail_url'] : ''; ?>"
                                               target="_blank"><?php echo!empty($value[0]['summary']['product_name']) ? $value[0]['summary']['product_name'] : ''; ?></a>
                                            <button type="button" class="btn btn-success"
                                                    onclick="informationSource('<?php echo $value[0]['message_type']; ?>',<?php echo $value[0]['type_id']; ?>, '关于该产品:<br/><?php echo!empty($value[0]['summary']['product_name']) ? $value[0]['summary']['product_name'] : ''; ?>');">
                                                回复
                                            </button>
                                            <span id="remark_<?php echo $value[0]['id']; ?>">
                                                <?php if (empty($value[0]['remark'])) { ?>
                                                    <i class="fa remark fa-pencil remark" style="cursor: pointer;"
                                                       data="<?php echo $value[0]['id']; ?>" data1=""></i>
                                                   <?php } else { ?>
                                                    <li class=" tag label btn-info md ion-close-circled">
                                                        <span style="cursor: pointer;word-break:normal;white-space:pre-wrap;"
                                                              class="remark" data-id="<?php echo $value[0]['id']; ?>"
                                                              data="<?php echo $value[0]['id']; ?>"
                                                              data1=""><?php echo $value[0]['remark']; ?></span>
                                                        <a href="javascript:void(0)" class="remove_remark"
                                                           data-id="<?php echo $value[0]['id']; ?>">x</a>
                                                    </li>
                                                <?php } ?>
                                            </span><br/>
                                            <span>
                                                <?php if (is_array($sku)) { ?>
                                                    相关sku：
                                                    <?php foreach ($sku as $val) { ?>
                                                        <a href="http://120.24.249.36/product/index/sku/<?php echo $val; ?>"
                                                           style="color:white" target='_blank'><?php echo $val; ?>
                                                            ——</a>
                                                    <?php } ?>
                                                    <br/>
                                                <?php } elseif($sku==1) { ?>
                                                    <a href="http://120.24.249.36/product/index/sku/<?php echo $value[0]['sku']; ?>"
                                                           style="color:white" target='_blank'><?php echo $value[0]['sku']; ?>
                                                            ——</a>
                                                      <br/>
                                                <?php }else{ ?>
                                                    相关sku：<?php echo $sku; ?><br/>
                                                <?php } ?>
                                            </span>

                                        </h3>
                                    <?php } else { ?>
                                        <h3 class="panel-title">
                                            <span id="remark_<?php echo $value[0]['id']; ?>">
                                                <?php if (empty($value[0]['remark'])) { ?>
                                                    <i class="fa remark fa-pencil remark" style="cursor: pointer;"
                                                       data="<?php echo $value[0]['id']; ?>" data1=""></i>
                                                   <?php } else { ?>
                                                    <li class=" tag label btn-info md ion-close-circled">
                                                        <span style="cursor: pointer;word-break:normal;white-space:pre-wrap;"
                                                              class="remark" data-id="<?php echo $value[0]['id']; ?>"
                                                              data="<?php echo $value[0]['id']; ?>"
                                                              data1=""><?php echo $value[0]['remark']; ?></span>
                                                        <a href="javascript:void(0)" class="remove_remark"
                                                           data-id="<?php echo $value[0]['id']; ?>">x</a>
                                                    </li>
                                                <?php } ?>
                                            </span>
                                        </h3>
                                    <?php } ?>
                                </div>
                                <?php
                                if (!empty($value)) {
                                    foreach ($value as $key => $val) {
                                        $wx += 1;
                                        ?>
                                        <div class="well">
                                            <div class="well_content"
                                                 <?php if (!empty($val['reply_from']) && $val['reply_from'] == 2) { ?>style="background:#F8F8FF;float:left; "
                                                 <?php } else { ?>style="background:#90EE90;float:right;" <?php } ?>>
                                                <p style="border-bottom: 2px solid #EEEEE0">
                                                    <b>发送人：<?php
                                                        if (!empty($val['reply_by']) && $val['reply_by']) {
                                                            echo!empty($val['reply_by']) ? $val['reply_by'] : '';
                                                        } else {
                                                            echo!empty($val['create_by']) ? $val['create_by'] : '';
                                                        }
                                                        ?></b>
                                                    <b>日期：<?php
                                                        if (!empty($val['gmt_create'])) {
                                                            echo!empty($val['gmt_create']) ? $val['gmt_create'] : '';
                                                        } else {
                                                            echo!empty($val['create_time']) ? $val['create_time'] : '';
                                                        }
                                                        ?></b>
                                                </p>
                                                <p class="pcontent_<?php echo $wx; ?>">
                                                    <span id="text_<?php echo $wx; ?>"
                                                          data-content="<?php echo!empty($val['reply_content']) ? rawurlencode(strip_tags(trim($val['reply_content']))) : ''; ?>">
                                                              <?php
                                                              if (!empty($val['reply_content_en'])) {
                                                                  echo htmlentities(trim($val['reply_content_en'])) . '<br><br>';
                                                              }
                                                              ?>
                                                              <?php echo!empty($val['reply_content']) ? trim($val['reply_content']) : ''; ?>
                                                    </span>
                                                    <?php if (!empty($val['reply_from']) && $val['reply_from'] == 2) { ?>
                                                        <a style="cursor: pointer;" data1="<?php echo $wx; ?>" class="transClik">&nbsp;&nbsp;点击翻译</a>
                                                    <?php } ?>
                                                    <br/>
                                                    <?php
                                                    $a_url = isset($val['fileBImg']) ? '<a href="' . $val['fileBImg'] . '" target="_blank">' : "";
                                                    $img = isset($val['fileImg']) ? '<img src="' . $val['fileImg'] . '">' : "";
                                                    $a_end = isset($val['fileBImg']) ? '</a>' : "";
                                                    echo $a_url . $img . $a_end;
                                                    ?>
                                                </p>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                }
                                ?>
                            </div>
                            <?php
                        }
                    }
                    ?>
                    </p>
                </div>
            </div>

            <div style="margin-top:-20px;float: left;width:100%;">
                <p style="color: #FFFFFF"><strong></strong></p>
                <ul class="nav nav-tabs">
                    <li class="active"><a data-toggle="tab" href="#menu1">订单相关信息</a></li>
                    <li><a data-toggle="tab" id="product_detail" href="#menu2">产品相关信息</a></li>
                </ul>
                <div class="tab-content">
                    <div id="menu1" class="tab-pane fade in active">
                        <?php
//判断买家ID是否不催付
                        $isNotReminder = ReminderMsgRule::buyerIdIsNotReminder(Platform::PLATFORM_CODE_ALI, $model->other_name);

                        echo $this->render('@app/modules/orders/views/order/aliexpressmessageorderlist', [
                            'model' => $orderModel,
                            'platformCode' => Platform::PLATFORM_CODE_ALI,
                            'buyerId' => $model->other_name,
                            'currentOrderId' => $currentOrderId,
                            'isNotReminder' => $isNotReminder,
                        ]);
                        ?>
                    </div>
                    <div id="menu2" class="tab-pane fade">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>产品名</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr id="current_pro">

                                </tr>
                                <?php
                                if (!empty($replyList)) {

                                    foreach ($replyList as $value) {
                                        if (empty($value['type_id'])) {
                                            foreach ($value as $val) {
                                                if (!empty($val['message_type']) && $val['message_type'] == 'product') {
                                                    ?>
                                                    <tr class="active">
                                                        <td style="width: 60%"><a
                                                                href="<?php echo $val['summary']['product_detail_url']; ?>"
                                                                target="_blank"><?php echo $val['summary']['product_name']; ?>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!--右边模板-->
        <div style="margin-left:10px;width:39%;float:left;margin-top: 30px">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"></h3>
                </div>
                <div id="collapseThree" class="panel-collapse">
                    <div class="panel-body" style="height:auto;">
                        <div style="margin-bottom: 10px">
                            <form class="bs-example bs-example-form" role="form">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="input-group">
                                            <input type="text" class="form-control mail_template_search_text"
                                                   placeholder="消息模板搜索">
                                            <span class="input-group-btn">
                                                <button class="btn btn-default btn-sm mail_template_search_btn"
                                                        type="button">搜索</button>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <?php
                                        $templateCateList = MailTemplateCategory::getCategoryList(Platform::PLATFORM_CODE_ALI, 0, 'list');
                                        if (!empty($templateCateList)) {
                                            echo '<select id="selMailTemplateCate" class="form-control" style="width:200px;">';
                                            foreach ($templateCateList as $key => $templateCate) {
                                                $templateCate = str_replace(' ', '&nbsp;', $templateCate);
                                                echo "<option value='{$key}'>{$templateCate}</option>";
                                            }
                                            echo '</select>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-body mail_template_area">
                                <?php
                                $templates = MailTemplate::getMyMailTemplate(Platform::PLATFORM_CODE_ALI);
                                if (!empty($templates)) {
                                    foreach ($templates as $template) {
                                        if (!empty($template[0])) {
                                            echo '<fieldset>';
                                            echo '<legend>' . ($template[0]['category_name'] ? $template[0]['category_name'] : '无分类名称') . '</legend>';
                                        }

                                        if (!empty($template) && is_array($template)) {
                                            foreach ($template as $item) {
                                                echo "<a href='#' class='mail_template_unity' value='{$item['id']}'>{$item['template_name']}</a>";
                                            }
                                        }
                                        if (!empty($template[0])) {
                                            echo '</fieldset>';
                                        }
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-default" id="Reply">回复消息</button>
                            <button type="button" class="btn btn-sm btn-default" value="1" onclick="markerReply(this.value);">
                                标记成已处理
                            </button>
                            <a class="btn btn-default btn-sm add-tags-button-button"
                               href="<?php echo '/mails/aliexpress/addtags?type=detail&ids=' . $model->id; ?>">添加标签</a>
                            <a class="btn btn-default btn-sm add-tags-button-button"
                               href="<?php echo '/mails/aliexpress/removetags?type=detail&id=' . $model->id; ?>">移除标签</a>
                            <input type="hidden" id="channel_id" value="<?php echo $model->channel_id; ?>"/>
                            <input type="hidden" id="account_id" value="<?php echo $model->account_id; ?>"/>
                            <input type="hidden" id="msg_sources" value="<?php echo $model->msg_sources; ?>"/>
                            <input type="hidden" id="id" value="<?php echo $id; ?>"/>
                            <button type="button" class="btn btn-sm btn-info" id="addexpression">添加表情</button>
                            <button type="button" class="btn btn-sm btn-success" id="return_info">获取退货信息</button>
                            <!--在鼠标移动位置插入参数-->
                            <div class="form_data" style="float: left;font-size: 12px;">
                                <?php
                                /*  获取国家信息 */
                                if ($order_info) {
                                    $countryList = Country::getCodeNamePairsList('en_name');

                                    if ($order_info['info']['real_ship_code']) {
                                        $logistic = Logistic:: getSendWayEng($order_info['info']['real_ship_code']);
                                        if (empty($logistic)) {
                                            $logistic = Logistic:: getSendWayEng($order_info['info']['ship_code']);
                                        }
                                    } else {
                                        $logistic = '';
                                    }
                                    if ($order_info['info']['track_number']) {
                                        $track = 'http://www.17track.net/zh-cn/track?nums=' . $order_info['info']['track_number'];
                                        $track_number = $order_info['info']['track_number'];
                                    } else {
                                        $track = '';
                                        $track_number = '';
                                    }
                                    if ($order_info['info']['buyer_id']) {
                                        $buyer_id = $order_info['info']['buyer_id'];
                                    } else {
                                        $buyer_id = '';
                                    }
                                    if ($order_info['info']['ship_country']) {
                                        $country = $order_info['info']['ship_country'];
                                        $ship_country = array_key_exists($country, $countryList) ? $countryList[$country] : '';
                                    } else {
                                        $ship_country = '';
                                    }
                                } else {
                                    $buyer_id = '';
                                    $track_number = '';
                                    $logistic = '';
                                    $track = '';
                                    $ship_country = '';
                                }
                                ?>
                                <select id="countDataType" class="form-control"
                                        style="width:100%;height:30px;padding: 2px 5px;">
                                    <option value="all">选择绑定参数</option>
                                    <option value="<?php echo $buyer_id; ?>">客户ID</option>
                                    <option value="<?php echo $track_number; ?>">跟踪号</option>
                                    <option value="<?php echo $logistic; ?>">发货方式</option>
                                    <option value="<?php echo $track; ?>">查询网址</option>
                                    <option value="<?php echo $ship_country ?>">国家</option>
                                </select>
                            </div>
                        </div>
                        <div class="well" style="width: 100%;margin-top: 5px;display: none" id="expression"><?php
                            if (!empty($expressionList)) {
                                foreach ($expressionList as $exvalue) {
                                    ?>
                                    <a href="#this" class="expression_url" data-value="<?php echo $exvalue['label']; ?>"><img
                                            src="<?php echo $exvalue['expression_url']; ?>" width="24" height="24"/></a>
                                        <?php
                                    }
                                }
                                ?>
                        </div>
                        <div class="form-group" style="margin-top: 10px;min-height:30px;">
                            <label class="sr-only" for="inputfile">文件输入</label>
                            <input type="file" id="inputfile">
                            <div id="updateimage">

                            </div>
                        </div>

                        <form role="form" style="height: 165px">
                            <?php echo Html::hiddenInput('sl_code', "", ['id' => 'sl_code']); ?>
                            <?php echo Html::hiddenInput('tl_code', "", ['id' => 'tl_code']); ?>
                            <div class="form-group">
                                <label for="name"></label>
                                <div>
                                    <textarea class="form-control" rows="6" placeholder="翻译前内容(英语)"
                                              id="reply_content"></textarea>
                                </div>
                                <div class="row"
                                     style="text-align: center;font-size: 13px;font-weight: bold;margin-top: 10px;margin-bottom: 10px;">
                                    <div class="col-sm-5 tr_q">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-default" type="button"
                                                    onclick="changeCode(3, 'en', '', $(this))">英语
                                            </button>
                                            <button class="btn btn-sm btn-default" type="button"
                                                    onclick="changeCode(3, 'fr', '', $(this))">法语
                                            </button>
                                            <button class="btn btn-sm btn-default" type="button"
                                                    onclick="changeCode(3, 'de', '', $(this))">德语
                                            </button>
                                            <?php if (is_array($googleLangCode) && !empty($googleLangCode)) { ?>
                                                <div class="btn-group">
                                                    <button data-toggle="dropdown"
                                                            class="btn btn-default btn-sm dropdown-toggle" type="button"
                                                            aria-expanded="false" id="sl_btn">更多&nbsp;&nbsp;<span
                                                            class="caret"></span></button>
                                                    <ul class="dropdown-menu language">
                                                        <?php foreach ($googleLangCode as $key => $value) { ?>
                                                            <li>
                                                                <a onclick="changeCode(1, '<?php echo $key; ?>', '<?php echo $value; ?>', $(this))"><?php echo $value; ?></a>
                                                            </li>
                                                        <?php } ?>
                                                    </ul>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <div class="fa-hover col-sm-1" style="width:0px;line-height: 30px;"><a><i
                                                class="fa fa-exchange"></i></a></div>
                                    <div class="col-sm-5 tr_h">
                                        <div class="btn-group">
                                            <button class="btn-sm btn btn-default" type="button"
                                                    onclick="changeCode(4, 'en', '', $(this))">英语
                                            </button>
                                            <button class="btn btn-sm btn-default" type="button"
                                                    onclick="changeCode(4, 'fr', '', $(this))">法语
                                            </button>
                                            <button class="btn btn-sm btn-default" type="button"
                                                    onclick="changeCode(4, 'de', '', $(this))">德语
                                            </button>
                                            <?php if (is_array($googleLangCode) && !empty($googleLangCode)) { ?>
                                                <div class="btn-group">
                                                    <button data-toggle="dropdown"
                                                            class="btn btn-default btn-sm dropdown-toggle" type="button"
                                                            aria-expanded="false" data="" id="tl_btn">更多&nbsp;&nbsp;<span
                                                            class="caret"></span></button>
                                                    <ul class="dropdown-menu language">
                                                        <?php foreach ($googleLangCode as $key => $value) { ?>
                                                            <li>
                                                                <a onclick="changeCode(2, '<?php echo $key; ?>', '<?php echo $value; ?>', $(this))"><?php echo $value; ?></a>
                                                            </li>
                                                        <?php } ?>
                                                    </ul>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <div class="col-sm-1">
                                        <button class="btn btn-sm btn-primary artificialTranslation" type="button"
                                                id="translations_btn">翻译 [ <b id="sl_name"></b> - <b id="tl_name"></b> ]
                                        </button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <textarea class="form-control" rows="6" placeholder="翻译后内容(如果有翻译则发送给客户的内容)"
                                                  id="reply_content_en"></textarea>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <div id="current_information">

                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!--下一封消息-->
        <div class="panel panel-default" style="width: 100%;float: left;margin-top: 20px;">
            <a href="<?php echo Url::toRoute(['/mails/aliexpress/details', 'id' => $next]); ?>"
               class="btn btn-primary btn-lg btn-block">下一封</a>
        </div>
        <script>
            //快捷键设置标签
            var keyboards = '<?php echo $keyboards; ?>'
            keyboards = JSON.parse(keyboards);
            var ids = '<?php echo $id; ?>'
            var tag_id = '';
            $(document).ready(
                    function () {
                        document.onkeyup = function (e) {
                            var event = window.event || e;
                            if (event.shiftKey && keyboards['shift'] != undefined && keyboards['shift'][event.keyCode] != undefined) {
                                tag_id = keyboards['shift'][event.keyCode]
                                if (tag_id != '' && tag_id != undefined) {
                                    $.post('<?= Url::toRoute(['/mails/aliexpress/addretags', 'ids' => $id, 'type' => 'details']) ?>', {
                                        'MailTag[inbox_id]': ids,
                                        'MailTag[tag_id][]': tag_id,
                                        'MailTag[type]': 'details'
                                    }, function (data) {
                                        if (data.code == "200" && data.url == 'add') {
                                            /*  window.location.href = data.url;*/
                                            var html = "";
                                            var result = data.data;
                                            $.each(result, function (i, v) {
                                                html += '<li style="margin-right: 20px;" class="btn btn-default" id = "tags_value' + i + '"><span use_data="' + i + '">' + v + '</span>&nbsp;<a class="btn btn-warning" href="javascript:void(0)" onclick="removetags(this);">x</a></li>';
                                            })
                                            $("#ulul").html(html);
                                        } else if (data.code == "200" && data.url == 'del') {
                                            var tags_id = data.js;
                                            $("#tags_value" + tags_id).hide(50);
                                        }
                                    }, 'json');
                                }
                            }
                            if (event.ctrlKey && keyboards['ctrl'] != undefined && keyboards['ctrl'][event.keyCode] != undefined) {
                                tag_id = keyboards['ctrl'][event.keyCode]
                                if (tag_id != '' && tag_id != undefined) {
                                    $.post('<?= Url::toRoute(['/mails/aliexpress/addretags', 'ids' => $id, 'type' => 'details']) ?>', {
                                        'MailTag[inbox_id]': ids,
                                        'MailTag[tag_id][]': tag_id,
                                        'MailTag[type]': 'details'
                                    }, function (data) {
                                        if (data.code == "200" && data.url == 'add') {
                                            /*  window.location.href = data.url;*/
                                            var html = "";
                                            var result = data.data;
                                            $.each(result, function (i, v) {
                                                html += '<li style="margin-right: 20px;" class="btn btn-default" id = "tags_value' + i + '"><span use_data="' + i + '">' + v + '</span>&nbsp;<a class="btn btn-warning" href="javascript:void(0)" onclick="removetags(this);">x</a></li>';
                                            })
                                            $("#ulul").html(html);
                                        } else if (data.code == "200" && data.url == 'del') {
                                            var tags_id = data.js;
                                            $("#tags_value" + tags_id).hide(50);
                                        }
                                    }, 'json');
                                }
                            }
                            if (event.altKey && keyboards['alt'] != undefined && keyboards['alt'][event.keyCode] != undefined) {
                                tag_id = keyboards['alt'][event.keyCode]
                                if (tag_id != '' && tag_id != undefined) {
                                    $.post('<?= Url::toRoute(['/mails/aliexpress/addretags', 'ids' => $id, 'type' => 'details']) ?>', {
                                        'MailTag[inbox_id]': ids,
                                        'MailTag[tag_id][]': tag_id,
                                        'MailTag[type]': 'details'
                                    }, function (data) {
                                        if (data.code == "200" && data.url == 'add') {
                                            /*  window.location.href = data.url;*/
                                            var html = "";
                                            var result = data.data;
                                            $.each(result, function (i, v) {
                                                html += '<li style="margin-right: 20px;" class="btn btn-default" id = "tags_value' + i + '"><span use_data="' + i + '">' + v + '</span>&nbsp;<a class="btn btn-warning" href="javascript:void(0)" onclick="removetags(this);">x</a></li>';
                                            })
                                            $("#ulul").html(html);
                                        } else if (data.code == "200" && data.url == 'del') {
                                            var tags_id = data.js;
                                            $("#tags_value" + tags_id).hide(50);
                                        }
                                    }, 'json');
                                }
                            }
                        }
                    }
            );
            $(function () {
                //模板ajax
                $('.mail_template_area').delegate('.mail_template_unity', 'click', function () {
                    $.post('<?php echo Url::toRoute(['/mails/msgcontent/gettemplate']); ?>', {'num': $(this).attr('value')}, function (data) {
                        switch (data.status) {
                            case 'error':
                                alert(data.message);
                                return;
                            case 'success':
                                var refund_content = $('#reply_content').val();
                                if (refund_content !== '') {
                                    $('#reply_content').val(refund_content + '\n' + data.content);
                                } else {
                                    $('#reply_content').val(data.content);
                                }
                        }
                    }, 'json');
                });

                //选择邮件模板分类
                $("#selMailTemplateCate").on("change", function () {
                    var category_id = $(this).val();
                    $.post("<?php echo Url::toRoute('/mails/aliexpress/getmailtemplatelist'); ?>", {
                        "category_id": category_id
                    }, function (data) {
                        if (data["code"] == 1) {
                            var data = data["data"];
                            if (data) {
                                var html = "";
                                for (var ix in data) {
                                    if (data[ix][0]) {
                                        html += "<fieldset>"
                                        html += "<legend>" + (data[ix][0]["category_name"] ? data[ix][0]["category_name"] : "无分类名称") + "</legend>";
                                    }
                                    var item = data[ix];
                                    for (var index in item) {
                                        html += "<a href='#' class='mail_template_unity' value='" + item[index]["id"] + "'>" + item[index]["template_name"] + "</a>";
                                    }
                                    if (data[ix][0]) {
                                        html += "</fieldset>";
                                    }
                                }
                                $(".mail_template_area").html(html);
                            }
                        } else {
                            layer.alert(data["message"]);
                            $(".mail_template_area").html("");
                        }
                    }, "json");
                    return false;
                });

                //点击获取退货信息
                $('#return_info').click(function () {
                    var current_order_id = $("input[name='current_order_id']").val();
                    var rule_warehouse_id = $("input[name='current_order_warehouse_id']").val();
                    var current_order_warehouse_name = $("input[name='current_order_warehouse_name']").val();
                    var warehouse_1 = '递四方';
                    var warehouse_2 = '谷仓';
                    var warehouse_3 = '万邑通';
                    var warehouse_4 = '旺集';
                    if (!rule_warehouse_id) {
                        layer.msg("暂无仓库信息", {icon: 5});
                        return;
                    }
                    if (!current_order_id) {
                        layer.msg("暂无订单信息", {icon: 5});
                        return;
                    }
                    if (current_order_warehouse_name.match(warehouse_1)
                            || current_order_warehouse_name.match(warehouse_2)
                            || current_order_warehouse_name.match(warehouse_3)
                            || current_order_warehouse_name.match(warehouse_4)) {

                        //弹出框输入追踪号
                        layer.prompt({title: '追踪号', value: '', formType: 0}, function (tracking_no, index) {
                            $.ajax({
                                type: "POST",
                                dataType: "JSON",
                                url: '<?php echo Url::toRoute(['/mails/refundtemplate/getrefundinfo']); ?>',
                                data: {
                                    'rule_warehouse_id': rule_warehouse_id,
                                    'order_id': current_order_id,
                                    'tracking_no': tracking_no
                                },
                                success: function (data) {
                                    switch (data.status) {
                                        case 'error':
                                            layer.msg(data.message, {icon: 5});
                                            return;
                                        case 'success':
                                            var html = "";
                                            html += 'rma:' + data.content.is_get_rma;
                                            html += "\n",
                                                    html += 'consignee:' + data.content.refund_name;
                                            html += "\n",
                                                    html += 'address:' + data.content.refund_address;
                                            var old_content = $('#reply_content').val();
                                            if (old_content !== '') {
                                                $('#reply_content').val(html + '\n' + old_content);
                                            } else {
                                                $('#reply_content').val(html);
                                            }
                                            $(this).attr('disabled', true);
                                    }

                                }
                            });

                            layer.close(index);
                        });

                    } else {
                        $.ajax({
                            type: "POST",
                            dataType: "JSON",
                            url: '<?php echo Url::toRoute(['/mails/refundtemplate/getrefundinfo']); ?>',
                            data: {
                                'rule_warehouse_id': rule_warehouse_id,
                                'order_id': current_order_id,
                            },
                            success: function (data) {
                                switch (data.status) {
                                    case 'error':
                                        layer.msg(data.message, {icon: 5});
                                        return;
                                    case 'success':
                                        var html = "";

                                        html += 'rma:' + data.content.is_get_rma;
                                        html += "\n",
                                                html += 'consignee:' + data.content.refund_name;
                                        html += "\n",
                                                html += 'address:' + data.content.refund_address;
                                        var old_content = $('#reply_content').val();
                                        if (old_content !== '') {
                                            $('#reply_content').val(html + '\n' + old_content);
                                        } else {
                                            $('#reply_content').val(html);
                                        }
                                        $(this).attr('disabled', true);
                                }
                            }
                        });
                    }
                });

                //邮件模板搜索
                $('.mail_template_search_btn').click(function () {
                    var name = $.trim($('.mail_template_search_text').val());
                    if (name.length == 0) {
                        layer.msg('搜索名称不能为空。', {icon: 5});
                        return;
                    }
                    $.post('<?php echo Url::toRoute(['/mails/aliexpress/searchmailtemplate']); ?>', {
                        "name": name
                    }, function (data) {
                        if (data["code"] == 1) {
                            var data = data["data"];
                            if (data) {
                                var html = "";
                                for (var ix in data) {
                                    if (data[ix][0]) {
                                        html += "<fieldset>"
                                        html += "<legend>" + (data[ix][0]["category_name"] ? data[ix][0]["category_name"] : "无分类名称") + "</legend>";
                                    }
                                    var item = data[ix];
                                    for (var index in item) {
                                        html += "<a href='#' class='mail_template_unity' value='" + item[index]["id"] + "'>" + item[index]["template_name"] + "</a>";
                                    }
                                    if (data[ix][0]) {
                                        html += "</fieldset>";
                                    }
                                }
                                $(".mail_template_area").html(html);
                            }
                        } else {
                            layer.alert(data["message"]);
                            $(".mail_template_area").html("");
                        }
                    }, 'json');
                    return false;
                });

                //鼠标定位添加订单信息
                $("#countDataType").on("change", function () {
                    var data_value = $(this).val();
                    if (data_value == '') {
                        alert("暂无此信息");
                    }
                    if (data_value != 'all') {
                        getValue('reply_content', data_value);
                    }
                })

                //表情
                $('#addexpression').click(function () {
                    $("#expression").toggle();
                });

                //鼠标定位添加表情
                $(".expression_url").click(function () {
                    var expression_url = $(this).attr('data-value');
                    getValue('reply_content', expression_url);
                });
                //添加表情
                //objid：textarea的id   str：要插入的内容
                function getValue(objid, str) {
                    var myField = document.getElementById("" + objid);
                    //IE浏览器
                    if (document.selection) {
                        myField.focus();
                        sel = document.selection.createRange();
                        sel.text = str;
                        sel.select();
                    } else if (myField.selectionStart || myField.selectionStart == '0') {
                        //得到光标前的位置
                        var startPos = myField.selectionStart;
                        //得到光标后的位置
                        var endPos = myField.selectionEnd;
                        // 在加入数据之前获得滚动条的高度
                        var restoreTop = myField.scrollTop;
                        myField.value = myField.value.substring(0, startPos) + str + myField.value.substring(endPos, myField.value.length);
                        //如果滚动条高度大于0
                        if (restoreTop > 0) {
                            // 返回
                            myField.scrollTop = restoreTop;
                        }
                        myField.focus();
                        myField.selectionStart = startPos + str.length;
                        myField.selectionEnd = startPos + str.length;
                    } else {
                        myField.value += str;
                        myField.focus();
                    }
                }

                //上传图片
                $("#inputUpload").click(function () {
                    $.ajax({
                        type: "POST",
                        url: "/mails/aliexpress/uploadpictures",
                        data: {
                            file: $('#inputfile').val(),
                            account_id: $('#account_id').val(),
                        }, // 要提交的表单
                        enctype: 'multipart/form-data',
                        success: function (msg) {
                            var obj = eval('(' + msg + ')');
                            if (obj.status == 1) {
                                $('#form2').css("display", "none");
                            } else {
                                alertMsg.info(obj.message);
                            }
                        }, error: function (error) {
                            alert(error);
                        }
                    });

                });
                $("#inputfile").change(function () {
                    //创建FormData对象
                    var data = new FormData();
                    //为FormData对象添加数据
                    $.each($('#inputfile')[0].files, function (i, file) {
                        data.append('upload_file', file);
                    });
                    $.ajax({
                        url: '/mails/aliexpress/uploadpictures?account_id=<?php echo $model->account_id; ?>',
                        type: 'POST',
                        data: data,
                        cache: false,
                        contentType: false, //不可缺
                        processData: false, //不可缺
                        success: function (msg) {
                            var obj = eval('(' + msg + ')');
                            if (obj.code == 200) {
                                $('#updateimage').text('');
                                $('#updateimage').append('<img src="' + obj.data + '" class="img-rounded imgPath" width="140" /><input type="hidden" id="imgPath_url" name="imgPath" value="' + obj.data + '" class="imgPath"><br><a onclick="imgPathRemove();" class="imgPath">删除</a>');
                            } else {
                                alert(obj.message);
                            }
                        }
                    });
                });
            });

            function imgPathRemove() {
                $(".imgPath").remove();
            }

            //单个点击回复
            function informationSource(message_type, type_id, current_information) {
                var string = '<div class="alert alert-success alert-dismissable">' +
                        ' <button type="button" class="close" data-dismiss="alert" aria-hidden="true">' + '&times;</button>' +
                        '<input type="hidden" id="type_id" value="' + type_id + '">' +
                        '<input type="hidden" id="message_type" value="' + message_type + '">' +
                        current_information
                        + '</div>';
                $("#current_information").html('');
                $("#current_information").html(string);
            }

            //标记回复
            function markerReply(deal_stat) {
                $.post("/mails/aliexpress/markerreply",
                        {
                            account_id: $('#account_id').val(),
                            channel_id: $('#channel_id').val(),
                            id: $('#id').val(),
                            deal_stat: deal_stat
                        },
                        function (result) {
                            var obj = eval('(' + result + ')');
                            $('#dear').html('处理状态：已处理');
                            alert(obj.message);
                        });
            }

            $(function () {
                //标记已读
                $("#read_stat").click(function () {
                    $.post("/mails/aliexpress/readstat",
                            {
                                account_id: $('#account_id').val(),
                                channel_id: $('#channel_id').val(),
                                msg_sources: $('#msg_sources').val()
                            },
                            function (result) {
                                var obj = eval('(' + result + ')');
                                alert(obj.message);
                            });
                });
                //回复
                $("#Reply").click(function () {
                    var reply_content_en = $('#reply_content_en').val();//翻译前内容
                    var reply_content = $('#reply_content').val();//翻译后内容

                    if (!reply_content) {
                        layer.msg('你还没有填写回复内容');
                        return false;
                    }
                    //判断
                    if (!$('#type_id').val()) {
                        var type_id = $(".type_id").val();
                    } else {
                        var type_id = $('#type_id').val();
                    }
                    if (!$('#message_type').val()) {
                        var message_type = $(".message_type").val();
                    } else {
                        var message_type = $('#message_type').val()
                    }
                    $.post("/mails/aliexpress/reply",
                            {
                                id: $("input[name='current_id']").val(),
                                account_id: $('#account_id').val(),
                                channel_id: $('#channel_id').val(),
                                type_id: type_id,
                                message_type: message_type,
                                content: reply_content,
                                content_en: reply_content_en,
                                imgPath: $('#imgPath_url').val()
                            },
                            function (data) {
                                if (data.code != '200') {
                                    layer.alert(data.message, {
                                        icon: 5
                                    });
                                } else {
                                    var reply = data.data;
                                    var replyList = '';
                                    var imgPath = '';
                                    if (reply.imgPath != '') {
                                        imgPath += '<br/><img src="' + reply.imgPath + '"/>';
                                    }
                                    replyList += '<div class="well" style="background:#90EE90;"> ' +
                                            '<p style="border-bottom: 2px solid #EEEEE0"> <span>发送人：' + reply.create_by +
                                            '</span><span>日期：' + reply.create_time + '</span></p> ' +
                                            '<p> ' + reply.reply_content + '</p>' + imgPath + ' </div>';
                                    $('#replyList').prepend(replyList);
                                    $('#Reply').attr('id', 'Reply11');
                                    layer.alert(data.message, {icon: 1});
                                    location.href = data.url
                                }

                            }, 'json');
                });
            });

            $('div.sidebar').hide();

            // 获取url参数
            function GetQueryString(name) {
                var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
                var r = window.location.search.substr(1).match(reg);
                if (r != null)
                    return unescape(r[2]);
                return null;
            }

            function removetags(obj) {
                var _id = GetQueryString('id');
                var tag_id = $(obj).siblings('span').attr('use_data');
                $.post('<?php echo Url::toRoute(['/mails/aliexpress/removetags', 'id' => $id, 'type' => 'details']); ?>', {
                    'MailTag[inbox_id]': _id,
                    'MailTag[tag_id][]': tag_id,
                    'MailTag[type]': 'details'
                }, function (data) {
                    if (data.url && data.code == "200")
                        $("#tags_value" + tag_id).hide(50);
                }, 'json');

            }


            /**
             * 回复客户邮件内容点击翻译(系统检测到用户语言)
             * @author allen <2018-1-29>
             */
            $(".transClik").click(function () {
                var sl = 'auto';
                var tl = 'en';
                var tag = $(this).attr('data1');
                var message = decodeURIComponent($("#text_" + tag).attr("data-content"));
                console.log(message);
                var that = $(this);
                if (message.length == 0) {
                    layer.msg('获取需要翻译的内容为空!');
                    return false;
                }
                $.ajax({
                    type: "POST",
                    dataType: "JSON",
                    url: '<?php echo Url::toRoute(['ebayinboxsubject/translate']); ?>',
                    data: {'sl': sl, 'tl': tl, 'returnLang': 1, 'content': message},
                    success: function (data) {
                        if (data) {
                            var htm = '<p style="color:green; font-weight:bold;">' + data.text + '</p>';
                            $(".pcontent_" + tag).after(htm);
                            $("#sl_code").val('en');
                            $("#sl_name").html('英语');
                            $("#tl_code").val(data.googleCode);
                            $("#tl_name").html(data.code);
                            that.remove();
                        }
                    }
                });
            });


            /**
             * 点击选择语言将选中语言赋值给对应控件
             * @param {type} type 类型
             * @param {type} code 语言code
             * @param {type} name 语言名称
             * @param {type} that 当前对象
             * @author allen <2018-1-11>
             * */
            function changeCode(type, code, name = "", that = "") {
                if (type == 1) {
                    $("#sl_code").val(code);
                    $("#sl_btn").html(name + '&nbsp;&nbsp;<span class="caret"></span>');
                    that.css('font-weight', 'bold');
                    $("#sl_name").html(name);
                } else if (type == 2) {
                    $("#tl_code").val(code);
                    $("#tl_btn").html(name + '&nbsp;&nbsp;<span class="caret"></span>');
                    $("#tl_name").html(name);
                    that.css('font-weight', 'bold');
                } else if (type == 3) {
                    var name = that.html();
                    $("#sl_code").val(code);
                    $("#sl_name").html(name);
                } else {
                    var name = that.html();
                    $("#tl_code").val(code);
                    $("#tl_name").html(name);
            }
            }

            /**
             * 绑定翻译按钮 进行手动翻译操作(系统未检测到用户语言)
             * @author allen <2018-1-11>
             **/
            $('.artificialTranslation').click(function () {
                var sl = $("#sl_code").val();
                var tl = $("#tl_code").val();
                var content = $.trim($("#reply_content").val());
                if (sl == "") {
                    layer.msg('请选择需要翻译的语言类型');
                    return false;
                }

                if (tl == "") {
                    layer.msg('请选择翻译目标的语言类型');
                    return false;
                }

                if (content.length <= 0) {
                    layer.msg('请输入需要翻译的内容!');
                    return false;
                }
                //ajax请求
                $.ajax({
                    type: "POST",
                    dataType: "JSON",
                    url: '<?php echo Url::toRoute(['ebayinboxsubject/translate']); ?>',
                    data: {'sl': sl, 'tl': tl, 'content': content},
                    success: function (data) {
                        if (data) {
                            $("#reply_content_en").val(data);
                        }
                    }
                });
            });

            /**
             * 添加 或者修改站内信备注功能
             * @author huwenjun <2018-05-22>
             */
            $(document).on('click', '.remark', function () {
                var id = $(this).attr('data');
                var remark = $(this).attr('data1');//默认备注
                if (remark == '') {
                    remark = $(this).text();
                }
                if (id == '') {
                    layer.msg('参数缺失，请检查后再提交！', {icon: 5});
                }
                layer.prompt({title: '站内信备注', value: remark, formType: 2}, function (text, index) {
                    $.ajax({
                        type: "POST",
                        dataType: "JSON",
                        url: '<?php echo Url::toRoute(['operationremark']); ?>',
                        data: {'id': id, 'remark': text},
                        success: function (data) {
                            if (data.status) {
                                layer.msg(data.info, {icon: 1});
                                var htm = '<li class="tag label btn-info md ion-close-circled"><span style="cursor: pointer;word-break:normal;white-space:pre-wrap;" class="remark" data="' + id + '" data1="">' + text + '</span>&nbsp;<a href="javascript:void(0)" class="remove_remark" data-id="' + id + '">x</a></li>';
                                $("#remark_" + id).html(htm);
                            } else {
                                layer.msg(data.info, {icon: 5});
                            }
                        }
                    });
                    layer.close(index);
                });
            });

            /**
             * 删除站内信备注功能
             * @author huwenjun <2018-02-10>
             */

            $(document).on('click', '.remove_remark', function () {
                var id = $(this).data('id');
                layer.confirm('您确定要删除么？', {
                    btn: ['确定', '再考虑一下'] //按钮
                }, function () {
                    $.ajax({
                        type: "POST",
                        dataType: "JSON",
                        url: '<?php echo Url::toRoute(['operationremark']); ?>',
                        data: {'id': id, 'remark': ''},
                        success: function (data) {
                            if (data.status) {
                                layer.msg(data.info, {icon: 1});
                                var htm = '<i class="fa remark fa-pencil remark" style="cursor: pointer;" data="' + id + '" data1=""></i>';
                                $("#remark_" + id).html(htm);
                            } else {
                                layer.msg(data.info, {icon: 5});
                            }
                        }
                    });
                }, function () {

                });
            });

            //点击获取产品详情链接
            $("#product_detail").click(function () {
                $("#current_pro").empty();
                var current_product_detail = $("#current_product_detail").val();
                if (current_product_detail != undefined) {
                    current_product_detail = JSON.parse(current_product_detail);
                    var html = "";
                    if (current_product_detail.length == 0) {
                        html += " <td colspan=\"2\" align=\"center\">没有当前订单产品信息！</td>"
                    } else {
                        for (var i = 0; i < current_product_detail.length; i++) {
                            var item_id = current_product_detail[i].item_id;
                            var mail_link = 'https://www.aliexpress.com/item//' + item_id + '.html';
                            var title = current_product_detail[i].title;
                            html += "<td style='width: 60%'>";
                            html += '<a href="' + mail_link + '" target="_blank">' + title;
                            html += "</td>";
                        }
                    }
                    $("#current_pro").append(html);
                }


            });
            
            function have_remark(ths){
                var remark = $(ths).data('remark');
                var id = $(ths).data('id');
                layer.tips(remark, '.have_remark_'+id, {
                    tips: [2, '#337ab7'], //设置tips方向和颜色 类型：Number/Array，默认：2 tips层的私有参数。支持上右下左四个方向，通过1-4进行方向设定。如tips: 3则表示在元素的下面出现。有时你还可能会定义一些颜色，可以设定tips: [1, '#c00']
                    tipsMore: false, //是否允许多个tips 类型：Boolean，默认：false 允许多个意味着不会销毁之前的tips层。通过tipsMore: true开启
                    area: ['auto', 'auto'],
                    closeBtn:1,
                    time: 8000  //2秒后销毁
                });
            }
        </script>