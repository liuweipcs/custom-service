<?php

use yii\helpers\Url;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\AliexpressDisputeAttachments;
use app\modules\mails\models\AliexpressDisputeProcess;

?>

<link href="<?php echo yii\helpers\Url::base(true); ?>/css/timeline.css" rel="stylesheet">
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
                        <td>售后单号：</td>
                        <td colspan="3">
                            <?php
                            if (!empty($afterSalesOrders)) {
                                foreach ($afterSalesOrders as $afterSalesOrder) {
                                    if ($afterSalesOrder['type'] == 1) {
                                        echo '<a _width="100%" _height="100%" class="edit-button" href="' . Url::toRoute(['/aftersales/sales/detailrefund', 'after_sale_id' => $afterSalesOrder['after_sale_id'], 'platform_code' => Platform::PLATFORM_CODE_ALI]) . '">' . $afterSalesOrder['after_sale_id'] . '</a>';
                                    } else if ($afterSalesOrder['type'] == 2) {
                                        echo '<a _width="100%" _height="100%" class="edit-button" href="' . Url::toRoute(['/aftersales/sales/detailreturn', 'after_sale_id' => $afterSalesOrder['after_sale_id'], 'platform_code' => Platform::PLATFORM_CODE_ALI]) . '">' . $afterSalesOrder['after_sale_id'] . '</a>';
                                    } else if ($afterSalesOrder['type'] == 3) {
                                        echo '<a _width="100%" _height="100%" class="edit-button" href="' . Url::toRoute(['/aftersales/sales/detailredirect', 'after_sale_id' => $afterSalesOrder['after_sale_id'], 'platform_code' => Platform::PLATFORM_CODE_ALI]) . '">' . $afterSalesOrder['after_sale_id'] . '</a>';
                                    }
                                }
                            } else {
                                echo '没有售后单号';
                            }
                            ?>
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

<?php
//判断纠纷是否结束
$isIssueEnd = false;
if (!empty($issue_solution['buyer'])) {
    foreach ($issue_solution['buyer'] as $item) {
        if (!empty($item['reached_time'])) {
            $isIssueEnd = true;
        }
    }
}
if (!empty($issue_solution['platform'])) {
    foreach ($issue_solution['platform'] as $item) {
        if (!empty($item['reached_time'])) {
            $isIssueEnd = true;
        }
    }
}
if (!empty($issue_solution['seller'])) {
    foreach ($issue_solution['seller'] as $item) {
        if (!empty($item['reached_time'])) {
            $isIssueEnd = true;
        }
    }
}

if ($isIssueEnd) {

    if (!empty($issue_id)) {
        //获取买家凭证
        $buyerAttachment = AliexpressDisputeAttachments::find()->select('file_path')->where([
            'platform_dispute_id' => $issue_id,
            'owner' => 'buyer'
        ])->asArray()->all();

        //获取卖家凭证
        $sellerAttachment = AliexpressDisputeAttachments::find()->select('file_path')->where([
            'platform_dispute_id' => $issue_id,
            'owner' => 'seller'
        ])->asArray()->all();
    }

    ?>
    <div class="panel panel-default">
        <div class="panel-heading">
            <a data-toggle="collapse" data-parent="#issue" href="#issue_result"><h4 class="panel-title">纠纷结果</h4></a>
        </div>
        <div id="issue_result" class="panel-collapse collapse in">
            <div class="panel-body">

                <?php if (!empty($issue_solution['buyer'])) { ?>
                    <?php foreach ($issue_solution['buyer'] as $item) { ?>
                        <?php if (!empty($item['reached_time'])) { ?>
                            <table class="table table-bordered" style="table-layout:fixed;">
                                <tr>
                                    <td>方案提出者：</td>
                                    <td>
                                        <?php
                                        switch ($item['solution_owner']) {
                                            case 'seller':
                                                echo '卖家';
                                                break;
                                            case 'buyer':
                                                echo '买家';
                                                break;
                                            case 'platform':
                                                echo '平台';
                                                break;
                                        }
                                        ?>
                                    </td>
                                    <td>方案类型：</td>
                                    <td>
                                        <?php
                                        switch ($item['solution_type']) {
                                            case 'refund':
                                                echo '退款';
                                                break;
                                            case 'return_and_refund':
                                                echo '退货退款';
                                                break;
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>退款币种/金额：</td>
                                    <td>
                                        <?php echo $item['refund_money_post'] . '/' . $item['refund_money_post_currency']; ?>
                                    </td>
                                    <td>方案达成类型：</td>
                                    <td>
                                        <?php
                                        switch ($item['reached_type']) {
                                            case 'negotiation_consensus':
                                                echo '协商一致';
                                                break;
                                            case 'platform_arbitrate':
                                                echo '平台仲裁';
                                                break;
                                            case 'seller_reponse_timeout':
                                                echo '卖家响应超时';
                                                break;
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>运费承担方：</td>
                                    <td>
                                        <?php
                                        switch ($item['logistics_fee_bear_role']) {
                                            case 'seller':
                                                echo '卖家';
                                                break;
                                            case 'buyer':
                                                echo '买家';
                                                break;
                                            case 'platform':
                                                echo '平台';
                                                break;
                                        }
                                        ?>
                                    </td>
                                    <td>达成时间：</td>
                                    <td>
                                        <?php echo !empty($item['reached_time']) ? $item['reached_time'] : ''; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>买家凭证：</td>
                                    <td colspan="3">
                                        <?php if (!empty($buyerAttachment)) { ?>
                                            <?php foreach ($buyerAttachment as $attachment) { ?>
                                                <img src="<?php echo $attachment['file_path']; ?>" class="issueAttachment">
                                            <?php } ?>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>我的凭证：</td>
                                    <td colspan="3">
                                        <?php if (!empty($sellerAttachment)) { ?>
                                            <?php foreach ($sellerAttachment as $attachment) { ?>
                                                <img src="<?php echo $attachment['file_path']; ?>" class="issueAttachment">
                                            <?php } ?>
                                        <?php } ?>
                                    </td>
                                </tr>
                            </table>
                        <?php } ?>
                    <?php } ?>
                <?php } ?>

                <?php if (!empty($issue_solution['platform'])) { ?>
                    <?php foreach ($issue_solution['platform'] as $item) { ?>
                        <?php if (!empty($item['reached_time'])) { ?>
                            <table class="table table-bordered" style="table-layout:fixed;">
                                <tr>
                                    <td>方案提出者：</td>
                                    <td>
                                        <?php
                                        switch ($item['solution_owner']) {
                                            case 'seller':
                                                echo '卖家';
                                                break;
                                            case 'buyer':
                                                echo '买家';
                                                break;
                                            case 'platform':
                                                echo '平台';
                                                break;
                                        }
                                        ?>
                                    </td>
                                    <td>方案类型：</td>
                                    <td>
                                        <?php
                                        switch ($item['solution_type']) {
                                            case 'refund':
                                                echo '退款';
                                                break;
                                            case 'return_and_refund':
                                                echo '退货退款';
                                                break;
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>退款币种/金额：</td>
                                    <td>
                                        <?php echo $item['refund_money_post'] . '/' . $item['refund_money_post_currency']; ?>
                                    </td>
                                    <td>方案达成类型：</td>
                                    <td>
                                        <?php
                                        switch ($item['reached_type']) {
                                            case 'negotiation_consensus':
                                                echo '协商一致';
                                                break;
                                            case 'platform_arbitrate':
                                                echo '平台仲裁';
                                                break;
                                            case 'seller_reponse_timeout':
                                                echo '卖家响应超时';
                                                break;
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>运费承担方：</td>
                                    <td>
                                        <?php
                                        switch ($item['logistics_fee_bear_role']) {
                                            case 'seller':
                                                echo '卖家';
                                                break;
                                            case 'buyer':
                                                echo '买家';
                                                break;
                                            case 'platform':
                                                echo '平台';
                                                break;
                                        }
                                        ?>
                                    </td>
                                    <td>达成时间：</td>
                                    <td>
                                        <?php echo !empty($item['reached_time']) ? $item['reached_time'] : ''; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>买家凭证：</td>
                                    <td colspan="3">
                                        <?php if (!empty($buyerAttachment)) { ?>
                                            <?php foreach ($buyerAttachment as $attachment) { ?>
                                                <img src="<?php echo $attachment['file_path']; ?>" class="issueAttachment">
                                            <?php } ?>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>我的凭证：</td>
                                    <td colspan="3">
                                        <?php if (!empty($sellerAttachment)) { ?>
                                            <?php foreach ($sellerAttachment as $attachment) { ?>
                                                <img src="<?php echo $attachment['file_path']; ?>" class="issueAttachment">
                                            <?php } ?>
                                        <?php } ?>
                                    </td>
                                </tr>
                            </table>
                        <?php } ?>
                    <?php } ?>
                <?php } ?>

                <?php if (!empty($issue_solution['seller'])) { ?>
                    <?php foreach ($issue_solution['seller'] as $item) { ?>
                        <?php if (!empty($item['reached_time'])) { ?>
                            <table class="table table-bordered" style="table-layout:fixed;">
                                <tr>
                                    <td>方案提出者：</td>
                                    <td>
                                        <?php
                                        switch ($item['solution_owner']) {
                                            case 'seller':
                                                echo '卖家';
                                                break;
                                            case 'buyer':
                                                echo '买家';
                                                break;
                                            case 'platform':
                                                echo '平台';
                                                break;
                                        }
                                        ?>
                                    </td>
                                    <td>方案类型：</td>
                                    <td>
                                        <?php
                                        switch ($item['solution_type']) {
                                            case 'refund':
                                                echo '退款';
                                                break;
                                            case 'return_and_refund':
                                                echo '退货退款';
                                                break;
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>退款币种/金额：</td>
                                    <td>
                                        <?php echo $item['refund_money_post'] . '/' . $item['refund_money_post_currency']; ?>
                                    </td>
                                    <td>方案达成类型：</td>
                                    <td>
                                        <?php
                                        switch ($item['reached_type']) {
                                            case 'negotiation_consensus':
                                                echo '协商一致';
                                                break;
                                            case 'platform_arbitrate':
                                                echo '平台仲裁';
                                                break;
                                            case 'seller_reponse_timeout':
                                                echo '卖家响应超时';
                                                break;
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>运费承担方：</td>
                                    <td>
                                        <?php
                                        switch ($item['logistics_fee_bear_role']) {
                                            case 'seller':
                                                echo '卖家';
                                                break;
                                            case 'buyer':
                                                echo '买家';
                                                break;
                                            case 'platform':
                                                echo '平台';
                                                break;
                                        }
                                        ?>
                                    </td>
                                    <td>达成时间：</td>
                                    <td>
                                        <?php echo !empty($item['reached_time']) ? $item['reached_time'] : ''; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>买家凭证：</td>
                                    <td colspan="3">
                                        <?php if (!empty($buyerAttachment)) { ?>
                                            <?php foreach ($buyerAttachment as $attachment) { ?>
                                                <img src="<?php echo $attachment['file_path']; ?>" class="issueAttachment">
                                            <?php } ?>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>我的凭证：</td>
                                    <td colspan="3">
                                        <?php if (!empty($sellerAttachment)) { ?>
                                            <?php foreach ($sellerAttachment as $attachment) { ?>
                                                <img src="<?php echo $attachment['file_path']; ?>" class="issueAttachment">
                                            <?php } ?>
                                        <?php } ?>
                                    </td>
                                </tr>
                            </table>
                        <?php } ?>
                    <?php } ?>
                <?php } ?>

            </div>
        </div>
    </div>
<?php } ?>

<?php
//获取操作记录
$issueProcess = AliexpressDisputeProcess::find()
    ->where(['platform_dispute_id' => $issue_id,])
    ->orderBy('gmt_create DESC')
    ->asArray()
    ->all();
//操作记录ID数组
$issueProcessIds = [];
//操作记录的附件
$issueProcessAttachments = [];
if (!empty($issueProcess)) {
    $issueProcessIds = array_column($issueProcess, 'id');
    $issueProcessAttachments = AliexpressDisputeAttachments::find()
        ->where(['in', 'process_id', $issueProcessIds])
        ->asArray()
        ->all();
    if (!empty($issueProcessAttachments)) {
        $tmp = [];
        foreach ($issueProcessAttachments as $attachment) {
            $tmp[$attachment['process_id']][] = $attachment;
        }
        $issueProcessAttachments = $tmp;
    }
}

if (!empty($issueProcess)) {
    ?>
    <div class="panel panel-default">
        <div class="panel-heading">
            <a data-toggle="collapse" data-parent="#issue" href="#issue_process"><h4 class="panel-title">纠纷历史</h4></a>
        </div>
        <div id="issue_process" class="panel-collapse collapse in">
            <div class="panel-body" style="height:640px;overflow-y:auto;">
                <div class="timeline timeline-line-dotted">
                    <?php foreach ($issueProcess as $process) { ?>

                        <?php if ($process['submit_member_type'] == 'seller' || $process['submit_member_type'] == 'buyer') { ?>
                            <div class="timeline-item <?php echo ($process['submit_member_type'] == 'buyer') ? 'timeline-item-left' : 'timeline-item-right'; ?>">
                                <div class="timeline-point <?php echo ($process['submit_member_type'] == 'buyer') ? 'timeline-point-warning' : 'timeline-point-info'; ?>">
                                    <i class="fa fa-star"></i>
                                </div>
                                <div class="timeline-event <?php echo ($process['submit_member_type'] == 'buyer') ? 'timeline-event-warning' : 'timeline-event-info'; ?>">
                                    <div class="timeline-heading">
                                        <h4>
                                            <?php
                                            switch ($process['submit_member_type']) {
                                                case 'seller':
                                                    echo '卖家';
                                                    break;
                                                case 'buyer':
                                                    echo '买家';
                                                    break;
                                                case 'waiter':
                                                    echo '客服';
                                                    break;
                                                case 'system':
                                                    echo '系统';
                                                    break;
                                            }
                                            ?>
                                            <?php
                                            switch ($process['action_type']) {
                                                case 'initiate':
                                                    echo '发起纠纷';
                                                    break;
                                                case 'cancel':
                                                    echo '取消纠纷';
                                                    break;
                                                case 'buyer_accept':
                                                    echo '买家同意方案';
                                                    break;
                                                case 'seller_accept':
                                                    echo '卖家同意方案';
                                                    break;
                                                case 'buyer_refuse':
                                                    echo '买家拒绝方案';
                                                    break;
                                                case 'buyer_refuse_platform':
                                                    echo '买家拒绝平台方案';
                                                    break;
                                                case 'buyer_create_proof':
                                                    echo '买家创建凭证';
                                                    break;
                                                case 'buyer_delete_proof':
                                                    echo '买家删除凭证';
                                                    break;
                                                case 'buyer_create_solution':
                                                    echo '买家创建方案';
                                                    break;
                                                case 'buyer_modify_solution':
                                                    echo '买家修改方案';
                                                    break;
                                                case 'buyer_delete_solution':
                                                    echo '买家删除方案';
                                                    break;
                                                case 'buyer_accept_platform':
                                                    echo '买家接受平台方案';
                                                    break;
                                                case 'buyer_send_goods':
                                                    echo '买家发送商品';
                                                    break;
                                                case 'buyer_send_goods_timeout':
                                                    echo '买家发货超时';
                                                    break;
                                                case 'seller_create_solution':
                                                    echo '卖家创建方案';
                                                    break;
                                                case 'seller_modify_solution':
                                                    echo '卖家修改方案';
                                                    break;
                                                case 'seller_delete_solution':
                                                    echo '卖家删除方案';
                                                    break;
                                                case 'seller_accept_platform':
                                                    echo '卖家同意平台方案';
                                                    break;
                                                case 'seller_create_proof':
                                                    echo '卖家创建凭证';
                                                    break;
                                                case 'seller_delete_proof':
                                                    echo '卖家删除凭证';
                                                    break;
                                                case 'platform_perform_solution':
                                                    echo '平台执行方案';
                                                    break;
                                                case 'platform_process':
                                                    echo '平台介入处理';
                                                    break;
                                                case 'platform_give_solution':
                                                    echo '平台给出方案';
                                                    break;
                                                default :
                                                    echo $process['action_type'];
                                                    break;
                                            }
                                            ?>
                                        </h4>
                                    </div>
                                    <div class="timeline-body">
                                        <p>
                                            <?php echo !empty($process['content']) ? $process['content'] : ''; ?>
                                            <br>
                                            <?php if (array_key_exists($process['id'], $issueProcessAttachments)) { ?>
                                                <?php foreach ($issueProcessAttachments[$process['id']] as $attachment) { ?>
                                                    <img src="<?php echo $attachment['file_path']; ?>" class="issueAttachment">
                                                <?php } ?>
                                            <?php } ?>
                                        </p>
                                    </div>
                                    <div class="timeline-footer">
                                        <p class="text-right"><?php echo !empty($process['gmt_create']) ? $process['gmt_create'] : ''; ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php } else { ?>
                            <span class="timeline-label">
                                <span class="label label-danger">
                                    <?php
                                    switch ($process['submit_member_type']) {
                                        case 'seller':
                                            echo '卖家';
                                            break;
                                        case 'buyer':
                                            echo '买家';
                                            break;
                                        case 'waiter':
                                            echo '客服';
                                            break;
                                        case 'system':
                                            echo '系统';
                                            break;
                                    }
                                    ?>
                                    <?php
                                    switch ($process['action_type']) {
                                        case 'initiate':
                                            echo '发起纠纷';
                                            break;
                                        case 'cancel':
                                            echo '取消纠纷';
                                            break;
                                        case 'buyer_accept':
                                            echo '买家同意方案';
                                            break;
                                        case 'seller_accept':
                                            echo '卖家同意方案';
                                            break;
                                        case 'buyer_refuse':
                                            echo '买家拒绝方案';
                                            break;
                                        case 'buyer_refuse_platform':
                                            echo '买家拒绝平台方案';
                                            break;
                                        case 'buyer_create_proof':
                                            echo '买家创建凭证';
                                            break;
                                        case 'buyer_delete_proof':
                                            echo '买家删除凭证';
                                            break;
                                        case 'buyer_create_solution':
                                            echo '买家创建方案';
                                            break;
                                        case 'buyer_modify_solution':
                                            echo '买家修改方案';
                                            break;
                                        case 'buyer_delete_solution':
                                            echo '买家删除方案';
                                            break;
                                        case 'buyer_accept_platform':
                                            echo '买家接受平台方案';
                                            break;
                                        case 'buyer_send_goods':
                                            echo '买家发送商品';
                                            break;
                                        case 'buyer_send_goods_timeout':
                                            echo '买家发货超时';
                                            break;
                                        case 'seller_create_solution':
                                            echo '卖家创建方案';
                                            break;
                                        case 'seller_modify_solution':
                                            echo '卖家修改方案';
                                            break;
                                        case 'seller_delete_solution':
                                            echo '卖家删除方案';
                                            break;
                                        case 'seller_accept_platform':
                                            echo '卖家同意平台方案';
                                            break;
                                        case 'seller_create_proof':
                                            echo '卖家创建凭证';
                                            break;
                                        case 'seller_delete_proof':
                                            echo '卖家删除凭证';
                                            break;
                                        case 'platform_perform_solution':
                                            echo '平台执行方案';
                                            break;
                                        case 'platform_process':
                                            echo '平台介入处理';
                                            break;
                                        case 'platform_give_solution':
                                            echo '平台给出方案';
                                            break;
                                        default :
                                            echo $process['action_type'];
                                            break;
                                    }
                                    ?>
                                    <?php echo !empty($process['gmt_create']) ? $process['gmt_create'] : ''; ?>
                                </span>
                            </span>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<div class="panel panel-default">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#issue" href="#buyer_solution"><h4 class="panel-title">买家方案</h4></a>
    </div>
    <div id="buyer_solution" class="panel-collapse collapse">
        <div class="panel-body">
            <?php if (!empty($issue_solution['buyer'])) { ?>
                <?php foreach ($issue_solution['buyer'] as $item) { ?>
                    <table class="table table-bordered">
                        <tr>
                            <td style="min-width:150px;">方案类型：</td>
                            <td>
                                <?php
                                switch ($item['solution_type']) {
                                    case 'refund':
                                        echo '退款';
                                        break;
                                    case 'return_and_refund':
                                        echo '退货退款';
                                        break;
                                }
                                ?>
                            </td>
                            <td>是否默认：</td>
                            <td>
                                <?php echo $item['is_default'] ? '是' : '否'; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>创建时间：</td>
                            <td><?php echo $item['gmt_create']; ?></td>
                            <td>修改时间：</td>
                            <td><?php echo $item['gmt_modified']; ?></td>
                        </tr>
                        <tr>
                            <td>退款金额(本币)：</td>
                            <td><?php echo $item['refund_money'] . $item['refund_money_currency']; ?></td>
                            <td>退款金额(美金)：</td>
                            <td><?php echo $item['refund_money_post'] . $item['refund_money_post_currency']; ?></td>
                        </tr>
                        <tr>
                            <td>方案状态：</td>
                            <td colspan="3">
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
                            <td>方案内容：</td>
                            <td colspan="3">
                                <?php echo $item['content']; ?>
                            </td>
                        </tr>
                    </table>
                <?php } ?>
            <?php } else { ?>
                <table class="table table-striped">
                    <tr>
                        <td>没有买家方案</td>
                    </tr>
                </table>
            <?php } ?>
        </div>
    </div>
</div>


<div class="panel panel-default">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#issue" href="#platform_solution"><h4 class="panel-title">平台方案</h4></a>
    </div>
    <div id="platform_solution" class="panel-collapse collapse">
        <div class="panel-body">
            <?php if (!empty($issue_solution['platform'])) { ?>
                <?php foreach ($issue_solution['platform'] as $item) { ?>
                    <table class="table table-bordered">
                        <tr>
                            <td style="min-width:150px;">方案类型：</td>
                            <td>
                                <?php
                                switch ($item['solution_type']) {
                                    case 'refund':
                                        echo '退款';
                                        break;
                                    case 'return_and_refund':
                                        echo '退货退款';
                                        break;
                                }
                                ?>
                            </td>
                            <td>是否默认：</td>
                            <td>
                                <?php echo $item['is_default'] ? '是' : '否'; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>创建时间：</td>
                            <td><?php echo $item['gmt_create']; ?></td>
                            <td>修改时间：</td>
                            <td><?php echo $item['gmt_modified']; ?></td>
                        </tr>
                        <tr>
                            <td>退款金额(本币)：</td>
                            <td><?php echo $item['refund_money'] . $item['refund_money_currency']; ?></td>
                            <td>退款金额(美金)：</td>
                            <td><?php echo $item['refund_money_post'] . $item['refund_money_post_currency']; ?></td>
                        </tr>
                        <tr>
                            <td>方案状态：</td>
                            <td colspan="3">
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
                            <td>方案内容：</td>
                            <td colspan="3">
                                <?php echo $item['content']; ?>
                            </td>
                        </tr>
                    </table>
                <?php } ?>
            <?php } else { ?>
                <table class="table table-striped">
                    <tr>
                        <td>没有平台方案</td>
                    </tr>
                </table>
            <?php } ?>
        </div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <a data-toggle="collapse" data-parent="#issue" href="#seller_solution"><h4 class="panel-title">卖家方案</h4></a>
    </div>
    <div id="seller_solution" class="panel-collapse collapse">
        <div class="panel-body">
            <?php if (!empty($issue_solution['seller'])) { ?>
                <?php foreach ($issue_solution['seller'] as $item) { ?>
                    <table class="table table-bordered">
                        <tr>
                            <td style="min-width:150px;">方案类型：</td>
                            <td>
                                <?php
                                switch ($item['solution_type']) {
                                    case 'refund':
                                        echo '退款';
                                        break;
                                    case 'return_and_refund':
                                        echo '退货退款';
                                        break;
                                }
                                ?>
                            </td>
                            <td>是否默认：</td>
                            <td>
                                <?php echo $item['is_default'] ? '是' : '否'; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>创建时间：</td>
                            <td><?php echo $item['gmt_create']; ?></td>
                            <td>修改时间：</td>
                            <td><?php echo $item['gmt_modified']; ?></td>
                        </tr>
                        <tr>
                            <td>退款金额(本币)：</td>
                            <td><?php echo $item['refund_money'] . $item['refund_money_currency']; ?></td>
                            <td>退款金额(美金)：</td>
                            <td><?php echo $item['refund_money_post'] . $item['refund_money_post_currency']; ?></td>
                        </tr>
                        <tr>
                            <td>方案状态：</td>
                            <td colspan="3">
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
                            <td>方案内容：</td>
                            <td colspan="3">
                                <?php echo $item['content']; ?>
                            </td>
                        </tr>
                    </table>
                <?php } ?>
            <?php } else { ?>
                <table class="table table-striped">
                    <tr>
                        <td>没有卖家方案</td>
                    </tr>
                </table>
            <?php } ?>
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

<script type="text/javascript">
    $(function () {
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
                    <?php if (!empty($id)) { ?>
                    window.location.replace("<?php echo Url::toRoute(['/mails/aliexpressdispute/details', 'id' => $id]); ?>");
                    <?php } else { ?>
                    window.location.replace("<?php echo Url::toRoute(['/mails/aliexpressdispute/showorder', 'issue_id' => $issue_id]); ?>");
                    <?php } ?>
                } else {
                    layer.alert(data["message"]);
                }
            }, "json");
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