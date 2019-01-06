<?php
/**
 * @desc　用户控制器
 * @author Fun
 */

namespace app\modules\users\controllers;

use app\components\Controller;
use app\modules\users\models\User;
use app\modules\users\models\UserRole;
use yii\web\UploadedFile;

class UserController extends Controller
{
    /**
     * @desc 登录
     */
    public function actionLogin()
    {
        $errorMsg = '';
        if (\yii::$app->request->getIsPost()) {
            $model     = new User();
            $loginName = trim($this->request->getBodyParam('login_name'));
            $password  = trim($this->request->getBodyParam('login_password'));
            if (empty($loginName) || empty($password)) {
                $errorMsg = \Yii::t('user', 'Login Name Or Password Is Empty');
            } else {
                if (!preg_match('/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])[A-Za-z0-9]{6,20}/', $password)) {
                    $errorMsg = '密码太简单 <a href="http://120.24.249.36/site/resetpassword" target="_blank">请先移步ERP修改密码</a>';
                } else {
                    $loginReturn = $model->login($loginName, $password);
                    if ($loginReturn === true) {
                        $this->redirect(['/systems/index/index']);
                        \Yii::$app->end();
                    } else {
                        $errorMsg = $loginReturn[1];
                    }
                }
            }
        }
        //如果已经登录，进入到首页
        if (!\Yii::$app->user->getIsGuest()) {
            $this->redirect('/systems/index/index');
            \Yii::$app->end();
        }
        $this->layout = false;
        echo $this->render('login', [
            'errorMsg' => $errorMsg,
        ]);
    }

    /**
     * @desc 退出
     */
    public function actionLogout()
    {
        \Yii::$app->user->logout();
        $this->redirect(['/users/user/login']);
        \yii::$app->end();
    }

    /**
     * @desc 用户列表
     * @return \yii\base\string
     */
    public function actionList()
    {
        \yii::$app->response->format = 'raw';
        $model                       = new User();
        $params                      = \Yii::$app->request->getBodyParams();
        $dataProvider                = $model->searchList($params);
        return $this->renderList('list', [
            'model'        => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @desc 新增用户
     * @return \yii\base\string
     */
    public function actionAdd()
    {
        $this->isPopup = true;
        $model         = new User(['scenario' => 'add']);
        if ($this->request->getIsAjax()) {
            $model->load($this->request->post());
            if ($model->validate()) {
                $roleIds       = $this->request->getBodyParam('User')[role_ids] ? $this->request->getBodyParam('User')[role_ids] : [];
                $roleIds       = array_unique($roleIds);
                $dbTransaction = $model->getDb()->beginTransaction();
                try {
                    $model->login_password = \Yii::$app->getSecurity()->generatePasswordHash($model->login_password);
                    if ($model->save(false)) {
                        //保存用户对应的角色ID
                        if (!is_array($roleIds) || empty($roleIds))
                            throw new \Exception('没有选择用户所在角色');
                        $flag = UserRole::addUserRoles($model->id, $roleIds);
                        if (!$flag)
                            throw new \Exception('保存用户角色失败');
                        $dbTransaction->commit();
                        $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,
                            'top.refreshTable("' . \yii\helpers\Url::toRoute('/users/user/list') . '");');
                    } else
                        $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
                } catch (\Exception $e) {
                    $dbTransaction->rollBack();
                    $this->_showMessage(\Yii::t('system', 'Operate Failed, ' . $e->getMessage()), false);
                }
            } else {
                $errors = $model->getErrors();
                $error  = array_shift($errors);
                $this->_showMessage(\Yii::t('system', $error[0]), false);
            }
        }
        $modelRole = new \app\modules\users\models\Role();
        $modelRole->getRoleTreeList(0, 1, true, $roleList);
        return $this->render('add', [
            'model'    => $model,
            'roleList' => $roleList,
        ]);
    }

    /**
     * @desc 批量删除记录
     */
    public function actionBatchdelete()
    {
        $model = new User();
        if ($this->request->getIsAjax()) {
            $ids = $this->request->getBodyParam('ids');
            if (empty($ids))
                $this->_showMessage(\Yii::t('system', 'Not Selected Data'), false);
            $ids           = array_filter($ids);
            $dbTransaction = $model->getDb()->beginTransaction();
            try {
                //删除用户ID
                $flag = $model->deleteByIds($ids);
                if (!$flag)
                    throw new \Exception('删除用户失败');
                //删除用户角色关系
                if (!UserRole::deleteByUserId($ids))
                    throw new \Exception('删除用户角色关系失败');
                $dbTransaction->commit();
                $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,
                    'top.refreshTable("' . \yii\helpers\Url::toRoute('/users/user/list') . '");');
            } catch (\Exception $e) {
                $this->_showMessage(\Yii::t('system', 'Operate Failed ' . $e->getMessage()), false);
            }
        }
    }

    /**
     * @desc 删除记录
     */
    public function actionDelete()
    {
        $id = (int)$this->request->getQueryParam('id');
        if (empty($id))
            $this->_showMessage(\Yii::t('system', 'Invalid Params'), false);
        $model         = new User();
        $dbTransaction = $model->getDb()->beginTransaction();
        try {
            //删除用户ID
            $flag = $model->deleteById($id);
            if (!$flag)
                throw new \Exception('删除用户失败');
            //删除用户角色关系
            if (!UserRole::deleteByUserId($id))
                throw new \Exception('删除用户角色关系失败');
            $dbTransaction->commit();
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,
                'top.refreshTable("' . \yii\helpers\Url::toRoute('/users/user/list') . '");');
        } catch (\Exception $e) {
            $this->_showMessage(\Yii::t('system', 'Operate Failed ' . $e->getMessage()), false);
        }
    }

    /**
     * @desc 编辑记录
     * @return \yii\base\string
     */
    public function actionEdit()
    {
        $this->isPopup = true;
        $id            = (int)$this->request->getQueryParam('id');
        if (empty($id))
            $this->_showMessage(\Yii::t('system', 'Invalid Params'), false, null, false, null,
                "top.layer.closeAll('iframe');");
        $model = User::findById($id);
        if (empty($model))
            $this->_showMessage(\Yii::t('system', 'Not Found Record'), false, null, false, null,
                "top.layer.closeAll('iframe');");
        if ($this->request->getIsAjax()) {
            $roleId = $this->request->getBodyParam('User')[role_ids] ? $this->request->getBodyParam('User')[role_ids][0] : 0;
            $model->role_id = $roleId;
            $model->load($this->request->post());
            if ($model->validate()) {
                $roleIds       = $this->request->getBodyParam('User')[role_ids] ? $this->request->getBodyParam('User')[role_ids] : [];
                $roleIds       = array_unique($roleIds);
                $dbTransaction = $model->getDb()->beginTransaction();
                try {
                    if ($model->save()) {
                        //保存用户对应的角色ID
                        if (!is_array($roleIds) || empty($roleIds))
                            throw new \Exception('没有选择用户所在角色');
                        //删除原先的用户角色关系
                        if (!UserRole::deleteByUserId($model->id))
                            throw new \Exception('删除用户角色关系失败');
                        $flag = UserRole::addUserRoles($model->id, $roleIds);
                        if (!$flag)
                            throw new \Exception('保存用户角色失败');
                        $dbTransaction->commit();
                        $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null,
                            'top.refreshTable("' . \yii\helpers\Url::toRoute('/users/user/list') . '");');
                    } else
                        $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
                } catch (\Exception $e) {
                    $dbTransaction->rollBack();
                    $this->_showMessage(\Yii::t('system', 'Operate Failed, ' . $e->getMessage()), false);
                }
            } else {
                $errors = $model->getErrors();
                $error  = array_pop($errors);
                $this->_showMessage(\Yii::t('system', $error[0]), false);
            }
        }
        $modelRole = new \app\modules\users\models\Role();
        $modelRole->getRoleTreeList(0, 1, true, $roleList);
        $model->role_ids = UserRole::getUserRoleIds($model->id);
        return $this->render('edit', [
            'model'    => $model,
            'roleList' => $roleList,
        ]);
    }


    /**
     * 导入用户工号信息
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \yii\base\Exception
     */
    public function actionImport()
    {
        if (\Yii::$app->request->isPost) {
            header("Content-type: text/html; charset=utf-8");
            ini_set('memory_limit', '50M');
            ini_set('post_max_size', '50M');
            ini_set('upload_max_filesize', '50M');
            set_time_limit('3600');
            $file     = UploadedFile::getInstanceByName('image');  //获取上传的文件实例
            $filePath = './uploads/user/' . date('Ym') . '/';
            if (!file_exists($filePath)) {
                @mkdir($filePath, 0777, true);
                @chmod($filePath, 0777);
            }
            if ($file) {
                $filename = date('Y-m-d', time()) . '_' . rand(1, 9999) . "." . $file->extension;
                $file->saveAs($filePath . $filename);   //保存文件
                $format = $file->extension;
                if (in_array($format, array('xls', 'xlsx','csv'))) {
                    $excelFile = trim(\Yii::$app->basePath . '/web/uploads/user/' . date('Ym').'/' . $filename);

                    if ($format == 'xlsx') {
                        $objReader = new \PHPExcel_Reader_Excel2007();
                        $objExcel  = $objReader->load($excelFile);
                    } else if ($format == 'xls') {
                        $objReader = new \PHPExcel_Reader_Excel5();
                        $objExcel  = $objReader->load($excelFile);
                    } else if ($format == 'csv') {
                        $PHPReader = new \PHPExcel_Reader_CSV();
                        //默认输入字符集
                        $PHPReader->setInputEncoding('GBK');
                        //默认的分隔符
                        $PHPReader->setDelimiter(',');
                        //载入文件
                        $objExcel = $PHPReader->load($file);
                    }
                    $sheet = $objExcel->getSheet(0);

                    $total_line   = $sheet->getHighestRow();//总行数
                    $total_column = $sheet->getHighestColumn();//总列数

                    if ($total_line > 1) {
                        for ($row = 2; $row <= $total_line; $row++) {
                            $data = array();
                            for ($column = 'A'; $column <= $total_column; $column++) {
                                $data[] = trim($sheet->getcell($column . $row)->getValue());
                            }
                            $user_name   = trim($data[0]);
                            $user_number = trim($data[1]);
                            $user_desc   = $data[2];
                            //一行行的插入数据库操作
                            $user_modal = User::findOne(['user_name' => $user_name]);
                            if (empty($user_modal)) {
                                $user_modal                 = new User();
                                $user_modal->user_name      = $user_name;
                                $user_modal->user_number    = $user_number;
                                $user_modal->login_name     = $user_name;
                                $user_modal->login_password = \Yii::$app->getSecurity()->generatePasswordHash($user_modal->login_password);
                                $user_modal->create_by      = 'system';
                                $user_modal->create_time    = date('Y-m-d H:i:s');
                                $user_modal->status         = 1;
                            }
                            $user_modal->user_number = $user_number;
                            $user_modal->modify_by   = 'system';
                            $user_modal->modify_time = date('Y-m-d H:i:s');
                            if ($user_modal->save()) {
                                $ok = 1;
                            }
                        }
                    }
                    if ($ok == 1) {
                        die(json_encode([
                            'code'    => 200,
                            'message' => '上传成功',
                        ]));
                    } else {
                        die(json_encode([
                            'code'    => 201,
                            'message' => '上传失败',
                        ]));
                    }
                }
            }

        }
    }
}