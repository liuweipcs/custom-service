<?php
/**
 * @desc 视图类扩展
 * @author Fun
 */
namespace app\components;
class View extends \yii\web\View
{
    /**
     * @desc 请求对象
     * @var unknown
     */
    public $request = null;
    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \yii\base\View::init()
     */
    public function init()
    {
        parent::init();
        $this->request = \Yii::$app->getRequest();
    }
}