<?php

namespace app\modules\mails\controllers;

use Yii;
use app\components\Controller;
use app\modules\mails\models\MailTemplateCategory;
use app\modules\accounts\models\Platform;
use yii\helpers\Url;

/**
 * 回复内容模版分类
 */
class MsgcontentcateController extends Controller
{

    /**
     * 列表
     */
    public function actionList()
    {
        $model = new MailTemplateCategory();
        $params = Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);
        return $this->renderList('list', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
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

        if (MailTemplateCategory::deleteAll(['in', 'id', $ids])) {
            $extraJs = 'top.refreshTable("' . Url::toRoute('/mails/msgcontentcate/list') . '");';
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
        $info = MailTemplateCategory::findOne($id);
        if (empty($info)) {
            $this->_showMessage('没有找到该分类', false);
        }
        if ($info->delete()) {
            $extraJs = 'top.refreshTable("' . Url::toRoute('/mails/msgcontentcate/list') . '");';
            $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
        } else {
            $this->_showMessage(Yii::t('system', 'Operate Failed'), false);
        }
    }

    /**
     * 添加
     */
    public function actionAdd()
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();
            //装载数据
            $model = new MailTemplateCategory();
            $model->load($data);
            if (!$model->validate()) {
                $this->_showMessage(current(current($model->getErrors())), false, Yii::$app->request->getUrl());
            }

            $model->create_by = Yii::$app->user->identity->login_name;
            $model->create_time = date('Y-m-d H:i:s');
            $model->modify_by = Yii::$app->user->identity->login_name;
            $model->modify_time = date('Y-m-d H:i:s');

            //保存数据
            if (!$model->save()) {
                $this->_showMessage(Yii::t('system', 'Operate Failed'), false);
            } else {
                $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/mails/msgcontentcate/list') . '");';
                $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
            }
        } else {
            $this->isPopup = true;
            $model = new MailTemplateCategory();
            //所属平台列表
            $platformList = Platform::getPlatformAsArray();
            $platformList = array_merge(['' => '请选择平台'], $platformList);
            return $this->render('add', [
                'model' => $model,
                'platformList' => $platformList,
            ]);
        }
    }

    /**
     * 修改
     */
    public function actionEdit()
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();

            $model = MailTemplateCategory::findOne($data['id']);
            if (empty($model)) {
                $this->_showMessage('没有找到信息', false);
            }
            //装载数据
            $model->load($data);
            if (!$model->validate()) {
                $this->_showMessage(current(current($model->getErrors())), false, Url::toRoute(['/mails/msgcontentcate/edit', 'id' => $data['id']]));
            }

            $model->modify_by = Yii::$app->user->identity->login_name;
            $model->modify_time = date('Y-m-d H:i:s');

            //保存数据
            if (!$model->save()) {
                $this->_showMessage(Yii::t('system', 'Operate Failed'), false);
            } else {
                $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/mails/msgcontentcate/list') . '");';
                $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
            }
        } else {
            $id = Yii::$app->request->get('id');

            if (empty($id)) {
                $this->_showMessage('ID不能为空', false);
            }

            $model = MailTemplateCategory::findOne($id);
            if (empty($model)) {
                $this->_showMessage('没有找到信息', false);
            }

            $this->isPopup = true;
            //所属平台列表
            $platformList = Platform::getPlatformAsArray();
            $platformList = array_merge(['' => '请选择平台'], $platformList);

            $categoryList = MailTemplateCategory::getCategoryList($model->platform_code, 0, 'list');

            return $this->render('edit', [
                'model' => $model,
                'platformList' => $platformList,
                'categoryList' => $categoryList,
            ]);
        }
    }
}