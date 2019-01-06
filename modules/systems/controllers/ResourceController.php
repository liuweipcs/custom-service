<?php
/**
 * @desc 资源控制器
 * @author Fun
 */
namespace app\modules\systems\controllers;
use app\components\Controller;
use app\modules\systems\models\Resource;
class ResourceController extends Controller
{
    /**
     * @desc刷新资源列表
     */
    public function actionRefresh()
    {
        $model = new Resource();
        if ($model->refreshResource())
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, true);
        else
            $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
    }
}