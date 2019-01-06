<?php

use yii\helpers\Url;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\AliexpressDisputeAttachments;
use app\modules\mails\models\AliexpressDisputeProcess;

?>

<style>
    #updateIssueInfo {
        margin-bottom: 5px;
    }

    .issueAttachment {
        border: 1px solid #ccc;
        padding: 2px;
        width: 60px;
        height: 60px;
        cursor: pointer;
    }
</style>

<div>
    <button type="button" id="updateIssueInfo" class="btn btn-primary" data-issueid="<?php echo !empty($issue_id) ? $issue_id : ''; ?>">
        更新纠纷
    </button>
    <span class="label label-danger">如果下方没有纠纷信息，可点击左边按钮进行更新</span>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#issue" href="#issue_basicinfo"><h4 class="panel-title">基本信息</h4></a>
    </div>
    <div id="issue_basicinfo" class="panel-collapse collapse in">
        <div class="panel-body">
            <table class="table table-bordered">
                <?php if (!empty($issue_info) && !empty($issue_list)) { ?>
                    <tr>
                        <td>纠纷原因:</td>
                        <td colspan="3">
                            CN：<?php echo !empty($issue_list['reason_chinese']) ? $issue_list['reason_chinese'] : ''; ?><br>
                            EN：<?php echo !empty($issue_list['reason_english']) ? $issue_list['reason_english'] : ''; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>纠纷ID：</td>
                        <td><?php echo !empty($issue_info['platform_dispute_id']) ? $issue_info['platform_dispute_id'] : ''; ?></td>
                        <td>状态：</td>
                        <td>
                            <?php
                            switch ($issue_info['issue_status']) {
                                case 'processing':
                                    echo '处理中';
                                    break;
                                case 'canceled_issue':
                                    echo '已取消';
                                    break;
                                case 'finish':
                                    echo '已完结';
                                    break;
                                default:
                                    break;
                            }

                            echo !empty($issue_info['after_sale_warranty']) ? '(售后宝)' : '';

                            //无忧物流问题判断
                            $process = AliexpressDisputeProcess::find()
                                ->andWhere(['platform_dispute_id' => $issue_info['platform_dispute_id']])
                                ->andWhere(['account_id' => $issue_info['account_id']])
                                ->asArray()
                                ->all();

                            //是否无忧物流问题
                            $isAliExpress = 0;
                            if (!empty($process)) {
                                foreach ($process as $key => $item) {
                                    //找到操作类型为fpl_authenticate并且内容为AliExpress accepted
                                    if ($item['action_type'] == 'fpl_authenticate' && $item['content'] == 'AliExpress accepted') {
                                        $next = $key + 1;
                                        //如果该操作记录下一条紧接着平台给出方案，可以判断为无忧物流问题
                                        if (array_key_exists($next, $process)) {
                                            if ($process[$next]['action_type'] == 'platform_give_solution') {
                                                $isAliExpress = 1;
                                            }
                                        }
                                    }
                                }
                            }

                            if ($isAliExpress) {
                                echo '<br>';
                                echo '无忧物流问题(仅供参考)';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>纠纷开始时间：</td>
                        <td><?php echo !empty($issue_info['gmt_create']) ? $issue_info['gmt_create'] : ''; ?></td>
                        <td>退款上限：</td>
                        <td><?php echo $issue_info['refund_money_max'] . $issue_info['refund_money_max_currency']; ?></td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <input type="checkbox" name="auto_create_aftersale_order">
                            <span style="color:#169BD5;">同意纠纷,自动建立退款售后单</span>
                        </td>
                    </tr>
                <?php } else { ?>
                    <tr>
                        <td>没有找到纠纷信息</td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#issue" href="#accept_solution"><h4 class="panel-title">接受方案</h4></a>
    </div>
    <div id="accept_solution" class="panel-collapse collapse in">
        <div class="panel-body">
            <?php if (!empty($issue_solution['platform'])) { ?>
                <?php foreach ($issue_solution['platform'] as $item) { ?>
                    <table class="table table-bordered">
                        <tr>
                            <td colspan="2" style="color:#169BD5;font-weight:bold;">
                                平台方案
                                <?php
                                switch ($item['solution_type']) {
                                    case 'refund':
                                        echo '(退款 <span style="color:red;">' . $item['refund_money_post'] . $item['refund_money_post_currency'] . '</span>)';
                                        break;
                                    case 'return_and_refund':
                                        echo '(退货退款 <span style="color:red;">' . $item['refund_money_post'] . $item['refund_money_post_currency'] . '</span>)';
                                        break;
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="width:100px;">提交时间：</td>
                            <td><?php echo $item['gmt_create']; ?></td>
                        </tr>
                        <tr>
                            <td>方案状态：</td>
                            <td>
                                <?php
                                switch ($item['status']) {
                                    case 'wait_buyer_and_seller_accept':
                                        echo '待买卖家双方接受';
                                        break;
                                    case 'wait_buyer_accept':
                                        echo '待买家接受';
                                        break;
                                    case 'wait_seller_accept':
                                        echo '待卖家接受';
                                        break;
                                    case 'reached':
                                        echo '达成';
                                        break;
                                    case 'buyer_refused':
                                        echo '买家拒绝';
                                        break;
                                    case 'seller_accept_and_buyer_refused':
                                        echo '卖家接受买家拒绝';
                                        break;
                                    case 'reach_cancle':
                                        echo '退货阶段,卖家上升仲裁,平台给方案,之前达成的方案改成此状态';
                                        break;
                                    case 'perform':
                                        echo '执行';
                                        break;
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td>备注：</td>
                            <td>
                                <?php echo $item['content']; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>操作：</td>
                            <td>
                                <?php
                                switch ($item['status']) {
                                    //待买卖家双方接受
                                    case 'wait_buyer_and_seller_accept':
                                        //待卖家接受
                                    case 'wait_seller_accept':
                                        echo "<button type='button' class='btn btn-primary agreeSolutionBtn' data-solutiontype='{$item['solution_type']}' data-issueid='{$item['platform_dispute_id']}' data-solutionid='{$item['solution_id']}'>同意</button>";
                                        break;
                                    default:
                                        break;
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                <?php } ?>
            <?php } else { ?>
                <table class="table table-bordered">
                    <tr>
                        <td>没有平台方案</td>
                    </tr>
                </table>
            <?php } ?>


            <?php if (!empty($issue_solution['buyer'])) { ?>
                <?php foreach ($issue_solution['buyer'] as $item) { ?>
                    <table class="table table-bordered">
                        <tr>
                            <td colspan="2" style="color:#169BD5;font-weight:bold;">
                                买家方案
                                <?php
                                switch ($item['solution_type']) {
                                    case 'refund':
                                        echo '(退款 <span style="color:red;">' . $item['refund_money_post'] . $item['refund_money_post_currency'] . '</span>)';
                                        break;
                                    case 'return_and_refund':
                                        echo '(退货退款 <span style="color:red;">' . $item['refund_money_post'] . $item['refund_money_post_currency'] . '</span>)';
                                        break;
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="width:100px;">提交时间：</td>
                            <td><?php echo $item['gmt_create']; ?></td>
                        </tr>
                        <tr>
                            <td>方案状态：</td>
                            <td>
                                <?php
                                switch ($item['status']) {
                                    case 'wait_buyer_and_seller_accept':
                                        echo '待买卖家双方接受';
                                        break;
                                    case 'wait_buyer_accept':
                                        echo '待买家接受';
                                        break;
                                    case 'wait_seller_accept':
                                        echo '待卖家接受';
                                        break;
                                    case 'reached':
                                        echo '达成';
                                        break;
                                    case 'buyer_refused':
                                        echo '买家拒绝';
                                        break;
                                    case 'seller_accept_and_buyer_refused':
                                        echo '卖家接受买家拒绝';
                                        break;
                                    case 'reach_cancle':
                                        echo '退货阶段,卖家上升仲裁,平台给方案,之前达成的方案改成此状态';
                                        break;
                                    case 'perform':
                                        echo '执行';
                                        break;
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td>备注：</td>
                            <td>
                                <?php echo $item['content']; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>附件：</td>
                            <td>
                                <?php
                                if (!empty($item['platform_dispute_id'])) {
                                    //获取买家凭证
                                    $buyerAttachment = AliexpressDisputeAttachments::find()->select('file_path')->where([
                                        'platform_dispute_id' => $item['platform_dispute_id'],
                                        'owner' => 'buyer'
                                    ])->asArray()->all();
                                }
                                ?>

                                <?php if (!empty($buyerAttachment)) { ?>
                                    <?php foreach ($buyerAttachment as $attachment) { ?>
                                        <img src="<?php echo $attachment['file_path']; ?>" class="issueAttachment">
                                    <?php } ?>
                                <?php } else { ?>
                                    无
                                <?php } ?>
                            </td>
                        </tr>
                        <tr>
                            <td>操作：</td>
                            <td>
                                <?php
                                switch ($item['status']) {
                                    //待买卖家双方接受
                                    case 'wait_buyer_and_seller_accept':
                                        //待卖家接受
                                    case 'wait_seller_accept':
                                        echo "<button type='button' class='btn btn-primary agreeSolutionBtn' data-solutiontype='{$item['solution_type']}' data-issueid='{$item['platform_dispute_id']}' data-solutionid='{$item['solution_id']}'>同意</button>";
                                        echo "&nbsp;&nbsp;";
                                        echo "<button type='button' class='btn btn-danger refuseAndAddSolutionBtn' data-solutiontype='{$item['solution_type']}' data-issueid='{$item['platform_dispute_id']}' data-solutionid='{$item['solution_id']}'>拒绝并新增方案</button>";
                                        break;
                                    default:
                                        break;
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                <?php } ?>
            <?php } else { ?>
                <table class="table table-bordered">
                    <tr>
                        <td>没有买家方案</td>
                    </tr>
                </table>
            <?php } ?>
        </div>
    </div>
</div>

<div style="margin:15px 0;">
    <!--
    <button type="button" id="addSellerSolution" class="btn btn-primary" data-issueid="<?php echo !empty($issue_id) ? $issue_id : ''; ?>">
        新增方案
    </button>
    -->

    <button type="button" id="uploadIssueImg" class="btn btn-primary" data-issueid="<?php echo !empty($issue_id) ? $issue_id : ''; ?>">
        上传凭证
    </button>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#issue" href="#my_solution"><h4 class="panel-title">我的方案</h4></a>
    </div>
    <div id="my_solution" class="panel-collapse collapse in">
        <div class="panel-body">
            <?php if (!empty($issue_solution['seller'])) { ?>
                <?php foreach ($issue_solution['seller'] as $item) { ?>
                    <table class="table table-bordered">
                        <tr>
                            <td colspan="2" style="color:#169BD5;font-weight:bold;">
                                我的方案
                                <?php
                                switch ($item['solution_type']) {
                                    case 'refund':
                                        echo '(退款 <span style="color:red;">' . $item['refund_money_post'] . $item['refund_money_post_currency'] . '</span>)';
                                        break;
                                    case 'return_and_refund':
                                        echo '(退货退款 <span style="color:red;">' . $item['refund_money_post'] . $item['refund_money_post_currency'] . '</span>)';
                                        break;
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="width:100px;">提交时间：</td>
                            <td><?php echo $item['gmt_create']; ?></td>
                        </tr>
                        <tr>
                            <td>是否默认：</td>
                            <td>
                                <?php echo $item['is_default'] ? '是' : '否'; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>方案状态：</td>
                            <td>
                                <?php
                                switch ($item['status']) {
                                    case 'wait_buyer_and_seller_accept':
                                        echo '待买卖家双方接受';
                                        break;
                                    case 'wait_buyer_accept':
                                        echo '待买家接受';
                                        break;
                                    case 'wait_seller_accept':
                                        echo '待卖家接受';
                                        break;
                                    case 'reached':
                                        echo '达成';
                                        break;
                                    case 'buyer_refused':
                                        echo '买家拒绝';
                                        break;
                                    case 'seller_accept_and_buyer_refused':
                                        echo '卖家接受买家拒绝';
                                        break;
                                    case 'reach_cancle':
                                        echo '退货阶段,卖家上升仲裁,平台给方案,之前达成的方案改成此状态';
                                        break;
                                    case 'perform':
                                        echo '执行';
                                        break;
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td>备注：</td>
                            <td>
                                <?php echo $item['content']; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>附件：</td>
                            <td>
                                <?php
                                if (!empty($item['platform_dispute_id'])) {
                                    //获取卖家凭证
                                    $sellerAttachment = AliexpressDisputeAttachments::find()->select('file_path')->where([
                                        'platform_dispute_id' => $item['platform_dispute_id'],
                                        'owner' => 'seller'
                                    ])->asArray()->all();
                                }
                                ?>

                                <?php if (!empty($sellerAttachment)) { ?>
                                    <?php foreach ($sellerAttachment as $attachment) { ?>
                                        <img src="<?php echo $attachment['file_path']; ?>" class="issueAttachment">
                                    <?php } ?>
                                <?php } else { ?>
                                    无
                                <?php } ?>
                            </td>
                        </tr>
                        <tr>
                            <td>操作：</td>
                            <td>
                                <button type="button" class="btn btn-primary updateSolutionBtn" data-solutioncontext="<?php echo rawurlencode($item['content']); ?>" data-refundamount="<?php echo !empty($item['refund_money_post']) ? $item['refund_money_post'] : 0; ?>"
                                        data-solutiontype="<?php echo $item['solution_type']; ?>" data-solutionid="<?php echo $item['solution_id']; ?>">修改
                                </button>
                            </td>
                        </tr>
                    </table>
                <?php } ?>
            <?php } else { ?>
                <table class="table table-bordered">
                    <tr>
                        <td>没有卖家方案</td>
                    </tr>
                </table>
            <?php } ?>
        </div>
    </div>
</div>

<div class="modal fade in" id="addSolutionModal" tabindex="-1" role="dialog" aria-labelledby="addSolutionModalLabel"
     style="top:300px;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">添加协商方案</h4>
            </div>
            <div class="modal-body">
                <form id="addSolutionForm" class="form-horizontal">
                    <div class="form-group">
                        <div class="col-sm-12">
                            <span style="color:red">* 注意：若您只想保留一个方案，请直接修改原有方案</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2">方案类型：</label>
                        <div class="col-sm-10">
                            <input type="radio" name="add_solution_type" value="refund" checked>退款
                            &nbsp;&nbsp;&nbsp;&nbsp;
                            <input type="radio" name="add_solution_type" value="return_and_refund">退货退款
                        </div>
                    </div>
                    <div class="form-group" id="return_good_address" style="display:none;">
                        <label class="col-sm-2">退货地址：</label>
                        <div class="col-sm-10">
                            <select name="return_good_address_id" class="form-control">
                                <option value="">请选择</option>
                                <?php if (!empty($refundAddress)) { ?>
                                    <?php foreach ($refundAddress as $address) { ?>
                                        <option value="<?php echo $address['address_id'] ?>">
                                            <?php
                                            echo !empty($address['name']) ? $address['name'] : '';
                                            echo !empty($address['phone']) ? '(' . $address['phone'] . ')' : '';
                                            echo !empty($address['country']) ? $address['country'] . '-' : '';
                                            echo !empty($address['province']) ? $address['province'] . '-' : '';
                                            echo !empty($address['county']) ? $address['county'] . '-' : '';
                                            echo !empty($address['street']) ? $address['street'] . '-' : '';
                                            echo !empty($address['street_address']) ? $address['street_address'] : '';
                                            ?>
                                        </option>
                                    <?php } ?>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2">退款金款：</label>
                        <div class="col-sm-10">
                            <div class="input-group">
                                <span class="input-group-addon"><?php echo $issue_info['refund_money_max_local_currency']; ?></span>
                                <input type="text" class="form-control" name="refund_amount" data-maxmoney="<?php echo !empty($issue_info['refund_money_max_local']) ? $issue_info['refund_money_max_local'] : 0; ?>">
                                <input type="hidden" name="refund_amount_currency" value="<?php echo $issue_info['refund_money_max_local_currency']; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2">描述问题：</label>
                        <div class="col-sm-10">
                            <textarea name="solution_context" class="form-control" rows="6"></textarea>
                        </div>
                    </div>
                    <input type="hidden" name="issue_id" value="<?php echo !empty($issue_id) ? $issue_id : ''; ?>">
                    <input type="hidden" name="modify_seller_solution_id" value="">
                    <input type="hidden" name="buyer_solution_id" value="">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="addSolutionBtn">保存</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade in" id="uploadIssueImgModal" tabindex="-1" role="dialog" aria-labelledby="uploadIssueImgModalLabel"
     style="top:300px;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">上传凭证</h4>
            </div>
            <div class="modal-body">
                <form id="uploadIssueImgForm" class="form-horizontal" enctype="multipart/form-data">
                    <div class="form-group">
                        <div class="col-sm-12">
                            <span style="color:red">* 注意：图片只能是JPG,JPEG,PNG格式,2M大小</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2">图片：</label>
                        <div class="col-sm-10">
                            <input type="file" name="image">
                        </div>
                    </div>
                    <input type="hidden" name="issue_id" value="<?php echo !empty($issue_id) ? $issue_id : ''; ?>">
                    <input type="hidden" name="id" value="<?php echo !empty($id) ? $id : 0; ?>">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="uploadIssueImgBtn">上传</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade in" id="seeIssueAttachmentModal" tabindex="-1" role="dialog" aria-labelledby="seeIssueAttachmentLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">查看凭证</h4>
            </div>
            <div class="modal-body">
                <img src="" style="width:100%;height:auto;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade in" id="agreeIssueYesRule" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">同意纠纷</h4>
            </div>
            <div class="modal-body">
                <p>当双方都同意后，纠纷将会按照该案执行，同意后无法取消，请确认是否同意该方案？</p>
                <br>
                <p>根据售后单规则配置，同意纠纷时创建的售后单信息如下：</p>
                <table class="table table-bordered">
                    <tr>
                        <td>责任部门</td>
                        <td>售后原因</td>
                        <td>金额/币种</td>
                    </tr>
                    <tr>
                        <td id="afterSaleRuleDepartmentId"></td>
                        <td id="afterSaleRuleReasonId"></td>
                        <td>
                            <div class="input-group">
                                <input type="text" class="form-control" id="afterSaleRuleAmount">
                                <div class="input-group-addon" id="afterSaleRuleCurrency">USD</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="yesRuleAgreeSolutionBtn">确认</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <input type="hidden" name="issue_id" value="">
                <input type="hidden" name="solution_id" value="">
            </div>
        </div>
    </div>
</div>

<div class="modal fade in" id="agreeIssueNoRule" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">同意纠纷</h4>
            </div>
            <div class="modal-body">
                <p>当双方都同意后，纠纷将会按照该案执行，同意后无法取消，请确认是否同意该方案？</p>
                <br>
                <p>根据售后单规则配置，没有匹配到规则，您可以：</p>
                <a href="<?php echo Url::toRoute(['/systems/aftersalemanage/add']); ?>" target="_blank" style="color:#169BD5;">添加售后单规则</a>
                <br>
                <a href="#" id="manualRegAfterSaleOrder" target="_blank" style="color:#169BD5;">手动登记售后单</a>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="noRuleAgreeSolutionBtn">确认</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <input type="hidden" name="issue_id" value="">
                <input type="hidden" name="solution_id" value="">
            </div>
        </div>
    </div>
</div>

<div class="modal fade in" id="agreeIssueRemind" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">同意纠纷</h4>
            </div>
            <div class="modal-body">
                <p>若同意此方案，此方案将立即执行，并将退款退回给买家，同时，不要安排商品出货。若你已将商品出货，请拒绝此方案。</p>
                <p>确定同意此方案？</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="remindAgreeSolutionBtn">确认</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <input type="hidden" name="issue_id" value="">
                <input type="hidden" name="solution_id" value="">
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {

        //刷新纠纷信息
        function flushIssueInfo(issue_id) {
            $.post("<?php echo Url::toRoute(['/mails/aliexpressdispute/updateissueinfo']) ?>", {
                "issue_id": issue_id,
            }, function (data) {
                if (data["code"] == 1) {
                    window.location.replace("<?php echo Url::toRoute(['/mails/aliexpressdispute/handle', 'id' => $id]); ?>");
                }
            }, "json");
        }

        //更新纠纷信息
        $("#updateIssueInfo").on("click", function () {
            var issueId = $(this).attr("data-issueid");

            if (issueId.length == 0) {
                layer.alert("纠纷ID不能为空");
                return false;
            }

            $.post("<?php echo Url::toRoute(['/mails/aliexpressdispute/updateissueinfo']) ?>", {
                "issue_id": issueId,
            }, function (data) {
                if (data["code"] == 1) {
                    layer.alert("更新成功");
                    window.location.replace("<?php echo Url::toRoute(['/mails/aliexpressdispute/handle', 'id' => $id]); ?>");
                } else {
                    layer.alert(data["message"]);
                }
            }, "json");
        });

        //点击退货退款
        $("input[name='add_solution_type'][value='return_and_refund']").on("click", function () {
            $("#return_good_address").css("display", "block");
        });
        //点击退款
        $("input[name='add_solution_type'][value='refund']").on("click", function () {
            $("#return_good_address").css("display", "none");
        });

        //同意方案
        $(".agreeSolutionBtn").on("click", function () {
            var issue_id = $(this).attr("data-issueid");
            var solution_id = $(this).attr("data-solutionid");

            if (issue_id.length == 0) {
                layer.alert("纠纷ID不能为空");
                return false;
            }
            if (solution_id.length == 0) {
                layer.alert("协商方案ID不能为空");
                return false;
            }

            if ($("input[name='auto_create_aftersale_order']").is(":checked")) {
                $.post("<?php echo Url::toRoute(['/mails/aliexpressdispute/getaftersaleorderrule']); ?>", {
                    "issue_id": issue_id,
                }, function (data) {
                    if (data["code"] == 1) {
                        if (data["have"] == 1) {
                            var data = data["data"];
                            $("#agreeIssueYesRule input[name='issue_id']").val(issue_id);
                            $("#agreeIssueYesRule input[name='solution_id']").val(solution_id);
                            $("#afterSaleRuleDepartmentId").text(data["department_name"]);
                            $("#afterSaleRuleReasonId").text(data["reason_name"]);
                            $("#afterSaleRuleAmount").val(data["order_amount"]);
                            $("#afterSaleRuleCurrency").text(data["order_currency"]);
                            $("#agreeIssueYesRule").modal("show");
                        } else {
                            $("#agreeIssueNoRule input[name='issue_id']").val(issue_id);
                            $("#agreeIssueNoRule input[name='solution_id']").val(solution_id);
                            $("#manualRegAfterSaleOrder").attr("href", "/aftersales/order/add?platform=ALI&order_id=" + (data["data"]["order_id"] ? data["data"]["order_id"] : ""));
                            $("#agreeIssueNoRule").modal("show");
                        }
                    } else {
                        layer.alert(data["message"]);
                    }
                }, "json");
            } else {
                $("#agreeIssueRemind input[name='issue_id']").val(issue_id);
                $("#agreeIssueRemind input[name='solution_id']").val(solution_id);
                $("#agreeIssueRemind").modal("show");
            }

            return false;
        });

        //最终同意纠纷
        $("#yesRuleAgreeSolutionBtn,#noRuleAgreeSolutionBtn,#remindAgreeSolutionBtn").on("click", function () {
            var _this = $(this);
            var issue_id = $(this).siblings("input[name='issue_id']").val();
            var solution_id = $(this).siblings("input[name='solution_id']").val();

            if (issue_id.length == 0) {
                layer.alert("纠纷ID不能为空");
                return false;
            }
            if (solution_id.length == 0) {
                layer.alert("协商方案ID不能为空");
                return false;
            }

            if (_this.attr("id") == "yesRuleAgreeSolutionBtn") {
                var amount = parseFloat($("#afterSaleRuleAmount").val());
                if (isNaN(amount)) {
                    layer.alert("请填写正确的金额");
                    return false;
                }
                if (amount < 0) {
                    layer.alert("金额不能小于0");
                    return false;
                }
            }

            $.post("<?php echo Url::toRoute(['/mails/aliexpressdispute/agreeissuesolution']); ?>", {
                "issue_id": issue_id,
                "solution_id": solution_id,
            }, function (data) {
                if (data["code"] == 1) {
                    layer.alert("同意方案成功");

                    if (_this.attr("id") == "yesRuleAgreeSolutionBtn") {
                        $.ajax({
                            url: "<?php echo Url::toRoute(['/mails/aliexpressdispute/createaftersaleorder']); ?>",
                            async: false,
                            type: "POST",
                            data: {"issue_id" : issue_id, "amount" : amount},
                            dataType: "json",
                            success: function (data) {
                            }
                        });
                    }

                    flushIssueInfo(issue_id);
                } else {
                    layer.alert(data["message"]);
                }

                $("#agreeIssueYesRule").modal("hide");
                $("#agreeIssueNoRule").modal("hide");
                $("#agreeIssueRemind").modal("hide");
            }, "json");
            return false;
        });

        //拒绝方案
        $(".refuseSolutionBtn").on("click", function () {
            var issue_id = $(this).attr("data-issueid");
            var solution_id = $(this).attr("data-solutionid");

            if (issue_id.length == 0) {
                layer.alert("纠纷ID不能为空");
                return false;
            }
            if (solution_id.length == 0) {
                layer.alert("协商方案ID不能为空");
                return false;
            }

            layer.confirm('是否确定拒绝该方案', function (index) {
                $.post("<?php echo Url::toRoute(['/mails/aliexpressdispute/refuseissuesolution']); ?>", {
                    "issue_id": issue_id,
                    "solution_id": solution_id,
                }, function (data) {
                    if (data["code"] == 1) {
                        layer.alert("拒绝方案成功");
                    } else {
                        layer.alert(data["message"]);
                    }
                }, "json");

                layer.close(index);
            });
            return false;
        });

        //新增方案
        $("#addSellerSolution").on("click", function () {
            $("#addSolutionForm input[name='modify_seller_solution_id']").val("");
            $("#addSolutionModal").modal("show");
            return false;
        });

        //拒绝并新增方案
        $(".refuseAndAddSolutionBtn").on("click", function () {
            var solution_id = $(this).attr("data-solutionid");
            $("#addSolutionForm input[name='buyer_solution_id']").val(solution_id);
            $("#addSolutionForm input[name='modify_seller_solution_id']").val("");
            $("#addSolutionModal").modal("show");
            return false;
        });

        //修改方案
        $(".updateSolutionBtn").on("click", function () {
            var solution_context = decodeURIComponent($(this).attr("data-solutioncontext"));
            var refund_amount = $(this).attr("data-refundamount");
            var solution_type = $(this).attr("data-solutiontype");
            var solution_id = $(this).attr("data-solutionid");

            if (solution_id.length == 0) {
                layer.alert("协商方案ID不能为空");
                return false;
            }

            $("#addSolutionForm input[name='add_solution_type'][value='" + solution_type + "']").click();
            $("#addSolutionForm input[name='refund_amount']").val(refund_amount);
            $("#addSolutionForm textarea[name='solution_context']").val(solution_context);
            $("#addSolutionForm input[name='modify_seller_solution_id']").val(solution_id);

            $("#addSolutionModal").modal("show");
            return false;
        });
        //弹窗关闭时清空数据
        $("#addSolutionModal").on('hidden.bs.modal', function (e) {
            $("#addSolutionForm")[0].reset();
            $("#addSolutionForm input[name='buyer_solution_id']").val("");
            $("#addSolutionForm input[name='modify_seller_solution_id']").val("");
            $("#return_good_address").css("display", "none");
        });
        //添加或修改方案
        $("#addSolutionBtn").on("click", function () {
            var issue_id = $("input[name='issue_id']").val();
            var add_solution_type = $("input[name='add_solution_type']").val();
            var refund_amount = $("input[name='refund_amount']").val();
            var maxmoney = parseFloat($("input[name='refund_amount']").attr("data-maxmoney"));
            var solution_context = $("textarea[name='solution_context']").val();

            if (add_solution_type.length == 0) {
                layer.alert("请选择方案类型");
                return false;
            }
            if (refund_amount.length == 0) {
                layer.alert("请填写退款金款");
                return false;
            }
            refund_amount = parseFloat(refund_amount);
            if (isNaN(refund_amount)) {
                layer.alert("请填写正确退款金款");
                return false;
            }
            if (refund_amount < 0) {
                layer.alert("退款金款不能小于0");
                return false;
            }
            if (refund_amount > maxmoney) {
                layer.alert("退款金款不能大于" + maxmoney);
                return false;
            }
            if (solution_context.length == 0) {
                layer.alert("请填写描述问题");
                return false;
            }

            var params = $("#addSolutionForm").serialize();
            $.post("<?php echo Url::toRoute(['/mails/aliexpressdispute/saveissuesolution']) ?>", params, function (data) {
                if (data["code"] == 1) {
                    layer.alert(data["message"]);
                    $("#addSolutionModal").modal("hide");

                    flushIssueInfo(issue_id);
                } else {
                    layer.alert(data["message"]);
                }
            }, "json");
            return false;
        });


        //上传纠纷图片
        $("#uploadIssueImg").on("click", function () {
            $("#uploadIssueImgModal").modal("show");
            return false;
        });
        //弹窗关闭时清空数据
        $("#uploadIssueImgModal").on('hidden.bs.modal', function (e) {
            $("#uploadIssueImgForm")[0].reset();
        });
        //上传纠纷图片
        $("#uploadIssueImgBtn").on("click", function () {
            var image = $("#uploadIssueImgForm input[name='image']").val();

            if (image.length == 0 || image == "") {
                layer.alert("请选择上传图片");
                return false;
            }

            $("#uploadIssueImgForm").attr("action", "<?php echo Url::toRoute(['/mails/aliexpressdispute/addissueimage']) ?>");
            $("#uploadIssueImgForm").attr("method", "post");
            $("#uploadIssueImgForm").submit();
            return false;
        });

        //查看凭证
        $(".issueAttachment").on("click", function () {
            var url = $(this).attr("src");
            $("#seeIssueAttachmentModal img").attr("src", url);
            $("#seeIssueAttachmentModal").modal("show");
            return false;
        });
        $("#seeIssueAttachmentModal").on('hidden.bs.modal', function (e) {
            $("#seeIssueAttachmentModal img").attr("src", "");
        });
    });
</script>