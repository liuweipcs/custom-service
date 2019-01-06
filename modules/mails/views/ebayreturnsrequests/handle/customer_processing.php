<?php

use app\modules\mails\models\EbayInquiryResponse;
use app\modules\mails\models\MailTemplate;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\accounts\models\Platform;
use yii\helpers\Url;
use app\modules\orders\models\Order;
use yii\helpers\Html;
use kartik\select2\Select2;
?>
<style>
    p {
        margin: 0px 0px 5px;
        font-size: 13px;
    }

    .list-group {
        margin-bottom: 0px;
    }

    .list-group-item {
        padding: 5px 0px;
        font-size: 13px;
    }

    .table {
        margin-bottom: 10px;
    }

    .btn-sm {
        line-height: 1;
    }

    .mail_template_area a {
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
    .cart-number-box { position: relative; }
    .cart-number-box input { width: 60px; height: 27px; margin-left: 26px; text-align: center; }
    .cart-number-box input,
    .cart-number-box .up,
    .cart-number-box .down { border: 1px solid #aaa; }
    .cart-number-box .up,
    .cart-number-box .down { position: absolute; display: block; width: 27px; height: 27px; top: 8px; text-align: center; line-height: 23px; font-style: normal; cursor: pointer; }
    .cart-number-box .up { left: 93px; }
    .disabled { cursor: not-allowed; filter: alpha(opacity=65); -webkit-box-shadow: none; box-shadow: none; opacity: .65 }
</style>

<div class="panel panel-default">
    <div class="panel-heading">
        <h4 class="panel-title">纠纷详情&处理</h4>
    </div>
    <div id="collapseThree" class="panel-collapse">
        <div class="panel-body">
            <div class="col-xs-12">
                <p>
                    Return Id：<?php echo $model->return_id; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    状态：<?php echo $model->status; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    原因：<?php echo $model->return_reason; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                </p>
                <p>
                    买家期望：<?php echo $model->current_type; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    创建时间：<?php echo $model->return_creation_date; ?>
                </p>
                <p>
                    留言：<?php echo $model->return_comments; ?>
                </p>
                <p>
                    售后单号：<?php
                    $afterSalesOrders = AfterSalesOrder::find()->select('after_sale_id')->where(['order_id' => $info['info']['order_id']])->asArray()->all();
                    if (empty($afterSalesOrders))
                        echo '<span>无售后处理单</span>';
                    else
                        echo '<span>' . implode(',', array_column($afterSalesOrders, 'after_sale_id')) . '</span>';

                    if (!empty($info))
                        echo '<a style="margin-left:10px" _width="90%" _height="90%" class="edit-button" href="' . Url::toRoute(['/aftersales/order/add', 'order_id' => $info['info']['order_id'], 'platform' => Platform::PLATFORM_CODE_EB]) . '">新建售后单</a>';

                    if (!empty($info) && $info['info']['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP) {
                        echo '<a style="margin-left:10px" _width="30%" _height="60%" class="edit-button" href="' . Url::toRoute(['/orders/order/cancelorder', 'order_id' => $info['info']['order_id'], 'platform' => Platform::PLATFORM_CODE_EB]) . '">永久作废</a>';
                        echo '&nbsp;&nbsp;<a _width="30%" _height="60%" class="edit-button" href="' . Url::toRoute(['/orders/order/holdorder', 'order_id' => $info['info']['order_id'], 'platform' => Platform::PLATFORM_CODE_EB]) . '">暂时作废</a>';
                    }

                    if (!empty($info) && $info['info']['complete_status'] == Order::COMPLETE_STATUS_HOLD) {
                        echo '<a confirm="确定取消暂时作废该订单？" class="ajax-button" href="' . Url::toRoute(['/orders/order/cancelholdorder', 'order_id' => $info['info']['order_id'], 'platform' => Platform::PLATFORM_CODE_EB]) . '">取消暂时作废</a>';
                    }
                    ?>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    无需自动退款
                    <?php
                    switch ($model->auto_refund) {
                        case 0:
                            $auto_refund_after_case_attribute = '';
                            break;
                        case 1:
                            $auto_refund_after_case_attribute = 'checked="checked"';
                            break;
                        case 2:
                            $auto_refund_after_case_attribute = 'checked="checked"  disabled="disabled"';
                    }
                    ?>


                    <?php if ($model->state == 'CLOSED' || in_array($model->status, ['CLOSED', 'CLOSED_WITH_ESCALATION', 'CS_CLOSED'])) $auto_refund_after_case_attribute .= ' disabled="disabled"'; ?>


                    <input <?php echo $auto_refund_after_case_attribute; ?> type="checkbox" class="auto_refund_after_case" id="auto_refund"/>
                    <script type="application/javascript">
<?php if ($model->auto_refund != 2): ?>
                            $(function () {
                            var id = '<?php echo $model->id; ?>';
                            $('.auto_refund_after_case').click(function () {
                            //var auto_refund = $('.auto_refund_after_case_actual').val();
                            if($('#auto_refund').is(':checked')) {
                            var auto_refund = 1;
                            }else{
                            var auto_refund = 0;
                            }
                            $.post('<?php echo Url::toRoute(['/mails/ebayreturnsrequests/changeautorefund']); ?>', {
                            'id': id,
                            'auto_refund': auto_refund
                            }, function (data) {
                            switch (data.status) {
                            case 'error':
                            layer.msg(data.message, {
                            icon: 2,
                            time: 2000 //2秒关闭（如果不配置，默认是3秒）
                            });
                            return;
                            case 'success':
                            if (auto_refund == 1) {
                            $('.auto_refund_after_case_actual').val(0);
                            }
                            else {
                            $('.auto_refund_after_case_actual').val(1);
                            }
                            }
                            }, 'json');
                            });
                            });
<?php endif; ?>
                    </script>
                </p>
                <?php
                if (!empty($model->seller_address)) {
                    $sellerAddress = unserialize($model->seller_address);
                    if (!empty($sellerAddress)) {
                        $addressLine = isset($sellerAddress->address->addressLine1) ? $sellerAddress->address->addressLine1 : $sellerAddress->address->addressLine2;
                        echo '<p>卖家地址：' . $sellerAddress->name, $addressLine, $sellerAddress->address->city, '&nbsp;&nbsp;&nbsp;&nbsp;', $sellerAddress->address->postalCode, '</p>';
                    }
                }
                ?>

            </div>

            <!--互动记录-->
            <div class="col-xs-12">
                <?php if (!empty($detailModel)) { ?>
                    <?php
                    if (empty($inbox_info)) {
                        echo '<h5 class="m-b-30 m-t-0">往来信息未更新</h5>';
                    } else if ($inbox_info->inbox_subject_id == 0) {
                        echo '<h5 class="m-b-30 m-t-0">处理过程&nbsp;&nbsp;&nbsp;&nbsp;<a target="_blank" href="/mails/ebayinbox/detail?id=' . $inbox_info->id . '">查看邮件</a></h5>';
                    } else {
                        echo '<h5 class="m-b-30 m-t-0">处理过程&nbsp;&nbsp;&nbsp;&nbsp;<a target="_blank" href="/mails/ebayinboxsubject/detail?id=' . $inbox_info->inbox_subject_id . '">查看邮件</a></h5>';
                    }
                    ?>
                    <ul class="list-group" style="height: auto; max-height:280px;overflow-y:scroll;">
                        <?php foreach ($detailModel as $key => $detail) { ?>
                            <li class="list-group-item">
                                <?php
                                echo isset($detail->creation_date_value) ? date('Y-m-d H:i:s', strtotime($detail->creation_date_value) + 28800) : '', '&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#FF7F00">', $detail->author, '</span>&nbsp;&nbsp;&nbsp;&nbsp;', $detail->activity;
                                if ($detail->activity == 'BUYER_ACCEPTS_PARTIAL_REFUND' && $detail->to_state == 'PARTIAL_REFUND_FAILED')
                                    echo '&nbsp;&nbsp;<b style="color:red;">接受退款失败！</b>';
                                ?>

                                <?php if (!empty($detail->notes)) { ?>
                                    <table class="table table-bordered table_div_<?php echo $key; ?>">
                                        <tbody>
                                            <tr class="ebay_dispute_message_board">
                                                <td style="width:100px;text-align: center;">留言</td>
                                                <td><?php echo!empty($detail->notes) ? $detail->notes . '<a style="cursor: pointer;" data1 = "div_' . $key . '" data="' . $detail->notes . '" class="transClik">&nbsp;&nbsp;点击翻译</a>' : ""; ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                <?php } ?>
                            </li>
                        <?php } ?>
                        <?php
                        //图片
                        if ($model->has_image > 0) {
                            $returnImages = \app\modules\mails\models\EbayReturnImage::find()->select('creation_date,submitter,GROUP_CONCAT(file_path SEPARATOR "{$}") file_paths')->where(['return_id' => $model->return_id, 'file_status' => 'PUBLISHED'])->orderBy(['creation_date' => SORT_DESC])->groupBy('submitter')->asArray()->all();
                            foreach ($returnImages as $returnImage) {
                                echo '<li class="list-group-item"><div style="color:#C71585"><span>图片上传方：</span><span>' . $returnImage['submitter'] . '</span>&nbsp;<span>' . $returnImage['creation_date'] . '</span></div><div>';
                                $imagesPath = explode('{$}', $returnImage['file_paths']);
                                foreach ($imagesPath as $imagePath) {
                                    echo '<a href="/' . $imagePath . '" target="_blank"><img width="70px" height="70px" src="/' . $imagePath . '" alt=""></a>';
                                }
                                echo '</div></li>';
                            }
                        }
                        ?>
                    </ul>
                <?php } ?>
            </div>

            <!--客服处理-->
            <div class="col-xs-12">
                <?php if ($model->state != 'CLOSED'): ?>
                    <div class="popup-wrapper">
                        <?php
                        $responseModel = new \app\modules\mails\models\EbayReturnsRequestsResponse();
                        $form = yii\bootstrap\ActiveForm::begin([
                                    'id' => 'account-form',
                                    'layout' => 'horizontal',
                                    'action' => Yii::$app->request->getUrl(),
                                    'enableClientValidation' => false,
                                    'validateOnType' => false,
                                    'validateOnChange' => false,
                                    'validateOnSubmit' => true,
                        ]);
                        ?>
                        <div class="popup-body">
                            <input type="hidden" name="id" value="<?php echo Yii::$app->request->getQueryParam('id') ?>">
                            <table class="table table-striped table-bordered">
                                <tr>
                                    <td><input type="checkbox" id="all" class="all"></td>
                                    <td>SKU</td>
                                    <td>数量</td>
                                </tr>
                                <input type="hidden" value="" name="EbayReturnsRequestsResponse[sku_info]" id="sku_info">
                                <?php if (!empty($info['product'])) { ?>
                                    <?php foreach ($info['product'] as $item) { ?>
                                        <tr>
                                            <td>
                                                <input name=""
                                                       data-sku="<?= $item['sku'] ?>"
                                                       data-product_title="<?php
                                                       if (isset($item['picking_name']) && !empty($item['picking_name'])) {
                                                           echo $item['picking_name'];
                                                       } else {
                                                           echo '无';
                                                       }
                                                       ?>"
                                                       data-linelist_cn_name="<?php
                                                       if (isset($item['linelist_cn_name']) && !empty($item['linelist_cn_name'])) {
                                                           echo $item['linelist_cn_name'];
                                                       } else {
                                                           echo '无';
                                                       }
                                                       ?>"
                                                       data-issue_quantity="<?= $item['quantity'] ?>"
                                                       data-quantity="<?= $item['quantity'] ?>"
                                                       type="checkbox" class="sel "></td>
                                            <td><?= $item['sku'] ?></td>
                                            <td class="num cart-number-box">
                                                <input type="text" value="1" name="number" class="quantity_sku" data-min="1" data-max="5" data-step="1">
                                                <i class="up input-num-up">+</i>
                                                <i class="down input-num-down">-</i>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                <?php } ?>

                            </table>

                            <div class="row">
                                <input class="auto_refund_after_case_actual" type="hidden"
                                       name="EbayReturnsRequests[auto_refund]" value="<?= $model->auto_refund ?>"/>
                                <input type="hidden" name="EbayReturnsRequestsResponse[ship_cost]"
                                       value="<?= $info['info']['ship_cost'] ?>"/>
                                <input type="hidden" name="EbayReturnsRequestsResponse[subtotal_price]"
                                       value="<?= $info['info']['subtotal_price'] ?>"/>
                                <div>
                                    <input type="radio" name="EbayReturnsRequestsResponse[type]" value="2">全额退款
                                    <div class="type_map_params">
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <div class="form-group">
                                                    <div class="col-sm-3">
                                                        <label for="ship_name" class=" control-label required">责任归属部门：<span
                                                                class="text-danger">*</span></label>
                                                        <select name="EbayReturnsRequestsResponse[department_id][2]"
                                                                id="department_id" class="form-control"
                                                                size="12" multiple="multiple">
                                                        </select>
                                                    </div>
                                                    <div class="col-sm-9">
                                                        <label for="ship_name" class="control-label required">退款原因：<span
                                                                class="text-danger">*</span></label>
                                                        <select name="EbayReturnsRequestsResponse[reason_code][2]"
                                                                id="reason_id" class="form-control"
                                                                size="12" multiple="multiple">
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="order_id"
                                                   value="<?php if (!empty($info['info'])) echo $info['info']['order_id']; ?>">
                                        </div>
                                        <div class="row" style="margin-top: 5px;">
                                            <label for="ship_name" class="col-sm-2 control-label">原因备注：</label>
                                            <div class="col-sm-9">
                                                <textarea class="form-control" name="EbayReturnsRequestsResponse[remark][2]"
                                                          rows="2" cols="3"></textarea>
                                            </div>
                                        </div>

                                        <div class="row" style="margin-top: 5px;">
                                            <label for="ship_name" class="col-sm-2 control-label">退款金额：</label>
                                            <div class="col-sm-10 btn-group">
                                                <input class="form-control" style="color:red;width: 60px;float:left;"
                                                       readonly type="text" name="EbayReturnsRequestsResponse[currency]"
                                                       value="<?= $model->currency ?>">
                                                <input class="form-control" style="color:red;width: 150px;" readonly type="text"
                                                       name="EbayReturnsRequestsResponse[refund_amount][2]"
                                                       value="<?= $model->buyer_estimated_refund_amount ?>"/>
                                            </div>
                                        </div>
                                        <div class="row" style="margin-top: 5px;margin-bottom: 2px;">
                                            <label for="ship_name" class="col-sm-2 control-label">给客户留言：</label>
                                            <div class="col-sm-9">
                                                <textarea class="form-control"
                                                          name="EbayReturnsRequestsResponse[content][2]" rows="5"
                                                          cols="6"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($info['info']['payment_status'] == 0) { ?>
                                        <div>
                                            <input type="radio" name="EbayReturnsRequestsResponse[type]" value="4">标记退款
                                            <div class="type_map_params">                            
                                                <div class="row" style="margin-top: 5px;margin-bottom: 2px;">
                                                    <label for="ship_name" class="col-sm-2 control-label">给客户留言：</label>
                                                    <div class="col-sm-9">
                                                        <textarea class="form-control"
                                                                  name="EbayReturnsRequestsResponse[content][4]" rows="5"
                                                                  cols="6"></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>

                                    <div>
                                        <input type="radio" name="EbayReturnsRequestsResponse[type]" value="3">部分退款
                                        <div class="type_map_params">
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <div class="form-group">
                                                        <div class="col-sm-3">
                                                            <label for="ship_name"
                                                                   class=" control-label required">责任所属部门：<span
                                                                    class="text-danger">*</span></label>
                                                            <select name="EbayReturnsRequestsResponse[department_id][3]"
                                                                    id="department_ids" class="form-control"
                                                                    size="12" multiple="multiple">
                                                            </select>
                                                        </div>
                                                        <input type="hidden" name="order_id"
                                                               value="<?php if (!empty($info['info'])) echo $info['info']['order_id']; ?>">
                                                        <div class="col-sm-9">
                                                            <label for="ship_name" class="control-label required">原因类型：<span
                                                                    class="text-danger">*</span></label>
                                                            <select name="EbayReturnsRequestsResponse[reason_code][3]"
                                                                    id="reason_ids" class="form-control"
                                                                    size="12" multiple="multiple">
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row" style="margin-top: 5px;">
                                                <label for="ship_name" class="col-sm-2 control-label">原因备注：</label>
                                                <div class="col-sm-9">
                                                    <textarea class="form-control"
                                                              name="EbayReturnsRequestsResponse[remark][3]" rows="2"
                                                              cols="3"></textarea>
                                                </div>
                                            </div>
                                            <div class="row" style="margin-top: 5px;">
                                                <label for="ship_name" class="col-sm-2 control-label">退款金额：</label>
                                                <div class="col-sm-9">
                                                    <div class="col-sm-6" style="padding-left:0px;padding-right: 10px;">
                                                        <input class="form-control" type="text"
                                                               name="EbayReturnsRequestsResponse[refund_amount][3]"
                                                               step="0.01"/></div>
                                                    <div class="col-sm-6" style="line-height:35px;"><?= $model->currency ?>
                                                        (允许退款最大金额：<b
                                                            style="color:red;"><?= $model->buyer_estimated_refund_amount; ?></b>)
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row" style="margin-top: 5px;">
                                                <label for="ship_name" class="col-sm-2 control-label">给客户留言：</label>
                                                <div class="col-sm-9">
                                                    <textarea class="form-control"
                                                              name="EbayReturnsRequestsResponse[content][3]" rows="5"
                                                              cols="50"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                    <?php if ($model->state == 'RETURN_REQUESTED' || $model->status == 'RETURN_REQUESTED' || $model->current_type == 'REPLACEMENT'): ?>
                                        <div>
                                            <input type="radio" name="EbayReturnsRequestsResponse[type]" value="1"
                                                   onclick='location.href = location.href + "#section-3"'>发送留言
                                            <div class="type_map_params">
                                                <div style="margin-bottom: 10px">
                                                    <div class="row">
                                                        <div class="col-lg-5">
                                                            <div class="input-group">
                                                                <input type="text" class="mail_template_title_search_text"
                                                                       placeholder="模板编号搜索">
                                                                <button class="btn btn-sm btn-default mail_template_title_search_btn"
                                                                        type="button">Go!
                                                                </button>
                                                            </div>
                                                            <!--                                                        <button type="button" class="btn btn-sm btn-success" id="return_info">获取退货信息</button>-->
                                                            <?php
                                                            $warehouseList = \app\modules\orders\models\Warehouse::getWarehouseListAll();
                                                            $order_id = isset($info['orderPackage'][0]['order_id']) ? $info['orderPackage'][0]['order_id'] : '';
                                                            $warehouse_id = isset($info['orderPackage'][0]['warehouse_id']) ? $info['orderPackage'][0]['warehouse_id'] : 0;

                                                            $current_order_warehouse_name = array_key_exists($warehouse_id, $warehouseList) ?
                                                                    $warehouseList[$warehouse_id] : '';

                                                            echo "<input type='hidden' name='current_order_warehouse_id' value='$warehouse_id'>";
                                                            echo "<input type='hidden' name='current_order_id' value='$order_id'>";
                                                            echo "<input type='hidden' name='current_order_warehouse_name' value='$current_order_warehouse_name'>";
                                                            ?>

                                                        </div>
                                                        <div class="col-lg-5">
                                                            <div class="input-group">
                                                                <input type="text" class="mail_template_search_text"
                                                                       placeholder="消息模板搜索">
                                                                <a class="btn btn-default btn-sm mail_template_search_btn">搜索</a>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn btn-sm btn-success" id="return_info">获取退货信息</button>
                                                    </div>
                                                </div>
                                                <?php
                                                $mailTemplates = MailTemplate::getMailTemplateDataAsArrayByUserId(Platform::PLATFORM_CODE_EB);
                                                if (!empty($mailTemplates)) {
                                                    ?>
                                                    <div class="mail_template_area">
                                                        <?php
                                                        foreach ($mailTemplates as $mailTemplatesId => $mailTemplateName) {
                                                            echo '<a class="mail_template_unity" value="' . $mailTemplatesId . '">' . $mailTemplateName . '</a> ';
                                                        }
                                                        ?>
                                                    </div>
                                                <?php } ?>

                                                <?php echo Html::hiddenInput('sl_code', "", ['id' => 'sl_code']); ?>
                                                <?php echo Html::hiddenInput('tl_code', "", ['id' => 'tl_code']); ?>
                                                <div><textarea id='leave_message'
                                                               name="EbayReturnsRequestsResponse[content][1]" rows="4"
                                                               cols=98"></textarea></div>
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
                                                                            class="btn btn-default btn-sm dropdown-toggle"
                                                                            type="button" aria-expanded="false" id="sl_btn">
                                                                        更多&nbsp;&nbsp;<span class="caret"></span></button>
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
                                                    <div class="fa-hover col-sm-1" style="width:0px;line-height: 30px;">
                                                        <a><i class="fa fa-exchange"></i></a></div>
                                                    <div class="col-sm-5 tr_h">
                                                        <div class="btn-group">
                                                            <button class="btn btn-sm btn-default" type="button"
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
                                                                            class="btn btn-default btn-sm dropdown-toggle"
                                                                            type="button" aria-expanded="false" data=""
                                                                            id="tl_btn">更多&nbsp;&nbsp;<span
                                                                            class="caret"></span></button>
                                                                    <ul class="dropdown-menu language">
                                                                        <?php foreach ($googleLangCode as $key => $value) { ?>
                                                                            <li>
                                                                                <a onclick="changeCode(2, '<?php echo $key; ?>', '<?php echo $value; ?>', $(this))"><?php echo $value; ?></a>
                                                                            </li>
                                                                        <?php } ?>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-1">
                                                        <button class="btn btn-sm btn-primary artificialTranslation"
                                                                type="button" id="translations_btn">翻译 [ <b
                                                                id="sl_name"></b> - <b id="tl_name"></b> ]
                                                        </button>
                                                    </div>
                                                </div>
                                                <div><textarea id='leave_message_en'
                                                               name="EbayReturnsRequestsResponse[content][1_en]" rows="4"
                                                               cols="98"></textarea></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div>
                                        <input type="radio" checked="checked" name="data[file]" value="1">上传凭证
                                        <div class="type_map_p">
                                            <p style="color:red;">*注意：图片只能是JPG.JPEG.PNG格式。2M大小</p>
                                            <button class="upload_file" type="button">上传文件</button>
                                            <div class="upload_file_ares">

                                            </div>
                                        </div>
                                    </div>
                                    <script type="application/javascript">
                                        $(function () {
                                        $('[name="EbayReturnsRequestsResponse[type]"]').click(function () {
                                        $('.type_map_params').hide();
                                        $(this).siblings('.type_map_params').show();

                                        });

                                        });
                                    </script>
                                </div>
                            </div>
                            <div class="popup-footer">
                                <button class="btn btn-primary get_info ajax-submit"
                                        type="button"><?php echo Yii::t('system', 'Submit'); ?></button>
                                <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close'); ?></button>
                            </div>
                            <?php
                            yii\bootstrap\ActiveForm::end();
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="<?php echo yii\helpers\Url::base(true); ?>/js/jquery.form.js"></script>
<script>
                                                                                    $(document).ready(function ($) {
                                                                                        departmentList = <?php echo $departmentList ?>;
                                                                                        var rightHtml = "";
                                                                                        for (var i in departmentList) {
                                                                                            rightHtml += '<option value="' + departmentList[i].depart_id + '">' + departmentList[i].depart_name + '</option>' + "\n";
                                                                                        }
                                                                                        $('#department_id').empty().html(rightHtml);
                                                                                        $('#department_ids').empty().html(rightHtml);
                                                                                    });
                                                                                    //模板ajax
                                                                                    $('.mail_template_area').delegate('.mail_template_unity', 'click', function () {
                                                                                        $.post('<?php echo Url::toRoute(['/mails/msgcontent/gettemplate']); ?>', {'num': $(this).attr('value')}, function (data) {
                                                                                            switch (data.status) {
                                                                                                case 'error':
                                                                                                    layer.msg(data.message, {
                                                                                                        icon: 2,
                                                                                                        time: 2000 //2秒关闭（如果不配置，默认是3秒）
                                                                                                    });
                                                                                                    return;
                                                                                                case 'success':
                                                                                                    $('#leave_message').val(data.content);
//                        UE.getEditor('editor').setContent(data.content);
                                                                                            }
                                                                                        }, 'json');
                                                                                    });
                                                                                    //上传凭证
                                                                                    $('.upload_file').click(function () {
                                                                                        layer.open({
                                                                                            area: ['500px', '200px'],
                                                                                            type: 1,
                                                                                            title: '上传凭证',
                                                                                            content: '<form style="padding:10px 0px 0px 20px" action="<?php echo Url::toRoute('/mails/ebayreturnsrequests/uploadimage') ?>" method="post" id="upload_pop_file" enctype="multipart/form-data"><input type="file" name="upload_file"/></form>',
                                                                                            btn: '上传',
                                                                                            yes: function (index, layero) {
                                                                                                layero.find('#upload_pop_file').ajaxSubmit({
                                                                                                    dataType: 'json',
                                                                                                    beforeSubmit: function (options) {
                                                                                                        if (!/(jpg|png|jpeg)/ig.test(options[0].value.type)) {
                                                                                                            layer.msg('文件格式错误！', {
                                                                                                                icon: 2,
                                                                                                                time: 2000 //2秒关闭（如果不配置，默认是3秒）
                                                                                                            });
                                                                                                            return false;
                                                                                                        }
                                                                                                    },
                                                                                                    success: function (response) {
                                                                                                        switch (response.status) {
                                                                                                            case 'error':
                                                                                                                layer.msg(response.info, {
                                                                                                                    icon: 2,
                                                                                                                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                                                                                                                });
                                                                                                                break;
                                                                                                            case 'success':
                                                                                                                $('.upload_file_ares').append('<div class="upload_file_display" style="float:left;"><input hidden="hidden"  value="' + response.file_name + '"><img style="height:50px;width:50px;" src="' + response.url + '" ><a class="btn btn-primary upload_file_delete">删除</a>&nbsp;<a  class="btn btn-primary upload_file_images">上传凭证</a></div>');
                                                                                                                layer.close(index);
                                                                                                        }
                                                                                                    },
                                                                                                });
                                                                                            }
                                                                                        });
                                                                                    });
                                                                                    //删除图片
                                                                                    $('.upload_file_ares').delegate('.upload_file_delete', 'click', function () {
                                                                                        if (window.confirm('确定要删除？')) {
                                                                                            var $this = $(this);
                                                                                            var delteImageUrl = $this.siblings('img').attr('src');
                                                                                            $.post('<?php echo Url::toRoute('/mails/ebayreturnsrequests/deleteimage') ?>', {'url': delteImageUrl}, function (response) {
                                                                                                switch (response.status) {
                                                                                                    case 'error':
                                                                                                        layer.msg(response.info, {icon: 2, time: 2000});
                                                                                                        break;
                                                                                                    case 'success':
                                                                                                        layer.msg('删除成功', {icon: 1, time: 2000});
                                                                                                        $this.parent().remove();
                                                                                                }
                                                                                            }, 'json');
                                                                                        }
                                                                                    });
                                                                                    $('.upload_file_ares').delegate('.upload_file_images', 'click', function () {
                                                                                        if (window.confirm('确定要提交？')) {
                                                                                            var $this = $(this);
                                                                                            var id = "<?php echo $model->id; ?>";
                                                                                            var return_id = "<?php echo $model->return_id ?>";
                                                                                            var ImageUrl = $this.siblings('input').val();
                                                                                            $.post('<?php echo Url::toRoute('/mails/ebayreturnsrequests/uploadimages') ?>', {'image': ImageUrl, 'id': id, 'return_id': return_id, }, function (response) {
                                                                                                switch (response.status) {
                                                                                                    case 'error':
                                                                                                        layer.msg(response.info, {icon: 2, time: 2000});
                                                                                                        break;
                                                                                                    case 'success':
                                                                                                        layer.msg('请求成功', {icon: 1, time: 2000});
                                                                                                        $this.parent().remove();
                                                                                                }
                                                                                            }, 'json');
                                                                                            return false;
                                                                                        }
                                                                                    });

                                                                                    //模板搜索
                                                                                    $('.mail_template_search_btn').click(function () {
                                                                                        var templateName = $.trim($('.mail_template_search_text').val());
                                                                                        if (templateName.length == 0) {
                                                                                            layer.msg('搜索名称不能为空。', {
                                                                                                icon: 2,
                                                                                                time: 2000 //2秒关闭（如果不配置，默认是3秒）
                                                                                            });
                                                                                            return;
                                                                                        }
                                                                                        $.post('<?php echo Url::toRoute(['/mails/msgcontent/searchtemplate']); ?>', {'name': templateName}, function (data) {
                                                                                            switch (data.status) {
                                                                                                case 'error':
                                                                                                    layer.msg(data.message, {
                                                                                                        icon: 2,
                                                                                                        time: 2000 //2秒关闭（如果不配置，默认是3秒）
                                                                                                    });
                                                                                                    return;
                                                                                                case 'success':
                                                                                                    var templateHtml = '';
                                                                                                    for (var i in data.content) {
                                                                                                        templateHtml += '<a class="mail_template_unity" value="' + i + '">' + data.content[i] + '</a>';
                                                                                                    }
                                                                                                    $('.mail_template_area').html(templateHtml);
                                                                                            }
                                                                                        }, 'json');
                                                                                    });


                                                                                    //模板编号搜索
                                                                                    $('.mail_template_title_search_btn').on('click', template_title);
                                                                                    $('.mail_template_title_search_text').bind('keypress', function () {
                                                                                        if (event.keyCode == "13") {
                                                                                            template_title();
                                                                                        }
                                                                                    });

                                                                                    function template_title() {
                                                                                        var templateTitle = $.trim($('.mail_template_title_search_text').val());
                                                                                        if (templateTitle.length == 0) {
                                                                                            layer.msg('搜索内容不能为空。', {
                                                                                                icon: 2,
                                                                                                time: 2000 //2秒关闭（如果不配置，默认是3秒）
                                                                                            });
                                                                                            return;
                                                                                        }
                                                                                        $.post('<?php echo Url::toRoute(['/mails/msgcontent/searchtemplatetitle']); ?>', {
                                                                                            'name': templateTitle,
                                                                                            'platform_code': 'EB'
                                                                                        }, function (data) {
                                                                                            if (data.code == 200) {
                                                                                                $('#leave_message').val(data.data);
                                                                                            } else {
                                                                                                layer.msg(data.message, {
                                                                                                    icon: 2,
                                                                                                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                                                                                                });
                                                                                                return;
                                                                                            }
                                                                                        }, 'json');
                                                                                    }


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
                                                                                        var content = $.trim($("#leave_message").val());
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
                                                                                                    $("#leave_message_en").val(data);
                                                                                                }
                                                                                            }
                                                                                        });
                                                                                    });

                                                                                    /**
                                                                                     * 回复客户邮件内容点击翻译(系统检测到用户语言)
                                                                                     * @author allen <2018-1-11>
                                                                                     */
                                                                                    $(".transClik").click(function () {
                                                                                        var sl = 'auto';
                                                                                        var tl = 'en';
                                                                                        var message = $(this).attr('data');
                                                                                        var tag = $(this).attr('data1');
                                                                                        var that = $(this);
                                                                                        if (message.length == 0) {
                                                                                            layer.msg('获取需要翻译的内容有错!');
                                                                                            return false;
                                                                                        }

                                                                                        $.ajax({
                                                                                            type: "POST",
                                                                                            dataType: "JSON",
                                                                                            url: '<?php echo Url::toRoute(['ebayinboxsubject/translate']); ?>',
                                                                                            data: {'sl': sl, 'tl': tl, 'returnLang': 1, 'content': message},
                                                                                            success: function (data) {
                                                                                                if (data) {
                                                                                                    var htm = '<tr class="ebay_dispute_message_board ' + tag + '"><td style="text-align: center;"><b style="color:red;">' + data.code + '</b></td><td><b style="color:green;">' + data.text + '</b></td></tr>';
                                                                                                    $(".table_" + tag).append(htm);
                                                                                                    $("#sl_code").val('en');
                                                                                                    $("#sl_name").html('英语');
                                                                                                    $("#tl_code").val(data.googleCode);
                                                                                                    $("#tl_name").html(data.code);
                                                                                                    that.remove();
                                                                                                }
                                                                                            }
                                                                                        });
                                                                                    });


                                                                                    //切换责任归属部门获取对应原因
                                                                                    $(document).on("change", "#department_id", function () {
                                                                                        var id = $(this).val();
                                                                                        if (id) {
                                                                                            $.ajax({
                                                                                                type: "POST",
                                                                                                dataType: "JSON",
                                                                                                url: '<?php echo Url::toRoute(['/aftersales/refundreason/getnetleveldata']); ?>',
                                                                                                data: {'id': id},
                                                                                                success: function (data) {
                                                                                                    var html = "";
                                                                                                    if (data) {
                                                                                                        $.each(data, function (n, value) {
                                                                                                            html += '<option value=' + n + '>' + value + '</option>';
                                                                                                        });
                                                                                                    } else {
                                                                                                        html = '<option value="">---请选择---</option>';
                                                                                                    }
                                                                                                    $("#reason_id").empty();
                                                                                                    $("#reason_id").append(html);
                                                                                                }
                                                                                            });
                                                                                        } else {
                                                                                            $("#reason_id").empty();
                                                                                            $("#reason_id").append(html);
                                                                                        }
                                                                                    });

                                                                                    $(document).on("change", "#department_ids", function () {
                                                                                        var id = $(this).val();
                                                                                        if (id) {
                                                                                            $.ajax({
                                                                                                type: "POST",
                                                                                                dataType: "JSON",
                                                                                                url: '<?php echo Url::toRoute(['/aftersales/refundreason/getnetleveldata']); ?>',
                                                                                                data: {'id': id},
                                                                                                success: function (data) {
                                                                                                    var html = "";
                                                                                                    if (data) {
                                                                                                        $.each(data, function (n, value) {
                                                                                                            html += '<option value=' + n + '>' + value + '</option>';
                                                                                                        });
                                                                                                    } else {
                                                                                                        html = '<option value="">---请选择---</option>';
                                                                                                    }
                                                                                                    $("#reason_ids").empty();
                                                                                                    $("#reason_ids").append(html);
                                                                                                }
                                                                                            });
                                                                                        } else {
                                                                                            $("#reason_ids").empty();
                                                                                            $("#reason_ids").append(html);
                                                                                        }
                                                                                    });
                                                                                    var new_str = '';
                                                                                    //批量获取sku
                                                                                    $(".all").bind("click",
                                                                                            function () {
                                                                                                $(".sel").prop("checked", $(this).prop("checked"));
                                                                                            });
                                                                                    $(".sel").bind("click",
                                                                                            function () {
                                                                                                var $sel = $(".sel");
                                                                                                var b = true;
                                                                                                for (var i = 0; i < $sel.length; i++) {
                                                                                                    if ($sel[i].checked == false) {
                                                                                                        b = false;
                                                                                                        break;
                                                                                                    }
                                                                                                }
                                                                                                $(".all").prop("checked", b);
                                                                                            });
                                                                                    $(".sel ").click(function () {
                                                                                        //清空
                                                                                        new_str = "";
                                                                                        $(":checked.sel").each(function () {
                                                                                            if (new_str == '') {
                                                                                                if ($(this).prop('checked') == true) {
                                                                                                    new_str = $(this).data('sku') + '&' + $(this).data('product_title') + '&' + $(this).data('linelist_cn_name') + "&" + $(".quantity_sku").val();
                                                                                                }
                                                                                            } else {
                                                                                                if ($(this).prop('checked') == true) {
                                                                                                    new_str += ',' + $(this).data('sku') + '&' + $(this).data('product_title') + '&' + $(this).data('linelist_cn_name') + "&" + $(".quantity_sku").val();
                                                                                                }
                                                                                            }
                                                                                        });
                                                                                        $("#sku_info").val(new_str);
                                                                                        if ($("#sku_info").val() == '') {
                                                                                            layer.msg('选择sku', {icon: 5});
                                                                                            return;
                                                                                        }
                                                                                    });
                                                                                    $(".all").click(function () {
                                                                                        //清空
                                                                                        new_str = "";
                                                                                        $(":checked.sel").each(function () {
                                                                                            if (new_str == '') {
                                                                                                if ($(this).prop('checked') == true) {
                                                                                                    new_str = $(this).data('sku') + '&' + $(this).data('product_title') + '&' + $(this).data('linelist_cn_name') + "&" + $(".quantity_sku").val();
                                                                                                }
                                                                                            } else {
                                                                                                if ($(this).prop('checked') == true) {
                                                                                                    new_str += ';' + $(this).data('sku') + '&' + $(this).data('product_title') + '&' + $(this).data('linelist_cn_name') + "&" + $(".quantity_sku").val();
                                                                                                }
                                                                                            }
                                                                                        });
                                                                                        $("#sku_info").val(new_str);
                                                                                        if ($("#sku_info").val() == '') {
                                                                                            layer.msg('选择sku', {icon: 5});
                                                                                            return;
                                                                                        }
                                                                                    });


                                                                                    $('.input-num-up').click(function () {
                                                                                        upDownOperation($(this));
                                                                                    });
                                                                                    $('.input-num-down').click(function () {
                                                                                        upDownOperation($(this));
                                                                                    });
                                                                                    function upDownOperation(element)
                                                                                    {
                                                                                        var _input = element.parent().find('input'),
                                                                                                _value = _input.val(),
                                                                                                _step = _input.attr('data-step') || 1;
                                                                                        //检测当前操作的元素是否有disabled，有则去除
                                                                                        element.hasClass('disabled') && element.removeClass('disabled');
                                                                                        //检测当前操作的元素是否是操作的添加按钮（.input-num-up）‘是’ 则为加操作，‘否’ 则为减操作
                                                                                        if (element.hasClass('input-num-up'))
                                                                                        {
                                                                                            var _new_value = parseInt(parseFloat(_value) + parseFloat(_step)),
                                                                                                    _max = _input.attr('data-max') || false,
                                                                                                    _down = element.parent().find('.input-num-down');

                                                                                            //若执行‘加’操作且‘减’按钮存在class='disabled'的话，则移除‘减’操作按钮的class 'disabled'
                                                                                            _down.hasClass('disabled') && _down.removeClass('disabled');
                                                                                            if (_max && _new_value >= _max) {
                                                                                                _new_value = _max;
                                                                                                element.addClass('disabled');
                                                                                            }
                                                                                        } else {
                                                                                            var _new_value = parseInt(parseFloat(_value) - parseFloat(_step)),
                                                                                                    _min = _input.attr('data-min') || false,
                                                                                                    _up = element.parent().find('.input-num-up');
                                                                                            //若执行‘减’操作且‘加’按钮存在class='disabled'的话，则移除‘加’操作按钮的class 'disabled'
                                                                                            _up.hasClass('disabled') && _up.removeClass('disabled');
                                                                                            if (_min && _new_value <= _min) {
                                                                                                _new_value = _min;
                                                                                                element.addClass('disabled');
                                                                                            }
                                                                                        }
                                                                                        _input.val(_new_value);
                                                                                    }
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
                                                                                                                var old_content = $('#leave_message').val();
                                                                                                                if (old_content !== '') {
                                                                                                                    $('#leave_message').val(html + '\n' + old_content);
                                                                                                                } else {
                                                                                                                    $('#leave_message').val(html + '\n' + old_content);
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
                                                                                                            var old_content = $('#leave_message_en').val();
                                                                                                            if (old_content !== '') {
                                                                                                                $('#leave_message_en').val(html + '\n' + old_content);
                                                                                                            } else {
                                                                                                                $('#leave_message_en').val(html + '\n' + old_content);
                                                                                                            }
                                                                                                            $(this).attr('disabled', true);
                                                                                                    }

                                                                                                }
                                                                                            });

                                                                                        }
                                                                                    });
</script>