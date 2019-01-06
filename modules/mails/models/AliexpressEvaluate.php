<?php

namespace app\modules\mails\models;

use app\common\VHelper;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\Account;
use app\modules\services\modules\aliexpress\models\AddEvaluation;
use app\modules\orders\models\OrderAliexpressSearch;
use app\modules\accounts\models\UserAccount;

class AliexpressEvaluate extends MailsModel
{
    const PLATFORM_CODE = Platform::PLATFORM_CODE_ALI;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%aliexpress_evaluate}}';
    }

    public function attributes()
    {
        $attributes = parent::attributes();
        $attributes[] = 'order_id';
        return $attributes;
    }

    /**
     * @desc 搜索过滤项
     * @return multitype:multitype:string multitype:  multitype:string multitype:string
     */
    public function filterOptions()
    {
        return [
            [
                'name' => 'platform_order_id',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'order_id',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'buyer_id',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'sku',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'account_id',
                'type' => 'dropDownList',
                'data' => self::AccountList(),
                'search' => '=',
            ],
            [
                'name' => 'issue_status',
                'type' => 'dropDownList',
                'data' => ['NO_ISSUE' => '无纠纷', 'IN_ISSUE' => '纠纷中', 'END_ISSUE' => '纠纷结束'],
                'search' => '=',
            ],
            [
                'name' => 'score',
                'type' => 'dropDownList',
                'data' => [0 => '0分', 1 => '1分', 2 => '2分', 3 => '3分', 4 => '4分', 5 => '5分'],
                'search' => '=',
            ],
        ];
    }

    /**
     * @desc search list
     * @param unknown $params
     * @param string $query
     */
    public function searchList($params = [])
    {
        $query = self::find();
        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'id' => SORT_ASC
        );


        if (!empty($params['sku'])) {
            //通过sku查询order_id
            $order_id = OrderAliexpressSearch::getOrder_id($params['sku']);


            if (!empty($order_id)) {
                //通过order_id查询platform_order_id
                $platform_order_id = OrderAliexpressSearch::getPlatformOrders($order_id);
                if ($platform_order_id) {
                    $query->andWhere(['in', 'platform_order_id', $platform_order_id]);
                }
            }

            unset($params['sku']);
        }

        //查询速卖通订单表得平台订单号
        if (!empty($params['order_id'])) {
            $platform_order_id = OrderAliexpressSearch::getPlatform($params['order_id']);
            if (!empty($platform_order_id)) {

                $query->andWhere(['platform_order_id' => $platform_order_id]);
            }
            unset($params['order_id']);
        }


        $dataProvider = parent::search($query, $sort, $params);
        $models = $dataProvider->getModels();
        foreach ($models as $k => $v) {
            $v['order_id'] = OrderAliexpressSearch::getOrderId($v['platform_order_id']);
        }

        $this->addition($models);
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => '订单号',
            'platform_order_id' => '平台订单号',
            'total_price' => '订单金额',
            'is_dispute' => '是否为纠纷订单',
            'buyer_id' => '买家ID',
            'currency' => '币种',
            'buyer_name' => '买家名称',
            'create_by' => '创建人',
            'create_time' => '创建时间',
            'modify_by' => '修改人',
            'modify_time' => '修改时间',
            'issue_status' => '纠纷状态',
            'score' => '分数',
            'feedback_content' => '评价内容',
            'account_id' => '店铺账号',
        ];
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \app\modules\mails\models\Inbox::addition()
     */
    public function addition(&$models)
    {
        foreach ($models as $key => $model) {
            $models[$key]->setAttribute('issue_status', self::getIssueStatus($model->issue_status));
            $models[$key]->setAttribute('platform_order_id', self::getInfo($model->platform_order_id));
            $models[$key]->setAttribute('score', self::getScore($model->score));
        }
    }

    public static function getInfo($platform_order_id)
    {
        return "<a class='edit-button' href='/mails/aliexpress/order?order_id={$platform_order_id}&platform=" . Platform::PLATFORM_CODE_ALI . "' title='订单信息'>{$platform_order_id}</a>";
    }

    public static function getIssueStatus($issue_status)
    {
        $data = ['NO_ISSUE' => '无纠纷', 'IN_ISSUE' => '纠纷中', 'END_ISSUE' => '纠纷结束'];
        return $data[$issue_status];
    }

    public static function getScore($score)
    {
        $data = [0 => '0分', 1 => '1分', 2 => '2分', 3 => '3分', 4 => '4分', 5 => '5分'];
        return isset($data[$score]) ? $data[$score] : '';
    }

    public static function AccountList()
    {
        $accountList = Account::getPlatformAccounts(Platform::PLATFORM_CODE_ALI, AccountTaskQueue::TASK_TYPE_MESSAGE);
        $list = [];
        if (!empty($accountList)) {
            foreach ($accountList as $value) {
                $list[$value->attributes['id']] = $value->attributes['account_name'];
            }
        }
        return $list;
    }

    public function getAccountId()
    {
        return $this->account_id;
    }

    public static function getOne($id)
    {
        return self::findOne(['id' => $id]);
    }

    public static function getFindOne($platform_order_id)
    {
        return self::findOne(['platform_order_id' => $platform_order_id]);
    }

    /*添加评价*/
    public function getAdd($data)
    {

        try {
            $aliexpressEvaluate = self::findOne(['platform_order_id' => $data['order_id']]);
            $evaluationModule = new AddEvaluation();
            $retuelt = $evaluationModule->getToStoreInformation($data);
            if ($retuelt) {
                $aliexpressEvaluate->score = $data['score'];
                $aliexpressEvaluate->is_evaluate = 1;
                $aliexpressEvaluate->feedback_content = $data['feedback_content'];
                if ($aliexpressEvaluate->save()) {
                    return true;
                } else {
                    return false;
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

}
