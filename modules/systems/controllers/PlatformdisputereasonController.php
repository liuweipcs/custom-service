<?php
namespace app\modules\systems\controllers;

use Yii;
use app\components\Controller;
use app\modules\systems\models\PlatformDisputeReason;
use yii\helpers\Url;

/**
 * 平台纠纷原因
 */
class PlatformdisputereasonController extends Controller
{
    /**
     * 列表
     */
    public function actionList()
    {
        $model = new PlatformDisputeReason();
        //获取查询参数
        $params = Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);

        return $this->renderList('list', [
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
    }

    /**
     * 添加
     */
    public function actionAdd()
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();
            //装载数据
            $model = new PlatformDisputeReason();
            $model->load($data, '');
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
                $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/systems/platformdisputereason/list') . '");';
                $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
            }
        } else {
            $this->isPopup = true;
            $platformList = PlatformDisputeReason::platformDropdown();
            return $this->render('add', [
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

            $model = PlatformDisputeReason::findOne($data['id']);
            if (empty($model)) {
                $this->_showMessage('没有找到纠纷原因信息', false);
            }
            //装载数据
            $model->load($data, '');
            if (!$model->validate()) {
                $this->_showMessage(current(current($model->getErrors())), false, Url::toRoute(['/systems/platformdisputereason/edit', 'id' => $data['id']]));
            }

            $model->modify_by = Yii::$app->user->identity->login_name;
            $model->modify_time = date('Y-m-d H:i:s');

            //保存数据
            if (!$model->save()) {
                $this->_showMessage(Yii::t('system', 'Operate Failed'), false);
            } else {
                $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/systems/platformdisputereason/list') . '");';
                $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
            }
        } else {
            $id = Yii::$app->request->get('id');

            if (empty($id)) {
                $this->_showMessage('纠纷原因ID不能为空', false);
            }

            $info = PlatformDisputeReason::find()->where(['id' => $id])->asArray()->one();
            if (empty($info)) {
                $this->_showMessage('没有找到纠纷原因信息', false);
            }

            $this->isPopup = true;
            $platformList = PlatformDisputeReason::platformDropdown();
            return $this->render('edit', [
                'info' => $info,
                'platformList' => $platformList,
            ]);
        }
    }

    /**
     * 批量删除收货规则
     */
    public function actionBatchdelete()
    {
        $ids = Yii::$app->request->post('ids');

        if (empty($ids) || !is_array($ids)) {
            $this->_showMessage('请选择删除项', false);
        }

        if (PlatformDisputeReason::deleteAll(['in', 'id', $ids])) {
            $extraJs = 'top.refreshTable("' . Url::toRoute('/systems/platformdisputereason/list') . '");';
            $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
        } else {
            $this->_showMessage(Yii::t('system', 'Operate Failed'), false);
        }
    }

    /**
     * 删除收货规则
     */
    public function actionDelete()
    {
        $id = Yii::$app->request->get('id');

        if (empty($id)) {
            $this->_showMessage('请选择删除项', false);
        }
        $info = PlatformDisputeReason::findOne($id);
        if (empty($info)) {
            $this->_showMessage('没有找到该规则', false);
        }
        if ($info->delete()) {
            $extraJs = 'top.refreshTable("' . Url::toRoute('/systems/platformdisputereason/list') . '");';
            $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
        } else {
            $this->_showMessage(Yii::t('system', 'Operate Failed'), false);
        }
    }
}