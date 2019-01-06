<?php

/**
 * Created by PhpStorm.
 * User: wuyang
 * Date: 2017/4/19 0011
 * Time: 上午 10:54
 */

namespace app\modules\mails\controllers;

use app\components\Controller;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\app\modules\mails\models;
use app\modules\mails\models\FeedbackTemplate;
use yii\helpers\Json;

class FeedbacktemplateController extends Controller {

    public function actionList() {
        $model = new FeedbackTemplate();
        $params = \Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);
        return $this->renderList('list', [
                    'model' => $model,
                    'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @desc 新增模版
     * @return \yii\base\string
     */
    public function actionAdd() {
        set_time_limit(30);
        $this->isPopup = true;

        $model = new FeedbackTemplate();
        if ($this->request->getIsAjax()) {
            // $data = $this->request->post();
            $model->load($this->request->post());
            if ($model->validate()) {
                if ($model->save())
                    $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, 'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/feedbacktemplate/list') . '");');
                else
                    $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
            } else {
                $errors = $model->getErrors();
                $error = array_pop($errors);
                $this->_showMessage(\Yii::t('system', $error[0]), false);
            }
        }

        return $this->render('add', [
                    'model' => $model,
        ]);
    }

    /**
     * @desc 批量删除记录
     * author wuyang
     * date 2017 04 19
     * 删除采取状态删除
     */
    public function actionBatchdelete() {
        set_time_limit(30);
        $model = new FeedbackTemplate();
        if ($this->request->getIsAjax()) {
            $ids = $this->request->getBodyParam('ids');
            if (empty($ids))
                $this->_showMessage(\Yii::t('system', 'Not Selected Data'), false);
            $ids = array_filter($ids);
//            var_dump($ids);
//            exit;
            $flag = $model->deleteByIds($ids);
            if ($flag)
                $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, 'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/feedbacktemplate/list') . '");');
            else
                $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
        }
    }

    /**
     * @单条记录状态删除
     * @只更改状态，不进行实际删除
     * @author wuyang
     * @date 2017 04 20
     */
    public function actionDelete() {
        set_time_limit(30);
        $model = new FeedbackTemplate();
        if ($this->request->getIsAjax()) {

            $id = $this->request->get('id');
            if (empty($id))
                $this->_showMessage(\Yii::t('system', 'Not Selected Data'), false);
            $flag = $model->deleteById($id);
            if ($flag)
                $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, 'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/feedbacktemplate/list') . '");');
            else
                $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
        }
    }

    /**
     * @desc 编辑记录
     * @return \yii\base\string
     * @author  wuyang
     * date 2017 4 19
     * 编辑
     *
     */
    public function actionEdit() {
        $this->isPopup = true;
        $id = (int) $this->request->getQueryParam('id');
        if (empty($id))
            $this->_showMessage(\Yii::t('system', 'Invalid Params'), false, null, false, null, "top.layer.closeAll('iframe');");
        $model = FeedbackTemplate::findById($id);
        if (empty($model))
            $this->_showMessage(\Yii::t('system', 'Not Found Record'), false, null, false, null, "top.layer.closeAll('iframe');");
        if ($this->request->getIsAjax()) {
            $model->load($this->request->post());
            if ($model->validate()) {
                if ($model->save())
                    $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, 'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/feedbacktemplate/list') . '");');
                else
                    $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
            } else {
                $errors = $model->getErrors();
                $error = array_pop($errors);
                $this->_showMessage(\Yii::t('system', $error[0]), false);
            }
        }

        return $this->render('edit', [
                    'model' => $model,
        ]);
    }

    /**
     * @desc 随机获取模板内容
     * @return string
     */
    public function actionGettemplatename() {
        $platform_code = $this->request->post('platform_code') ? :'';
        if (!empty($platform_code)) {
            $template_info = FeedbackTemplate::Getemplatenamecode($platform_code);
            $template_content = $template_info[1];
        }else{
            $template_content = FeedbackTemplate::Gettemplatename();
        }
        if (!empty($template_content)) {

            $this->_showMessage('', true, null, false, $template_content);
        }
        $this->_showMessage('未找到模板数据', false);
    }
   /**
     * @desc 根据平台code随机获取模板内容
     * @return string
    * @author harvin <2018-11-21>
     */
    public function actionGetfeedbacktemplate() {
        $code=$this->request->post('platformCode');   
        $template_content=FeedbackTemplate::Getemplatenamecode($code); 
        if($template_content){
            return json_encode(['state'=>1,'msg'=>$template_content[1],'id'=>$template_content[0]]);
        }else{
              return json_encode(['state'=>0,'msg'=>'数据获取失败']);
        }           
    }
    
     /**
     * @desc 根据平台code获取模板内容
     * @return string
    * @author harvin <2018-11-22>
     */
    public function actionGetfeedbacktemplateall(){
        $code=$this->request->post('platformCode');   
        $re=FeedbackTemplate::find()->where(['platform_code'=>$code])->all();    
        
        foreach ($re as $key => $v) {
            $res[$v->id]=$v->template_content;
        }
        return json_encode(['data'=>$res]);      
    }
    /**
     * @desc 根据平台code获取指定模板内容
     * @return string
    * @author harvin <2018-11-22>
     */
    public function actionGetfeedbacktemplateinfo(){
        $feedbacktemplateid=$this->request->post('feedbacktemplateid');
        
      $template_content=FeedbackTemplate::find()->select('template_content')->where(['id'=>$feedbacktemplateid])->asArray()->one(); 
       return json_encode(['data'=>$template_content['template_content']]);   
        
        
        
    }
    

}
