<?php

namespace app\modules\mails\models;

use app\components\Model;
use app\modules\accounts\models\Account;
use yii\data\Sort;
use app\modules\accounts\models\Platform;

/**
 * 亚马逊主动联系发送邮件表
 */
class ActiveSendEmail extends Model
{
    public static function tableName()
    {
        return '{{%active_send_email}}';
    }

    public function attributeLabels()
    {
        return [
            'account_id' => '账号',
            'platform_code' => '平台code',
            'platform_order_id' => '平台订单ID',
            'sender_email' => '发送者邮箱',
            'receive_email' => '接收者邮箱',
            'title' => '主题',
            'content' => '内容',
            'asin' => 'ASIN',
            'sku' => 'sku',
            'tag' => '邮件标签ID',
            'attachments' => '附件',
            'inbox_id' => '关联的站内信id',
            'create_by' => '创建人',
            'create_time' => '创建时间',
            'modify_by' => '修改人',
            'modify_time' => '修改时间',
        ];
    }

    /**
     * 搜索筛选项
     */
    public function filterOptions()
    {
        return [
            [
                'name' => 'platform_code',
                'type' => 'dropDownList',
                'data' => self::platformDropdown(),
                'search' => '='
            ],
            [
                'name' => 'account_id',
                'type' => 'search',
                'data' => self::accountDropdown(),
                'search' => '=',
            ],
            [
                'name' => 'platform_order_id',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'sender_email',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'receive_email',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'title',
                'type' => 'text',
                'search' => '=',
            ],
            [
                'name' => 'content',
                'type' => 'text',
                'search' => 'FULL LIKE',
            ],
            [
                'name' => 'create_by',
                'type' => 'text',
                'search' => '=',
            ],
        ];
    }

    /**
     * 返回平台下拉框数据
     */
    public static function platformDropdown()
    {
        return Platform::getPlatformAsArray();
    }

    /**
     * 返回账号下拉框数据
     */
    public static function accountDropdown()
    {
        $accounts = Account::find()->select('id,account_name')->where(['status' => 1])->asArray()->all();

        $data = [' ' => '全部'];
        foreach ($accounts as $account) {
            $data[$account['id']] = $account['account_name'];
        }
        return $data;
    }

    public function searchList($params)
    {
        $query = self::find();

        $sort = new Sort();
        $sort->defaultOrder = array(
            'id' => SORT_DESC
        );

        $dataProvider = parent::search($query, $sort, $params);
        $models = $dataProvider->getModels();
        $this->addition($models);
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    public function addition(&$models)
    {
        foreach ($models as $model) {
            $account = Account::findOne($model->account_id);
            if (!empty($account->account_name)) {
                $model->setAttribute('account_id', $account->account_name);
            }
        }
    }
}