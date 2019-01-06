<?php

use yii\helpers\Url;
?>
<div class="popup-wrapper">
    <form action="#" method="post" role="form" class="form-horizontal">
        <input type="hidden" name="complaint_order" value="<?php echo $complaint_order; ?>"/>
        <div class="popup-body">
            <div class="row">
                <div class="col-sm-9">
                    <div class="form-group field-subject-tag">
                        <label class="control-label col-sm-3" for="ebayreply-reply_title">结果:</label>
                        <div class="col-sm-9">
                            <label class="checkbox-inline">
                                <input type="radio" name="status" value="1">审核通过
                            </label>
                            <label class="checkbox-inline">
                                <input type="radio" name="status" value="-1">审核不通过
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-9">
                    <div class="form-group field-ebayfeedback-comment_text">
                        <label class="control-label col-sm-3" for="ebayfeedback-comment_text">原因：</label>
                        <div class="col-sm-6">
                            <textarea id="ebayfeedback-comment_text" class="form-control" name="remark" maxlength="80" rows="7" placeholder="请输入原因"></textarea>
                            <div class="help-block help-block-error "></div>
                        </div>
                    </div>                          
                </div>
            </div>
        </div>
        <div class="popup-footer">
            <button class="btn btn-primary ajax-submit" type="button"><?php echo Yii::t('system', 'Submit'); ?></button>
            <button class="btn btn-default close-button"><?php echo Yii::t('system', 'Close'); ?></button>
        </div>
    </form>
</div>