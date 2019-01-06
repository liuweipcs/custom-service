<?php

namespace app\modules\systems\controllers;

use Yii;
use yii\models;
use app\components\Controller;
//use app\models\User;
use app\modules\systems\models\Gbc;
//use app\modules\accounts\models\Platform;
use app\modules\systems\models\ErpOrderApi;
use app\modules\orders\models\Order;
//use app\modules\mails\models\MailTemplate;
//use app\controllers\BaseController;
//use yii\web\NotFoundHttpException;
use yii\data\Pagination;
use app\modules\systems\models\GbcBlacklist;
/**
 * Created by zhangchu
 * Date: 2018/6/5
 */
class GbcController extends Controller
{

    /**
     * Lists all  models.
     * @return mixed
     */
    public function actionList()
    {
        $model = new Gbc();
        $params = \Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);

        return $this->renderList('list', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    /****
     * 操作日志详情
     * 
     * ***/
    public function actionLogdetail()
    {
        $this->isPopup = true;
        $id = (int)$this->request->getQueryParam('id');        
        $gbc_data=Gbc::getgbctype($id);
        if(empty($gbc_data)){
             $this->_showMessage('请求参数错误', false);
        }
        //获取操作日志数据
       $log= GbcBlacklist::getlog($gbc_data);
        //创建分页组件
        $page = new Pagination([
            //总的记录条数
            'totalCount' => count($log),
            //分页大小
            'pageSize' => $pageSize,
            //设置地址栏当前页数参数名
            'pageParam' => 'pageCur',
            //设置地址栏分页大小参数名
            'pageSizeParam' => 'pageSize',
        ]);
        
        return $this->renderList('log_detail', [
            'log' => $log,
            'page' =>$page,
        ]);
        
       
    }

      /**
     * 保存Gbc表的数据
     * @param  object $model Gbc模型
     * @param  array $postData 前端提交过来的post数据
     * @param  object $transaction 开启的事物对象
     * @return object $model 保存后的rule对象
     */
    protected function validateAndSaveRuleData($model, $postData, $transaction)
    {
        //模型加载数据
        $model->load($postData);

        //数据验证失败
        if (!$model->validate()) {
            $transaction->rollBack();
            $this->_showMessage(current(current($model->getErrors())), false);
        }

        //保存数据表数据失败
        if (!$model->save()) {
            $transaction->rollBack();
            $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
        }

        return $model;
    }

    /**
     * 新增标签规则
     */
    public function actionAdd()
    {
        $this->isPopup = true;
        $model = new Gbc();

        if ($this->request->getIsAjax()) {
            $postData = $this->request->post();
            if (empty($postData['Gbc']['type'])) {
                $this->_showMessage('请选择填写类型', false);
            }
            if (empty($postData['Gbc']['account_type'])) {
                $this->_showMessage('请选择数据来源', false);
            }
            if (empty($postData['Gbc']['platform_code'])) {
                $this->_showMessage('请选择平台',false);
            }

            if ($postData['Gbc']['type'] == '1') {
                $exists = Gbc::findOne(['platform_code' => $postData['Gbc']['platform_code'], 'type' => $postData['Gbc']['type']]);
                if (!empty($exists)) {
                    $this->_showMessage($postData['Gbc']['platform_code'] . '平台已经存在一条账号类型的数据', false);
                }
                if (empty($postData['Gbc']['ebay_id'])) {
                    $this->_showMessage('请填写买家ID', false);
                }
            } else if ($postData['Gbc']['type'] == '2') {
                $exists = Gbc::findOne(['platform_code' => $postData['Gbc']['platform_code'], 'type' => $postData['Gbc']['type']]);
                if (!empty($exists)) {
                    $this->_showMessage($postData['Gbc']['platform_code'] . '平台已经存在一条付款邮箱类型的数据', false);
                }
                if (empty($postData['Gbc']['payment_email'])) {
                    $this->_showMessage('请付款邮箱', false);
                }
            } else if ($postData['Gbc']['type'] == '3') {
                if (empty($postData['Gbc']['country']) ||
                    empty($postData['Gbc']['state']) ||
                    empty($postData['Gbc']['city']) ||
                    empty($postData['Gbc']['address']) ||
                    empty($postData['Gbc']['recipients'])) {
                    $this->_showMessage('请填写地址或收件人信息', false);
                }
            }
            $postData['Gbc']['modify_by'] = Yii::$app->user->identity->user_name;

            $erpOrderApi = new ErpOrderApi();
            if (!$erpOrderApi->addGbc($postData)) {
                $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
                return false;
            }

            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/gbc/list') . '");';
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, $refreshUrl);
        }

        //默认类型为ebay账号
        $model->type = 1;
        //默认数据来源为公司
        $model->account_type = 2;

        return $this->render('add', [
            'model' => $model,
        ]);
    }

    /**
     * 编辑记录
     */
    public function actionEdit()
    {
        $this->isPopup = true;
        $id = (int)$this->request->getQueryParam('id');

        //没有勾选项
        if (empty($id)) {
            $this->_showMessage('ID不能为空', false, null, false, null, "top.layer.closeAll('iframe');");
        }

        $model = Gbc::findById($id);
        isset($model->ebay_id) && $model->ebay_id = implode(',', json_decode($model->ebay_id, true));
        isset($model->payment_email) && $model->payment_email = implode(',', json_decode($model->payment_email, true));

        //模型不合法
        if (empty($model)) {
            $this->_showMessage('没有找到该记录', false, null, false, null, "top.layer.closeAll('iframe');");
        }

        if ($this->request->getIsAjax()) {

            $postData = $this->request->post();
            if (empty($postData['Gbc']['type'])) {
                $this->_showMessage('请选择填写类型', false);
            }
            if (empty($postData['Gbc']['account_type'])) {
                $this->_showMessage('请选择数据来源', false);
            }
            if (empty($postData['Gbc']['platform_code'])) {
                $this->_showMessage('请选择平台',false);
            }

            if ($postData['Gbc']['type'] == '1') {
                $exists = Gbc::findOne(['platform_code' => $postData['Gbc']['platform_code'], 'type' => $postData['Gbc']['type']]);
                if (!empty($exists) && $exists->id != $model->id) {
                    $this->_showMessage($postData['Gbc']['platform_code'] . '平台已经存在一条账号类型的数据', false);
                }
                if (empty($postData['Gbc']['ebay_id'])) {
                    $this->_showMessage('请填写买家ID', false);
                }
            } else if ($postData['Gbc']['type'] == '2') {
                $exists = Gbc::findOne(['platform_code' => $postData['Gbc']['platform_code'], 'type' => $postData['Gbc']['type']]);
                if (!empty($exists) && $exists->id != $model->id) {
                    $this->_showMessage($postData['Gbc']['platform_code'] . '平台已经存在一条付款邮箱类型的数据', false);
                }
                if (empty($postData['Gbc']['payment_email'])) {
                    $this->_showMessage('请付款邮箱', false);
                }
            } else if ($postData['Gbc']['type'] == '3') {
                if (empty($postData['Gbc']['country']) ||
                    empty($postData['Gbc']['state']) ||
                    empty($postData['Gbc']['city']) ||
                    empty($postData['Gbc']['address']) ||
                    empty($postData['Gbc']['recipients'])) {
                    $this->_showMessage('请填写地址或收件人信息', false);
                }
            }

            $postData['Gbc']['id'] = $id;
            $postData['Gbc']['modify_by'] = Yii::$app->user->identity->user_name;
            $postData['Gbc']['is_deleted'] = '1';

            $erpOrderApi = new ErpOrderApi();
            if (!$erpOrderApi->editGbc($postData)) {
                $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
                return false;
            }

            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/gbc/list') . '");';
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, $refreshUrl);
        }

        return $this->render('edit', [
            'model' => $model,
        ]);

    }

    /**
     * @desc 删除记录
     */
    public function actionDelete()
    {
        $id = (int)$this->request->getQueryParam('id');
        //没有勾选
        if (empty($id)) {
            $this->_showMessage(\Yii::t('tag', 'Invalid Params'), false);
        }

        $model = Gbc::findById($id);
        //模型不合法
        if (empty($model)) {
            $this->_showMessage(\Yii::t('tag', 'Not Found Record'), false, null, false, null, "top.layer.closeAll('iframe');");
        }

        $postData = [];
        $postData['Gbc']['id'] = $id;
        $postData['Gbc']['modify_by'] = Yii::$app->user->identity->user_name;
        $postData['Gbc']['is_deleted'] = '0';

        $erpOrderApi = new ErpOrderApi();
        if (!$erpOrderApi->editGbc($postData)) {
            //删除失败
            $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
            return false;
        }

        $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/gbc/list') . '");';
        $this->_showMessage(\Yii::t('tag', 'Operate Successful'), true, null, false, null, $refreshUrl);
    }

    /**
     * @desc 批量删除记录
     */
    public function actionBatchdelete()
    {
        if ($this->request->getIsAjax()) {
            $ids = $this->request->getBodyParam('ids');

            //没有选取标签
            if (empty($ids)) {
                $this->_showMessage(\Yii::t('tag', 'Not Selected Data'), false);
            }
            $ids = array_filter($ids);

            $postData = [];
            $postData['Gbc']['id'] = $ids;
            $postData['Gbc']['modify_by'] = Yii::$app->user->identity->user_name;
            $postData['Gbc']['is_deleted'] = '0';

            $erpOrderApi = new ErpOrderApi();
            if (!$erpOrderApi->editGbc($postData)) {
                //删除失败
                $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
                return false;
            }

            //删除成功
            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/gbc/list') . '");';
            $this->_showMessage(\Yii::t('tag', 'Operate Successful'), true, null, false, null, $refreshUrl);
        }
    }

    /**
     * 添加GBC黑名单
     */
    public function actionAddblacklist()
    {
        $buyerId = Yii::$app->request->post('buyer_id', '');
        $platformCode = Yii::$app->request->post('platform_code', '');
        $type = Yii::$app->request->post('type', 0);
        $account_type = Yii::$app->request->post('account_type', 2);

        if (empty($buyerId)) {
            die(json_encode([
                'code' => 0,
                'msg' => '买家ID不能为空',
            ]));
        }
        if (empty($platformCode)) {
            die(json_encode([
                'code' => 0,
                'msg' => '平台不能为空',
            ]));
        }
        if (empty($type)) {
            die(json_encode([
                'code' => 0,
                'msg' => '类型不能为空',
            ]));
        }
 
        $data['platform_code'] = $platformCode;
        $data['type'] = $type;
        $data['account_type'] = $account_type;
        $data['ebay_id'] = trim($buyerId);
        $data['modify_by'] = Yii::$app->user->identity->user_name;
        //记录操作日志
        $log=new GbcBlacklist();
        $log->platform_code=$platformCode;     
        $log->type=$type;
        $log->account_type=$account_type;      
        $log->update_user= Yii::$app->user->identity->user_name;
        $result = Order::updateGbcData($data);
        if ($result) {
             $log->content=$buyerId.'--添加黑名单成功';
             $log->update_time=date('Y-m-d H:i:s');
             $log->save();
            die(json_encode([
                'code' => 1,
                'msg' => '添加黑名单成功',
            ]));
        } else {
             $log->content=$buyerId.'--添加黑名单失败';
             $log->update_time=date('Y-m-d H:i:s');
             $log->save();
            die(json_encode([
                'code' => 0,
                'msg' => '添加黑名单失败',
            ]));
        }
    }

    /**
     * 取消GBC黑名单
     */
    public function actionCancelblacklist()
    {
        $buyerId = Yii::$app->request->post('buyer_id', '');
        $platformCode = Yii::$app->request->post('platform_code', '');
        $type = Yii::$app->request->post('type', 0);
        $account_type = Yii::$app->request->post('account_type', 2);

        if (empty($buyerId)) {
            die(json_encode([
                'code' => 0,
                'msg' => '买家ID不能为空',
            ]));
        }
        if (empty($platformCode)) {
            die(json_encode([
                'code' => 0,
                'msg' => '平台不能为空',
            ]));
        }
        if (empty($type)) {
            die(json_encode([
                'code' => 0,
                'msg' => '类型不能为空',
            ]));
        }

        $data['platform_code'] = $platformCode;
        $data['type'] = $type;
        $data['account_type'] = $account_type;
        $data['ebay_id'] = trim($buyerId);
        $data['is_remove'] = 1;
        $data['modify_by'] = Yii::$app->user->identity->user_name;
       //记录操作日志
        $log=new GbcBlacklist();
        $log->platform_code=$platformCode;
        $log->type=$type;
        $log->account_type=$account_type;
        $log->update_user= Yii::$app->user->identity->user_name;
        $result = Order::updateGbcData($data);
        if ($result) {
            $log->content=$buyerId.'--取消黑名单成功';
            $log->update_time=date('Y-m-d H:i:s');
            $log->save();
            die(json_encode([
                'code' => 1,
                'msg' => '取消黑名单成功',
            ]));
        } else {
            $log->content=$buyerId.'--取消黑名单失败';
            $log->update_time=date('Y-m-d H:i:s');
            $log->save();
            die(json_encode([
                'code' => 0,
                'msg' => '取消黑名单失败',
            ]));
        }
    }
}    