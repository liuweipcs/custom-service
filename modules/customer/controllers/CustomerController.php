<?php

namespace app\modules\customer\controllers;

use app\components\Controller;
use app\modules\accounts\models\Account;
use app\modules\customer\models\CustomerList;
use app\modules\customer\models\CustomerTags;
use app\modules\customer\models\CustomerTagsRule;
use app\modules\customer\models\CustomerTagsDetail;
use app\modules\customer\models\CustomerOperation;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\UserAccount;
use app\modules\orders\models\OrderEbay;
use app\modules\orders\models\OrderKefu;
use Yii;
use yii\helpers\Url;
use app\modules\customer\models\CustomerGroup;
use app\modules\customer\models\CustomerGroupDetail;
use app\common\VHelper;
use yii\web\UploadedFile;
use app\models\UploadForm;
use app\modules\services\modules\amazon\components\Mail;
use app\modules\mails\models\WishInbox;
use app\modules\mails\models\WishReply;
use app\modules\mails\models\AliexpressInbox;
use app\modules\mails\models\AliexpressReply;
use app\modules\mails\models\MailTemplate;
use yii\helpers\Json;
use app\modules\mails\models\EbayInbox;
use app\modules\mails\models\EbayReply;
use PhpImap\Exception;
use app\modules\mails\models\MailOutbox;
use app\modules\systems\models\BasicConfig;

class CustomerController extends Controller
{
    const XLS_UPLOAD_PATH = 'uploads/xls/';

    public function actionList()
    {
        //客户列表
        $params = Yii::$app->request->get();
        if(empty($params)){
            $params = Yii::$app->request->getBodyParams();
        }
        $model = new CustomerList();
        $dataProvider = $model->searchList($params);
        $followList = $model->getFollowList($params);
        //echo "<pre>";var_dump($followList);exit;
        return $this->renderList('list', [
            'model' => $model,
            'dataProvider' => $dataProvider,
            'follows' => $followList
        ]);
    }

    //标签列表
    public function actionTags()
    {
        $params = Yii::$app->request->getBodyParams();
        $model = new CustomerTags();
        $dataProvider = $model->searchList($params);
        return $this->renderList('tags', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    //分组列表
    public function actionGroup()
    {
        $params = Yii::$app->request->getBodyParams();
        $model = new CustomerGroup();
        $dataProvider = $model->searchList($params);
        return $this->renderList('group', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }


    /**
     * 客户列表加入分组
     */
    public function actionListgroup()
    {
        $this->isPopup = true;
        $user_id = $this->request->getQueryParam('ids');
        $user_id = explode(',', $user_id);
        $plaformCodeArr = CustomerList::find()->select('platform_code')->where(['in', 'id', $user_id])->column();
        $plaformCodeArr = array_merge($plaformCodeArr, ['ALL']);
        $plaformCodeArr = array_unique($plaformCodeArr);
        $group = CustomerGroup::find()
            ->select('id, group_name, platform_code')
            ->andWhere(['in', 'platform_code', $plaformCodeArr])
            ->andWhere(['status' => 1])
            ->asArray()
            ->all();
        if (!empty($group)) {
            $tmp = [];
            foreach ($group as $grp) {
                $tmp[$grp['platform_code']][] = $grp;
            }
            $group = $tmp;
        }

        $groupDetail = CustomerGroupDetail::find()
            ->select('group_id')
            ->where(['in', 'buyer_id', $user_id])
            ->asArray()
            ->column();

        if(Yii::$app->request->isPost) {
            $data = Yii::$app->request->post('grp');
            if (empty($data)) {
                if (CustomerGroupDetail::deleteAll(['in', 'buyer_id', $user_id]) !== false) {
                    foreach($user_id as $id){
                        $obj = new CustomerOperation();
                        $obj->action = '添加客户分组';
                        $obj->mark = '删除所有分组';
                        $obj->create_by = Yii::$app->user->identity->login_name;
                        $obj->create_time = date('Y-m-d H:i:s');
                        $obj->buyer_id = $id;
                        $obj->save();
                    }
                    $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/customer/customer/list') . '");';
                    $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
                }
            } else {
                if ($data) {
                    $arr = [];
                    foreach($user_id as $id){
                        $group_id = CustomerGroupDetail::find()
                            ->select('group_id')
                            ->where(['buyer_id'=>$id])
                            ->asArray()
                            ->column();
                        $group_old_name = CustomerGroup::find()
                            ->select('group_name')
                            ->where(['in','id',$group_id])
                            ->asArray()
                            ->column();
                        $group_old_name = implode(',',$group_old_name);
                        $arr[$id] = $group_old_name;
                    }
                    $result = CustomerGroupDetail::deleteAll(['in', 'buyer_id', $user_id]);
                    if ($result !== false) {
                        foreach ($user_id as $item) {
                            foreach ($data as $k => $plat) {
                                foreach ($plat as $value) {
                                    $model = new CustomerGroupDetail();
                                    $model->group_id = $value;
                                    $model->buyer_id = $item;
                                    $model->platform_code = $k;
                                    $model->save(false);
                                }
                            }
                        }
                        foreach($user_id as $id){
                            $group_id = CustomerGroupDetail::find()
                                ->select('group_id')
                                ->where(['buyer_id'=>$id])
                                ->asArray()
                                ->column();
                            $group_name = CustomerGroup::find()
                                ->select('group_name')
                                ->where(['in','id',$group_id])
                                ->asArray()
                                ->column();
                            $group_name = implode(',',$group_name);
                            if(array_key_exists($id,$arr)){
                                $obj = new CustomerOperation();
                                $obj->action = '添加客户分组';
                                $obj->mark =  $arr[$id].'->'.$group_name;
                                $obj->create_by = Yii::$app->user->identity->login_name;
                                $obj->create_time = date('Y-m-d H:i:s');
                                $obj->buyer_id = $id;
                                $obj->save();
                            }
                        }
                        $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/customer/customer/list') . '");';
                        $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
                    }else{
                        $this->_showMessage(Yii::t('system', 'Operate Failed'), false, Yii::$app->request->getUrl());
                    }

                }
            }

        }

        return $this->render('listgtoup', [
            'user_id' => $user_id,
            'group' => $group,
            'groupDetail' => $groupDetail,
        ]);
    }

    /**
     *客户列表添加标签
     */
    public function actionListtags()
    {


        $this->isPopup = true;
        $user_id = $this->request->getQueryParam('ids');
        $user_id = explode(',', $user_id);

        $plaformCodeArr = CustomerList::find()->select('platform_code')->where(['in', 'id', $user_id])->column();
        $plaformCodeArr = array_merge($plaformCodeArr, ['ALL']);
        $plaformCodeArr = array_unique($plaformCodeArr);

        $tags = CustomerTags::find()
            ->select('id, tag_name, platform_code')
            ->andWhere(['in', 'platform_code', $plaformCodeArr])
            ->andWhere(['status' => 1])
            ->asArray()
            ->all();

        if (!empty($tags)) {
            $tmp = [];
            foreach ($tags as $tag) {
                $tmp[$tag['platform_code']][] = $tag;
            }
            $tags = $tmp;
        }

        $tagsDetail = CustomerTagsDetail::find()
            ->select('tags_id')
            ->where(['in', 'buyer_id', $user_id])
            ->asArray()
            ->column();

        if(Yii::$app->request->isPost) {
            $data = Yii::$app->request->post('tag');
            if (empty($data)) {
                if (CustomerTagsDetail::deleteAll(['in', 'buyer_id', $user_id]) !== false) {
                    foreach($user_id as $id){
                        $obj = new CustomerOperation();
                        $obj->action = '添加客户标签';
                        $obj->mark = '删除所有标签';
                        $obj->create_by = Yii::$app->user->identity->login_name;
                        $obj->create_time = date('Y-m-d H:i:s');
                        $obj->buyer_id = $id;
                        $obj->save();
                    }
                    $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/customer/customer/list') . '");';
                    $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
                }
            } else {
                if ($data) {
                    $arr = [];
                    foreach($user_id as $id){
                        $tags_id = CustomerTagsDetail::find()
                            ->select('tags_id')
                            ->where(['buyer_id'=>$id])
                            ->asArray()
                            ->column();
                        $tags_old_name = CustomerTags::find()
                            ->select('tag_name')
                            ->where(['in','id',$tags_id])
                            ->asArray()
                            ->column();
                        $tags_old_name = implode(',',$tags_old_name);
                        $arr[$id] = $tags_old_name;
                    }
                    $result = CustomerTagsDetail::deleteAll(['in', 'buyer_id', $user_id]);
                    if ($result !== false) {
                        foreach ($user_id as $item) {
                            foreach ($data as $k => $plat) {
                                foreach ($plat as $value) {
                                    $model = new CustomerTagsDetail();
                                    $model->tags_id = $value;
                                    $model->buyer_id = $item;
                                    $model->platform_code = $k;
                                    $model->save(false);
                                }
                            }
                        }
                        foreach($user_id as $id){
                            $tags_id = CustomerTagsDetail::find()
                                ->select('tags_id')
                                ->where(['buyer_id'=>$id])
                                ->asArray()
                                ->column();
                            $tags_name = CustomerTags::find()
                                ->select('tag_name')
                                ->where(['in','id',$tags_id])
                                ->asArray()
                                ->column();
                            $tags_name = implode(',',$tags_name);
                            if(array_key_exists($id,$arr)){
                                $obj = new CustomerOperation();
                                $obj->action = '添加客户标签';
                                $obj->mark =  $arr[$id].'->'.$tags_name;
                                $obj->create_by = Yii::$app->user->identity->login_name;
                                $obj->create_time = date('Y-m-d H:i:s');
                                $obj->buyer_id = $id;
                                $obj->save();
                            }

                        }
                        $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/customer/customer/list') . '");';
                        $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
                    }else{
                        $this->_showMessage(Yii::t('system', 'Operate Failed'), false, Yii::$app->request->getUrl());
                    }

                }
            }

        }
        return $this->render('listtags', [
            'user_id' => $user_id,
            'tags' => $tags,
            'tagsDetail' => $tagsDetail,
        ]);
    }


    /**
     * 添加标签
     */
    public function actionAdd()
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();

            if(empty($data['rule_type'])){
                $this->_showMessage('标签规则不能为空', false, Yii::$app->request->getUrl());
            }

            if(count($data['rule_type']) != count(array_unique($data['rule_type']))){
                $this->_showMessage('标签规则不能从复', false, Yii::$app->request->getUrl());
            }
            //装载数据
            $model = new CustomerTags();
            $model->load($data, '');
            if (!$model->validate()) {
                $this->_showMessage(current(current($model->getErrors())), false, Yii::$app->request->getUrl());
            }

            if (!empty($data['rule_value']) || !empty($data['end_value'])) {
                if (is_array($data['rule_value'])) {
                    foreach ($data['rule_value'] as $k => $value) {
                        if (empty($value) && empty($data['end_value'][$k])) {
                            $this->_showMessage('条件的值不能都为空', false, Yii::$app->request->getUrl());
                        }
                    }
                }
            }

            $model->create_by = Yii::$app->user->identity->login_name;
            $model->create_time = date('Y-m-d H:i:s');
            $model->modify_by = Yii::$app->user->identity->login_name;
            $model->modify_time = date('Y-m-d H:i:s');

            //保存数据
            if (!$model->save()) {
                $this->_showMessage(Yii::t('system', 'Operate Failed'), false, Yii::$app->request->getUrl());
            } else {
                if (!empty($data['rule_type']) && (!empty($data['rule_value']) || !empty($data['end_value']))) {
                    foreach ($data['rule_type'] as $key => $type) {
                        $rule = new CustomerTagsRule();
                        $rule->tags_id = $model->id;
                        $rule->type = $type;
                        $rule->value = array_key_exists($key, $data['rule_value']) ? $data['rule_value'][$key] : '';
                        $rule->value1 = array_key_exists($key, $data['end_value']) ? $data['end_value'][$key] : '';
                        $rule->status = 1;
                        $rule->save();
                    }

                }

                $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/customer/customer/tags') . '");';
                $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
            }

        } else {
            $this->isPopup = true;
            //获取平台列表
            $all = [Platform::PLATFORM_CODE_ALL => '全平台'];
            $platformList = array_merge($all, Platform::getPlatformAsArray());
            //规则类型列表
            $ruleTypeList = CustomerTagsRule::getRuleTypeList();

            return $this->render('add', [
                'platformList' => $platformList,
                'ruleTypeList' => $ruleTypeList,
            ]);
        }
    }


    /**
     * 批量删除标签
     */
    public function actionDeletetags()
    {
        $ids = Yii::$app->request->post('ids');

        if (empty($ids) || !is_array($ids)) {
            $this->_showMessage('请选择删除项', false);
        }

        if (CustomerTags::deleteAll(['in', 'id', $ids])) {
            foreach ($ids as $id) {
                CustomerTagsRule::deleteAll(['tags_id' => $id]);
            }

            $extraJs = 'top.refreshTable("' . Url::toRoute('/customer/customer/tags') . '");';
            $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
        } else {
            $this->_showMessage(Yii::t('system', 'Operate Failed'), false);
        }
    }

    /**
     *上传文件xls
     */
    public function actionUploadimage()
    {
        if (\Yii::$app->request->isPost)
        {
            $uploadFile = UploadedFile::getInstanceByName('upload_file');

            if (empty($uploadFile)) {
                die(json_encode([
                    'status' => 'error',
                    'info' => '上传失败',
                ]));
            }
            $filePath = self::XLS_UPLOAD_PATH . date('Ymd') . '/';
            if (!file_exists($filePath)) {
                @mkdir($filePath, 0777, true);
                @chmod($filePath, 0777);
            }
            $fileName = md5($uploadFile->baseName) . '.' . $uploadFile->extension;
            $file = $filePath . $fileName;
            if ($uploadFile->saveAs($file)) {
                die(json_encode([
                    'status' => 'success',
                    'info' => '上传成功',
                    'url' => $fileName,
                ]));
            } else {
                die(json_encode([
                    'status' => 'error',
                    'info' => '上传失败',
                ]));
            }
        }
    }
    /**
     *删除文件xls
     */
    public function actionDeleteimage()
    {
        $fileName = trim($this->request->post('url'));
        $filePath = self::XLS_UPLOAD_PATH . date('Ymd') . '/';
        if (!file_exists($filePath)) {
            @mkdir($filePath, 0777, true);
            @chmod($filePath, 0777);
        }
        $url = $filePath . $fileName;
        $host = $this->request->hostInfo;
        if($url === false)
        {
            $response = ['status'=>'error','info'=>'参数错误。'];
        }
        else
        {
            $url = str_replace($host.'/','',$url);
            if(file_exists($url))
            {
                unlink($url);
                $response = ['status'=>'success'];
            }
            else
            {
                $response = ['status'=>'error','info'=>'文件不存在。'];
            }
        }
        echo json_encode($response);
        \Yii::$app->end();
    }
    /**
     * 删除标签
     */
    public function actionDelete()
    {
        $id = Yii::$app->request->get('id');

        if (empty($id)) {
            $this->_showMessage('请选择删除项', false);
        }
        $info = CustomerTags::findOne($id);
        if (empty($info)) {
            $this->_showMessage('没有找到该删除项', false);
        }
        if ($info->delete()) {
            CustomerTagsRule::deleteAll(['tags_id' => $id]);

            $extraJs = 'top.refreshTable("' . Url::toRoute('/customer/customer/tags') . '");';
            $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
        } else {
            $this->_showMessage(Yii::t('system', 'Operate Failed'), false);
        }
    }
    /**
     * 导入文件
     */
    public function actionComeexcel()
    {
        if(\Yii::$app->request->isPost){

            set_time_limit(0);
            $file_name = $this->request->post('url');

            $filePath = self::XLS_UPLOAD_PATH . date('Ymd') . '/';
            if (!file_exists($filePath)) {
                @mkdir($filePath, 0777, true);
                @chmod($filePath, 0777);
            }
            $file = $filePath . '/' .$file_name;
            $fileType   = \PHPExcel_IOFactory::identify($file);
            $excelReader  = \PHPExcel_IOFactory::createReader($fileType);
            $phpexcel    = $excelReader->load($file)->getSheet(0);
            $total_line = $phpexcel->getHighestRow();            //多少行
            $total_column = $phpexcel->getHighestColumn();       //多少列
            for($row = 2; $row <= $total_line; $row++) {
                $oneUser = array();
                for ($column = 'A'; $column <= $total_column; $column++) {
                    $oneUser[] = trim($phpexcel->getCell($column . $row)->getValue());
                }
           // var_dump($oneUser);die;//获取到的每一行数据
                //一行行的插入数据库操作
                $model = CustomerList::find()->where(['platform_code' => $oneUser[0], 'buyer_id' => $oneUser[1]])->one();

                if(empty($model)) {
                    $model = new CustomerList();
                }

                $model->platform_code = $oneUser[0];
                $model->buyer_id  = $oneUser[1];
                $model->buyer_email  = $oneUser[2];
                $model->buyer_name  = $oneUser[3];
                $model->account_name  = $oneUser[4];
                $model->pay_email  = $oneUser[5];
                $model->phone  = $oneUser[6];
                $model->wechat  = $oneUser[7];
                $model->skype  = $oneUser[8];
                $model->whatsapp  = $oneUser[9];
                $model->trademanager  = $oneUser[10];
                $model->type  = 1;
                $model->create_by = Yii::$app->user->identity->login_name;
                $model->create_time = date('Y-m-d H:i:s');
                $model->modify_by = Yii::$app->user->identity->login_name;
                $model->modify_time = date('Y-m-d H:i:s');
                if ($model->save(false)) {
                    $ok = 1;
                }
        }
        if($ok == 1){
            $reason = [
                'status' => 'success',
                'url'=> Url::toRoute('/customer/customer/list'),
                'info'=> '导入成功',
            ];
        }else{
            $reason = [
                'status' => 'error',
                'info'=> '导入失败',
            ];
        }
        return json_encode($reason);
        }

        $this->isPopup = true;
        return $this->render('comeexcel', [
        ]);
    }
    /**
     * 批量联系
     */
    public function actionContacts()
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();
            $id = explode(',',$data['id']);
            if (empty($data['content'])) {
                $this->_showMessage(Yii::t('system', '回复内容不能为空'), false, Url::toRoute(['/customer/customer/contacts']));
            }
            if (empty($data['inbox']) && empty($data['email'])) {
                $this->_showMessage(Yii::t('system', '发送方式必须选'), false, Url::toRoute(['/customer/customer/contacts']));
            }
            //勾选邮件
            if (isset($data['email']) && isset($data['inbox'])) {
                $this->_showMessage(Yii::t('system', '请选择一种方式发送'), false, Url::toRoute(['/customer/customer/contacts']));
            }
            if (isset($data['email'])) {
                if (empty($data['platform_code']) || empty($data['to_email']) || empty($data['buyer_email'])) {
                    $this->_showMessage(Yii::t('system', '数据不全'), false, Url::toRoute(['/customer/customer/contacts']));
                }
                if (empty($data['email_title'])) {
                    $this->_showMessage(Yii::t('system', '邮件主题不能为空'), false, Url::toRoute(['/customer/customer/contacts']));
                }


                $to_email = explode(',', $data['to_email']);
                $buyer_email = explode(',', $data['buyer_email']);

                if (count($to_email) != count($buyer_email)) {
                    $this->_showMessage(Yii::t('system', '发件箱收件箱必须一一对应'), false, Url::toRoute(['/customer/customer/contacts']));
                }
                foreach ($to_email as $k => $email) {
                    $returnArr = ['bool' => 1, 'msg' => '发送成功!'];
                    $sendEmal = trim($to_email[$k]);
                    $recipientEmail = $buyer_email[$k];
                    $title = $data['email_title'];
                    $content = $data['content'];
                    $res = Mail::instance($sendEmal)
                        ->setTo($recipientEmail)
                        ->setSubject($title)
                        ->seHtmlBody($content)
                        ->setFrom($sendEmal)
                        ->sendmail();
                    if (!$res) {
                        $this->_showMessage(Yii::t('system', '回复失败'), false, Url::toRoute(['/customer/customer/contacts']));
                    }

                    $extraJs = 'top.refreshTable("' . Url::toRoute('/customer/customer/list') . '");';
                    $this->_showMessage(Yii::t('system', '回复成功'), true, null, false, null, $extraJs);
                }
            }
            if(isset($data['inbox'])){
                if($data['platform_code'] == Platform::PLATFORM_CODE_EB){
                    $custo = Customerlist::find()->where(['in','id', $id])->asArray()->all();
                    foreach($custo as $k=>$v){
                        $account[]=$v['account_id'];
                    }
                    $ebay_info = EbayInbox::find()->where(['in','account_id',$account])->asArray()->all();
                    $account_name = explode(',',$data['account_name']);
                    $buyer_id = explode(',',$data['buyer_id']);
                    $reply = new EbayReply();
                    $flag = true;
                    $errorInfo = '';
                    if($flag) {
                        foreach ($ebay_info as $k => $item){
                            $transaction = EbayReply::getDb()->beginTransaction();
                            $reply_title = '';
                            if (empty(trim($reply_title)))
                                $reply_title = $data['sender'] . '针对物品编号' . $data['item_id'] . '提出问题';

                            $reply->item_id = $item->item_id;
                            $reply->reply_content = $data['reply_content'];
                            $reply->reply_content_en = $data['reply_content'];
                            $reply->question_type = 2;
                            $reply->sender = $account_name[$k];
                            $reply->account_id = $item->account_id;
                            $reply->recipient_id = $buyer_id[$k];
                            $reply->reply_title = $reply_title;
                            $reply->platform_order_id = '';
                            $reply->is_draft = 0;
                            try {
                                $flag = $reply->save();
                                if (!$flag)
                                    $errorInfo .= VHelper::getModelErrors($reply);
                            } catch (Exception $e) {
                                $flag = false;
                                $errorInfo .= $e->getMessage();
                            }
                        }
                        if($flag && !$reply->is_draft) {
                            $mailOutBox = new MailOutbox();
                            $mailOutBox->platform_code = Platform::PLATFORM_CODE_EB;
                            $mailOutBox->reply_id = $reply->id;
                            $mailOutBox->account_id = $reply->account_id;
                            $mailOutBox->subject = $reply->reply_title;
                            $mailOutBox->content = $reply->reply_content;
                            $mailOutBox->send_status = MailOutbox::SEND_STATUS_WAITTING;
                            $sendParams = ['account_id' => $reply->account_id, 'ItemID' => $reply->item_id, 'QuestionType' => EbayReply::$questionTypeMap[$reply->question_type], 'RecipientID' => $reply->recipient_id];
                            $mailOutBox->send_params = json_encode($sendParams);
                            try {
                                $flag = $mailOutBox->save();
                                if (!$flag)
                                    $errorInfo .= VHelper::getModelErrors($mailOutBox);
                            } catch (Exception $e) {
                                $flag = false;
                                $errorInfo .= $e->getMessage();
                            }
                        }

                        if($flag)
                        {
                            $transaction->commit();

                        }
                        else
                        {
                            $transaction->rollBack();

                        }
                        }

                    if($errorInfo){
                        $extraJs = 'top.refreshTable("' . Url::toRoute('/customer/customer/list') . '");';
                        $this->_showMessage(Yii::t('system', '回复成功'), true, null, false, null, $extraJs);
                    }else{
                        $this->_showMessage('回复失败！', false);
                    }

                }else{
                    $this->_showMessage(Yii::t('system', '回复失败'), false, Url::toRoute(['/customer/customer/contacts']));
                }
            }

        }
        $this->isPopup = true;
        $id = $this->request->getQueryParam('ids');
        if (empty($id)) {
            $this->_showMessage('ID不能为空', false);
        }
        $ids = explode(',',$id);
        $info = Customerlist::find()->where(['in','id',$ids])->asArray()->all();
        if (empty($info)) {
            $this->_showMessage('没有找到客户信息', false);
        }
        $platform_codes =[];
        foreach($info as $k=>$value){
            $platform_codes[$value['platform_code']][] = $value;
        }
        if(count($platform_codes) > 1 ){
            $this->_showMessage('客户平台必须一致', false);
        }
        $account = [];
        foreach ($info as $k => $item){
            if(empty($item['account_id'])){
                $this->_showMessage('所选账号不能为空', false);
            }
            $account[$item['buyer_id']] = $item['account_id'];
            $buyer_id[] = $item['buyer_id'];
            $buyer_email[] =  $item['buyer_email'];
        }

        $buyer_id = implode(',',$buyer_id);
        $buyer_email = implode(',',$buyer_email);
        $account_name= [];
        foreach ($info as $k => $item){
            $account_name[$item['account_id']]= $item['account_name'];
        }
        $account_name = implode(',',$account_name);

        //所有平台
        $platform = Platform::getPlatformAsArray();
        //订单信息
        $a = [];
        foreach ($account as $k => &$item) {
            if($info[0]['platform_code'] == Platform::PLATFORM_CODE_AMAZON) {
                $site_code = Account::findSiteCode($account[$k], $info[0]['platform_code']);
                //获取站点邮箱
                if ($site_code == 'es') {
                    $site_code = 'sp';
                }
                $a[] = Account::find()->select('email')->where(['old_account_id' => $account[$k], 'site_code' => $site_code, 'status' => 1])->asArray()->one()['email'];
            }else{

                $a[] = Account::find()->select('email')->where(['old_account_id' => $account[$k], 'status' => 1])->asArray()->one()['email'];
            }

        }
        $email = implode(',',$a);
        return $this->render('contacts', [
            'info' => $info,
            'platform'=> $platform,
            'email' => $account,
            'account_name' => $account_name,
            'buyer_id' => $buyer_id,
            'email' => $email,
            'buyer_email' => $buyer_email,
            'id' => $id,
        ]);


    }
    /**
     *联系客户
     */
    public function actionContact()
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();

          if(empty($data['content'])){
              $this->_showMessage(Yii::t('system', '回复内容不能为空'), false, Url::toRoute(['/customer/customer/contact', 'id' => $data['id']]));
          }
          if(empty($data['inbox']) && empty($data['email'])){
              $this->_showMessage(Yii::t('system', '发送方式必须选'), false, Url::toRoute(['/customer/customer/contact', 'id' => $data['id']]));
          }
          //勾选邮件
            if(isset($data['email']) && isset($data['inbox'])){
                $this->_showMessage(Yii::t('system', '请选择一种方式发送'), false, Url::toRoute(['/customer/customer/contact', 'id' => $data['id']]));
            }
          if(isset($data['email'])){
              if(empty($data['platform_code']) || empty($data['to_email']) || empty($data['buyer_email'])){
                  $this->_showMessage(Yii::t('system', '数据不全'), false, Url::toRoute(['/customer/customer/contact', 'id' => $data['id']]));
              }
              if(empty($data['email_title'])){
                  $this->_showMessage(Yii::t('system', '邮件主题不能为空'), false, Url::toRoute(['/customer/customer/contact', 'id' => $data['id']]));
              }

              $returnArr      = ['bool' => 1, 'msg' => '发送成功!'];
              $sendEmal       = trim($data['to_email']);
              $recipientEmail = $data['buyer_email'];
              $title          = $data['email_title'];
              $content        = $data['content'];
              $res            = Mail::instance($sendEmal)
                  ->setTo($recipientEmail)
                  ->setSubject($title)
                  ->seHtmlBody($content)
                  ->setFrom($sendEmal)
                  ->sendmail();
              if (!$res) {
                  $this->_showMessage(Yii::t('system', '回复失败'), false, Url::toRoute(['/customer/customer/contact', 'id' => $data['id']]));
              }
              $obj = new CustomerOperation();
              $obj->action = '发送邮件';
              $obj->mark = $title."\n\n".$content;
              $obj->create_by = Yii::$app->user->identity->login_name;
              $obj->create_time = date('Y-m-d H:i:s');
              $obj->buyer_id = $data['id'];
              $obj->save();
              $extraJs = 'top.refreshTable("' . Url::toRoute('/customer/customer/list') . '");';
              $this->_showMessage(Yii::t('system', '回复成功'), true, null, false, null, $extraJs);
          }
          //站内信
          if(isset($data['inbox'])){

              if($data['platform_code'] == Platform::PLATFORM_CODE_WALMART || $data['platform_code'] == Platform::PLATFORM_CODE_AMAZON){
                  $this->_showMessage(Yii::t('system', '此平台不支持'), false, Url::toRoute(['/customer/customer/contact', 'id' => $data['id']]));
              }

              $custo = Customerlist::find()->where(['id' => $data['id']])->asArray()->one();
              if($data['platform_code'] == Platform::PLATFORM_CODE_WISH){
                   $wish_info = WishInbox::find()->select('*')->where(['user_id' => $data['buyer_id']])->orderBy(['id' => SORT_DESC])->asArray()->one();
                   $info =[
                       'account_id' => $wish_info['account_id'],//店铺账号ID
                       'platform_id' => $wish_info['platform_id'],
                       'content' => $data['content'],
                       'content_en' => $data['content'],
                       'image_url_merchant' => '',
                   ];
                  $reply = new WishReply();
                  $Reply_id = $reply->getAdd($info);
                  $Reply_data = WishReply::findOne(['id'=>$Reply_id]);
                  if(!empty($Reply_data)){
                      $jsonData = array(
                          'message' => '回复成功!',
                          'status' => 1,
                          'data'=>$Reply_data
                      );
                      $obj = new CustomerOperation();
                      $obj->action = '发送站内信';
                      $obj->mark = $info['content'];
                      $obj->create_by = Yii::$app->user->identity->login_name;
                      $obj->create_time = date('Y-m-d H:i:s');
                      $obj->buyer_id = $data['id'];
                      $obj->save();
                      $extraJs = 'top.refreshTable("' . Url::toRoute('/customer/customer/list') . '");';
                      $this->_showMessage(Yii::t('system', '回复成功'), true, null, false, null, $extraJs);
                  }else{
                      $this->_showMessage(Yii::t('system', '回复失败'), false, Url::toRoute(['/customer/customer/contact', 'id' => $data['id']]));
                  }

              }elseif($data['platform_code'] == Platform::PLATFORM_CODE_ALI) {
                  $ali_info = AliexpressInbox::findOne(['account_id' => $custo['account_id']]);
                  $reply = new AliexpressReply();
                  $info = [
                      'account_id' => $ali_info['account_id'],
                      'channel_id' => $ali_info['channel_id'],
                      'content' => $data['content'],
                      'content_en' => $data['content'],
                      'message_type' => '',
                      'type_id' => '',
                  ];
                  $flag = $reply->getAdd($data);
                  if ($flag) {
                      $obj = new CustomerOperation();
                      $obj->action = '发送站内信';
                      $obj->mark = $info['content'];
                      $obj->create_by = Yii::$app->user->identity->login_name;
                      $obj->create_time = date('Y-m-d H:i:s');
                      $obj->buyer_id = $data['id'];
                      $obj->save();
                      $extraJs = 'top.refreshTable("' . Url::toRoute('/customer/customer/list') . '");';
                      $this->_showMessage(Yii::t('system', '回复成功'), true, null, false, null, $extraJs);
                  } else{
                      $this->_showMessage('回复失败！', false);
                  }
              }elseif($data['platform_code'] == Platform::PLATFORM_CODE_EB){
                  $ebay_info = EbayInbox::findOne(['account_id' => $custo['account_id']]);
                  $reply = new EbayReply();
                  $flag = true;
                  if($flag) {
                      $transaction = EbayReply::getDb()->beginTransaction();

                      $reply_title = '';
                      if (empty(trim($reply_title)))
                          $reply_title = $data['sender'] . '针对物品编号' . $data['item_id'] . '提出问题';

                      $reply->item_id = $ebay_info->item_id;
                      $reply->reply_content = $data['reply_content'];
                      $reply->reply_content_en = $data['reply_content'];
                      $reply->question_type = 2;
                      $reply->sender = $data['account_name'];
                      $reply->account_id = $ebay_info->account_id;
                      $reply->recipient_id = $data['buyer_id'];
                      $reply->reply_title = $reply_title;
                      $reply->platform_order_id = '';
                      $reply->is_draft = 0;
                      try {
                          $flag = $reply->save();
                          if (!$flag)
                              $errorInfo = VHelper::getModelErrors($flag);
                      } catch (Exception $e) {
                          $flag = false;
                          $errorInfo = $e->getMessage();
                      }
                  }
                      if($flag && !$reply->is_draft) {
                          $mailOutBox = new MailOutbox();
                          $mailOutBox->platform_code = Platform::PLATFORM_CODE_EB;
                          $mailOutBox->reply_id = $reply->id;
                          $mailOutBox->account_id = $reply->account_id;
                          $mailOutBox->subject = $reply->reply_title;
                          $mailOutBox->content = $reply->reply_content;
                          $mailOutBox->send_status = MailOutbox::SEND_STATUS_WAITTING;
                          $sendParams = ['account_id' => $reply->account_id, 'ItemID' => $reply->item_id, 'QuestionType' => EbayReply::$questionTypeMap[$reply->question_type], 'RecipientID' => $reply->recipient_id];
                          $mailOutBox->send_params = json_encode($sendParams);
                          try {
                              $flag = $mailOutBox->save();
                              if (!$flag)
                                  $errorInfo = VHelper::getModelErrors($mailOutBox);
                          } catch (Exception $e) {
                              $flag = false;
                              $errorInfo = $e->getMessage();
                          }
                      }

                      if($flag)
                      {
                          $transaction->commit();
                          $response = ['status'=>'success','info'=>'成功！'];

                      }
                      else
                      {
                          if(isset($transaction))
                              $transaction->rollBack();
                          $response = ['status'=>'error','info'=>$errorInfo];
                      }
                     if($response['status'] == 'success'){
                         $obj = new CustomerOperation();
                         $obj->action = '发送站内信';
                         $obj->mark = $data['reply_content'];
                         $obj->create_by = Yii::$app->user->identity->login_name;
                         $obj->create_time = date('Y-m-d H:i:s');
                         $obj->buyer_id = $data['id'];
                         $obj->save();
                         $extraJs = 'top.refreshTable("' . Url::toRoute('/customer/customer/list') . '");';
                         $this->_showMessage(Yii::t('system', '回复成功'), true, null, false, null, $extraJs);
                     }else{
                         $this->_showMessage('回复失败！', false);
                     }

              }else{
                  $this->_showMessage(Yii::t('system', '回复失败'), false, Url::toRoute(['/customer/customer/contact', 'id' => $data['id']]));
              }

          }

        } else {
            $id = Yii::$app->request->get('id');
            if (empty($id)) {
                $this->_showMessage('ID不能为空', false);
            }

            $info = Customerlist::find()->where(['id' => $id])->asArray()->one();
            if (empty($info)) {
                $this->_showMessage('没有找到客户信息', false);
            }
            //所有平台
            $platform = Platform::getPlatformAsArray();
            //订单信息
            if($info['platform_code'] == Platform::PLATFORM_CODE_AMAZON){
                $site_code = Account::findSiteCode($info['account_id'], $info['platform_code']);
                //获取站点邮箱
                if ($site_code == 'es') {
                    $site_code = 'sp';
                }
                $model = Account::find()->select('email')->where(['old_account_id' => $info['account_id'], 'site_code' => $site_code, 'status' => 1])->asArray()->one();
            }else{
                $model = Account::find()->select('email')->where(['old_account_id' => $info['account_id'], 'status' => 1])->asArray()->one();
            }

            $email = $model['email'];
            //平台下所有账号
            $this->isPopup = true;

            return $this->render('contact', [
                'info' => $info,
                'platform'=> $platform,
                'email' => $email,
            ]);
        }
    }


    /**
     *编辑客户信息
     */
    public function actionListeditor()
    {

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();

            $model = CustomerList::findOne($data['id']);
            $list = CustomerList::findOne($data['id']);
            if (empty($model)) {
                $this->_showMessage('没有找到客户信息', false);
            }
            //装载数据
            $model->load($data, '');
            if (!$model->validate()) {
                $this->_showMessage(current(current($model->getErrors())), false, Url::toRoute(['/customer/customer/listeditor', 'id' => $data['id']]));
            }

            $model->modify_by = Yii::$app->user->identity->login_name;
            $model->modify_time = date('Y-m-d H:i:s');

            //保存数据
            if (!$model->save()) {
                $this->_showMessage(Yii::t('system', 'Operate Failed'), false, Url::toRoute(['/customer/customer/listeditor', 'id' => $data['id']]));
            } else {
                //加入操作日志
                $str = '';
                if($model->platform_code !== $list->platform_code){
                    $list_plt = empty($list->platform_code) ? '空': $list->platform_code;
                    $model_plt = empty($model->platform_code) ? '空': $model->platform_code;
                    $str .= '平台：'.$list_plt.'->'.$model_plt.'<br />';
                }
                if($model->buyer_id !== $list->buyer_id){
                    $list_buy = empty($list->buyer_id) ? '空': $list->buyer_id;
                    $model_buy = empty($model->buyer_id) ? '空': $model->buyer_id;
                    $str .= '客户ID：'.$list_buy.'->'.$model_buy.'<br />';
                }
                if($model->buyer_email !== $list->buyer_email){
                    $list_ema = empty($list->buyer_email) ? '空': $list->buyer_email;
                    $model_ema = empty($model->buyer_email) ? '空': $model->buyer_email;
                    $str .= '客户邮箱：'.$list_ema.'->'.$model_ema.'<br />';
                }
                if($model->buyer_name !== $list->buyer_name){
                    $list_name = empty($list->buyer_name) ? '空': $list->buyer_name;
                    $model_name = empty($model->buyer_name) ? '空': $model->buyer_name;
                    $str .= '客户名称：'.$list_name.'->'.$model_name.'<br />';
                }
                if($model->account_name !== $list->account_name){
                    $list_acc = empty($list->account_name) ? '空': $list->account_name;
                    $model_acc = empty($model->account_name) ? '空': $model->account_name;
                    $str .= '店铺：'.$list_acc.'->'.$model_acc.'<br />';
                }
                if($model->pay_email !== $list->pay_email){
                    $list_pay = empty($list->pay_email) ? '空': $list->pay_email;
                    $model_pay = empty($model->pay_email) ? '空': $model->pay_email;
                    $str .= '付款邮箱：'.$list_pay.'->'.$model_pay.'<br />';
                }
                if($model->credit_rating != $list->credit_rating){
                    $list_cre = empty($list->credit_rating) ? '空': $list->credit_rating;
                    $model_cre = empty($model->credit_rating) ? '空': $model->credit_rating;
                    $str .= '信用评级：'.$list_cre.'->'.$model_cre.'<br />';
                }
                if($model->phone !== $list->phone){
                    $list_pho = empty($list->phone) ? '空': $list->phone;
                    $model_pho = empty($model->phone) ? '空': $model->phone;
                    $str .= '电话号码：'.$list_pho.'->'.$model_pho.'<br />';
                }
                if($model->wechat !== $list->wechat){
                    $list_wec = empty($list->wechat) ? '空': $list->wechat;
                    $model_wec = empty($model->wechat) ? '空': $model->wechat;
                    $str .= 'Wechat：'.$list_wec.'->'.$model_wec.'<br />';
                }
                if($model->skype !== $list->skype){
                    $list_sky = empty($list->skype) ? '空': $list->skype;
                    $model_sky = empty($model->skype) ? '空': $model->skype;
                    $str .= 'Skype：'.$list_sky.'->'.$model_sky.'<br />';
                }
                if($model->whatsapp !== $list->whatsapp){
                    $list_wha = empty($list->whatsapp) ? '空': $list->whatsapp;
                    $model_wha = empty($model->whatsapp) ? '空': $model->whatsapp;
                    $str .= 'Whatsapp：'.$list_wha.'->'.$model_wha.'<br />';
                }
                if($model->trademanager !== $list->trademanager){
                    $list_tra = empty($list->trademanager) ? '空': $list->trademanager;
                    $model_tra = empty($model->trademanager) ? '空': $model->trademanager;
                    $str .= 'Trademanager：'.$list_tra.'->'.$model_tra.'<br />';
                }
                if(!empty($str)){
                    $obj = new CustomerOperation();
                    $obj->action = '修改客户信息';
                    $obj->mark = '<br />'.$str;
                    $obj->create_by = Yii::$app->user->identity->login_name;
                    $obj->create_time = date('Y-m-d H:i:s');
                    $obj->buyer_id = $data['id'];
                    $obj->save();
                }
                $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/customer/customer/list') . '");';
                $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
            }

        }else{
            $id = Yii::$app->request->get('id');
            if (empty($id)) {
                $this->_showMessage('ID不能为空', false);
            }

            $info = Customerlist::find()->where(['id' => $id])->asArray()->one();
            if (empty($info)) {
                $this->_showMessage('没有找到客户信息', false);
            }
            //所有平台
            $platform = Platform::getPlatformAsArray();
            //平台下所有账号
            $account = Account::getIdNameKVList($info['platform_code']);
            $this->isPopup = true;
        }
            return $this->render('listeditor', [
                'info' => $info,
                'platform'=> $platform,
                'account'=> $account,
            ]);

    }

    /**搜索模板
     * @author alpha
     * @desc
     */
    public function actionSearchtemplate()
    {
        $name = Yii::$app->request->post('name');
        $platform_code = Yii::$app->request->getBodyParam('platform_code');
        $login_name = Yii::$app->user->identity->user_name;

        $query = MailTemplate::find()
            ->select('id, template_name')
            ->andWhere(['status' => MailTemplate::MAIL_TEMPLATE_STATUS_VALID])
            ->andWhere(['template_type' => MailTemplate::MAIL_TEMPLATE_TYPE_CUSTOMER])
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
                    ['create_by' => $login_name],
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
        // echo $query->createCommand()->getRawSql();die;

        $data = $query->asArray()->all();

        if (empty($data)) {
            $response = ['status' => 'error', 'message' => '未找到数据'];
        } else {
            $response = ['status' => 'success', 'content' => array_column($data, 'template_name', 'id')];
        }
        echo Json::encode($response);
    }

    public function actionGettemplate()
    {
        $id = Yii::$app->request->post('num');
        if (is_numeric($id) && $id > 0 && $id % 1 === 0) {
            $template = MailTemplate::find()->select('template_content')->where(['id' => $id, 'status' => MailTemplate::MAIL_TEMPLATE_STATUS_VALID, 'template_type' => MailTemplate::MAIL_TEMPLATE_TYPE_CUSTOMER])->one()->template_content;
            if (empty($template)) {
                $response['status'] = 'error';
                $response['message'] = '未找到模板';
            } else {
                $response['status'] = 'success';
                $response['content'] = $template;
            }
        } else {
            $response['status'] = 'error';
            $response['message'] = 'num格式错误';
        }
        echo Json::encode($response);
        Yii::$app->end();
    }
    /**
     * 编辑标签
     */
    public function actionEditor()
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();

            $model = CustomerTags::findOne($data['id']);
            if (empty($model)) {
                $this->_showMessage('没有找到标签信息', false);
            }
            //装载数据
            $model->load($data, '');
            if (!$model->validate()) {
                $this->_showMessage(current(current($model->getErrors())), false, Url::toRoute(['/customer/customer/editor', 'id' => $data['id']]));
            }

            if (!empty($data['rule_value']) || !empty($data['end_value'])) {
                if (is_array($data['rule_value'])) {
                    foreach ($data['rule_value'] as $k => $value) {
                        if (empty($value) && empty($data['end_value'][$k])) {
                            $this->_showMessage('条件的值不能都为空', false, Yii::$app->request->getUrl());
                        }
                    }
                }
            }

            $model->modify_by = Yii::$app->user->identity->login_name;
            $model->modify_time = date('Y-m-d H:i:s');

            //保存数据
            if (!$model->save()) {
                $this->_showMessage(Yii::t('system', 'Operate Failed'), false, Url::toRoute(['/customer/customer/editor', 'id' => $data['id']]));
            } else {
                if (!empty($data['rule_type']) && (!empty($data['rule_value']) || !empty($data['end_value']))) {
                    if (CustomerTagsRule::deleteAll(['tags_id' => $model->id])) {
                        foreach ($data['rule_type'] as $key => $type) {
                            $rule = new CustomerTagsRule();
                            $rule->tags_id = $model->id;
                            $rule->type = $type;
                            $rule->value = array_key_exists($key, $data['rule_value']) ? $data['rule_value'][$key] : '';
                            $rule->value1 = array_key_exists($key, $data['end_value']) ? $data['end_value'][$key] : '';
                            $rule->status = 1;
                            $rule->save();
                        }
                    }
                }

                $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/customer/customer/tags') . '");';
                $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
            }
        } else {
            $id = Yii::$app->request->get('id');
            if (empty($id)) {
                $this->_showMessage('ID不能为空', false);
            }

            $info = CustomerTags::find()->where(['id' => $id])->asArray()->one();
            if (empty($info)) {
                $this->_showMessage('没有找到标签信息', false);
            }

            $this->isPopup = true;
            //获取平台列表
            $all = [Platform::PLATFORM_CODE_ALL => '全平台'];
            $platformList = array_merge($all, Platform::getPlatformAsArray());
            //规则类型列表
            $ruleTypeList = CustomerTagsRule::getRuleTypeList();
            //规则列表
            $manageRuleList = CustomerTagsRule::getManageRuleList($info['id']);

            return $this->render('editor', [
                'info' => $info,
                'platformList' => $platformList,
                'ruleTypeList' => $ruleTypeList,
                'manageRuleList' => $manageRuleList,
            ]);
        }
    }

    /**
     * 添加分组
     */

    public function actionAddgroup()
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();

            //装载数据
            $model = new CustomerGroup();
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
                $this->_showMessage(Yii::t('system', 'Operate Failed'), false, Yii::$app->request->getUrl());
            } else {
                $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/customer/customer/group') . '");';
                $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
            }

        } else {
            $this->isPopup = true;
            //获取平台列表
            $all = [Platform::PLATFORM_CODE_ALL => '全平台'];
            $platformList = array_merge($all, Platform::getPlatformAsArray());

            return $this->render('addgroup', [
                'platformList' => $platformList,
            ]);
        }
    }

    /**
     * 编辑分组
     */

    public function actionEditorgroup()
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();

            $model = CustomerGroup::findOne($data['id']);
            if (empty($model)) {
                $this->_showMessage('没有找到分组信息', false);
            }
            //装载数据
            $model->load($data, '');
            if (!$model->validate()) {
                $this->_showMessage(current(current($model->getErrors())), false, Url::toRoute(['/customer/customer/editorgroup', 'id' => $data['id']]));
            }

            $model->modify_by = Yii::$app->user->identity->login_name;
            $model->modify_time = date('Y-m-d H:i:s');

            //保存数据
            if (!$model->save()) {
                $this->_showMessage(Yii::t('system', 'Operate Failed'), false, Yii::$app->request->getUrl());
            } else {
                $extraJs = 'top.layer.closeAll("iframe");top.refreshTable("' . Url::toRoute('/customer/customer/group') . '");';
                $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
            }

        } else {

            $id = Yii::$app->request->get('id');
            if (empty($id)) {
                $this->_showMessage('ID不能为空', false);
            }

            $info = CustomerGroup::find()->where(['id' => $id])->asArray()->one();
            if (empty($info)) {
                $this->_showMessage('没有找到分组信息', false);
            }

            $this->isPopup = true;
            //获取平台列表
            $all = [Platform::PLATFORM_CODE_ALL => '全平台'];
            $platformList = array_merge($all, Platform::getPlatformAsArray());

            return $this->render('editorgroup', [
                'platformList' => $platformList,
                'info' => $info,
            ]);
        }
    }

    /**
     * 删除分组
     */
    public function actionDeletegroup()
    {
        $id = Yii::$app->request->get('id');

        if (empty($id)) {
            $this->_showMessage('请选择删除项', false);
        }
        $info = CustomerGroup::findOne($id);
        if (empty($info)) {
            $this->_showMessage('没有找到该删除项', false);
        }
        if ($info->delete()) {
            $extraJs = 'top.refreshTable("' . Url::toRoute('/customer/customer/group') . '");';
            $this->_showMessage(Yii::t('system', 'Operate Successful'), true, null, false, null, $extraJs);
        } else {
            $this->_showMessage(Yii::t('system', 'Operate Failed'), false);
        }
    }

    /**
     * 导出excel
     */
    public function actionExport()
    {
        set_time_limit(0);
        ini_set('memory_limit', '256M');

        //获取get参数
        $get = YII::$app->request->get();
        //id数组
        $ids = !empty($get['ids']) ? $get['ids'] : [];
        //导出数据
        $data = [];

        if (is_array($ids) && !empty($ids)) {
            //取出选中的数据
            $data = CustomerList::find()
                ->alias('i')
                ->select('i.*')
                ->andWhere(['in', 'i.id', $ids])
                ->orderBy('i.create_time DESC')
                ->asArray()
                ->all();

        } else {
            //取出筛选的数据
            $query = CustomerList::find()
                ->alias('i')
                ->select('i.*')
                ->orderBy('i.create_time DESC');

            if(!empty($get['platform_code'])){
                $query->andWhere(['i.platform_code' => $get['platform_code']]);
            }
            if (!empty($get['order_id'])) {
                $query->andWhere(['like', 'i.order_id', $get['order_id']]);
            }

            if(isset($get['buyer_id_email']) && !empty($get['buyer_id_email'])){
                $query->andWhere([
                    'or',
                    ['i.buyer_id' => $get['buyer_id_email']],
                    ['like', 'i.buyer_email','%'. $get['buyer_id_email'] . '%', false],
                ]);
            }

            if(!empty($get['pay_email'])){
                $query->andWhere(['i.pay_email' => $get['pay_email']]);
            }

            //购买次数
            if (!empty($get['purchase_times_start']) && !empty($get['purchase_times_end'])) {
                $query->andWhere(['between', 'i.purchase_times', $get['purchase_times_start'],$get['purchase_times_end']]);

            } else if (!empty($get['purchase_times_start'])) {
                $query->andWhere(['>=', 'i.purchase_times', $get['purchase_times_start']]);

            } else if (!empty($get['purchase_times_end'])) {
                $query->andWhere(['<=', 'i.purchase_times', $get['purchase_times_end']]);

            }

            //成交金额
            if (!empty($get['turnover_start']) && !empty($get['turnover_end'])) {
                $query->andWhere(['between', 'i.turnover', $get['turnover_start'],$get['turnover_end']]);

            } else if (!empty($get['turnover_start'])) {
                $query->andWhere(['>=', 'i.turnover', $get['turnover_start']]);

            } else if (!empty($get['turnover_end'])) {
                $query->andWhere(['<=', 'i.turnover', $get['turnover_end']]);

            }

            //信用评级
            if (!empty($get['credit_rating_start']) && !empty($get['credit_rating_end'])) {
                $query->andWhere(['between', 'i.credit_rating', $get['credit_rating_start'],$get['credit_rating_end']]);

            } else if (!empty($get['credit_rating_start'])) {
                $query->andWhere(['>=', 'i.credit_rating', $get['credit_rating_start']]);

            } else if (!empty($get['credit_rating_end'])) {
                $query->andWhere(['<=', 'i.credit_rating', $get['credit_rating_end']]);

            }

            //创建日期
            if (!empty($get['start_time']) && !empty($get['end_time'])) {
                $query->andWhere(['between', 'i.create_time', $get['start_time'],$get['end_time']]);
            } else if (!empty($get['start_time'])) {
                $query->andWhere(['>=', 'i.create_time', $get['start_time']]);
            } else if (!empty($get['end_time'])) {
                $query->andWhere(['<=', 'i.create_time', $get['end_time']]);
            }

            //纠纷次数
            if (!empty($get['disputes_start']) && !empty($get['disputes_end'])) {
                $query->andWhere(['between', 'i.disputes_number', $get['disputes_start'],$get['disputes_end']]);

            } else if (!empty($get['disputes_start'])) {
                $query->andWhere(['>=', 'i.disputes_number', $get['disputes_start']]);

            } else if (!empty($get['disputes_end'])) {
                $query->andWhere(['<=', 'i.disputes_number', $get['disputes_end']]);

            }

            if(!empty($get['tags_id'])){
                $query ->innerJoin(['t2'=>CustomerTagsDetail::tableName()],'t2.buyer_id = i.id');
                $query->andWhere(['t2.tags_id' => $get['tags_id']]);
            }

            if(!empty($get['group_id'])){
                $query ->innerJoin(['t1'=>CustomerGroupDetail::tableName()],'t1.buyer_id = i.id');
                $query->andWhere(['t1.group_id' => $get['group_id']]);
            }
         //  echo $query->createCommand()->getRawSql();die;
            $data = $query->asArray()->all();
        }

        //标题数组
        $fieldArr = [
            '创建人/时间',
            '平台',
            '客户ID/邮箱',
            '客户名称',
            '添加类型',
            '店铺',
            '付款邮箱',
            '电话号码',
            '在线联系方式',
            '购买次数',
            '成交金额',
            '纠纷次数',
            '信用评级',
            '客户标签',
            '更新人',
            '更新时间',
        ];
        //导出数据数组
        $dataArr = [];

        if (!empty($data)) {

            foreach ($data as &$item) {

                $repliedStatus = '';
                switch ($item['type']) {
                    case 0:
                        $repliedStatus = '系统';
                        break;
                    case 1:
                        $repliedStatus = '手动';
                        break;
                }
                $tags = CustomerTagsDetail::find()->select('tags_id')->where(['buyer_id'=>$item['id']])->column();
                $arr = CustomerTags::find()->select('tag_name')->where(['in','id',$tags])->column();
                $item['tags_name'] = implode(',',$arr);


                $dataArr[] = [
                    $item['create_by'].'---'.$item['create_time'],
                    $item['platform_code'],
                    $item['buyer_id'].'---'.$item['buyer_email'],
                    $item['buyer_name'],
                    $repliedStatus,
                    $item['account_name'],
                    $item['pay_email'],
                    $item['phone'],
                    $item['other_contacts'],
                    $item['purchase_times'],
                    $item['turnover'],
                    $item['disputes_number'],
                    $item['credit_rating'],
                    $item['tags_name'],
                    $item['modify_by'],
                    $item['modify_time'],
                ];
            }
        }

        VHelper::exportExcel($fieldArr, $dataArr, 'customer_' . date('Y-m-d'));
    }

    /**
     * 操作日志
     */
    public function actionOperation()
    {
        $id = Yii::$app->request->get('id');
        $list = CustomerOperation::find()->select('*')->where(['buyer_id' => $id])->orderBy(['id' => SORT_DESC])->asArray()->all();
        $this->isPopup = true;

        return $this->render('operation', [
            'list' => $list,
        ]);
    }

    /**
     * 跟进状态
     */
    public function actionFollowstatus() {

        $buyer_id = Yii::$app->request->post('buyer_id', '');
        $type_id  = Yii::$app->request->post('type_id', '');
        $step_id  = Yii::$app->request->post('step_id', '');
        $mark     = Yii::$app->request->post('text', '');

        $msg = "操作成功";
        $action = ($type_id==1) ? '更新跟进状态' : '其他';
        $stepList = BasicConfig::getParentList(35); //跟进状态
        $follow_status = $stepList[$step_id];
        if (!$buyer_id) {
            die(json_encode([
                'status' => 0,
                'msg' => '请选择相应客户！',
            ]));
        }

        if (!$step_id) {
            die(json_encode([
                'status' => 0,
                'msg' => '跟进处理状态不能为空！',
            ]));
        }
        $model = CustomerOperation::find()->where(['buyer_id' => $buyer_id, 'action' => '更新跟进状态'])->one();
        if (!$model) {
            $logData = [
                'buyer_id' => $buyer_id,
                'action' => $action,
                'follow_status' => $follow_status,
                'mark' => $mark,
                'create_time' => date("Y-m-d H:i:s"),
                'create_by' => Yii::$app->user->identity->user_name
            ];
            $res = CustomerOperation::addData($logData);
            if (!$res) {
                die(json_encode([
                    'status' => 0,
                    'msg' => '保存操作日志失败',
                ]));
            }    
        } else {
            $model->follow_status = $follow_status;
            $model->mark = $mark;
            $model->create_time = date("Y-m-d H:i:s");
            $model->create_by = Yii::$app->user->identity->user_name;
            $res = $model->save();
            if (!$res) {
                die(json_encode([
                    'status' => 0,
                    'msg' => '保存操作日志失败',
                ]));
            }  
        }

        $return_arr = ['status' => 1, 'msg' => $msg];
        echo json_encode($return_arr);
        die;
    }


}