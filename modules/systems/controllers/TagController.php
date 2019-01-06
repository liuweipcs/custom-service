<?php

namespace app\modules\systems\controllers;

use Yii;
use app\modules\systems\models\Tag;
use app\modules\accounts\models\Platform;
use app\components\Controller;
use yii\web\NotFoundHttpException;
use app\common\VHelper;
/**
 * TagController implements the CRUD actions for Tag model.
 */
class TagController extends Controller
{


    /**
     * Lists all Tag models.
     * @return mixed
     */
    public function actionList()
    {   
        $model = new Tag();
        $params = \Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);

        return $this->renderList('list', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @desc 新增标签
     * @return \yii\base\string
     */
    public function actionAdd()
    {
        $this->isPopup = true;
        $model = new Tag();

        if ($this->request->getIsAjax()) {

            //数据验证以及保存数据
            $this->validateAndSaveData($this->request->post(),$model);

            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/tag/list') . '");';
            $this->_showMessage(\Yii::t('tag', 'Operate Successful'), true, null, false, null,$refreshUrl);
        }
        return $this->render('add', [
            'model' => $model,
            'platformList' => Platform::getPlatformAsArray(),
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
        
        //没有勾选标签
        if (empty($id)) {
            $this->_showMessage(\Yii::t('tag', 'Invalid Params'), false, null, false, null,"top.layer.closeAll('iframe');");
        }

        $model = Tag::findById($id);
        
        //模型不合法
        if (empty($model)) {
            $this->_showMessage(\Yii::t('tag', 'Not Found Record'), false, null, false, null,"top.layer.closeAll('iframe');");
        }

        if ($this->request->getIsAjax()) {

            $model->modfiy_time = date('Y-m-d H:i:s',time());
            //数据验证以及保存数据
            $this->validateAndSaveData($this->request->post(),$model);
            
            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/tag/list') . '");';
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,$refreshUrl);
        }

        return $this->render('edit', [
            'model' => $model,
            'platformList' => Platform::getPlatformAsArray(),
        ]);
    }

    /**
     * @desc 删除记录
     */
    public function actionDelete()
    {
        $id = (int)$this->request->getQueryParam('id');

        //没有勾选标签
        if (empty($id)) {
            $this->_showMessage(\Yii::t('tag', 'Invalid Params'), false);
        }
        
        //勾选的标签被绑定了规则不允许删除
        if (!Tag::isAlowDeleteById($id)) {
           $this->_showMessage(\Yii::t('tag', 'tag have rule,forbidden delete'), false);
        }

        $model = new Tag();
        $flag = $model->deleteById($id);

        //删除失败
        if (!$flag) {
            $this->_showMessage(\Yii::t('tag', 'Operate Failed'), false);
        }

        $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/tag/list') . '");';
        $this->_showMessage(\Yii::t('tag', 'Operate Successful'), true, null, false, null,$refreshUrl);
    }

    /**
     * @desc 批量删除记录
     */
    public function actionBatchdelete()
    {
        $model = new Tag();
        if ($this->request->getIsAjax()) {
            $ids = $this->request->getBodyParam('ids');

            //没有选取标签
            if (empty($ids)) {
                $this->_showMessage(\Yii::t('tag', 'Not Selected Data'), false);
            }

            $ids = Tag::getAllowDeleteId($ids);

            //没有允许删除的标签
            if (empty($ids)) {
                $this->_showMessage(\Yii::t('tag', 'no have ids allow delete'), false);
            }

            $ids = array_filter($ids);
            $result = $model->deleteByIds($ids);

            //删除失败
            if (!$result) {
                $this->_showMessage(\Yii::t('tag', 'Operate Failed'), false);
            }

            //删除成功
            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/tag/list') . '");';
            $this->_showMessage(\Yii::t('tag', 'Operate Successful'), true, null, false, null,$refreshUrl);
        }
    }

    public function actionGettagsbyplatformcode()
    {
        $platform_code = Yii::$app->request->getQueryParam('platform_code');

        $list = Tag::getTagAsArray($platform_code);

        if($list)
        {
            $this->_showMessage('', true, null, false, $list);
        }
        else
        {
            $this->_showMessage('未获取到标签信息！',false);
        }
    }
}
