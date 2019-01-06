<?php
use \app\modules\mails\models\EbayInquiryResponse;
use app\modules\accounts\models\Platform;
use app\modules\aftersales\models\AfterSalesOrder;
use yii\helpers\Url;
use \app\modules\mails\models\MailTemplate;
use \app\modules\orders\models\Order;
use yii\helpers\Html;
?>
<style type="text/css">
    .type_map_params{
        display: none;
    }
    .ebay_dispute_message_board
    {
        background: #F1F6FC;
    }
    #remarkTable tr td{width: 250px;}
    
    .language {width:900px;float: left;}
    .language li{width:12%;float:left;}
    .language li a{font-size: 10px; text-align: left;cursor: pointer;}
</style>

<div class="popup-wrapper">
    <div class="popup-body">
        <ul class="nav nav-tabs">
            <li class="active"><a data-toggle="tab" href="#home">纠纷处理</a></li>
            <li><a data-toggle="tab" href="#menu1">基本信息</a></li>
            <li><a data-toggle="tab" href="#menu2">产品信息</a></li>
            <li><a data-toggle="tab" href="#menu3">交易信息</a></li>
            <li><a data-toggle="tab" href="#menu4">包裹信息</a></li>
            <li><a data-toggle="tab" href="#menu5">利润信息</a></li>
            <li><a data-toggle="tab" href="#menu6">仓储物流</a></li>
        </ul>

        <div class="tab-content">
            <div id="home" class="tab-pane fade in active">
                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>Inquiry Id</th>
                        <th><?=$model->inquiry_id?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td><?=$model->attributeLabels()['status']?></td>
                        <td><?php echo $model->status;?></td>
                    </tr>
                    <tr>
                        <td><?=$model->attributeLabels()['buyer_initial_expected_resolution']?></td>
                        <td><?php echo $model->buyer_initial_expected_resolution;?></td>
                    </tr>
                    <tr>
                        <td><?=$model->attributeLabels()['creation_date']?></td>
                        <td><?php echo $model->creation_date;?></td>
                    </tr>
                    <tr>
                        <td>售后单号</td>
                        <td>
                            <?php
                            if(isset($info['info']) && !empty($info['info'])){
                            $afterSalesOrders = AfterSalesOrder::find()->select('after_sale_id')->where(['order_id'=>$info['info']['order_id']])->asArray()->all();
                                if(empty($afterSalesOrders)){
                                    echo '<span>无售后处理单</span>';
                                }else{
                                    echo '<span>'.implode(',',array_column($afterSalesOrders,'after_sale_id')).'</span>';
                                }
                            }else{
                                echo '<span>无售后处理单</span>';
                            }
                            if(!empty($info))
                                echo '<a style="margin-left:10px" _width="90%" _height="90%" class="edit-button" href="'.Url::toRoute(['/aftersales/order/add','order_id'=>$info['info']['order_id'],'platform'=>Platform::PLATFORM_CODE_EB]).'">新建售后单</a>';

                            if(!empty($info) && $info['info']['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP)
                            {
                                echo '<a style="margin-left:10px" _width="30%" _height="60%" class="edit-button" href="'.Url::toRoute(['/orders/order/cancelorder','order_id'=>$info['info']['order_id'],'platform'=>Platform::PLATFORM_CODE_EB]).'">永久作废</a>';
                                echo '<a style="margin-left:10px" confirm="确定暂时作废该订单？" class="ajax-button" href="'.Url::toRoute(['/orders/order/holdorder','order_id'=>$info['info']['order_id'],'platform'=>Platform::PLATFORM_CODE_EB]).'">暂时作废</a>';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php if( $model->state != 'CLOSED' && !in_array($model->status,['CLOSED','CLOSED_WITH_ESCALATION','CS_CLOSED']) ):?>
                    <tr>
                        <td>无需自动退款</td>
                        <?php
                        switch($model->auto_refund)
                        {
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
                        <td><input <?php echo $auto_refund_after_case_attribute;?> type="checkbox" class="auto_refund_after_case"/></td>
                    </tr>
                    <script type="application/javascript">
                        <?php if($model->auto_refund != 2):?>
                        $(function(){
                            $('.auto_refund_after_case').click(function(){
                                $('.auto_refund_after_case_actual').val(Number(this.checked));
                            });
                        });
                        <?php endif;?>
                    </script>
                    <?php endif;?>
                    </tbody>
                </table>
                <?php if(!empty($detailModel)):?>
                    处理过程
                <ul class="list-group">
                    <?php foreach($detailModel as $key => $detail):?>
                        <li class="list-group-item">
                            <?php echo isset($detail->date) ? date('Y-m-d H:i:s',strtotime($detail->date)+28800):'','&nbsp;&nbsp;&nbsp;&nbsp;','<span style="color:#FF7F00">',$detail::$actorMap[$detail->actor],'</span>','&nbsp;&nbsp;&nbsp;&nbsp;',$detail->action;?>
                            <table class="table table-bordered table_div_<?php echo $key;?>">
                                <tbody>
                                <tr class="ebay_dispute_message_board">
                                    <td style="width:100px;text-align: center;">留言</td>
                                    <td><?php echo !empty($detail->description) ? $detail->description.'<a style="cursor: pointer;" data1 = "div_'.$key.'" data="'.$detail->description.'" class="transClik">&nbsp;&nbsp;点击翻译</a>' : "";?></td>
                                </tr>
                                </tbody>
                            </table>
                        </li>
                    <?php endforeach;?>
                </ul>
                <?php endif;?>

                <?php
                $item_id = $model->item_id;
                $account_id = $model->account_id;
                $buyer_id = $model->buyer;

                $subject_model = \app\modules\mails\models\EbayInboxSubject::findOne(['buyer_id'=>$buyer_id,'item_id'=>$item_id,'account_id'=>$account_id]);
                ?>

                <dl class="dl-horizontal">
                    <dt style="width:100px;">ebay message</dt>
                    <?php
                    if($subject_model)
                    {
                        echo '<dd><a href="/mails/ebayinboxsubject/detail?id='.$subject_model->id.'" target="_blank">'.$subject_model->first_subject.'</a></dd>';
                    }
                    else
                    {
                        echo '<dd style="width:70px;">无</dd>';
                    }
                    ?>
                </dl>

                <?php if( $model->state != 'CLOSED' && !in_array($model->status,['CLOSED','CLOSED_WITH_ESCALATION','CS_CLOSED']) ):?>
                    <div class="popup-wrapper">
                        <?php
                        $responseModel = new EbayInquiryResponse();
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
                        <link href="<?php echo yii\helpers\Url::base(true);?>/laydate/need/laydate.css" rel="stylesheet">
                        <link href="<?php echo yii\helpers\Url::base(true);?>/laydate/skins/default/laydate.css" rel="stylesheet">
                        <script src="<?php echo yii\helpers\Url::base(true);?>/laydate/laydate.js"></script>
                        <div class="popup-body">
                            <div class="row">
                                <input class="auto_refund_after_case_actual" type="hidden" name="EbayInquiry[auto_refund]" value="<?=$model->auto_refund?>"/>
                                <div>
                                    <input type="radio" name="EbayInquiryResponse[type]" value="2" >全额退款
                                    <div class="type_map_params">
                                        <input type="hidden" name="order_id" value="<?php if(!empty($info['info'])) echo $info['info']['order_id'];?>">
                                        退款原因：
                                        <select name="EbayInquiryResponse[reason_code]">
                                            <?php foreach($reasonCode as $key =>$value){?>
                                                <option value="<?=$value->id?>"><?=$value->content?></option>
                                            <?php }?>
                                        </select>
                                        <br/>
                                        <textarea name="EbayInquiryResponse[content][2]" rows="5" cols="50"></textarea>
                                    </div>
                                </div>
                                <div>
                                    <input type="radio" name="EbayInquiryResponse[type]" value="3" >提供发货信息
                                    <div class="type_map_params">
                                        承运人：<input type="text" name="EbayInquiryResponse[shipping_carrier_name]"/>
                                        发货时间：<input class="laydate-icon" id="ebay_inquiry_history_shipping_date" value="" name="EbayInquiryResponse[shipping_date]"/>
                                        跟踪号：<input type="text" name="EbayInquiryResponse[tracking_number]">
                                        <br/>
                                        <textarea name="EbayInquiryResponse[content][3]" rows="5" cols="50"></textarea>
                                    </div>
                                </div>
                                <div>
                                    <input type="radio" name="EbayInquiryResponse[type]" value="4" >升级
                                    <div class="type_map_params">
                                        原因：<select class="form-control" name="EbayInquiryResponse[escalation_reason]">
                                            <?php foreach(EbayInquiryResponse::$escalationReasonMap as $escalationReasonK=>$escalationReasonV):?>
                                                <option value="<?=$escalationReasonK?>"><?=$escalationReasonV?></option>
                                            <?php endforeach;?>
                                            </select>
                                        <textarea name="EbayInquiryResponse[content][4]" rows="5" cols="50"></textarea>
                                    </div>
                                </div>
                                <div>
                                    <input type="radio" name="EbayInquiryResponse[type]" value="1" >发送留言
                                    <div class="type_map_params">
                                        <div style="margin-bottom: 10px">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="input-group">
                                                        <input type="text" class="form-control mail_template_title_search_text" placeholder="模板编号搜索">
                                                        <span class="input-group-btn">
                                        <button class="btn btn-default mail_template_title_search_btn" type="button">Go!</button>
                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <br />
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="input-group">
                                                        <input type="text" class="form-control mail_template_search_text" placeholder="消息模板搜索">
                                                        <span class="input-group-btn">
                                                            <a class="btn btn-default mail_template_search_btn" >搜索</a>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="panel panel-default">
                                            <div class="mail_template_area panel-body">
                                                <?php
                                                $mailTemplates = MailTemplate::getMailTemplateDataAsArrayByUserId(Platform::PLATFORM_CODE_EB);
                                                foreach ($mailTemplates as $mailTemplatesId => $mailTemplateName)
                                                {
                                                    echo '<a class="mail_template_unity" value="'.$mailTemplatesId.'">'.$mailTemplateName.'</a> ';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        
                                        <?php echo Html::hiddenInput('sl_code',"",['id'=>'sl_code']);?>
                                        <?php echo Html::hiddenInput('tl_code',"",['id'=>'tl_code']);?>
                                        <div><textarea id='leave_message' name="EbayInquiryResponse[content][1]" rows="6" cols="180"></textarea></div>
                                        <div class="row col-sm-12" style="text-align: center;font-size: 13px;font-weight: bold;margin-top: 10px;margin-bottom: 10px;">
                                            <div class="col-sm-2"></div>
                                                <div class="col-sm-2">
                                                       <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-default" type="button" onclick="changeCode(3,'en','',$(this))">英语</button>
                                                    <button class="btn btn-default" type="button" onclick="changeCode(3,'fr','',$(this))">法语</button>
                                                    <button class="btn btn-default" type="button" onclick="changeCode(3,'de','',$(this))">德语</button>
                                                    <?php if(is_array($googleLangCode) && !empty($googleLangCode)){?>
                                                    <div class="btn-group">
                                                      <button data-toggle="dropdown" class="btn btn-default btn-sm dropdown-toggle" type="button" aria-expanded="false" id="sl_btn">更多&nbsp;&nbsp;<span class="caret"></span> </button>
                                                      <ul class="dropdown-menu language">
                                                        <?php foreach ($googleLangCode as $key => $value) { ?>
                                                                <li><a onclick="changeCode(1,'<?php echo $key;?>','<?php echo $value;?>',$(this))"><?php echo $value;?></a></li>        
                                                        <?php } ?>
                                                      </ul>
                                                    </div>
                                                    <?php } ?>
                                                  </div>
                                                </div>
                                               <div class="fa-hover col-sm-1" style="width:0px;line-height: 30px;"><a><i class="fa fa-exchange"></i></a></div>
                                               <div class="col-sm-2">
                                                   <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-default" type="button" onclick="changeCode(4,'en','',$(this))">英语</button>
                                                    <button class="btn btn-default" type="button" onclick="changeCode(4,'fr','',$(this))">法语</button>
                                                    <button class="btn btn-default" type="button" onclick="changeCode(4,'de','',$(this))">德语</button>
                                                    <?php if(is_array($googleLangCode) && !empty($googleLangCode)){?>
                                                    <div class="btn-group">
                                                      <button data-toggle="dropdown" class="btn btn-default btn-sm dropdown-toggle" type="button" aria-expanded="false" data="" id="tl_btn">更多&nbsp;&nbsp;<span class="caret"></span> </button>
                                                      <ul class="dropdown-menu language">
                                                        <?php foreach ($googleLangCode as $key => $value) { ?>
                                                          <li><a onclick="changeCode(2,'<?php echo $key;?>','<?php echo $value;?>',$(this))"><?php echo $value;?></a></li>              
                                                        <?php } ?>
                                                       </li>
                                                     </ul>
                                                    </div>
                                                    <?php } ?>
                                                  </div>
                                                </div>
                                               <div class="col-sm-1"><button class="btn btn-sm btn-primary artificialTranslation" type="button" id="translations_btn">翻译 [ <b id="sl_name"></b> - <b id="tl_name"></b> ] </button></div>
                                       </div>    
                                        <div><textarea id='leave_message_en' name="EbayInquiryResponse[content][1_en]" rows="6" cols="180"></textarea></div>
                                    </div>
                                </div>
                                <script>
                                    void function(){
                                        laydate({
                                            elem: '#ebay_inquiry_history_shipping_date',
                                            format: 'YYYY/MM/DD hh:mm:ss',
                                        })
                                        $(function(){
                                            $('[name="EbayInquiryResponse[type]"]').click(function(){
                                                $('.type_map_params').hide();
                                                $(this).siblings('.type_map_params').show();
                                            });
                                        });
                                    }();
                                </script>
                            </div>
                        </div>
                        <div class="popup-footer">
                            <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit');?></button>
                            <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close');?></button>
                        </div>
                        <?php
                        yii\bootstrap\ActiveForm::end();
                        ?>
                    </div>
                <?php endif;?>
            </div>
            <div id="menu1" class="tab-pane fade">
                <table class="table">
                    </thead>
                    <tbody id="basic_info">
                    <?php if(!empty($info['info'])){?>
                        <?php
                        $account_info = \app\modules\accounts\models\Account::getHistoryAccountInfo($info['info']['account_id'],$info['info']['platform_code']);
                        ?>
                        <tr><td>订单号</td><td><?php echo isset($account_info->account_short_name) ? $account_info->account_short_name.'-'.$info['info']['order_id'] : $info['info']['order_id'];?></td><td>销售平台</td><td><?php echo $info['info']['platform_code'];?></td></tr>
                        <tr><td>平台订单号</td><td><?php echo $info['info']['platform_order_id'];?></td><td>买家ID</td><td><?php echo $info['info']['buyer_id'];?></td></tr>
                        <tr>
                            <td>下单时间</td>
                            <td><?php echo $info['info']['created_time'];?></td>
                            <td>付款时间</td><td><?php echo $info['info']['paytime'];?></td></tr>
                        <tr><td>运费</td><td><?php echo $info['info']['ship_cost'] .'('. $info['info']['currency'].')';?></td><td>总费用</td><td><?php echo $info['info']['total_price'] .'('. $info['info']['currency'].')';?></td></tr>
                        <tr><td>eBay账号</td><td><?php echo $accountName?></td><td>送货地址</td><td colspan="3" >
                                <?php echo $info['info']['ship_name'];?>
                                (tel:<?php echo $info['info']['ship_phone'];?>)<br>
                                <?php echo $info['info']['ship_street1'] . ',' . ($info['info']['ship_street2'] == '' ? '' : $info['info']['ship_street2'] . ',') . $info['info']['ship_city_name'];?>,
                                <?php echo $info['info']['ship_stateorprovince'];?>,
                                <?php echo $info['info']['ship_zip'];?>,<br/>
                                <?php echo $info['info']['ship_country_name'];?>
                            </td>
                        </tr>
                        <tr><td>客户email</td><td><?php echo $info['info']['email'];?></td><td><a class="edit-button" href="/mails/ebayreply/initiativeadd?order_id=<?php echo $info['info']['order_id'];?>&platform=EB">发送消息</a></td></tr>
                         <?php if(Platform::PLATFORM_CODE_EB == 'EB'):?>
                                <tr><td>客户留言</td><td colspan="3"><?php if(!empty($info['note']))echo $info['note']['note']?></td>
                                </tr>
                            <tr><td>订单状态</td><td colspan="3">
                                    <?php
                                    $complete_status = Order::getOrderCompleteStatus();
                                    echo $complete_status[$info['info']['complete_status']];
                                    ?>
                                </td>
                            </tr>
                                <tr>
                                <td id='remarkTable' colspan="4">
                                    <?php if(!empty($info['remark'])):?>
                                        <table style="width:100%;">
                                        <?php foreach ($info['remark'] as $key => $value):?>
                                            <tr>
                                                <td style="width:80%;"><?php echo nl2br(strip_tags($value['remark']));?></td>
                                                <td><?=$value['create_user']?></td>
                                                <td><?=$value['create_time']?></td>
                                                <td><a href="javascript:;" onclick="removeRemark(<?php echo $value['id'];?>)">删除</a></td>
                                            </tr>
                                        <?php endforeach;?>
                                        </table>
                                    <?php endif;?>
                                        
                                </td>
                                
                                </tr>
                                <tr><td>订单备注</td>
                                    <td colspan="3"><textarea style="width:360px;height:80px;" class="remark"></textarea>
                                        <button onclick=saveRemark("<?php echo $info['info']['order_id'];?>")>添加备注</button><input class="detail_order_id" type="hidden" value="<?php echo $info['info']['order_id'];?>"/>
                                    </td>
                                </tr>
                                <input type="hidden" class="platform_code" value="<?php echo $info['info']['platform_code']?>">
                                <tr><td>出货备注</td>
                                    <td colspan="3"><textarea style="width:360px;height:80px;" class="print_remark"><?php echo $info['info']['print_remark']?></textarea>
                                        <button onclick=save_print_remark("<?php echo $info['info']['order_id'];?>")>添加发货备注</button><input class="detail_order_id" type="hidden" value="<?php echo $info['info']['order_id'];?>"/>
                                    </td>
                                </tr>
                            <?php endif;?>


                    <?php }else{?>
                        <tr><td colspan="2" align="center">没有找到信息！</td></tr>
                    <?php }?>
                    </tbody>
                </table>
            </div>
            <div id="menu2" class="tab-pane fade">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>标题</th>
                        <th>绑定产品sku</th>
                        <th>数量</th>
                        <th>产品sku</th>
                        <th>数量</th>
                        <th>平台卖价</th>
                        <th>总运费</th>
                        <th>欠货数量</th>
                        <th>库存</th>
                        <th>在途数</th>
                        <th>缩略图</th>
                        <th>总计</th>
                    </tr>
                    </thead>
                    <tbody id="product">
                    <?php if(!empty($info['product'])){?>
                        <?php foreach ($info['product'] as $value){?>
                            <tr>
                                <td style="width: 50%">
                                    <a href="<?php echo 'http://www.ebay.com/itm/'.$value['item_id'];?>" target="_blank"><?php echo $value['title'];?>&nbsp;(item_number:<?php echo $value['item_id'];?>)</a></td>
                                <td rowspan="2"><?php echo $value['sku_old'];?></td>
                                <td rowspan="2"><?php echo $value['quantity_old'];?></td>
                                <td rowspan="2"><?php echo $value['sku'];?></td>
                                <td rowspan="2"><?php echo $value['quantity'];?></td>
                                <td rowspan="2"><?php echo $value['sale_price'];?></td>
                                <td rowspan="2"><?php echo $value['ship_price'];?></td>
                                <td rowspan="2"><?php echo $value['qs'];?></td>
                                <td rowspan="2"><?php echo $value['stock'];?></td>
                                <td rowspan="2"><?php echo $value['on_way_stock'];?></td>
                                <td rowspan="2" ><img style="border:1px solid #ccc;padding:2px;width:60px;height:60px;" src="<?php echo Order::getProductImageThub($value['sku']);?>" alt="<?php echo $value['sku']?>" /></td>
                                <td rowspan="2"><?php echo $value['total_price'];?></td>
                            </tr>
                            <tr>
                                <td bgcolor="#F8F8F8" valign="<?php echo $value['picking_name'];?>" class="p-picking-name"><?php echo $value['picking_name']?>&nbsp;(sku:<?php echo $value['sku'];?>)</td>
                            </tr>
                        <?php }?>
                    <?php }else{?>
                        <tr><td colspan="6" align="center">没有找到信息！</td></tr>
                    <?php }?>
                    </tbody>
                </table>
            </div>
            <div id="menu3" class="tab-pane fade">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>交易号</th>
                        <th>付款帐号</th>
                        <th>收款帐号</th>
                        <th>交易时间</th>
                        <th>交易类型</th>
                        <th>交易状态</th>
                        <th>交易金额</th>
                        <th>手续费</th>
                    </tr>
                    </thead>
                    <tbody id="trade">
                    <?php if(!empty($info['trade'])){?>
                        <?php foreach ($info['trade'] as $value){?>
                            <tr>
                                <td><?php echo $value['transaction_id'];?></td>
                                <td><?php echo $value['payer_email'];?></td>
                                <td><?php echo $value['receiver_business'];?></td>
                                <td><?php echo $value['order_pay_time'];?></td>
                                <td><?php echo $value['receive_type'];?></td>
                                <td><?php echo $value['payment_status'];?></td>
                                <td><?php echo $value['amt'];?>(<?php echo $value['currency'];?>)</td>
                                <td><?php echo $value['fee_amt'];?></td>
                            </tr>
                        <?php }?>
                    <?php }else{?>
                        <tr><td colspan="6" align="center">没有找到信息！</td></tr>
                    <?php }?>
                    </tbody>
                </table>
            </div>
            <div id="menu4" class="tab-pane fade">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>包裹号</th>
                        <th>发货仓库</th>
                        <th>运输方式</th>
                        <th>追踪号</th>
                        <th>总运费</th>
                        <th>出货时间</th>
                        <th>重量</th>
                        <th>产品</th>
                    </tr>
                    </thead>
                    <tbody id="package">
                    <?php if(!empty($info['orderPackage'])){?>
                        <?php foreach ($info['orderPackage'] as $value){?>
                            <tr>
                                <td><?php echo $value['package_id'];?></td>
                                <td><?php echo $value['warehouse_name'];?></td>
                                <td><?php echo $value['ship_name'];?></td>
                                <td><?php
                                    if (!empty($value['tracking_number_1'])) {
                                        echo "<a target=\"_blank\" href='http://www.17track.net/zh-cn/track?nums=" . $value['tracking_number_1'] . "' title='物流商实际追踪号'>".$value['tracking_number_1'] ."</a>";
                                    }else{
                                        echo "<a target=\"_blank\" href='http://www.17track.net/zh-cn/track?nums=" . $value['tracking_number_2' ] . "' title='代理商追踪号'>".$value['tracking_number_2'] ."</a>";
                                    }
                                    ?>
                                </td>
                                <td><?php echo $value['shipping_fee'];?></td>
                                <td><?php echo $value['shipped_date'];?></td>
                                <td><?php echo $value['package_weight'];?></td>
                                <td>
                                    <?php foreach ($value['items'] as $sonvalue){?>
                                        <p>sku：<?php echo $sonvalue['sku'];?> 数量：<?php echo $sonvalue['quantity'];?></p>
                                    <?php }?>
                                </td>
                            </tr>
                        <?php }?>
                    <?php }else{?>
                        <tr><td colspan="7" align="center">没有找到信息！</td></tr>
                    <?php }?>
                    </tbody>
                </table>
            </div>
            <div id="menu5" class="tab-pane fade">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th colspan="3">收入</th>
                        <th colspan="8">成本/支出</th>
                        <th >利润</th>
                        <th>利润率</th>
                    </tr>
                    </thead>
                    <tbody id="profit_id">
                    <tr>
                        <td style="color: green;">产品金额</td>
                        <td style="color: green;">运费</td>
                        <td style="color: green;">调整金额</td>
                        <td style="color: red;">平台佣金</td>
                        <td style="color: red;">交易佣金</td>
                        <td style="color: red;">货物成本</td>
                        <td style="color: red;">包装成本</td>
                        <td style="color: red;">包材成本</td>
                        <td style="color: red;">运费成本</td>
                        <td style="color: red;">退款金额</td>
                        <td style="color: red;">重寄费用</td>
                        <?php if(!empty($info['profit'])){?>
                            <td rowspan="3">
                                <?php echo $info['profit']['profit'] >= 0 ? '<font color="green">' . $info['profit']['profit']. '(CNY)</font>'
                                    : '<font color="red">' . $info['profit']['profit'] . '(CNY)</font>';?>
                            </td>
                            <td rowspan="3">
                                <?php echo $info['profit']['profit_rate'] >= 0 ? '<font color="green">' . $info['profit']['profit_rate'] . '%</font>'
                                    : '<font color="red">' . $info['profit']['profit_rate'] . '%</font>';?>
                            </td>
                        <?php }; ?>
                    </tr>
                    <?php if(!empty($info['profit'])){?>
                        <tr>
                            <td><?php echo $info['profit']['product_price'];?>(CNY)</td>
                            <td><?php echo $info['profit']['shipping_price'];?>(CNY)</td>
                            <td><?php echo $info['profit']['adjust_amount'];?>(CNY)</td>
                            <td><?php echo $info['profit']['final_value_fee'];?>(CNY)</td>
                            <td><?php echo $info['profit']['pay_cost'];?>(CNY)</td>
                            <td><?php echo $info['profit']['purchase_cost'];?>(CNY)</td>
                            <td><?php echo $info['profit']['package_cost'];?>(CNY)</td>
                            <td><?php echo $info['profit']['packing_cost'];?>(CNY)</td>
                            <td><?php echo $info['profit']['shipping_cost'];?>(CNY)</td>
                            <td><?php echo $info['profit']['refund_amount'];?>(CNY)</td>
                            <td><?php echo $info['profit']['redirect_cost'];?>(CNY)</td>
                        </tr>
                        <?php
                        $totalRevnue = 0;
                        $totalCost = 0;
                        if (!empty($info['profit']))
                        {
                            $totalRevnue += $info['profit']['product_price'] + $info['profit']['shipping_price'] + $info['profit']['adjust_amount'];
                            $totalCost += $info['profit']['purchase_cost'] + $info['profit']['final_value_fee'] + $info['profit']['shipping_cost'] + $info['profit']['pay_cost']
                                + $info['profit']['refund_amount'] + $info['profit']['redirect_cost'] + $info['profit']['packing_cost'] + $info['profit']['package_cost'];
                        }
                        ?>
                        <tr>
                            <td colspan="3" align="center" style="color: green"><?php echo $totalRevnue;?>(CNY)</td>
                            <td colspan="8" align="center" style="color: red"><?php echo $totalCost;?>(CNY)</td>
                        </tr>
                        <tr>
                            <td colspan="13"><strong>汇率值：</strong><?php echo $info['profit']['currency_rate'];?>&nbsp;&nbsp;
                                (<?php echo substr($info['profit']['create_time'], 0, 10);?>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $info['profit']['currency'];?>
                                ->CNY)&nbsp;&nbsp;<strong>利润计算公式：</strong>（收入-成本/支出）-退款-重寄费用。
                            </td>
                        </tr>
                    <?php }else{?>
                        <tr><td colspan="9" align="center">没有找到信息！</td></tr>
                    <?php }?>
                    </tbody>
                </table>
            </div>
            <div id="menu6" class="tab-pane fade">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>发货仓库:</th>
                        <th>邮寄方式</th>
                    </tr>
                    </thead>
                    <tbody id="wareh_logistics">
                    <?php if(!empty($info['wareh_logistics'])){?>
                        <tr>
                            <td><?php echo $info['wareh_logistics']['warehouse']['warehouse_name'];?></td>
                            <td><?php //echo $info['wareh_logistics']['logistics']['ship_name'];?></td>
                        </tr>
                    <?php }else{?>
                        <tr><td colspan="2" align="center">没有找到信息！</td></tr>
                    <?php }?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    //订单备注
function saveRemark(orderId){

    var url = '<?php echo Url::toRoute(['/orders/order/addremark']);?>';
    $.post(url,{'order_id':orderId,'remark':$('.remark').val()},function(data){
          if (data.ack != true)
              alert(data.message);
          else
          {
              var info = data.info;
              var html = '';
              for (var i in info)
              {
                  html += '<tr>' + "\n"+
                    '<td style="width:80%;">' + info[i].remark.replace(/\n/g,"<br>") + '</td>' + "\n" +
                    '<td>' + info[i].create_user + '</td>' + "\n" +
                    '<td>' + info[i].create_time + '</td>' + "\n" +
                    '<td><a href="javascript:void(0)" onclick="removeRemark(' + info[i].id + ')">删除</a></td>' + "\n" +
                    '</tr>' + "\n";
              }
              $('#remarkTable').empty().html(html);
          }
    },'json');
}

//删除订单备注
function removeRemark(id)
{   
    console.log(id);
    var url ='<?php echo Url::toRoute(['/orders/order/removeremark']);?>';
    $.get(url,{id:id},function(data){
          if (data.ack != true)
              alert(data.message);
          else
          {
              var info = data.info;
              var html = '';
              for (var i in info)
              {
                  html += '<tr>' + "\n"+
                        
                        '<td style="width:80%;">' + info[i].remark.replace(/\n/g,"<br>") + '</td>' + "\n" +
                        '<td>' + info[i].create_user + '</td>' + "\n" +
                        '<td>' + info[i].create_time + '</td>' + "\n" +
                        '<td><a href="javascript:void(0)" onclick="removeRemark(' + info[i].id + ')">删除</a></td>' + "\n" +
                        '</tr>' + "\n";
              }
              $('#remarkTable').empty().html(html);
          }
    },'json');
}

//添加出货备注
function save_print_remark(orderId){
    var url = '<?php echo Url::toRoute(['/orders/order/addprintremark']);?>';
    var platform = $('.platform_code').val();
    $.post(url,{'order_id':orderId,'platform':platform,'print_remark':$('.print_remark').val()},function(data){
          alert(data.info);
    },'json');
}

    //模板ajax
    $('.mail_template_area').delegate('.mail_template_unity','click',function(){
        $.post('<?php echo Url::toRoute(['/mails/msgcontent/gettemplate']);?>',{'num':$(this).attr('value')},function(data){
            switch(data.status)
            {
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
        },'json');
    });

    //模板搜索
    $('.mail_template_search_btn').click(function(){
        var templateName = $.trim($('.mail_template_search_text').val());
        if(templateName.length == 0)
        {
            layer.msg('搜索名称不能为空。', {
                icon: 2,
                time: 2000 //2秒关闭（如果不配置，默认是3秒）
            });
            return;
        }
        $.post('<?php echo Url::toRoute(['/mails/msgcontent/searchtemplate']);?>',{'name':templateName},function(data){
            switch(data.status)
            {
                case 'error':
                    layer.msg(data.message, {
                        icon: 2,
                        time: 2000 //2秒关闭（如果不配置，默认是3秒）
                    });
                    return;
                case 'success':
                    var templateHtml = '';
                    for(var i in data.content)
                    {
                        templateHtml += '<a class="mail_template_unity" value="'+i+'">'+data.content[i]+'</a>';
                    }
                    $('.mail_template_area').html(templateHtml);
            }
        },'json');
    });

    //模板编号搜索
    $('.mail_template_title_search_btn').on('click',template_title);
    $('.mail_template_title_search_text').bind('keypress',function(){
        if(event.keyCode == "13")
        {
            template_title();
        }
    });

    function template_title()
    {
        var templateTitle = $.trim($('.mail_template_title_search_text').val());
        if(templateTitle.length == 0)
        {
            layer.msg('搜索内容不能为空。', {
                icon: 2,
                time: 2000 //2秒关闭（如果不配置，默认是3秒）
            });
            return;
        }
        $.post('<?php echo Url::toRoute(['/mails/msgcontent/searchtemplatetitle']);?>',{'name':templateTitle,'platform_code':'EB'},function(data){
            if(data.code == 200)
            {
                $('#leave_message').val(data.data);
            }
            else
            {
                layer.msg(data.message, {
                    icon: 2,
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                });
                return;
            }
        },'json');
    }
    
    
    
    
    /**
    * 点击选择语言将选中语言赋值给对应控件
     * @param {type} type 类型
     * @param {type} code 语言code
     * @param {type} name 语言名称
     * @param {type} that 当前对象
     * @author allen <2018-1-11>
     * */
    function changeCode(type,code,name = "",that = ""){
        if(type == 1){
            $("#sl_code").val(code);
            $("#sl_btn").html(name+'&nbsp;&nbsp;<span class="caret"></span>');
            that.css('font-weight','bold');
            $("#sl_name").html(name);
        }else if(type == 2){
            $("#tl_code").val(code);
            $("#tl_btn").html(name+'&nbsp;&nbsp;<span class="caret"></span>');
            $("#tl_name").html(name);
            that.css('font-weight','bold');
        }else if(type == 3){
            var name = that.html();
            $("#sl_code").val(code);
            $("#sl_name").html(name);
        }else{
            var name = that.html();
            $("#tl_code").val(code);
            $("#tl_name").html(name);
        }
    }
    
    /**
     * 绑定翻译按钮 进行手动翻译操作(系统未检测到用户语言)
     * @author allen <2018-1-11>
     **/
    $('.artificialTranslation').click(function(){
        var sl = $("#sl_code").val();
        var tl = $("#tl_code").val();
        var content = $.trim($("#leave_message").val());
        if(sl == ""){
            layer.msg('请选择需要翻译的语言类型');
            return false;
        }
        
        if(tl == ""){
            layer.msg('请选择翻译目标的语言类型');
            return false;
        }
        
        if(content.length <= 0){
           layer.msg('请输入需要翻译的内容!');
           return false;
        }
        //ajax请求
        $.ajax({
            type:"POST",
            dataType:"JSON",
            url:'<?php echo Url::toRoute(['ebayinboxsubject/translate']);?>',
            data:{'sl':sl,'tl':tl,'content':content},
            success:function(data){
                if(data){
                    $("#leave_message_en").val(data);
                }
            }
        });
    });
    
    /**
     * 回复客户邮件内容点击翻译(系统检测到用户语言)
     * @author allen <2018-1-11>
     */
    $(".transClik").click(function(){
        var sl = 'auto';
        var tl = 'en';
        var message = $(this).attr('data');
        var tag = $(this).attr('data1');
        var that = $(this);
        if(message.length == 0)
        {
           layer.msg('获取需要翻译的内容有错!');
           return false;
        }
        
        $.ajax({
            type:"POST",
            dataType:"JSON",
            url:'<?php echo Url::toRoute(['ebayinboxsubject/translate']);?>',
            data:{'sl':sl,'tl':tl,'returnLang':1,'content':message},
            success:function(data){
                if(data){
                    var htm = '<tr class="ebay_dispute_message_board '+tag+'"><td style="text-align: center;"><b style="color:red;">'+data.code+'</b></td><td><b style="color:green;">'+data.text+'</b></td></tr>';
                    $(".table_"+tag).append(htm);
                    that.remove();
                }
            }
        });
    });
</script>