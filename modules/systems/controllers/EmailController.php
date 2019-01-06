<?php

namespace app\modules\systems\controllers;
use app\components\Controller;
use app\modules\systems\models\Email;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
class EmailController extends Controller
{
    public function actionList()
    {
        $model = new Email();
        $params = \Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);

        return $this->renderList('list', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }
    /**
     * @desc 新增邮箱
     * @return \yii\base\string
     */
    public function actionAdd()
    {
        $this->isPopup = true;
        $model = new Email();

        if ($this->request->getIsAjax()) {

            //数据验证以及保存数据
            $this->validateAndSaveData($this->request->post(),$model);

            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/email/list') . '");';
            $this->_showMessage(\Yii::t('tag', 'Operate Successful'), true, null, false, null,$refreshUrl);
        }
        return $this->render('add', [
            'model' => $model,
        ]);
    }
    /**
     * 验证数据以及保存数据
     * @param array  $postData 前端提交的post数据
     * @param object $model 模型对象
     */
    protected function validateAndSaveData($postData,$model)
    {   
        //模型加载数据
        
        //去除请求参数前后空格
        if(!empty($postData)){
            foreach ($postData as $key => $value) {
                if(!empty($value)){
                    foreach ($value as $k => $val) {
                        $postData[$key][$k] = trim($val);
                    }
                }
            }
        }
        
        $model->load($postData);

        //数据验证失败
        if (!$model->validate()) {
            $this->_showMessage(current(current($model->getErrors())), false);
        }

        //保存失败
        if (!$model->save()) {
            $this->_showMessage(\Yii::t('tag', 'Operate Failed'), false);
        }
    }

    /**
     * @desc 编辑记录
     * @return \yii\base\string
     */
    public function actionEdit()
    {
        $this->isPopup = true;
        $id = (int)$this->request->getQueryParam('id');
        
        //没有勾选邮箱
        if (empty($id)) {
            $this->_showMessage(\Yii::t('tag', 'Invalid Params'), false, null, false, null,"top.layer.closeAll('iframe');");
        }

        $model = Email::findById($id);
        
        //模型不合法
        if (empty($model)) {
            $this->_showMessage(\Yii::t('tag', 'Not Found Record'), false, null, false, null,"top.layer.closeAll('iframe');");
        }

        if ($this->request->getIsAjax()) {

            $model->modify_time = date('Y-m-d H:i:s',time());
            //数据验证以及保存数据
            $this->validateAndSaveData($this->request->post(),$model);
            
            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/email/list') . '");';
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,$refreshUrl);
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

        //没有勾选邮箱
        if (empty($id)) {
            $this->_showMessage(\Yii::t('tag', 'Invalid Params'), false);
        }

        $model = new Email();
        $flag = $model->deleteById($id);

        //删除失败
        if (!$flag) {
            $this->_showMessage(\Yii::t('tag', 'Operate Failed'), false);
        }

        $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/email/list') . '");';
        $this->_showMessage(\Yii::t('tag', 'Operate Successful'), true, null, false, null,$refreshUrl);
    }

    /**
     * @desc 批量删除记录
     */
    public function actionBatchdelete()
    {
        $model = new Email();
        if ($this->request->getIsAjax()) {
            $ids = $this->request->getBodyParam('ids');

            //没有选取标签
            if (empty($ids)) {
                $this->_showMessage(\Yii::t('tag', 'Not Selected Data'), false);
            }

            $ids = array_filter($ids);
            $result = $model->deleteByIds($ids);

            //删除失败
            if (!$result) {
                $this->_showMessage(\Yii::t('tag', 'Operate Failed'), false);
            }

            //删除成功
            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/email/list') . '");';
            $this->_showMessage(\Yii::t('tag', 'Operate Successful'), true, null, false, null,$refreshUrl);
        }
    }    

    public function actionCeshi($email){
        $model = new Email;

        $a = $model->getFilterOption($email);
        var_dump($a);exit;
    }
  
}
