<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;
use yii\bootstrap\ActiveForm;
use app\modules\mails\components\GridView;
use app\modules\accounts\models\Platform;
use app\modules\aftersales\models\AfterSalesOrder;
use app\modules\orders\models\Order;
use app\modules\mails\models\AmazonInbox;

/* @var $this yii\web\View */
/* @var $model app\modules\mails\models\AmazonInbox */

$this->title = $model->subject;
$this->params['breadcrumbs'][] = ['label' => 'Amazon Inboxes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<script type="text/javascript" src="/js/jquery.form.js"></script>
<div id="page-wrapper-inbox" class="row">
    <!-- <p>
        <a href='/mails/aliexpress/index' style='text-decoration:none;'><button type="button" class="btn btn-primary btn-lg btn-block">返回列表</button></a>
    </p> -->


    <div class="panel panel-default col-md-6">
        <div class="panel-heading">
            <h3 class='panel-title'>
                <i class="fa fa-pencil"></i>查看邮件
            </h3>
        </div>
        <div class="panel-heading">
            <h3 class='panel-title'>
                <ul class="list-inline" id="ulul">
                    <?php if(!empty($tags_data)){
                        foreach($tags_data as $key => $value)
                        { ?>
                            <li style="margin-right: 20px;" class="btn btn-default" id = "tags_value<?php echo $key;?>"><span use_data="<?php echo $key;?>"><?php echo $value;?></span>&nbsp;<a class="btn btn-warning" href="javascript:void(0)" onclick="removetags(this);">x</a></li>
                        <?php }
                    }?>
                </ul>
            </h3>
        </div>

        <div class="panel-body">
            <div>
                <!-- <ul class="nav nav-tabs">
                    <li class="active"><a href="#tab-body" data-toggle="tab">正文内容</a></li>
                    <li><a href="#tab-base" data-toggle="tab">详细信息</a></li>
                    <li><a href="#tab-attachments" data-toggle="tab">附件</a></li>
                    <li><a href="#tab-replyhistory" data-toggle="tab">回复历史</a></li>
                </ul> -->

                <div class="tab-content">
                    <div class="tab-pane active" id="tab-body">
                        <h3><?= Html::encode($this->title) ?>   <?php echo  AmazonInbox::wherethrAttch($model->id,0)?></h3> 

                        <address>
                            发件人: <?= $model->sender ?> &lt; <a href="mailto:#"><?= $model->sender_email ?></a> &gt;<br/>
                            时间: <?= $model->receive_date ?> <br/>
                            收件人: <?= $model->receiver ?> &lt; <?= $model->receive_email ?> &gt;
                        </address>

                        <div style="margin: 0 0 10px;">
                            <?= Html::a('删除', ['delete', 'id' => $model->id], [
                                'class' => 'btn btn-danger',
                                'data' => [
                                    'confirm' => 'Are you sure you want to delete this item?',
                                    'method' => 'post',
                                ],
                            ]) ?>

                            <!-- <?= Html::a('回复', '#abc', ['class' => 'btn btn-info'])?> -->

                            <div class="dropdown" style="display: inline-block;">
                                <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                    标记为...
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                                    <li><a href="javascript:markEmailStatus(<?= $model->id ?>, 1);">已读</a></li>
                                    <li><a href="javascript:markEmailStatus(<?= $model->id ?>, 2);">已回复</a></li>
                                </ul>
                            </div>


                                <?= Html::a('下一封', Url::toRoute(['/mails/amazoninbox/view', 'next' => 1]), ['class' => 'btn btn btn-primary']); ?>

                             <?= Html::a('新增标签', Url::toRoute(['/mails/amazoninbox/addtags', 'ids' => $model->id,'type'=>'detail']), ['class' => 'btn btn btn-primary add-tags-button-button']); ?>
                             
                              <?= Html::a('移除标签', Url::toRoute(['/mails/amazoninbox/removetags', 'id' => $model->id,'type'=>'detail']), ['class' => 'btn btn-danger add-tags-button-button']); ?>

                            <div class="dropdown" style="display: inline-block;">
                                <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                    附件
                                    <span class="caret"></span>
                                </button>
                                <?php if (!empty($attachments)): ?>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">

                                    <?php foreach ($attachments as $attachment) : ?>

                                        <li><?= Html::a($attachment->name, str_replace(\Yii::$app->basePath.DIRECTORY_SEPARATOR.'web', '', $attachment->file_path),['target' => '_blank'])?></li>

                                    <?php endforeach; ?>

                                    </ul>
                                    <?php else: ?>

                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">暂无附件</ul>

                                    <?php endif; ?>
                            </div>
                        </div>

                        <div class="panel panel-default" style="height: 400px;">
                            <div class="panel-body embed-responsive" style="width: 100%;height: 100%;">
                                <iframe class="embed-responsive-item" src="<?= Url::toRoute(['/mails/amazoninbox/content', 'id' => $model->id])?>"></iframe>
                            </div>
                        </div>
                    </div>

                    <!-- <div class="tab-pane" id="tab-base">

                        <h3><?= Html::encode($this->title) ?></h3>

                        <div style="margin: 0 0 10px;">
                            <?= Html::a('Delete', ['delete', 'id' => $model->id], [
                                'class' => 'btn btn-danger',
                                'data' => [
                                    'confirm' => 'Are you sure you want to delete this item?',
                                    'method' => 'post',
                                ],
                            ]) ?> -->

                            <!-- <?= Html::a('回复', '#abc', ['class' => 'btn btn-info'])?> -->

                            <!-- <div class="dropdown" style="display: inline-block;">
                                <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                    标记为...
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                                    <li><a href="#">已读</a></li>
                                    <li><a href="#">已回复</a></li>
                                </ul>
                            </div>
                                <?= Html::a('下一封', Url::toRoute(['/mails/amazoninbox/view', 'next' => 1]), ['class' => 'btn btn btn-primary']) ?>
                        </div> -->

                        <!-- <? /* = DetailView::widget([
                            'model' => $model,
                            'attributes' => [
                                // 'id',
                                // 'message_id',
                                // 'parent_id',
                                // 'platform_id',
                                'subject',
                                'order_id',
                                'account_id',
                                // 'body:ntext',
                                'mail_type',
                                'sender',
                                'sender_email:email',
                                'receiver',
                                'receive_email:email',
                                'receive_date',
                                // 'message_time',
                                'is_read_text',
                                'is_replied_text',
                                'reply_date',
                                'create_by',
                                'create_time',
                                'modify_by',
                                'modify_time',
                                'status',
                            ],
                        ]) */ ?> -->
                        
                    <!-- </div> -->

                    <div class="tab-pane" id="tab-attachments">
                        <?php if (!empty($attachments)): ?>
                        <ul>

                        <?php foreach ($attachments as $attachment) : ?>

                            <li><?= Html::a($attachment->name, str_replace(\Yii::$app->basePath.DIRECTORY_SEPARATOR.'web', '', $attachment->file_path),['target' => '_blank'])?></li>

                        <?php endforeach; ?>

                        </ul>
                        <?php else: ?>

                        <div class="center-block">暂无附件</div>

                        <?php endif; ?>

                    </div>

                </div>
            </div>

            <div class="tab-pane" id="tab-replyhistory">
                <div>回复历史</div>
                <div class="list-group" style="margin: 5px auto;width:98%">
                    <?php if (!empty($history)): ?>

                    <?php foreach ($history as $ht): ?>

                    <a href="javascript:void(0);" dataid="<?= $ht->id?>" class="list-group-item" onclick="expand(this)" v="1">
                        <h5><?= $ht->reply_title ?></h5>

                        <div style="display: none;">
                            <address>
                                发件人: <?= $ht->reply_by ?> &lt; <?= $model->receive_email ?> &gt;<br/>
                                时间: <?= $ht->create_time?> <br/>
                                收件人: <?= $model->sender?> &lt; <?= $model->sender_email?> &gt;
                            </address>
                            <p>
                                <?= nl2br($ht->reply_content) ?>
                            </p>

                            <p>

                            <?php if ($ht->attachment): ?>
                                附件:

                            <?php foreach ($ht->attachment as $att): ?>

                                <span><?= $att->name ?></span> <br/>

                            <?php endforeach; ?>

                            <?php endif; ?>

                            </p>

                        </div>
                    </a>

                    <?php endforeach; ?>

                    <?php else: ?>

                    <p>暂没有回复历史</p>

                    <?php endif; ?>
                </div>
            </div>

            <hr>


            <!-- <fieldset> -->
                <!-- <legend><i class="fa fa-envelope-o"></i><?= Html::a(' 回复邮件', null, ['name' => 'abc', 'style' => 'text-decoration:none;color:#000;']) ?></legend> -->

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-comment-o"></i> 会话历史记录</h3>
                </div>

                <div class="panel-body">
                    <div id="history">
                        <table class="table table-bordered">
                            <tr>
                                <th>订单号</th>
                                <th>账号</th>
                                <th>主题</th>
                                <th>发件人</th>
                                <th>收件人</th>
                                <th>收件时间</th>
                            </tr>
                            <?php foreach ($historyInboxs as $inbox) { ?>
                                <tr <?php if($_GET['id'] == $inbox->id):?>bgcolor="#C2D5E2"<?php endif;?> >
                                    <td><?php echo $inbox->order_id;?></td>
                                    <td><?php echo isset($accounts[$inbox->account_id]) ? $accounts[$inbox->account_id] : '';?></td>
                                    <td><?php echo Html::a($model->subject, Url::toRoute(['/mails/amazoninbox/view', 'id' => $inbox->id]));?>
                                       <?php echo  AmazonInbox::wherethrAttch($inbox->id,0)?>
                                    </td>
                                    <td><?php echo $inbox->sender;?></td>
                                    <td><?php echo $inbox->receiver;?></td>
                                    <td><?php echo $inbox->receive_date;?></td>
                                </tr>
                            <?php } ?>
                        </table>
                    </div>
                </div>
            </div>

            <!-- </fieldset> -->

        </div>
    </div>


    <div class="panel col-md-6">

            <div class="panel panel-default">

                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-envelope-o">回复邮件</i></h3>
                </div>

                <div class="panel-body">

                    <?php
                    $form = ActiveForm::begin([
                        'id' => 'amazoninbox-form',
                        'layout' => 'horizontal',
                        'action' => Url::toRoute(['/mails/amazonreply/create', 'id' => $reply->inbox_id, 'next' => 1]),
                        'enableClientValidation' => false,
                        'validateOnType' => false,
                        'validateOnChange' => false,
                        'validateOnSubmit' => true,

                    ]);
                    ?>
                    <?= $form->field($reply, 'reply_title')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($reply, 'inbox_id', ['template' => '{input}', 'options' => ['style' => 'display:none;']])->hiddenInput() ?>

                    <!-- <?= $form->field($reply, 'is_draft')->dropDownList(['0' => '否', '1' => '是'], ['style' => 'width:80px;']) ?> -->

                    <div class="form-group">
                        <label class="control-label col-sm-3">选择模板</label>
                        <div class="col-sm-6">
                            <div class="input-group" style="width: 280px;">
                                <input type="text" class="form-control" placeholder="Search for..." id="t-search">
                            <span class="input-group-btn">
                                <button class="btn btn-default" type="button" onclick="go(this)">Go!</button>
                            </span>
                            </div>

                            <div class="panel t-zone" style="margin: 10px 0 0;">
                                <?php $i = 1; ?>
                                <?php foreach ($templates as $key => $value): ?>

                                    <?php if ($i <= 12): ?>
                                        <a href="javascript:void(0);" onclick="choosesTemplate(<?=$key?>)" style="text-align:center;color:black;">
                                            [ <span class="bg-success"><?= $value ?></span> ]
                                        </a>

                                    <?php else: ?>

                                        <a href="javascript:void(0);" onclick="choosesTemplate(<?=$key?>)" style="text-align:center;color:black;display:none" class="hd">
                                            [ <span class="bg-success"><?= $value ?></span>]
                                        </a>

                                    <?php endif; ?>

                                    <?php $i++; ?>
                                <?php endforeach; ?>
                                <a href="javascript:void(0);" onclick="showMores(this)" vi="1">更多&gt;&gt;&gt;</a>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">附件</label>
                        <div class="col-sm-6">
                            <div>
                                <input type="file" id="" name="AmazonReply[file][]" style="display: inline-block; width: 80%;" />
                                <a href="javascript:void(0);" onclick="doaddfile(this);">添加</a>
                                <a href="javascript:void(0);" onclick="deletefile(this);">删除</a>

                            </div>
                        </div>
                    </div>

                    <?= $form->field($reply, 'reply_content')->textarea(['rows' => 6, 'id' => 'amz-reply']) ?>

                    <div class="form-group">
                        <label class="control-label col-sm-3">&nbsp;</label>
                        <div class="col-sm-6">
                            <button class="btn btn-primary pull-right submit" type="button" onclick="dosubmit()"><i class="fa fa-plus-circle"></i> 添加回复</button>
                        </div>
                    </div>

                    <?php ActiveForm::end(); ?>

                </div>



            </div>

            <div class="panel panel-default" style="margin-top: 10px;">
                
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-info-circle"></i> 订单信息 </h3>
                </div>
                <div class="panel-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="tab-info">      
                        <table class="table">
                        <thead>
                        <tr>
                            <th>订单号</th>
                            <!-- <th>平台订单号</th> -->
                            <th>国家</th>
                            <th>订单金额</th>
                            <th>订单状态</th>
                            <th>纠纷状态</th>
                            <th>退款</th>
                            <th>售后问题</th>
							<th>付款时间</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody id="basic_info">
                      <!-- 这里原先是遍历控制器传过来的历史订单的数据现在改为遍历订单数据(修改者--wangguanhua)-->
                        <?php
                        if (!empty($orderInfo)){

                            foreach ($orderInfo as $order){
                                $current = '';
                                $redirectLabel = '';
                                /*判断是否为当前订单ID*/
                                if($order['platform_order_id'] == $model->order_id){
                                    $current = '<span class="label label-danger">当前订单</span>';
                                }
                                if ($order['order_type'] == Order::ORDER_TYPE_REDIRECT_ORDER)
                                    $redirectLabel = '<span class="label label-warning">重寄订单</span>';
                                ?>
                                <tr class="active">
                                    <td><a _width="70%" _height="70%" class="edit-button" href="<?php echo Url::toRoute(['/orders/order/orderdetails',
                                        'order_id' => $order['platform_order_id'],
                                        'platform' => Platform::PLATFORM_CODE_AMAZON,
                                        'system_order_id' => $order['order_id']]);?>" title="订单信息"><?php echo $order['order_id'];?><?php echo $current . $redirectLabel;?></a>
                                    </td>
                                    <!-- <td><a _width="70%" _height="70%" class="edit-button" href="<?php //echo Url::toRoute(['/orders/order/orderdetails',
                                        //'order_id' => $hvalue['platform_order_id'],
                                        //'platform' => Platform::PLATFORM_CODE_ALI,
                                        //'system_order_id' => $hvalue['order_id']]);?>" title="订单信息"><?php //echo $hvalue['platform_order_id'];?></a> <?php //echo current;?></td>-->
                                    <td><?php echo $order['ship_country'];?></td>
                                    <td><?php echo $order['total_price'] . $order['currency'];?></td>
                                    <td><?php echo $order['complete_status_text'];?></td>
                                    <td><span class="label label-danger">无纠纷</span></td>
                                    <td><?php 
                                            if ($order['refund_status'] == 0)
                                                echo '<span class="label label-success">无</span>';
                                            else if ($order['refund_status'] == 1)
                                                echo '<span class="label label-danger">部分退款</span>';
                                            else
                                                echo '<span class="label label-danger">全部退款</span>';
                                        ?></td>
                                    <td>
                                    <?php
                                    // 售后信息 显示 退款 退货 重寄 退件
                                    $aftersaleinfo = AfterSalesOrder::hasAfterSalesOrder(Platform::PLATFORM_CODE_AMAZON,  $order['order_id']);
                                    //是否有售后订单
                                    if ($aftersaleinfo) {
                                        $res = AfterSalesOrder::getAfterSalesOrderByOrderId( $order['order_id'], Platform::PLATFORM_CODE_AMAZON);
                                        //获取售后单信息
                                        if (!empty($res['refund_res'])) {
                                            $refund_res = '退款';
                                            foreach ($res['refund_res'] as $refund_re) {
                                                $refund_res .=
                                                    '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailrefund?after_sale_id=' .
                                                    $refund_re['after_sale_id'] . '&platform_code=' . Platform::PLATFORM_CODE_AMAZON . '&status=' . $aftersaleinfo->status . '" >' .
                                                    $refund_re['after_sale_id'] . '</a>';
                                            }
                                        } else {
                                            $refund_res = '';
                                        }

                                        if (!empty($res['return_res'])) {
                                            $return_res = '退货';
                                            foreach ($res['return_res'] as $return_re) {
                                                $return_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailreturn?after_sale_id=' .
                                                    $return_re['after_sale_id'] . '&platform_code=' . Platform::PLATFORM_CODE_AMAZON . '&status=' . $aftersaleinfo->status . '" >' .
                                                    $return_re['after_sale_id'] . '</a>';
                                            }
                                        } else {
                                            $return_res = '';
                                        }

                                        if (!empty($res['redirect_res'])) {
                                            $redirect_res = '重寄';
                                            foreach ($res['redirect_res'] as $redirect_re) {
                                                $redirect_res .= '<a _width="100%" _height="100%" class="edit-button" href="/aftersales/sales/detailredirect?after_sale_id=' .
                                                    $redirect_re['after_sale_id'] . '&platform_code=' . Platform::PLATFORM_CODE_AMAZON . '&status=' . $aftersaleinfo->status . '" >' .
                                                    $redirect_re['after_sale_id'] . '</a>';
                                            }
                                        } else {
                                            $redirect_res = '';
                                        }
                                        if (!empty($res['domestic_return'])) {
                                            $domestic_return = '退货跟进';
                                            if ($res['domestic_return']['state'] == 1) {
                                                $state = '未处理';
                                            } elseif ($res['domestic_return']['state'] == 2) {
                                                $state = '无需处理';
                                            } elseif ($res['domestic_return']['state'] == 3) {
                                                $state = '已处理';
                                            } else {
                                                $state = '驳回EPR';
                                            }
                                            //状态：1、未处理，2、无需处理，3、已处理，4、驳回EPR
                                            $domestic_return.= '<a target="_blank" href="/aftersales/domesticreturngoods/orderslist?sortBy=&sortOrder=&order_id=&trackno=&buyer_id=&return_type=&state=&handle_type=&start_date=&end_date=&return_number=' .
                                                $res['domestic_return']['return_number'] . '&platform_code=' . Platform::PLATFORM_CODE_AMAZON . '" >' .
                                                $res['domestic_return']['return_number'] . '('.$state .')'. '</a>';
                                        } else {
                                            $domestic_return = '';
                                        }
                                        $after_sale_text = '';
                                        if (!empty($refund_res)) {
                                            $after_sale_text .= $refund_res . '<br>';
                                        }
                                        if (!empty($return_res)) {
                                            $after_sale_text .= $return_res . '<br>';
                                        }
                                        if (!empty($redirect_res)) {
                                            $after_sale_text .= $redirect_res . '<br>';
                                        }
                                        if (!empty($domestic_return)) {
                                            $after_sale_text .= $domestic_return;
                                        }
                                        echo $after_sale_text;
                                    } else {
                                        echo '<span class="label label-success">无</span>';
                                    }
                                    ?>
                                    </td>
									<td>
										<?php
                                            if($order['payment_status'] == 0)
                                                
                                                echo "未付款";
                                            else
                                                echo $order['paytime'];
                                        ?>
									</td>
                                    <td>
                                        <div class="btn-group btn-list">
                                            <button type="button" class="btn btn-default btn-sm"><?php echo Yii::t('system', 'Operation');?></button>
                                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                                <span class="caret"></span>
                                                <span class="sr-only"><?php echo Yii::t('system', 'Toggle Dropdown List');?></span>                                            
                                            </button>
                                            <ul class="dropdown-menu" rol="menu">
                                            <?php if ($order['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP) { ?>
                                                <li><a _width="30%" _height="60%" class="edit-button" href="<?php echo Url::toRoute(['/orders/order/cancelorder', 
                                                    'order_id' => $order['order_id'], 'platform' => Platform::PLATFORM_CODE_AMAZON]);?>">永久作废</a></li>
                                                <li><a _width="30%" _height="60%" class="edit-button" href="<?php echo Url::toRoute(['/orders/order/holdorder',
                                                    'order_id' => $order['order_id'], 'platform' => Platform::PLATFORM_CODE_AMAZON]);?>">暂时作废</a></li>
                                                <?php 
                                                    } 
                                                    if ($order['complete_status'] == Order::COMPLETE_STATUS_HOLD)
                                                    {
                                                ?>
                                                <li><a confirm="确定取消暂时作废该订单？" class="ajax-button" href="<?php echo Url::toRoute(['/orders/order/cancelholdorder', 
                                                    'order_id' => $order['order_id'], 'platform' => Platform::PLATFORM_CODE_AMAZON]);?>">取消暂时作废</a></li>                                                        
                                                <?php        
                                                    }
                                                ?>
                                                <?php if ($order['order_type'] != Order::ORDER_TYPE_REDIRECT_ORDER) { ?>
                                                <li><a _width="80%" _height="80%" class="edit-button" href="<?php echo Url::toRoute(['/aftersales/order/add', 
                                                    'order_id' => $order['order_id'], 'platform' => Platform::PLATFORM_CODE_AMAZON]);?>">新建售后单</a></li> 
                                                <?php } ?>
                                                <li>
                                                    <a _width="50%" _height="80%" class="edit-button"
                                                       href="<?php echo Url::toRoute(['/orders/order/invoice', 'order_id' => $order['order_id'], 'platform' => Platform::PLATFORM_CODE_AMAZON]); ?>">发票</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr class="active"> <td colspan="7" align="center">没有相关订单信息！</td> </tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                          </div>
                    </div>
                </div>
            </div>

    </div>

    <!-- <div class="panel panel-default" style="margin-left:10px;width: 100%;float: left;">
        <a href="<?php //echo Url::toRoute(['/mails/amazoninbox/view', 'next' => 1]);?>" class="btn btn-primary btn-lg btn-block">下一封</a>
    </div> -->
</div>

<script type="text/javascript">

    //给标签设置快捷键
    var keyboards = '<?php echo $keyboards; ?>'
    keyboards = JSON.parse(keyboards);
    var ids = '<?php echo $model->id; ?>'
    var tag_id = '';
    $(document).ready(
        function(){
            document.onkeyup = function(e)
            {
                var event = window.event || e;
                if(event.shiftKey && keyboards['shift'] != undefined && keyboards['shift'][event.keyCode] != undefined)
                {
                    tag_id = keyboards['shift'][event.keyCode]
                    if (tag_id != '' && tag_id != undefined) {
                        $.post('<?= Url::toRoute(['/mails/amazoninbox/addretags', 'ids' => $model->id, 'type' => 'detail'])?>', {
                            'MailTag[inbox_id]': ids,
                            'MailTag[tag_id][]': tag_id,
                            'MailTag[type]': 'detail'
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
                                $("#tags_value"+tags_id).hide(50);
                            }
                        }, 'json');
                    }
                }
                if(event.ctrlKey && keyboards['ctrl'] != undefined && keyboards['ctrl'][event.keyCode] != undefined)
                {
                    tag_id = keyboards['ctrl'][event.keyCode]
                    if (tag_id != '' && tag_id != undefined) {
                        $.post('<?= Url::toRoute(['/mails/amazoninbox/addretags', 'ids' => $model->id, 'type' => 'detail'])?>', {
                            'MailTag[inbox_id]': ids,
                            'MailTag[tag_id][]': tag_id,
                            'MailTag[type]': 'detail'
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
                                $("#tags_value"+tags_id).hide(50);
                            }
                        }, 'json');
                    }
                }
                if(event.altKey && keyboards['alt'] != undefined && keyboards['alt'][event.keyCode] != undefined) {
                    tag_id = keyboards['alt'][event.keyCode]
                    if (tag_id != '' && tag_id != undefined) {
                        $.post('<?= Url::toRoute(['/mails/amazoninbox/addretags', 'ids' => $model->id, 'type' => 'detail'])?>', {
                            'MailTag[inbox_id]': ids,
                            'MailTag[tag_id][]': tag_id,
                            'MailTag[type]': 'detail'
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
                                $("#tags_value"+tags_id).hide(50);
                            }
                        }, 'json');
                    }
                }
            }
        }
    );

    var markEmailStatus = function (id, stat) {
        $.get('<?= Url::toRoute("/mails/amazoninbox/mark") ?>', {id:id, stat:stat}, function (data) {
            var $data = $.parseJSON(data);
            if ($data.url && $data.code == "200") window.location.href = $data.url;
        })
    }

    var showMores = function (obj) {
        if ($(obj).attr('vi') == '1')
            $(obj).attr('vi','2').text('<<<收起').siblings('a.hd').css('display', 'inline-block');
        else
            $(obj).attr('vi', '1').text('更多>>>').siblings('a.hd').css('display', 'none');
    }

    var choosesTemplate = function (id) {
        $.post('<?= Url::toRoute("/mails/msgcontent/gettemplate")?>', {'num' : id}, function (data) {
            if (data.status == 'success')
                $('#amz-reply').val(data.content);
        }, 'json');
    }

    var expand = function (obj) {
        if ($(obj).attr('v') == 1)
            $(obj).attr('v', '2').find('div').css('display', 'block');
        else
            $(obj).attr('v', '1').find('div').css('display', 'none');
    }

    var go = function (obj) {
        var j = 1,
            s = '',
            search = $('#t-search').val();

        $.post('<?= Url::toRoute("/mails/msgcontent/searchtemplate")?>', {name:search}, function (data) {
                if (data.status) {
                    for (var i in data.content) {
                        if (j <= 12) {
                            s += '<a href="javascript:void(0);" onclick="choosesTemplate('+i+')" style="text-align:center;color:black;"> [ <span class="bg-success">'+data.content[i]+'</span> ]</a>';
                        } else {
                            s += '<a href="javascript:void(0);" onclick="choosesTemplate('+i+')" style="text-align:center;color:black;display:none" class="hd"> [ <span class="bg-success">'+data.content[i]+'</span>]</a>';
                        }

                        $('.t-zone').html(s);

                        j++;
                    }
                }
        }, 'json');
    }

    var dosubmit = function () {
        if (!$('#amz-reply').val()) {
            layer.alert('请填写回复内容!', {icon:5});
            return false;
        }

        $('#amazoninbox-form').ajaxSubmit({success : function (data) {
            var $data = $.parseJSON(data);

            if ($data.code == "200") {
                $('#amazoninbox-form')[0].reset();
                icon = 1;
            } else {
                icon = 5;
            }

            layer.alert($data.message, {icon: icon, yes : function () {
                if ($data.url && $data.code == "200") window.location.href = $data.url;
            }});

        }});

        return true;
    }

    function doaddfile(obj) {
        var str = '<div>' +
                  '<input type="file" id="" name="AmazonReply[file][]" style="display: inline-block; width: 80%;" /> <a href="javascript:void(0);" onclick="doremovefile(this);">删除</a>' +
                  '</div>';

        $(obj).parent('div').after(str);
    }
    
    function deletefile(obj) {
        $(obj).siblings('input').val('') ;
    }

    function doremovefile(obj) {
        $(obj).parent('div').remove();
    }

    $('div.sidebar').hide();

    // 获取url参数
    function GetQueryString(name)
    {
        var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
        var r = window.location.search.substr(1).match(reg);
        if(r!=null)return  unescape(r[2]); return null;
    }

    function removetags(obj) {
        var _id = GetQueryString('id');
        var tag_id = $(obj).siblings('span').attr('use_data');
        $.post('<?= Url::toRoute(['/mails/amazoninbox/removetags','id' => $model->id,'type'=>'detail'])?>', {'MailTag[inbox_id]' : _id, 'MailTag[tag_id][]' : tag_id, 'MailTag[type]' : 'detail'}, function (data) {
            if (data.url && data.code == "200")
                $("#tags_value"+tag_id).hide(50);
        }, 'json');

    }
</script>
