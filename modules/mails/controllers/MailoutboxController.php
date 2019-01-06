<?php
/**
 * @desc 发件箱控制器
 * @author Fun
 */

namespace app\modules\mails\controllers;

use Yii;
use app\components\Controller;
use app\modules\mails\models\EbayReply;
use app\modules\mails\models\EbayReplyPicture;
use app\modules\mails\models\MailOutbox;
use app\modules\accounts\models\Platform;
use yii\db\Exception;
use yii\helpers\Url;
use app\common\VHelper;

class MailoutboxController extends Controller
{
    /**
     * 列表
     */
    public function actionAliexpresssendlist()
    {
        $params = Yii::$app->request->getBodyParams();
        $params['platform_code'] = Platform::PLATFORM_CODE_ALI;

        list($model, $dataProvider) = $this->dataList($params);

        return $this->renderList('list', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'platform_code' => Platform::PLATFORM_CODE_ALI,
        ]);
    }

    /**
     * @desc 邮件列表公共方法
     */
    protected function dataList($params)
    {
        $model = new MailOutbox();
        $model->platform = $params['platform_code'];
        $dataProvider = $model->searchList($params);
        return [$model, $dataProvider];
    }

    /**
     * @desc amazon列表
     */
    public function actionAmazonsendlist()
    {
        $params = Yii::$app->request->getBodyParams();
        $params['platform_code'] = Platform::PLATFORM_CODE_AMAZON;

        list($model, $dataProvider) = $this->dataList($params);

        return $this->renderList('list', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'platform_code' => Platform::PLATFORM_CODE_AMAZON,
        ]);
    }

    /**
     * walmart邮件发送列表
     */
    public function actionWalmartsendlist()
    {
        $params = Yii::$app->request->getBodyParams();
        $params['platform_code'] = Platform::PLATFORM_CODE_WALMART;

        list($model, $dataProvider) = $this->dataList($params);

        return $this->renderList('list', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'platform_code' => Platform::PLATFORM_CODE_WALMART,
        ]);
    }

    /**
     * cdiscount邮件发送列表
     */
    public function actionCdiscountsendlist()
    {
        $params = Yii::$app->request->getBodyParams();
        $params['platform_code'] = Platform::PLATFORM_CODE_CDISCOUNT;

        list($model, $dataProvider) = $this->dataList($params);

        return $this->renderList('cdiscountlist', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'platform_code' => Platform::PLATFORM_CODE_CDISCOUNT,
        ]);
    }

    /**
     * @desc wish列表
     */
    public function actionWishsendlist()
    {
        $params = Yii::$app->request->getBodyParams();
        $params['platform_code'] = Platform::PLATFORM_CODE_WISH;

        list($model, $dataProvider) = $this->dataList($params);

        return $this->renderList('list', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'platform_code' => Platform::PLATFORM_CODE_WISH,
        ]);
    }

    /**
     * @desc eBay列表
     */
    public function actionEbaysendlist()
    {
        $params = Yii::$app->request->getBodyParams();
        $params['platform_code'] = Platform::PLATFORM_CODE_EB;
        list($model, $dataProvider) = $this->dataList($params);

        return $this->renderList('ebaylist', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'platform_code' => Platform::PLATFORM_CODE_EB,
        ]);
    }

    /**
     * @desc 重新发送
     */
    public function actionResend()
    {
        $id = (int)$this->request->getQueryParam('id');
        $platform_code = $this->request->getQueryParam('platform_code');
        if (empty($id)) {
            $this->_showMessage(Yii::t('system', 'Invalid Params'), false);
        }
        $mailOutbox = MailOutbox::findById($id);
        if (empty($mailOutbox)) {
            $this->_showMessage(Yii::t('system', 'Not Found Record'), false, null, false, null,
                "top.layer.closeAll('iframe');");
        }
        if ($mailOutbox->send_status != MailOutbox::SEND_STATUS_FAILED && !($mailOutbox->send_status == MailOutbox::SEND_STATUS_SENDING && (time() - strtotime($mailOutbox->create_time) > '300'))) {
            $this->_showMessage(Yii::t('system', 'Send Status Incorrect'), false);
        }
        if ($mailOutbox->sendMessage()) {
            switch ($platform_code) {
                case 'EB':
                    $action = 'ebaysendlist';
                    break;
                case 'AMAZON':
                    $action = 'amazonsendlist';
                    break;
                case 'ALI':
                    $action = 'aliexpresssendlist';
                    break;
                case 'WISH':
                    $action = 'wishsendlist';
                    break;
                case 'WALMART':
                    $action = 'walmartsendlist';
                    break;
                case 'CDISCOUNT':
                    $action = 'cdiscountsendlist';
                    break;
                default :
                    $action = '';

            }
            if ($action) {
                $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null,
                    'top.refreshTable("' . Url::toRoute('/mails/mailoutbox/' . $action) . '");');
            } else {
                $this->_showMessage(Yii::t('system', 'Operate Successful'), true);
            }
        } else {
            $this->_showMessage(Yii::t('system', 'Operate Failed'), false);
        }
    }

    /**
     * @desc 批量重新发送
     */
    public function actionBatchresend()
    {
        $ids = $this->request->post('ids');
        $platform_code = $this->request->getQueryParam('platform_code');

        foreach ($ids as $id) {
            if (empty($id)) {
                $this->_showMessage(Yii::t('system', 'Invalid Params'), false);
            }
            $mailOutbox = MailOutbox::findById($id);
            if (empty($mailOutbox)) {
                $this->_showMessage(Yii::t('system', 'Not Found Record'), false, null, false, null,
                    "top.layer.closeAll('iframe');");
            }
            if ($mailOutbox->send_status != MailOutbox::SEND_STATUS_FAILED && !($mailOutbox->send_status == MailOutbox::SEND_STATUS_SENDING && (time() - strtotime($mailOutbox->create_time) > '300'))) {
                $this->_showMessage(Yii::t('system', 'Send Status Incorrect'), false);
            }
            $mailOutbox->sendMessage();
        }
        switch ($platform_code) {
            case 'EB':
                $action = 'ebaysendlist';
                break;
            case 'AMAZON':
                $action = 'amazonsendlist';
                break;
            case 'ALI':
                $action = 'aliexpresssendlist';
                break;
            case 'WISH':
                $action = 'wishsendlist';
                break;
            case 'WALMART':
                $action = 'walmartsendlist';
                break;
            case 'CDISCOUNT':
                $action = 'cdiscountsendlist';
                break;
            default :
                $action = '';

        }
        if ($action) {
            $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null,
                'top.refreshTable("' . Url::toRoute('/mails/mailoutbox/' . $action) . '");');
        } else {
            $this->_showMessage(Yii::t('system', 'Operate Successful'), true);
        }
    }

    /**
     * @desc 删除发送失败的信息
     */
    public function actionDelete()
    {
        $id = (int)$this->request->getQueryParam('id');
        $platform_code = $this->request->getQueryParam('platform_code');
        if (empty($id)) {
            $this->_showMessage(Yii::t('system', 'Invalid Params'), false);
        }
        $mailOutbox = MailOutbox::findById($id);
        if (empty($mailOutbox)) {
            $this->_showMessage(Yii::t('system', 'Not Found Record'), false, null, false, null, "top.layer.closeAll('iframe');");
        }
        if ($mailOutbox->send_status != MailOutbox::SEND_STATUS_FAILED && !($mailOutbox->send_status == MailOutbox::SEND_STATUS_SENDING && (time() - strtotime($mailOutbox->create_time) > '300'))) {
            $this->_showMessage(Yii::t('system', 'Delete Status Incorrect'), false);
        }

        try {
            $dbTransaction = MailOutbox::getDb()->beginTransaction();
            $flag = $mailOutbox->delete();
            if ($flag) {
                if ($platform_code == Platform::PLATFORM_CODE_EB) {
                    $flag = EbayReply::deleteAll(['id' => $mailOutbox->reply_id]);
                }
            }
            if ($flag) {
                if ($platform_code == Platform::PLATFORM_CODE_EB) {
                    $host = $this->request->hostInfo;
                    // 删除图片
                    $picture_models = EbayReplyPicture::find()->where(['reply_table_id' => $mailOutbox->reply_id])->all();
                    foreach ($picture_models as $picture_model) {
                        $url = str_replace($host . '/', '', $picture_model->picture_url);
                        @unlink($url);
                        $flag = $picture_model->delete();
                    }
                }
                $dbTransaction->commit();
            }

        } catch (Exception $e) {
            $flag = false;
            $dbTransaction->rollBack();
            $this->_showMessage($e->getMessage(), false);
        }
        if ($flag) {
            switch ($platform_code) {
                case 'EB':
                    $action = 'ebaysendlist';
                    break;
                case 'AMAZON':
                    $action = 'amazonsendlist';
                    break;
                case 'ALI':
                    $action = 'aliexpresssendlist';
                    break;
                case 'WISH':
                    $action = 'wishsendlist';
                    break;
                case 'WALMART':
                    $action = 'walmartsendlist';
                    break;
                case 'CDISCOUNT':
                    $action = 'cdiscountsendlist';
                    break;
                default :
                    $action = '';

            }
            if ($action) {
                $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null,
                    'top.refreshTable("' . Url::toRoute('/mails/mailoutbox/' . $action) . '");');
            } else {
                $this->_showMessage(Yii::t('system', 'Operate Successful'), true);
            }
        } else {
            $this->_showMessage(Yii::t('system', 'Operate Failed'), false);
        }
    }

    /**
     * 修改主题
     */
    public function actionUpdate()
    {
        if (Yii::$app->request->isPost) {
            $id = Yii::$app->request->post('id', 0);

            $subject = Yii::$app->request->post('subject', '');
            $content = Yii::$app->request->post('content', '');
            $content_en = Yii::$app->request->post('content_en', '');

            if (empty($id)) {
                $this->_showMessage('ID不能为空', false);
            }

            if (empty($subject)) {
                $this->_showMessage('当前主题标题不能为空', false);
            }

            if (empty($content_en)) {
                $this->_showMessage('修改后的主题不能为空', false);
            }

            $info = MailOutbox::findOne($id);
            if (empty($info)) {
                $this->_showMessage('没有找到该邮件信息', false);
            }

            $info->subject = $subject;
            if (!empty($content)) {
                $info->content = $content;
            } else {
                $info->content = $content_en;
            }

            if (!$info->save()) {
                $this->_showMessage('修改主题失败', false);
            } else {
                $extraJs = 'top.layer.closeAll("iframe");top.location.reload();';
                $this->_showMessage('修改主题成功', true, null, false, null, $extraJs);
            }

        } else {
            $id = Yii::$app->request->get('id', 0);

            if (empty($id)) {
                $extraJs = 'top.layer.closeAll("iframe");';
                $this->_showMessage('ID不能为空', false, null, false, null, $extraJs);
            }

            $info = MailOutbox::findOne($id);
            if (empty($info)) {
                $extraJs = 'top.layer.closeAll("iframe");';
                $this->_showMessage('没有找到该邮件信息', false, null, false, null, $extraJs);
            }

            if ($info->send_status != MailOutbox::SEND_STATUS_FAILED) {
                $extraJs = 'top.layer.closeAll("iframe");';
                $this->_showMessage('发送失败的邮件才能修改主题', false, null, false, null, $extraJs);
            }

            //将邮件内容中的html标签去掉
            $info->content = strip_tags($info->content, '<br>');
            //替换内容中的<br>标签
            $info->content = str_replace('<br>', "\n", $info->content);
            $info->content = str_replace('<br/>', "\n", $info->content);

            //谷歌翻译
            $googleLangCode = VHelper::googleLangCode();

            $this->isPopup = true;
            return $this->render('update', [
                'info' => $info,
                'googleLangCode' => $googleLangCode,
            ]);
        }
    }

    /**
     * 批量修改主题
     */
    public function actionBatchupdate()
    {
        if (Yii::$app->request->isPost) {
            $ids = Yii::$app->request->post('ids', 0);

            $content = Yii::$app->request->post('content', '');
            $content_en = Yii::$app->request->post('content_en', '');

            if (empty($ids)) {
                $this->_showMessage('IDS不能为空', false);
            }

            if (empty($content_en)) {
                $this->_showMessage('修改后的主题不能为空', false);
            }

            $ids = explode(',', $ids);
            if (!empty($ids)) {
                $isOK = true;
                $errors = [];
                foreach ($ids as $id) {
                    $info = MailOutbox::findOne($id);
                    if (empty($info)) {
                        $isOK = false;
                        $errors[] = "没有找到ID为{$id}的邮件信息";
                    }

                    if (!empty($content)) {
                        $info->content = $content;
                    } else {
                        $info->content = $content_en;
                    }

                    if (!$info->save()) {
                        $isOK = false;
                        $errors[] = "ID为{$id}的修改主题失败";
                    }
                }

                if (!$isOK) {
                    $this->_showMessage('批量修改主题失败:' . implode(',', $errors), false);
                } else {
                    $extraJs = 'top.layer.closeAll("iframe");top.location.reload();';
                    $this->_showMessage('批量修改主题成功', true, null, false, null, $extraJs);
                }
            }

        } else {
            $ids = Yii::$app->request->get('ids', 0);

            if (empty($ids)) {
                $extraJs = 'top.layer.closeAll("iframe");';
                $this->_showMessage('请选中修改项', false, null, false, null, $extraJs);
            }

            $ids = explode(',', $ids);
            if (!empty($ids)) {
                foreach ($ids as $id) {
                    $mail = MailOutbox::findOne($id);
                    if ($mail->send_status != MailOutbox::SEND_STATUS_FAILED) {
                        $extraJs = 'top.layer.closeAll("iframe");';
                        $this->_showMessage('发送失败的邮件才能修改主题', false, null, false, null, $extraJs);
                    }
                }
            }

            //默认选取第一个选中的邮件作为批量修改的内容
            $id = !empty($ids[0]) ? $ids[0] : 0;

            $info = MailOutbox::findOne($id);
            if (empty($info)) {
                $extraJs = 'top.layer.closeAll("iframe");';
                $this->_showMessage('没有找到该邮件信息', false, null, false, null, $extraJs);
            }

            //将邮件内容中的html标签去掉
            $info->content = strip_tags($info->content, '<br>');
            //替换内容中的<br>标签
            $info->content = str_replace('<br>', "\n", $info->content);
            $info->content = str_replace('<br/>', "\n", $info->content);

            //谷歌翻译
            $googleLangCode = VHelper::googleLangCode();

            $this->isPopup = true;
            return $this->render('batchupdate', [
                'info' => $info,
                'googleLangCode' => $googleLangCode,
                'ids' => $ids,
            ]);
        }
    }
}
