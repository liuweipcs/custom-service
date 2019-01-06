<?php
/**
 * @desc　Index controller
 * @author Fun
 */
namespace app\modules\systems\controllers;
use app\components\Controller;
class IndexController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }
}