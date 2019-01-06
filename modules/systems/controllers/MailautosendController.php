<?php
/**
 * Created by PhpStorm.
 * User: huwenjun
 * Date: 2018/8/9 0009
 * Time: 19:18
 */

namespace app\modules\systems\controllers;

use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\products\models\ProductDescription;
use app\modules\systems\models\Country;
use app\modules\mails\models\MailTemplate;
use yii\helpers\Url;
use Yii;
use app\components\Controller;
use app\modules\systems\models\MailAutoManage;
use yii\helpers\Json;

class MailautosendController extends Controller
{

    /**
     * 列表
     */
    public function actionList()
    {
        $model = new MailAutoManage();
        //获取查询参数
        $params       = Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);
        //查询
        return $this->renderList('list', [
            'dataProvider' => $dataProvider,
            'model'        => $model,
        ]);
    }

    /**
     * 添加
     */
    public function actionAdd()
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();
            $data['sending_template'] = preg_replace("/<p.*?>|<\/p>/is","", $data['sending_template']);
            //装载数据
            $model = new MailAutoManage();
            $model->load($data, '');
            if (!$model->validate()) {
                $this->_showMessage(current(current($model->getErrors())), false, Yii::$app->request->getUrl());
            }
            if (!empty($data['account_ids'])) {
                $model->account_id = !empty($data['account_ids']) ? json_encode($data['account_ids']) : '';
            }
            $model->status = $data['status'];
            if (!empty($data['site_codes'])) {
                $model->site = !empty($data['site_codes']) ? json_encode($data['site_codes']) : '';
            }
            if (!empty($data['sender_type'])) {
                $model->sender_type    = $data['sender_type'];
                $model->sender_content = !empty($data['sender_content']) ? nl2br($data['sender_content']) : '';
            }
            if (!empty($data['subject_type'])) {
                $model->subject_type    = $data['subject_type'];
                $model->subject_content = !empty($data['subject_content']) ? nl2br($data['subject_content']) : '';
            }
            if (!empty($data['subject_body_type'])) {
                $model->subject_body_type    = $data['subject_body_type'];
                $model->subject_body_content = !empty($data['subject_body_content']) ? nl2br($data['subject_body_content']) : '';
            }
            if (!empty($data['erp_sku_type'])) {
                $model->erp_sku_type    = $data['erp_sku_type'];
                $model->erp_sku_content = !empty($data['erp_sku_content']) ? json_encode($data['erp_sku_content']) : '';
            }
            if (!empty($data['product_id_type'])) {
                $model->product_id_type    = $data['product_id_type'];
                $model->product_id_content = !empty($data['product_id_content']) ? nl2br($data['product_id_content']) : '';
            }
            if (!empty($data['country_type'])) {
                $model->country_type    = $data['country_type'];
                $model->country_content = !empty($data['country_content']) ? json_encode($data['country_content']) : '';
            }
            if (!empty($data['order_id_type'])) {
                $model->order_id_type    = $data['order_id_type'];
                $model->order_id_content = !empty($data['order_id_content']) ? nl2br($data['order_id_content']) : '';
            }
            if (!empty($data['platform_order_id_type'])) {
                $model->platform_order_id_type    = $data['platform_order_id_type'];
                $model->platform_order_id_content = !empty($data['platform_order_id_content']) ? nl2br($data['platform_order_id_content']) : '';
            }
            if (!empty($data['customer_email_type'])) {
                $model->customer_email_type    = $data['customer_email_type'];
                $model->customer_email_content = !empty($data['customer_email_content']) ? nl2br($data['customer_email_content']) : '';
            }
            if (!empty($data['buyer_id_type'])) {
                $model->buyer_id_type    = $data['buyer_id_type'];
                $model->buyer_id_content = !empty($data['buyer_id_content']) ? nl2br($data['buyer_id_content']) : '';
            }
            if (!empty($data['start_time'])) {
                $model->start_time = $data['start_time'];
            }
            if (!empty($data['end_time'])) {
                $model->end_time = $data['end_time'];
            }
            if (!empty($data['is_permanent']) && $data['is_permanent'] == 1) {
                $model->is_permanent = $data['is_permanent'];//永久有效
            }
            if (!empty($data['send_time'])) {
                $model->send_time = $data['send_time'];
            }

            if (!empty($data['order_minimum_money'])) {
                $model->order_minimum_money = $data['order_minimum_money'];
            }
            if (!empty($data['order_highest_money'])) {
                $model->order_highest_money = $data['order_highest_money'];
            }
            if (!empty($data['content_batch'])) {
                $sendingTemplate = preg_replace("/<p.*?>|<\/p>/is","", $data['content_batch']);
                /*$sendingTemplate = preg_replace('/<br\\s*?\/??>/i', '', $data['content_batch']);*/
                $model->sending_template = $sendingTemplate;
            }

            $model->create_by   = Yii::$app->user->identity->login_name;
            $model->create_time = date('Y-m-d H:i:s');
            $model->modify_by   = Yii::$app->user->identity->login_name;
            $model->modify_time = date('Y-m-d H:i:s');
            //保存数据
            if (!$model->save()) {
                $this->_showMessage(Yii::t('system', 'Operate Failed'), false, Yii::$app->request->getUrl());
            } else {
                $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/systems/mailautosend/list') . '");';
                $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
            }

        } else {
            $this->isPopup = true;
            //获取平台列表
            $platformList = Platform::getPlatformAsArray();
            $platformCode = isset($_REQUEST['platform_code']) ? trim($_REQUEST['platform_code']) : null;
            if ($platformCode == null) {
                $ImportPeople_list = Account::getIdNameList('EB');
                $siteList          = Account::getSiteList('EB');
            } else {
                $ImportPeople_list = Account::getIdNameList($platformCode);
                $siteList          = Account::getSiteList($platformCode);
            }

            $countries = Country::getAllCountrieList();
            $list      = [];

            if (!empty($countries)) {
                foreach ($countries as $row)
                    $list[$row->en_abbr] = $row->en_name . '(' . $row->cn_name . ')';
            }
            $ImportPeople_list[0] = '全部';
            $sku_lists            = ProductDescription::getAllSku();
            return $this->render('add', [
                'platformList'  => $platformList,
                'account_lists' => $ImportPeople_list,
                'siteLists'     => $siteList,
                'countryList'   => $list,
                'sku_lists'     => $sku_lists,
                'begin_date'    => date('Y-m-d H:i:s'),
                'end_date'      => date('Y-m-d H:i:s', strtotime('+1 day')),
            ]);
        }
    }

    /**
     * @author alpha
     * @desc ajax获取账号
     */
    public function actionGetaccountbyplatformcode()
    {
        $request           = Yii::$app->request->post();
        $platformCode      = isset($request['platform_code']) ? trim($request['platform_code']) : null;
        $ImportPeople_list = Account::getIdNameList($platformCode);
        if (!empty($ImportPeople_list)) {
            $response['status']  = 'success';
            $response['message'] = '';
            $response['data']    = $ImportPeople_list;
            die(Json::encode($response));
        } else {
            $response['status']  = 'error';
            $response['message'] = '暂无账号信息';
            die(Json::encode($response));
        }
    }

    /**
     * ajax 获取站点
     */
    public function actionGetsitebyplatformcode()
    {
        $request      = Yii::$app->request->post();
        $platformCode = isset($request['platform_code']) ? trim($request['platform_code']) : null;
        $site_lists   = Account::find()
            ->select(['old_account_id', 'site'])
            ->where(['platform_code' => $platformCode, 'status' => 1])
            ->andWhere('site is not null')
            ->groupBy('site')
            ->asArray()->all();
        if (!empty($site_lists)) {
            foreach ($site_lists as $value) {
                $returnData[$value['old_account_id']] = $value['site'];
            }
            $response['status']  = 'success';
            $response['message'] = '';
            $response['data']    = $returnData;
            die(Json::encode($response));
        } else {
            $response['status']  = 'error';
            $response['message'] = '暂无站点信息';
            die(Json::encode($response));
        }
    }

    /**
     * 编辑
     */
    public function actionEdit()
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();
            $data['sending_template'] = preg_replace("/<p.*?>|<\/p>/is","", $data['sending_template']);
            $model = MailAutoManage::findOne($data['id']);
            if (empty($model)) {
                $this->_showMessage('没有找到邮件过滤器信息', false);
            }
            //装载数据
            $model->load($data, '');
            if (!$model->validate()) {
                $this->_showMessage(current(current($model->getErrors())), false, Url::toRoute(['/systems/mailautosend/edit', 'id' => $data['id']]));
            }
            $model->load($data, '');
            if (!$model->validate()) {
                $this->_showMessage(current(current($model->getErrors())), false, Yii::$app->request->getUrl());
            }
            if (!empty($data['account_ids'])) {
                $model->account_id = !empty($data['account_ids']) ? json_encode($data['account_ids']) : '';
            }
            $model->status = $data['status'];
            if (!empty($data['site_codes'])) {
                $model->site = !empty($data['site_codes']) ? json_encode($data['site_codes']) : '';
            }
            if (!empty($data['sender_type'])) {
                $model->sender_type    = $data['sender_type'];
                $model->sender_content = !empty($data['sender_content']) ? nl2br($data['sender_content']) : '';
            }
            if (!empty($data['subject_type'])) {
                $model->subject_type    = $data['subject_type'];
                $model->subject_content = !empty($data['subject_content']) ? nl2br($data['subject_content']) : '';
            }
            if (!empty($data['subject_body_type'])) {
                $model->subject_body_type    = $data['subject_body_type'];
                $model->subject_body_content = !empty($data['subject_body_content']) ? nl2br($data['subject_body_content']) : '';
            }
            if (!empty($data['erp_sku_type'])) {
                $model->erp_sku_type    = $data['erp_sku_type'];
                $model->erp_sku_content = !empty($data['erp_sku_content']) ? json_encode($data['erp_sku_content']) : '';
            }
            if (!empty($data['product_id_type'])) {
                $model->product_id_type    = $data['product_id_type'];
                $model->product_id_content = !empty($data['product_id_content']) ? nl2br($data['product_id_content']) : '';
            }
            if (!empty($data['country_type'])) {
                $model->country_type    = $data['country_type'];
                $model->country_content = !empty($data['country_content']) ? json_encode($data['country_content']) : '';
            }
            if (!empty($data['order_id_type'])) {
                $model->order_id_type    = $data['order_id_type'];
                $model->order_id_content = !empty($data['order_id_content']) ? nl2br(trim($data['order_id_content'])) : '';
            }
            if (!empty($data['platform_order_id_type'])) {
                $model->platform_order_id_type    = $data['platform_order_id_type'];
                $model->platform_order_id_content = !empty($data['platform_order_id_content']) ? nl2br(trim($data['platform_order_id_content'])) : '';
            }
            if (!empty($data['customer_email_type'])) {
                $model->customer_email_type    = $data['customer_email_type'];
                $model->customer_email_content = !empty($data['customer_email_content']) ? nl2br(trim($data['customer_email_content'])) : '';
            }
            if (!empty($data['buyer_id_type'])) {
                $model->buyer_id_type    = $data['buyer_id_type'];
                $model->buyer_id_content = !empty($data['buyer_id_content']) ? nl2br(trim($data['buyer_id_content'])) : '';
            }
            if (!empty($data['is_permanent']) && $data['is_permanent'] == 1) {
                $model->is_permanent = $data['is_permanent'];//永久有效
            }

            if (!empty($data['start_time'])) {
                $model->start_time = $data['start_time'];
            }
            if (!empty($data['end_time'])) {
                $model->end_time = $data['end_time'];
            }
            if (!empty($data['send_time'])) {
                $model->send_time = $data['send_time'];
            }

            if (!empty($data['order_minimum_money'])) {
                $model->order_minimum_money = $data['order_minimum_money'];
            }
            if (!empty($data['order_highest_money'])) {
                $model->order_highest_money = $data['order_highest_money'];
            }
            $model->modify_by   = Yii::$app->user->identity->login_name;
            $model->modify_time = date('Y-m-d H:i:s');
            //保存数据
            if (!$model->save()) {
                $this->_showMessage(Yii::t('system', 'Operate Failed'), false, Url::toRoute(['/systems/mailautosend/edit', 'id' => $data['id']]));
            } else {
                $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/systems/mailautosend/list') . '");';
                $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
            }
        } else {
            $id = Yii::$app->request->get('id');

            if (empty($id)) {
                $this->_showMessage('ID不能为空', false);
            }

            $info = MailAutoManage::find()->where(['id' => $id])->asArray()->one();
            if (empty($info)) {
                $this->_showMessage('没有找到邮件自动发送信息', false);
            }
            $info['account_id'] = json_decode($info['account_id'],true);
            $info['site'] = json_decode($info['site'],true);
            $this->isPopup = true;
            //获取平台列表
            $platformList = Platform::getPlatformAsArray();
            $platformCode=$info['platform_code'];
            if ($platformCode == null) {
                $ImportPeople_list = Account::getIdNameList('EB');
                $siteList          = Account::getSiteList('EB');
            } else {
                $ImportPeople_list = Account::getIdNameList($platformCode);
                $siteList          = Account::getSiteList($platformCode);
            }
            $countries = Country::getAllCountrieList();
            $list      = [];

            if (!empty($countries)) {
                foreach ($countries as $row)
                    $list[$row->en_abbr] = $row->en_name . '(' . $row->cn_name . ')';
            }

            $ImportPeople_list[0] = '全部';
            $sku_lists            = ProductDescription::getAllSku();
            return $this->render('edit', [
                'info'          => $info,
                'platformList'  => $platformList,
                'account_lists' => $ImportPeople_list,
                'siteLists'     => $siteList,
                'countryList'   => $list,
                'sku_lists'     => $sku_lists,
            ]);
        }
    }

    /**
     * 批量删除
     */
    public function actionBatchdelete()
    {
        $ids = Yii::$app->request->post('ids');

        if (empty($ids) || !is_array($ids)) {
            $this->_showMessage('请选择删除项', false);
        }

        if (MailAutoManage::deleteAll(['in', 'id', $ids])) {
            $extraJs = 'top.refreshTable("' . Url::toRoute('/systems/mailautosend/list') . '");';
            $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
        } else {
            $this->_showMessage(Yii::t('system', 'Operate Failed'), false);
        }
    }

    /**
     * 删除
     */
    public function actionDelete()
    {
        $id = Yii::$app->request->get('id');

        if (empty($id)) {
            $this->_showMessage('请选择删除项', false);
        }
        $info = MailAutoManage::findOne($id);
        if (empty($info)) {
            $this->_showMessage('没有找到该删除项', false);
        }
        if ($info->delete()) {
            $extraJs = 'top.refreshTable("' . Url::toRoute('/systems/mailautosend/list') . '");';
            $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
        } else {
            $this->_showMessage(Yii::t('system', 'Operate Failed'), false);
        }
    }

    /**
     * 查询邮件回复模版
     */
    public function actionGettemplates()
    {

        $platform_code  = Yii::$app->request->post('platform_code');
        $template_name  = Yii::$app->request->post('template_name');
        $template_title = Yii::$app->request->post('template_title');

        $templates = MailTemplate::getMailsendingtemplate($platform_code, $template_name, $template_title);

        if (!empty($templates)) {
            $response['status']  = 'success';
            $response['message'] = '';
            $response['data']    = $templates;
            die(Json::encode($response));
        } else {
            $response['status']  = 'error';
            $response['message'] = '暂无模版信息';
            die(Json::encode($response));
        }
    }
}