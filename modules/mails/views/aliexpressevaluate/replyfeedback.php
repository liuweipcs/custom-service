<?php

use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
use kartik\select2\Select2;
use app\modules\accounts\models\Account;

?>
<style>
    #addReplyFeedback {
        margin: 20px auto 0 auto;
        width: 90%;
        height: auto;
        border-collapse: collapse;
    }

    #addReplyFeedback td {
        border: 1px solid #ccc;
        padding: 10px;
    }

    #addReplyFeedback td.col1 {
        width: 120px;
        text-align: right;
        font-weight: bold;
    }

    #addReplyFeedback .glyphicon.glyphicon-star {
        color: #ff9900;
        font-size: 20px;
    }
</style>
<div class="popup-wrapper">
    <?php
    $form = ActiveForm::begin([
        'id' => 'addReplyFeedbackForm',
        'action' => Url::toRoute(['/mails/aliexpressevaluate/replyfeedback']),
        'method' => 'post',
    ]);
    ?>
    <div class="popup-body">
        <table id="addReplyFeedback">
            <tr>
                <td class="col1">店铺名</td>
                <td>
                    <?php
                        $account = Account::findOne($info['account_id']);
                        if (!empty($account)) {
                            echo $account->account_name;
                        }
                    ?>
                </td>
            </tr>
            <tr>
                <td class="col1">itemID</td>
                <td>
                    <a target="_blank" href="https://www.aliexpress.com/item//<?php echo $info['platform_product_id']; ?>.html"><?php echo $info['platform_product_id']; ?></a>
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <a class="btn btn-primary" id="nextReplyFeedback">下一封回复评价</a>
                </td>
            </tr>
            <tr>
                <td class="col1">我留的评价</td>
                <td>
                    <?php for ($ix = 0; $ix < $info['seller_evaluation']; $ix++) { ?>
                        <span class="glyphicon glyphicon-star" aria-hidden="true"></span>
                    <?php } ?>
                    <br>
                    <?php echo $info['seller_feedback'] ?>
                </td>
            </tr>
            <tr>
                <td class="col1">我收到的评价</td>
                <td>
                    <?php for ($ix = 0; $ix < $info['buyer_evaluation']; $ix++) { ?>
                        <span class="glyphicon glyphicon-star" aria-hidden="true"></span>
                    <?php } ?>
                    <br>
                    <span id="buyerFeedback"><?php echo $info['buyer_feedback'] ?></span>
                    <br>
                    <span id="translateResult" style="color:green;font-weight:bold;"></span>
                    <br>
                    <?php if (!empty($info['buyer_feedback'])) { ?>
                        <a href="javascript:void(0);" id="clickTranslate">点击翻译</a>
                    <?php } ?>
                </td>
            </tr>
            <tr>
                <td class="col1">评论内容</td>
                <td>
                    <?php if ($info['reply_status'] != '1') { ?>
                        <div style="margin-bottom:10px;">
                            <?php
                            echo Select2::widget([
                                'name' => 'selReplyContent',
                                'data' => $replyContent,
                                'options' => [
                                    'placeholder' => '请输入',
                                ],
                            ]);
                            ?>
                        </div>
                        <textarea name="seller_reply" rows="10" class="form-control"></textarea>
                    <?php } else { ?>
                        <textarea name="seller_reply" rows="10" class="form-control" disabled readonly><?php echo !empty($info['seller_reply']) ? $info['seller_reply'] : ''; ?></textarea>
                    <?php } ?>
                </td>
            </tr>
            <?php if ($info['reply_status'] != '1') { ?>
                <tr>
                    <td></td>
                    <td>
                        <input type="submit" class="btn btn-primary" value="提交">
                        <input type="reset" class="btn btn-default" value="取消">
                        <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
    <div class="popup-footer"></div>
    <?php
    ActiveForm::end();
    ?>
</div>
<script type="text/javascript">
    $(function () {
        $("select[name='selReplyContent']").on("change", function () {
            var id = $(this).val();
            $.post("<?php echo Url::toRoute('/mails/aliexpressevaluate/randgetreplycontent') ?>", {
                "id": id
            }, function (data) {
                if (data["code"] == 1) {
                    $("textarea[name='seller_reply']").val(data["data"]);
                } else {
                    layer.alert(data["message"]);
                }
            }, "json");
            return false;
        });

        $("#clickTranslate").on("click", function () {
            var buyerFeedback = $("#buyerFeedback").text();

            $.post("<?php echo Url::toRoute('/mails/aliexpressevaluate/translate') ?>", {
                "content": buyerFeedback,
            }, function (data) {
                if (data["text"]) {
                    $("#translateResult").text(data["text"]);
                }
            }, "json");
        });

        $("#nextReplyFeedback").on("click", function () {
            location.href = "<?php echo Url::toRoute(['/mails/aliexpressevaluate/replyfeedback', 'account_id' => $info['account_id'], 'next' => '1']); ?>";
        });
    });
</script>