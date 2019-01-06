<?php

namespace app\modules\systems\controllers;

use app\modules\systems\models\Keyboard;
use Yii;
use app\modules\systems\models\Tag;
use app\modules\accounts\models\Platform;
use app\components\Controller;
use yii\web\NotFoundHttpException;
use app\common\VHelper;
/**
 * TagController implements the CRUD actions for Tag model.
 */
class KeyboardController extends Controller
{


    /**
     * Lists all Tag models.
     * @return mixed
     */
    public function actionList()
    {   
        $model = new Keyboard();
        $params = \Yii::$app->request->getBodyParams();
//        var_dump($params);exit;
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
        $model = new Keyboard();

        if ($this->request->getIsAjax()) {
            $data = $this->request->post('Keyboard');
            $is_exist = Keyboard::find()->where(['platform_code'=>$data['platform_code'],'tag_id'=>$data['tag_id']])->orWhere(['platform_code'=>$data['platform_code'],'key_name'=>$data['key_name']])->one();
            if($is_exist)
                $this->_showMessage(\Yii::t('system','该标签快捷键已经存在！'),false);

            //数据验证以及保存数据
            $this->validateAndSaveData($this->request->post(),$model);

            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/keyboard/list') . '");';
            $this->_showMessage(\Yii::t('tag', 'Operate Successful'), true, null, false, null,$refreshUrl);
        }

        $platformList = Platform::getPlatformAsArray();

        return $this->render('add', [
            'model' => $model,
            'platformList' => $platformList,
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

        $this->isPopup = true;
        $model = Keyboard::findOne($id);

        if ($this->request->getIsAjax()) {
            $data = $this->request->post('Keyboard');
            $is_exist = Keyboard::find()->where(['platform_code'=>$data['platform_code'],'key_name'=>$data['key_name']])->one();
            if($is_exist)
                $this->_showMessage(\Yii::t('system','该标签快捷键已经存在！'),false);

            //数据验证以及保存数据
            $this->validateAndSaveData($this->request->post(),$model);

            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/keyboard/list') . '");';
            $this->_showMessage(\Yii::t('tag', 'Operate Successful'), true, null, false, null,$refreshUrl);
        }

        $platformList = Platform::getPlatformAsArray();

        // 根据已经选择平台获取标签
        $tags = Tag::getTagAsArray($model->platform_code);
//        var_dump($tags);exit;

        return $this->render('edit', [
            'model' => $model,
            'platformList' => $platformList,
            'tags' => $tags,
        ]);
    }

    /**
     * @desc 删除记录
     */
    public function actionDelete()
    {
        $id = (int)$this->request->getQueryParam('id');

        if (empty($id))
            $this->_showMessage(\Yii::t('system', 'Invalid Params'), false);
        $model = new Keyboard();
        $flag = $model->deleteById($id);
        if ($flag)
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,
                'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/keyboard/list') . '");');
        else
            $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
    }

    /**
     * @desc 批量删除记录
     */
    public function actionBatchdelete()
    {
        $model = new Keyboard();
        if ($this->request->getIsAjax())
        {
            $ids = $this->request->getBodyParam('ids');
            if (empty($ids))
                $this->_showMessage(\Yii::t('system', 'Not Selected Data'), false);
            $ids = array_filter($ids);
            $flag = $model->deleteByIds($ids);
            if ($flag)
                $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,
                    'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/keyboard/list') . '");');
            else
                $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
        }
    }
}
