<?php

/**
 * Created by PhpStorm.
 * User: wuyang
 * Date: 2017/4/19 0011
 * Time: 上午 10:54
 */

namespace app\modules\mails\controllers;

use app\modules\mails\models\MailTemplateCategory;
use app\modules\mails\models\MailTemplate;
use app\components\Controller;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\MailTemplateStrReplacement;
use yii\helpers\Json;
use Yii;
use yii\helpers\Url;

class MsgcontentController extends Controller
{
    public function actionList()
    {
        $model        = new MailTemplate();
        $params       = \Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);
        return $this->renderList('list', [
            'model'        => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @desc 新增模版
     * @return \yii\base\string
     */
    public function actionAdd()
    {
        $this->isPopup = true;
        $model         = new MailTemplate();

        if ($this->request->getIsAjax()) {
            $model->load($this->request->post());

            if ($model->validate()) {
                try {
                    if ($model->save())
                        $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,
                            'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/msgcontent/list') . '");');
                    else
                        $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
                } catch (\Exception $e) {
                    $errors = $e->getMessage();
                    $this->_showMessage(\Yii::t('system', $errors), false);
                }

            } else {
                $errors = $model->getErrors();
                $error  = array_pop($errors);
                $this->_showMessage(\Yii::t('system', $error[0]), false);
            }
        }

        //所属平台列表
        $platformList = Platform::getPlatformAsArray();
        $platformList = array_merge([0 => '请选择平台'], $platformList);

        //模板分类列表
        $categoryList = [];

        //模版变量列表
        $replaceList = MailTemplateStrReplacement::find()
            ->select('id, position_str_cn')
            ->where(['status' => 1])
            ->asArray()
            ->all();

        if (!empty($replaceList)) {
            $replaceList = array_column($replaceList, 'position_str_cn', 'id');
        }
        $replaceList = array_merge(['0' => '请选择'], $replaceList);

        return $this->render('add', [
            'model'        => $model,
            'categoryList' => $categoryList,
            'platformList' => $platformList,
            'replaceList'  => $replaceList,
        ]);
    }

    /**
     * @desc 批量复制私有模板
     * author JD 詹姆斯
     * date 2018 10 20
     */
    public  function actionCopy()
    {
        $model = new Mailtemplate();
        if($this->request->getIsAjax()){
            $ids = $this->request->getBodyParam('ids');
            if (empty($ids)) {
                $this->_showMessage(Yii::t('system', '请选择需要复制的模板'), false);
            }

            $data_list = $model::find()->where(['in','id',$ids])->asArray()->all();

            foreach ($data_list as $k => $item) {
                if($item['private'] == $model::MAIL_TEMPLATE_STATUS_PUBLIC){
                    $this->_showMessage(Yii::t('system', '所有模板必须全部为私有模板'), false);
                }
                if($item['status'] == $model::MAIL_TEMPLATE_STATUS_INVALID){
                    $this->_showMessage(Yii::t('system', '无效的模板'), false);
                }
            }

            foreach ($data_list as $k => $value){
                $obj = new Mailtemplate();
                $obj->category_id = $value['category_id'];
                $obj->template_name = $value['template_name'];
                $obj->template_content = $value['template_content'];
                $obj->template_title = $value['template_title'];
                $obj->template_description = $value['template_description'];
                $obj->platform_code = $value['platform_code'];
                $obj->template_type = $value['template_type'];
                $obj->status = $value['status'];
                $obj->sort_order = $value['sort_order'];
                $obj->create_by = Yii::$app->user->identity->login_name;
                $obj->create_time = date('Y-m-d H:i:s');
                $obj->modify_by = Yii::$app->user->identity->login_name;
                $obj->modify_time = date('Y-m-d H:i:s');
                $obj->private = $value['private'];
                $obj->save();
            }

            $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/mails/msgcontent/list') . '");';
            $this->_showMessage(Yii::t('system', '复制成功'), true, null, false, null, $extraJs);

        }

    }
    /**
     * @desc 批量删除记录
     * author wuyang
     * date 2017 04 19
     * 删除采取状态删除
     */
    public function actionBatchdelete()
    {
        $model = new Mailtemplate();
        if ($this->request->getIsAjax()) {
            $ids = $this->request->getBodyParam('ids');
            if (empty($ids)) {
                $this->_showMessage(\Yii::t('system', 'Not Selected Data'), false);
            }
            $ids = array_filter($ids);

            $delete_arr = ['cs_amazonmanager', 'cs_aliexpress-manager',
                'cs_ebay-manager', 'cs_cdwish-manager',
                'cs_shopee-manager', 'cs_site-manager', 'admin'];
            $session    = Yii::$app->user->identity->role;
            $role_code  = $session->role_code;
            
            if ($model->private == MailTemplate::MAIL_TEMPLATE_STATUS_PRIVATE && strpos($role_code,'member') !== false) {
                if ($model->create_by != \Yii::$app->user->identity->user_name) {
                    $this->_showMessage('成员角色只能删除本人创建的私有模板', false);
                }
            }
            
            
            
//            if (!in_array($role_code, $delete_arr)) {
//                $this->_showMessage('非主管级别模板不能删除', false);
//            }
            if (in_array($role_code, $delete_arr)) {
                $flag = $model->deleteAll(['and', ['in', 'id', $ids]]);
            } else {
                $flag = $model->deleteAll(['and', ['in', 'id', $ids], ['create_by' => \Yii::$app->user->identity->user_name]]);
            }

            if (count($ids) > 0 && $flag == count($ids)) {
                $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,
                    'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/msgcontent/list') . '");');
            } else if (count($ids) > 0 && $flag != count($ids)) {
                $this->_showMessage('操作成功，非本人创建模板未删除！', true, null, false, null,
                    'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/msgcontent/list') . '");');
            } else {
                $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
            }
        }
    }

    /**
     * @单条记录状态删除
     * @只更改状态，不进行实际删除
     * @author wuyang
     * @date 2017 04 20
     */
    public function actionDelete()
    {
        $model = new MailTemplate();
        if ($this->request->getIsAjax()) {
            $id                = $this->request->get('id');
            $mailTemplateModel = MailTemplate::findOne($id);
            /************
             * cs_amazonmanager 亚马逊客服主管
             * cs_ebay-manager  ebay客服主管
             * cs_cdwish-manager CD Wish客服主管
             * cs_shopee-manager shopee客服主管
             * cs_site-manager 独立网站客服主管
             ************/
            $delete_arr = ['cs_amazonmanager', 'cs_aliexpress-manager',
                'cs_ebay-manager', 'cs_cdwish-manager',
                'cs_shopee-manager', 'cs_site-manager', 'admin'];
            $session    = Yii::$app->user->identity->role;
            $role_code  = $session->role_code;

            if ($mailTemplateModel->private == MailTemplate::MAIL_TEMPLATE_STATUS_PRIVATE && strpos($role_code,'member') !== false) {
                if ($mailTemplateModel->create_by != \Yii::$app->user->identity->user_name) {
                    $this->_showMessage('成员角色只能删除本人创建的私有模板', false);
                }
            }
            
//            if ($mailTemplateModel->private == MailTemplate::MAIL_TEMPLATE_STATUS_PRIVATE) {
//                //私有模板 主管可以删除其他组员的模板
//                if (!in_array($role_code, $delete_arr)) {
//                    $this->_showMessage('非主管级别模板不能删除', false);
//                } else if ($mailTemplateModel->create_by != \Yii::$app->user->identity->user_name && !in_array($role_code, $delete_arr)) {
//                    $this->_showMessage('越界了！！！非本人创建模板不能删除', false);
//                }
//            }

            if (empty($id)) {
                $this->_showMessage(\Yii::t('system', 'Not Selected Data'), false);
            }
            $flag = $model->deleteById($id);
            if ($flag) {
                $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,
                    'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/msgcontent/list') . '");');
            } else {
                $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
            }
        }
    }


    /**
     * @desc 编辑记录
     * @return \yii\base\string
     * @author  wuyang
     */
    public function actionEdit()
    {
        $this->isPopup = true;
        $id            = (int)$this->request->getQueryParam('id');
        if (empty($id)) {
            $this->_showMessage(\Yii::t('system', 'Invalid Params'), false, null, false, null,
                "top.layer.closeAll('iframe');");
        }
        $model = MailTemplate::findById($id);
        if (empty($model)) {
            $this->_showMessage(\Yii::t('system', 'Not Found Record'), false, null, false, null,
                "top.layer.closeAll('iframe');");
        }
        if ($this->request->getIsAjax()) {
            $session   = Yii::$app->user->identity->role;
            $role_code = $session->role_code;

            //如果模板为私有的才进行如下判断
            if ($model->private == MailTemplate::MAIL_TEMPLATE_STATUS_PRIVATE && strpos($role_code,'member') !== false) {
                if ($model->create_by != \Yii::$app->user->identity->user_name) {
                    $this->_showMessage('成员角色只能修改本人创建的私有模板', false);
                }
            }

//            if ($model->template_type == MailTemplate::MAIL_TEMPLATE_TYPE_ORDER && !in_array($role_code, ['cs_amazonmanager', 'cs_aliexpress-manager', 'cs_ebay-manager', 'cs_cdwish-manager', 'cs_leader'])) {
//                $this->_showMessage('信息模板只有主管才能修改', false);
//            }

            $model->load($this->request->post());
            if ($model->validate()) {
                if ($model->updatefield()) {
                    $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, 'top.refreshTable("' . \yii\helpers\Url::toRoute('/mails/msgcontent/list') . '");', true, 'msg');
                } else {
                    $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
                }
            } else {
                $errors = $model->getErrors();
                $error  = array_pop($errors);
                $this->_showMessage(\Yii::t('system', $error[0]), false);
            }
        }

        //所属平台列表
        $platformList = Platform::getPlatformAsArray();
        $platformList = array_merge([0 => '请选择平台'], $platformList);

        //模板分类列表
        $categoryList = MailTemplateCategory::getCategoryList($model->platform_code, 0, 'list');

        return $this->render('edit', [
            'model'        => $model,
            'categoryList' => $categoryList,
            'platformList' => $platformList,
        ]);
    }

    /**
     * @desc 返回平台的目录列表
     * @return \yii\base\string
     * @author  wuyang
     */
    public function actionGetlist()
    {
        $platformCode = $this->request->getBodyParam('platform_code');

        if (empty($platformCode)) {
            die(json_encode([
                'code'    => 1,
                'message' => '平台code不能为空',
                'data'    => [],
            ]));
        }

        $data = MailTemplateCategory::getCategoryList($platformCode);

        if (empty($data)) {
            die(json_encode([
                'code'    => 0,
                'message' => '数据为空',
            ]));
        } else {
            die(json_encode([
                'code'    => 1,
                'message' => '成功',
                'data'    => $data,
            ]));
        }
    }

    /**
     * 获取模板
     * @throws \yii\base\ExitException
     */
    public function actionGettemplate()
    {
        $id = \yii::$app->request->post('num');
        if (is_numeric($id) && $id > 0 && $id % 1 === 0) {
            $template = MailTemplate::find()->select('template_content')->where(['id' => $id, 'status' => MailTemplate::MAIL_TEMPLATE_STATUS_VALID])->one()->template_content;
            if (empty($template)) {
                $response['status']  = 'error';
                $response['message'] = '未找到模板';
            } else {
                $response['status']  = 'success';
                $response['content'] = $template;
            }
        } else {
            $response['status']  = 'error';
            $response['message'] = 'num格式错误';
        }
        echo Json::encode($response);
        \yii::$app->end();
    }

    /**搜索模板
     * @author alpha
     * @desc
     */
    public function actionSearchtemplate()
    {
        $name          = Yii::$app->request->post('name');
        $platform_code = Yii::$app->request->post('platform_code');
        $user_name     = Yii::$app->user->identity->user_name;

        $query = MailTemplate::find()
            ->select('id, template_name')
            ->andWhere(['status' => MailTemplate::MAIL_TEMPLATE_STATUS_VALID])
            ->andWhere([
                'or',
                [
                    'and',
                    ['private' => MailTemplate::MAIL_TEMPLATE_STATUS_PUBLIC],
                    [
                        'or',
                        ['like', 'template_name', $name],
                        ['like', 'template_title', $name],
                    ],
                ],
                [
                    'and',
                    ['private' => MailTemplate::MAIL_TEMPLATE_STATUS_PRIVATE],
                    ['like', 'create_by', $user_name],
                    [
                        'or',
                        ['like', 'template_name', $name],
                        ['like', 'template_title', $name],
                    ],
                ],
            ]);


        if (!empty($platform_code)) {
            $query->andWhere(['platform_code' => $platform_code]);
        }

        $data = $query->asArray()->all();

        if (empty($data)) {
            $response = ['status' => 'error', 'message' => '未找到数据'];
        } else {
            $response = ['status' => 'success', 'content' => array_column($data, 'template_name', 'id')];
        }
        echo Json::encode($response);
    }

    /**
     * 查询模板标题
     */
    public function actionSearchtemplatetitle()
    {
        $name          = Yii::$app->request->post('name');
        $platform_code = Yii::$app->request->post('platform_code');
        $user_name     = Yii::$app->user->identity->user_name;

        $template = MailTemplate::find()
            ->select('template_content')
            ->andWhere(['platform_code' => $platform_code])
            ->andWhere(['status' => MailTemplate::MAIL_TEMPLATE_STATUS_VALID])
            ->andWhere([
                'or',
                [
                    'and',
                    ['private' => MailTemplate::MAIL_TEMPLATE_STATUS_PUBLIC],
                    ['like', 'template_title', $name],
                ],
                [
                    'and',
                    ['private' => MailTemplate::MAIL_TEMPLATE_STATUS_PRIVATE],
                    ['like', 'create_by', $user_name],
                    ['like', 'template_title', $name],
                ],
            ])
            ->one();

        if (empty($template)) {
            $this->_showMessage('未找到数据', false);
        } else {
            $this->_showMessage('', true, null, false, $template->template_content);
        }
    }

    /**
     * @desc 获取模版变量要插入到内容中的变量样式
     * @author wuyang
     * @date  2017 04 24
     */
    public function actionGetvar()
    {
        $code                            = $this->request->getBodyParam('valcode');
        $model                           = new MailTemplate();
        $MailTemplateStrReplacementModel = New MailTemplateStrReplacement();
        $replace_str                     = $model->getData($MailTemplateStrReplacementModel, 'position_str', 'one', "where id ='" . $code . "'");
        return $replace_str['position_str'];
    }
}