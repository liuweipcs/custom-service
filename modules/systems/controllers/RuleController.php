<?php

namespace app\modules\systems\controllers;

use app\modules\mails\models\InboxSubject;
use app\modules\services\modules\ebay\controllers\CustormerorderController;
use app\modules\systems\models\Rule;
use app\components\Controller;
use app\modules\accounts\models\Platform;
use app\modules\systems\models\Tag;
use app\modules\systems\models\Condition;
use app\modules\systems\models\ConditionOption;
use app\modules\systems\models\ConditionGroup;
use app\modules\systems\models\RuleCondtion;
use app\modules\mails\models\MailTemplate;
use app\modules\systems\models\Country;
use app\modules\orders\models\Logistic;
use Yii;
use app\modules\mails\models\EbayInboxSubject;
use app\modules\mails\models\AmazonInboxSubject;
use app\modules\mails\models\WalmartInboxSubject;
use app\modules\mails\models\AliexpressInbox;
use app\modules\mails\models\WishInbox;
use app\modules\mails\models\CdiscountInboxSubject;

class RuleController extends Controller
{
    public function actionList()
    {
        $model = new Rule();
        $params = \Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params);

        return $this->renderList('list', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }


    /**
     * 新增标签规则
     * @return string
     * @throws \yii\db\Exception
     */
    public function actionAdd()
    {
        $this->isPopup = true;
        $model = new Rule();
        $model->type = Rule::RULE_TYPE_TAG;

        if ($this->request->getIsAjax()) {
            //启动事务处理
            $transaction = \Yii::$app->db->beginTransaction();
            $postData = $this->request->post();
            //保存rule表的数据
            $model = $this->validateAndSaveRuleData($model, $postData, $transaction);

            //没有勾选条件 
            if (empty($postData['Rule']['rule_condtion'])) {
                $transaction->rollBack();
                $this->_showMessage(\Yii::t('system', 'no Option Data'), false);
            }

            //验证以及存取rule_condtion表数据
            $this->validateAndSaveRuleConditionData($model, $postData, $transaction);

            $transaction->commit();
            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/rule/list') . '");';
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, $refreshUrl);
        }

        $conditionData = Condition::getCondition(ConditionGroup::GROUP_TYPE_TAG);

        $platformList = Platform::getPlatformAsArray();

        return $this->render('add', [
            'model' => $model,
            'tagList' => Tag::getTagAsArray(key($platformList)),
            'platformList' => $platformList,
            'conditionData' => $conditionData,
        ]);
    }

    /**
     * 保存rule表的数据
     * @param  object $model ruel模型
     * @param  array $postData 前端提交过来的post数据
     * @param  object $transaction 开启的事物对象
     * @return object $model 保存后的rule对象
     */
    protected function validateAndSaveRuleData($model, $postData, $transaction)
    {
        //模型加载数据
        $model->load($postData);

        //数据验证失败
        if (!$model->validate()) {
            $transaction->rollBack();
            $this->_showMessage(current(current($model->getErrors())), false);
        }

        //保存rule表数据失败
        if (!$model->save()) {
            $transaction->rollBack();
            $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
        }

        return $model;
    }

    /**
     * 执行标签规则
     */
    public function actionExecute()
    {
        $this->isPopup = true;
        $model = new Rule();
        $model->type = Rule::RULE_TYPE_TAG;

        if($this->request->getIsAjax()){
            $postData = $this->request->post();
            if(empty($postData['mail_number'])){
                $refreshUrl = "top.layer.closeAll('iframe');";
                $this->_showMessage(\Yii::t('system', '操作失败'), false, null, false, null, $refreshUrl);
            }
            if(!empty($postData['platfrom_code'])){

                switch ($postData['platfrom_code']){
                    case Platform::PLATFORM_CODE_EB:
                        $mailObj =  EbayInboxSubject::find()
                            ->andWhere(['is_replied' => 0])
                            ->andWhere(['between','receive_date',$postData['start_time'],$postData['end_time']])
                            ->all();
                        break;
                    case Platform::PLATFORM_CODE_AMAZON:
                        $mailObj = AmazonInboxSubject::find()
                            ->andWhere(['is_replied' => 0])
                            ->andWhere(['between','receive_date',$postData['start_time'],$postData['end_time']])
                            ->all();
                        break;
                    case Platform::PLATFORM_CODE_ALI:
                        $mailObj = AliexpressInbox::find()
                            ->andWhere(['is_replied' => 0])
                            ->andWhere(['between','receive_date',$postData['start_time'],$postData['end_time']])
                            ->all();
                        break;
                    case Platform::PLATFORM_CODE_WISH:
                        $mailObj = WishInbox::find()
                            ->anWhere(['is_replied' => 0])
                            ->andWhere(['between','order_time',$postData['start_time'],$postData['end_time']])
                            ->all();
                        break;
                }
                if(!empty($mailObj)) {
                    foreach ($mailObj as $k => $item) {
                        if ($postData['platfrom_code'] == Platform::PLATFORM_CODE_EB) {
                            $arg = true;
                            $obj = new InboxSubject();
                            $flag = $obj->matchTagsPlat($item);
                            if (!$flag) {
                                $arg = false;
                                throw new \Exception('Match Tag Failed');

                            }
                        }
                        if ($postData['platfrom_code'] == Platform::PLATFORM_CODE_AMAZON) {
                            $arg = true;
                            $obj = new AmazonInboxSubject();
                            $flag = $obj->matchTagsPlat($item);
                            if (!$flag) {
                                $arg = false;
                                throw new \Exception('Match Tag Failed');

                            }
                        }
                        if ($postData['platfrom_code'] == Platform::PLATFORM_CODE_ALI) {
                            $arg = true;
                            $obj = new AliexpressInbox();
                            $flag = $obj->matchTags($item);
                            if (!$flag) {
                                $arg = false;
                                throw new \Exception('Match Tag Failed');

                            }
                        }
                        if ($postData['platfrom_code'] == Platform::PLATFORM_CODE_WISH) {
                            $arg = true;
                            $obj = new WishInbox();
                            $flag = $obj->matchTagsPlat($item);
                            if (!$flag) {
                                $arg = false;
                                throw new \Exception('Match Tag Failed');

                            }
                        }

                    }
                }

                if(!empty($arg)){
                    $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/rule/list') . '");';
                    $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, $refreshUrl);
                }

            }

        }

        $rule_id = (int)$this->request->getQueryParam('id');

        //没有勾选规则
        if (empty($rule_id)) {
            $refreshUrl = "top.layer.closeAll('iframe');";
            $this->_showMessage(\Yii::t('system', 'Invalid Params'), false, null, false, null, $refreshUrl);
        }

        $model = Rule::findById($rule_id);
        $end_time = date('Y-m-d H:i:s');
        $start_time = date('Y-m-d H:i:s',strtotime('-7 day',strtotime($end_time)));

        if($model->platform_code == Platform::PLATFORM_CODE_EB){
            $mail_number = EbayInboxSubject::find()
                ->andWhere(['is_replied' => 0])
                ->andWhere(['between','receive_date',$start_time,$end_time])
                ->count();
        }else if($model->platform_code == Platform::PLATFORM_CODE_AMAZON){
            $mail_number = AmazonInboxSubject::find()
                ->andWhere(['is_replied' => 0])
                ->andWhere(['between','receive_date',$start_time,$end_time])
                ->count();
        }else if($model->platform_code == Platform::PLATFORM_CODE_ALI){
            $mail_number = AliexpressInbox::find()
                ->andWhere(['is_replied' => 0])
                ->andWhere(['between','receive_date',$start_time,$end_time])
                ->count();
        }else if($model->platform_code == Platform::PLATFORM_CODE_WISH){
            $mail_number = WishInbox::find()
                ->andWhere(['is_replied' => 0])
                ->andWhere(['between','order_time',$start_time,$end_time])
                ->count();
        }else{
            $refreshUrl = "top.layer.closeAll('iframe');";
            $this->_showMessage(\Yii::t('system', '该平台暂无邮件规则'), false, null, false, null, $refreshUrl);
        }


        return $this->render('execute', [
            'mail_number' => $mail_number,
            'platfrom_code' => $model->platform_code,
            'start_time' => $start_time,
            'end_time' => $end_time,
        ]);


    }

    /**
     * 保存rule_condtion表的数据
     * @param object $model 保存后的rule对象
     * @param array $postData 前端提交过来的post数据
     * @param  object $transaction 开启的事物对象
     */
    protected function validateAndSaveRuleConditionData($model, $postData, $transaction)
    {
        foreach ($postData['Rule']['rule_condtion'] as $key => $value) {
            $modelRuleCondition = new RuleCondtion();
            if (!isset($value['value']) || $value['value'] == null || $value['value'] == '') {
                $transaction->rollBack();
                $this->_showMessage(\Yii::t('system', 'no Option Value'), false);
            }

            //一个条件下只有单个选项的情况
            $this->dealSingleData($key, $value, $model, $modelRuleCondition, $transaction);

            //一个条件下有多个选项的情况
            $this->dealMultitermData($key, $value, $model, $transaction);
        }
    }

    /**
     * 新增和编辑规则的时候处理指定提条件的单个option值
     * @param int $key 条件id
     * @param array $value option相关数据
     * @param object $model rule模型
     * @param object $modelRuleCondition RuleCondition模型
     * @param object $transaction 事物对象
     */
    protected function dealSingleData($key, $value, $model, $modelRuleCondition, $transaction)
    {
        if (!is_array($value['value'])) {
            //没有填写必须的数据
            if ($value['value'] == null || $value['value'] == '') {
                $transaction->rollBack();
                $this->_showMessage(\Yii::t('system', 'no Option Value'), false);
            }
            //构造验证数据
            //暂时关闭对option_id的维护'option_id' => count($value_value) > 1 ? $value_value[1] : null,
            //$value_value = explode("|",$value['value']);
            $load_data = [
                'rule_id' => $model->id,
                'condtion_id' => $key,
                'option_id' => null,
                'oprerator' => $value['oprerator'],
                'option_value' => $value['value'],
                'input_type' => $value['input_type'],
                'condition_name' => $value['condition_name'],
                'condition_key' => $value['condition_key'],
            ];
            $modelRuleCondition->load($load_data, '');

            if (!$modelRuleCondition->validate()) {
                $transaction->rollBack();
                $this->_showMessage(current(current($modelRuleCondition->getErrors())), false);
            }

            if (!$modelRuleCondition->save()) {
                $transaction->rollBack();
                $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
            }
        }
    }

    /**
     * 新增和编辑规则的时候处理指定提条件的多个option值
     * @param int $key 条件id
     * @param array $value option相关数据
     * @param object $model rule模型
     * @param object $transaction 事物对象
     */
    protected function dealMultitermData($key, $value, $model, $transaction)
    {
        if (is_array($value['value'])) {

            $data = $this->getDealMultitermData($key, $value, $model, $transaction);
            //循环数据进行批量入库
            foreach ($data as $y => $ye) {
                $_model = new RuleCondtion();
                $_model->setAttributes($ye);

                if (!$_model->validate()) {
                    $transaction->rollBack();
                    $this->_showMessage(current(current($_model->getErrors())), false);
                }

                if (!$_model->save()) {
                    $transaction->rollBack();
                    $this->_showMessage(\Yii::t('system', 'Operate Failed'), false);
                }
            }
        }
    }

    /**
     * 构造入库数据以及验证数据
     * @param int $key 条件id
     * @param array $value option相关数据
     * @param object $model rule模型
     * @param object $transaction 事物对象
     */
    protected function getDealMultitermData($key, $value, $model, $transaction)
    {
        //当input_type是范围的时候验证数据的合法性 
        $this->validateRangeData($value, $transaction);

        //构造数据,暂时关闭对option_id字段的维护
        $data = [];
        foreach ($value['value'] as $ke => $ve) {
            $data[$ke]['option_id'] = null;
            $data[$ke]['option_value'] = $ve;
            $data[$ke]['rule_id'] = $model->id;
            $data[$ke]['condtion_id'] = $key;
            $data[$ke]['oprerator'] = $value['oprerator'];
            $data[$ke]['input_type'] = $value['input_type'];
            $data[$ke]['condition_name'] = $value['condition_name'];
            $data[$ke]['condition_key'] = $value['condition_key'];
        }
        return $data;
    }

    /**
     * @param array $value option相关数据
     * @param object $transaction 事物对象
     */
    protected function validateRangeData($value, $transaction)
    {
        //对条件的input_type类型为范围的时候的开始范围和结束范围的数据格式进行验证
        if ($value['input_type'] == Condition::CONDITION_INPUT_TYPE_RANGE) {
            list($range_start_value, $range_end_value) = $value['value'];

            //验证日期格式
            $this->validateRangeDataDate($range_start_value, $range_end_value, $transaction);

            //验证数字格式
            $this->validateRangeDataNumber($range_start_value, $range_end_value, $transaction);
        }
    }

    /**
     * 验证input_type是范围的时候客服输入的数据是日期格式
     * @param string $range_start_value 开始范围的值
     * @param string $range_end_value 结束范围的值
     * @param object $transaction 事务对象
     */
    protected function validateRangeDataDate($range_start_value, $range_end_value, $transaction)
    {
        if (strpos($range_start_value, "-") !== false) {
            //开始范围和结束范围必须同时是时间格式
            if (substr_count($range_start_value, "-") != 2 || substr_count($range_end_value, "-") != 2) {
                $transaction->rollBack();
                $this->_showMessage(\Yii::t('system', 'wrong Format Value'), false);
            }

            if (!strtotime($range_start_value) || !strtotime($range_start_value)) {
                $transaction->rollBack();
                $this->_showMessage(\Yii::t('system', 'wrong Format Value'), false);
            }

            //结束范围必须大于开始范围
            if (strtotime($range_end_value) <= strtotime($range_start_value)) {
                $transaction->rollBack();
                $this->_showMessage(\Yii::t('system', 'over must dayu start'), false);
            }
        }
    }

    /**
     * 验证input_type是范围的时候客服输入的数据是日期格式
     * @param string $range_start_value 开始范围的值
     * @param string $range_end_value 结束范围的值
     * @param object $transaction 事务对象
     */
    protected function validateRangeDataNumber($range_start_value, $range_end_value, $transaction)
    {
        if (strpos($range_start_value, "-") === false) {
            //开始范围是数字格式
            if (!is_numeric($range_end_value) || !is_numeric($range_start_value)) {
                $transaction->rollBack();
                $this->_showMessage(\Yii::t('system', 'wrong Format Value'), false);
            }

            //结束范围必须大于开始范围
            if ($range_start_value >= $range_end_value) {
                $transaction->rollBack();
                $this->_showMessage(\Yii::t('system', 'over must dayu start'), false);
            }
        }
    }

    /**
     * @desc 批量删除记录
     */
    public function actionBatchdelete()
    {
        if ($this->request->getIsAjax()) {

            //要删除的规则id组成的数组
            $rule_ids = $this->request->getBodyParam('ids');
            //进行删除操作
            $this->deleteData($rule_ids, 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/rule/list') . '");');
        }
    }

    /**
     * 公共删除规则方法
     */
    protected function deleteData($rule_id, $refreshUrl)
    {
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            //没有勾选要删除的规则
            if (empty($rule_id)) {
                $transaction->rollBack();
                $this->_showMessage(\Yii::t('system', 'Invalid Rule Id'), false);
            }

            //删除单条的规则数据
            if (!is_array($rule_id)) {
                $this->deleteSingleData($rule_id, $transaction);
            }

            //批量删除规则数据
            if (is_array($rule_id)) {
                $this->deleteBatchData($rule_id, $transaction);
            }

            //提交事务并且返回成功提示
            $transaction->commit();
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, $refreshUrl);

        } catch (Exception $e) {
            $error = $e->getMessage();
            $transaction->rollBack();
        }
    }

    /**
     * 删除单条的规则数据
     * @param int $rule_id 要删除的规则id
     * @param object $transaction 事务对象
     */
    protected function deleteSingleData($rule_id, $transaction)
    {
        $model = new Rule();
        $result = $model->deleteById($rule_id);

        //删除rule表数据失败
        if (!$result) {
            $transaction->rollBack();
            $this->_showMessage(\Yii::t('system', 'Operate Rule Failed'), false);
        }

        //删除rule_condtion的数据
        $result = RuleCondtion::deleteAll('rule_id = :rule_id', [':rule_id' => $rule_id]);

        //删除rule_condtion表数据失败
        if (!$result) {
            $transaction->rollBack();
            $this->_showMessage(\Yii::t('system', 'Operate Ruleconditin Failed'), false);
        }
    }

    /**
     * 批量删除规则数据
     * @param array $rule_ids 要删除的规则id组成数组
     * @param object $transaction 事务对象
     */
    protected function deleteBatchData($rule_ids, $transaction)
    {
        $model = new Rule();

        //过滤数组数据并且删除rule表的数据
        $ids = array_filter($rule_ids);
        $result = $model->deleteByIds($rule_ids);

        //删除规则失败
        if (!$result) {
            $transaction->rollBack();
            $this->_showMessage(\Yii::t('system', 'Operate Rule Failed'), false);
        }

        //删除rule_condtion表的数据
        $result = RuleCondtion::deleteAll(['in', 'rule_id', $rule_ids]);

        //删除rule_condtion表的数据失败
        if (!$result) {
            $transaction->rollBack();
            $this->_showMessage(\Yii::t('system', 'Operate Rulecondition Failed'), false);
        }
    }

    /**
     * 单独获取规则条件表中的操作符数据
     * @param int $conditionId 条件id
     * @param string $rule_platform_code 平台code
     * @param string $condition_key 条件key用于匹配的时候获取匹字段名称
     * @param int $input_type 条件类型(1代表input,2代表radio,3代表select,4代表checkbox, 5代表范围)
     * @return 返回json数据
     */
    public function actionGetopreratordata($input_type, $condition_id, $condition_key, $rule_platform_code, $type = 1)
    {
        $data['input_type'] = $input_type;

        if ($condition_key == Condition::CONDITION_KEY_PRODUCT_SUBJECT) {
            $data['oprerator_data'] = [
                RuleCondtion::RULE_CONDITION_OPRERATOR_DENGYU => \Yii::t('system', 'oprerator Dengyu'),
                RuleCondtion::RULE_CONDITION_OPRERATOR_BAOHAN => \Yii::t('system', 'oprerator Baohan'),
                RuleCondtion::RULE_CONDITION_OPRERATOR_BUBAOHAN => \Yii::t('system', 'oprerator Bubaohan'),
                RuleCondtion::RULE_CONDITION_OPRERATOR_BAOHANIN => \Yii::t('system', 'oprerator Baohanin'),
            ];
        } else if ($condition_key == Condition::CONDITION_KEY_CUSTOMER_COUNTRY ||
            $condition_key == Condition::CONDITION_KEY_LOGISTICS_MODE) {
            $data['oprerator_data'] = [];
        } else {
            $data['oprerator_data'] = RuleCondtion::getOpreratorAsArray($input_type);
        }

        switch ($input_type) {
            case Condition::CONDITION_INPUT_TYPE_INPUT:
            case Condition::CONDITION_INPUT_TYPE_RANGE:
                break;
            case Condition::CONDITION_INPUT_TYPE_RADIO:
            case Condition::CONDITION_INPUT_TYPE_SELECT:
            case Condition::CONDITION_INPUT_TYPE_CHECKBOX:
                $data['option_data'] = ConditionOption::getOptionDataByConditionId($condition_id, $condition_key, $rule_platform_code, $type);
                break;
        }

        //返回json数据
        echo json_encode($data);
    }

    /**
     * 根据规则id删除规则数据
     * @param int id 规则id
     */
    public function actionDelete()
    {
        //要删除的规则id
        $rule_id = (int)$this->request->getQueryParam('id');

        //进行删除操作
        $this->deleteData($rule_id, 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/rule/list') . '");');
    }

    /**
     * 编辑规则信息
     * @return \yii\base\string
     */
    public function actionEdit()
    {
        $this->isPopup = true;
        $rule_id = (int)$this->request->getQueryParam('id');

        //没有勾选规则
        if (empty($rule_id)) {
            $refreshUrl = "top.layer.closeAll('iframe');";
            $this->_showMessage(\Yii::t('system', 'Invalid Params'), false, null, false, null, $refreshUrl);
        }

        $model = Rule::findById($rule_id);

        //非法操作
        if (empty($model)) {
            $refreshUrl = "top.layer.closeAll('iframe');";
            $this->_showMessage(\Yii::t('system', 'Not Found Record'), false, null, false, null, $refreshUrl);
        }

        if ($this->request->getIsAjax()) {
            //启动事务处理
            $transaction = \Yii::$app->db->beginTransaction();
            $postData = $this->request->post();

            //保存rule表的数据
            $model = $this->validateAndSaveRuleData($model, $postData, $transaction);

            //没有勾选条件 
            if (empty($postData['Rule']['rule_condtion'])) {
                $transaction->rollBack();
                $this->_showMessage(\Yii::t('system', 'no Option Data'), false);
            }

            //删除指定规则的规则条件表的数据然后再重新存取
            $this->deleteRuleConditionDataByRuleId($model, $transaction);

            //验证以及存取rule_condtion表数据
            $this->validateAndSaveRuleConditionData($model, $postData, $transaction);

            $transaction->commit();
            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/rule/list') . '");';
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, $refreshUrl);
        }

        $condition_data = Condition::getCondition(ConditionGroup::GROUP_TYPE_TAG);
        $rule_condition_data = RuleCondtion::getRuleConditionData($rule_id);

        $platformList = Platform::getPlatformAsArray();

        //国家列表
        $countryList = Country::getCodeNamePairsList('cn_name');
        //物流列表
        $logisticsList = Logistic::getLogisArrCodeName();

        return $this->render('edit', [
            'model' => $model,
            'tagList' => Tag::getTagAsArray($model->platform_code),
            'platformList' => $platformList,
            'conditionData' => $condition_data,
            'rule_condition_data' => $rule_condition_data,
            'oprerator_data' => RuleCondtion::getOpreratorAsArray(),
            'countryList' => $countryList,
            'logisticsList' => $logisticsList,
        ]);
    }

    /**
     * 删除指定规则id的rule_condtion表的数据
     * @param object $model 带有规则id的rule模型
     * @param object $transaction 事物对象
     */
    protected function deleteRuleConditionDataByRuleId($model, $transaction)
    {
        //非法数据
        if (empty($model->id)) {
            $transaction->rollBack();
            $this->_showMessage(\Yii::t('system', 'no Rule Id'), false);
        }

        //删除数据
        $result = RuleCondtion::deleteAll('rule_id = :rule_id', [':rule_id' => $model->id]);

        //删除失败
        if (!$result) {
            $transaction->rollBack();
            $this->_showMessage(\Yii::t('system', 'Operate Ruleconditin Failed'), false);
        }
    }

    /**
     * 自动回复规则列表
     */
    public function actionListreply()
    {
        $model = new Rule();
        $params = \Yii::$app->request->getBodyParams();
        $dataProvider = $model->searchList($params, Rule::RULE_TYPE_AUTO_ANSWER);

        return $this->renderList('listreply', [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * 新增自动回复规则
     */
    public function actionAddreply()
    {
        $this->isPopup = true;
        $model = new Rule();
        $model->type = Rule::RULE_TYPE_AUTO_ANSWER;

        if ($this->request->getIsAjax()) {
            //启动事务处理
            $transaction = \Yii::$app->db->beginTransaction();
            $postData = $this->request->post();
            if (!empty($postData['Rule']['new_rule_condition'])) {
                $arr = [];
                foreach ($postData['Rule']['new_rule_condition'] as $k => $v) {
                    if (!empty($postData['buyer_message_day']) && (int)$postData['buyer_message_day'] > 0) {
                        if ($v['condition_name'] == 'buyer_message') {
                            $arr[] = $v['condition_name'] . '&' . (int)$postData['buyer_message_day'];
                        } else {
                            $arr[] = $v['condition_name'];
                        }

                    } else {
                        $arr[] = $v['condition_name'];
                    }
                }
                if (!empty($arr)) {
                    $condition_by = json_encode($arr);
                } else {
                    $condition_by = '';
                }
                $model->condition_by = $condition_by;
                $model->save();
            }

            if ($postData['Rule']['execute_hour'] >= 24) {
                $this->_showMessage('触发时间选择有误，时条件不能超过24', false);
            }
            if ($model->type == Rule::RULE_TYPE_AUTO_ANSWER && empty($postData['Rule']['execute_id'])) {
                $this->_showMessage('请选择触发时间', false);
            }
            
            //如果设置的区间则开始时间  结束时间必填
            if($postData['Rule']['status'] == 2 && (empty($postData['Rule']['survival_str_time']) || empty($postData['Rule']['survival_end_time']))){
                $this->_showMessage('当前规则状态下 开始时间或者结束时间必填', false);
            }

            //保存rule表的数据
            $model = $this->validateAndSaveRuleData($model, $postData, $transaction);

            //没有勾选条件 
            if (empty($postData['Rule']['rule_condtion'])) {
                $transaction->rollBack();
                $this->_showMessage(\Yii::t('system', 'no Option Data'), false);
            }

            //验证以及存取rule_condtion表数据
            $this->validateAndSaveRuleConditionData($model, $postData, $transaction);

            $transaction->commit();
            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/rule/listreply') . '");';
            $url = '/systems/rule/listreply';
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, $url, false, null, $refreshUrl);
        }

        $execute_infos = include \Yii::getAlias("@app") . '/config/order_rule_exec_condition.php';
        $conditionData = Condition::getCondition(ConditionGroup::GROUP_TYPE_AUTO_ANSWER);
        $platformList = Platform::getPlatformAsArray();
        return $this->render('add', [
            'model' => $model,
            'tagList' => MailTemplate::getOrderTemplateDataAsArray(key($platformList)),
            'platformList' => $platformList,
            'conditionData' => $conditionData,
            'execute_infos' => $execute_infos,
        ]);
    }

    /**
     * 根据规则id删除自动回复规则数据
     * @param int $id 规则id
     */
    public function actionDeletereply()
    {
        $rule_id = (int)$this->request->getQueryParam('id');

        //进行删除操作
        $this->deleteData($rule_id, 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/rule/listreply') . '");');
    }

    /**
     * 批量删除自动回复规则
     * @param array $ids 要删除的规则id组成的数组
     */
    public function actionBatchdeletereply()
    {
        if ($this->request->getIsAjax()) {

            //要删除的规则id组成的数组
            $rule_ids = $this->request->getBodyParam('ids');
            //进行删除操作
            $this->deleteData($rule_ids, 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/rule/listreply') . '");');
        }
    }

    /**
     * 编辑自动回复规则
     */
    public function actionEditreply()
    {
        $this->isPopup = true;
        $rule_id = (int)$this->request->getQueryParam('id');

        //没有勾选规则
        if (empty($rule_id)) {
            $refreshUrl = "top.layer.closeAll('iframe');";
            $this->_showMessage(\Yii::t('system', 'Invalid Params'), false, null, false, null, $refreshUrl);
        }

        $model = Rule::findById($rule_id);

        //非法操作
        if (empty($model)) {
            $refreshUrl = "top.layer.closeAll('iframe');";
            $this->_showMessage(\Yii::t('system', 'Not Found Record'), false, null, false, null, $refreshUrl);
        }

        if ($this->request->getIsAjax()) {
            //启动事务处理
            $transaction = \Yii::$app->db->beginTransaction();
            $postData = $this->request->post();
            if (!empty($postData['Rule']['new_rule_condition'])) {
                $arr = [];
                foreach ($postData['Rule']['new_rule_condition'] as $k => $v) {
                    if (strpos($v['condition_name'], 'buyer_message') !== false) {
                        $arr[] = 'buyer_message&' . (int)$postData['buyer_message_day'];
                    } else {
                        $arr[] = $v['condition_name'];
                    }
                }
                $model->condition_by = json_encode($arr);
                $model->save();
            } else {
                $model->condition_by = '';
                $model->save();
            }
            if ($postData['Rule']['execute_hour'] >= 24) {
                $this->_showMessage('触发时间选择有误，时条件不能超过24', false);
            }
            if ($model->type == Rule::RULE_TYPE_AUTO_ANSWER && empty($postData['Rule']['execute_id'])) {
                $this->_showMessage('请选择触发时间', false);
            }
            
            //如果设置的区间则开始时间  结束时间必填
            if($postData['Rule']['status'] == 2 && (empty($postData['Rule']['survival_str_time']) || empty($postData['Rule']['survival_end_time']))){
                $this->_showMessage('当前规则状态下 开始时间或者结束时间必填', false);
            }

            //保存rule表的数据
            $model = $this->validateAndSaveRuleData($model, $postData, $transaction);

            //没有勾选条件 
            if (empty($postData['Rule']['rule_condtion'])) {
                $transaction->rollBack();
                $this->_showMessage(\Yii::t('system', 'no Option Data'), false);
            }

            //删除指定规则的规则条件表的数据然后再重新存取
            $this->deleteRuleConditionDataByRuleId($model, $transaction);

            //验证以及存取rule_condtion表数据
            $this->validateAndSaveRuleConditionData($model, $postData, $transaction);

            $transaction->commit();
            $refreshUrl = 'top.refreshTable("' . \yii\helpers\Url::toRoute('/systems/rule/listreply') . '");';
            $this->_showMessage(\Yii::t('system', 'Operate Successful'), true, null, false, null, $refreshUrl);
        }

        $execute_infos = include \Yii::getAlias("@app") . '/config/order_rule_exec_condition.php';
        $condition_data = Condition::getCondition(ConditionGroup::GROUP_TYPE_AUTO_ANSWER);
        $rule_condition_data = RuleCondtion::getRuleConditionData($rule_id);
        return $this->render('edit', [
            'model' => $model,
            'tagList' => MailTemplate::getOrderTemplateDataAsArray($model->platform_code),
            'platformList' => Platform::getPlatformAsArray(),
            'conditionData' => $condition_data,
            'rule_condition_data' => $rule_condition_data,
            'oprerator_data' => RuleCondtion::getOpreratorAsArray(),
            'execute_infos' => $execute_infos,
        ]);
    }

}
