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
    p {margin: 0px 0px 5px;font-size:13px;}
    .list-group{margin-bottom: 0px;}
    .list-group-item{padding: 5px 5px;font-size:13px;}
    .table{margin-bottom: 10px;}
    .btn-sm{line-height: 1;}
    .mail_template_area a{cursor: pointer;}
    .col-sm-5{width:auto;}
    .tr_q .dropdown-menu{left:-136px;}
    .tr_h .dropdown-menu {left:-392px;}
     #wrapper .popup-body{padding-top: 0px;}
</style>
<div class="panel panel-default">
    <div class="panel-heading">
        <h4 class="panel-title">纠纷详情&处理</h4>
    </div>
    <div id="collapseThree" class="panel-collapse">
        <div class="panel-body">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>Cancel Id</th>
                    <th><?=$model->cancel_id?></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>状况</td>
                    <td><?php echo $model->cancel_state == 0 ? '' : $model::$cancelStateMap[$model->cancel_state];?></td>
                </tr>
                <tr>
                    <td>原因</td>
                    <td><?php echo $model->cancel_reason == 0 ? '' : $model::$ReasonMap[$model->cancel_reason];?></td>
                </tr>
                <tr>
                    <td>发起时间</td>
                    <td><?=$model->cancel_request_date?></td>
                </tr>
                <tr>
                    <td>售后单号</td>
                    <td>
                        <?php
                        $order_id = $info['info']['order_id'];
                        $afterSalesOrders = AfterSalesOrder::find()->select('after_sale_id')->where(['order_id'=>$order_id])->asArray()->all();
                        if(empty($afterSalesOrders))
                            echo '<span>无售后处理单</span>';
                        else
                            echo '<span>'.implode(',',array_column($afterSalesOrders,'after_sale_id')).'</span>';

                        if(!empty($info))
                            echo '<a style="margin-left:10px" _width="90%" _height="90%" class="edit-button" href="'.Url::toRoute(['/aftersales/order/add','order_id'=>$info['info']['order_id'],'platform'=>Platform::PLATFORM_CODE_EB]).'">新建售后单</a>';

                        if(!empty($info) && $info['info']['complete_status'] < Order::COMPLETE_STATUS_PARTIAL_SHIP)
                        {
                            echo '<a style="margin-left:10px" _width="30%" _height="60%" class="edit-button" href="'.Url::toRoute(['/orders/order/cancelorder','order_id'=>$info['info']['order_id'],'platform'=>Platform::PLATFORM_CODE_EB]).'">永久作废</a>';
                            echo '<a style="margin-left:10px" _width="30%" _height="60%" class="edit-button" href="'.Url::toRoute(['/orders/order/holdorder','order_id'=>$info['info']['order_id'],'platform'=>Platform::PLATFORM_CODE_EB]).'">暂时作废</a>';
                        }
                        ?>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php if(!empty($detailModel)):?>
                处理过程
                <ul class="list-group">
                    <?php foreach($detailModel as $detail):?>
                        <li class="list-group-item"><?php echo isset($detail->action_date) ? date('Y-m-d H:i:s',strtotime($detail->action_date)+28800):'','&nbsp;&nbsp;&nbsp;&nbsp;','<span style="color:#FF7F00">',$detail->activity_party,'</span>','&nbsp;&nbsp;&nbsp;&nbsp;',$detail->activity_type;?></li>
                    <?php endforeach;?>
                </ul>
            <?php endif;?>

            <?php
            $item_id = $model->legacy_order_id;
            $item_id = substr($item_id,0,strpos($item_id,'-'));
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

            <?php if($model->cancel_state != 2 && $model->cancel_status != 5):?>
                <div class="popup-wrapper">
                    <?php
                    $responseModel = new \app\modules\mails\models\EbayCancellationsResponse();
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
                        <div class="row">
                            <input type="hidden" name="order_id" value="<?php if (!empty($info['info'])) echo $info['info']['order_id']; ?>">
                            <input type="radio" name="EbayCancellationsResponse[type]" value="1" checked>接受
                            <input type="radio" name="EbayCancellationsResponse[type]" value="2">拒绝
                            <div class="ebay_cancellations_response_explain">
                                <textarea cols="100" rows="10" name="EbayCancellationsResponse[explain]"></textarea>
                            </div>
                            <link href="<?php echo yii\helpers\Url::base(true);?>/laydate/need/laydate.css" rel="stylesheet">
                            <link href="<?php echo yii\helpers\Url::base(true);?>/laydate/skins/default/laydate.css" rel="stylesheet">
                            <script src="<?php echo yii\helpers\Url::base(true);?>/laydate/laydate.js"></script>
                            <div class="ebay_cancellations_refuse_params">
                                发货时间：<input class="laydate-icon" id="demo" value="" name="EbayCancellationsResponse[shipment_date]"/>
                                跟踪号：<input type="text" name="EbayCancellationsResponse[tracking_number]"/>
                            </div>
                            <script>
                                void function(){
                                    laydate({
                                        elem: '#demo',
                                        format: 'YYYY/MM/DD hh:mm:ss',
                                    })
                                }();
                                $(function(){
                                    $('.ebay_cancellations_refuse_params').hide();
                                    $('input[name="EbayCancellationsResponse[type]"]').click(function(){
                                        switch($(this).val())
                                        {
                                            case '1' :
                                                $('.ebay_cancellations_response_explain').show();
                                                $('.ebay_cancellations_refuse_params').hide();
                                                break;
                                            case '2' :
                                                $('.ebay_cancellations_response_explain').hide();
                                                $('.ebay_cancellations_refuse_params').show();
                                        }
                                    });
                                });
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
    </div>
</div>

<script>
    //模板ajax
    $('.mail_template_area').delegate('.mail_template_unity', 'click', function () {
        $.post('<?php echo Url::toRoute(['/mails/msgcontent/gettemplate']); ?>', {'num': $(this).attr('value')}, function (data) {
            switch (data.status)
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
        }, 'json');
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
    $('.mail_template_title_search_btn').on('click', template_title);
    $('.mail_template_title_search_text').bind('keypress', function () {
        if (event.keyCode == "13")
        {
            template_title();
        }
    });

    function template_title()
    {
        var templateTitle = $.trim($('.mail_template_title_search_text').val());
        if (templateTitle.length == 0)
        {
            layer.msg('搜索内容不能为空。', {
                icon: 2,
                time: 2000 //2秒关闭（如果不配置，默认是3秒）
            });
            return;
        }
        $.post('<?php echo Url::toRoute(['/mails/msgcontent/searchtemplatetitle']); ?>', {'name': templateTitle, 'platform_code': 'EB'}, function (data) {
            if (data.code == 200)
            {
                $('#leave_message').val(data.data);
            } else
            {
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
        if (message.length == 0)
        {
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
</script>