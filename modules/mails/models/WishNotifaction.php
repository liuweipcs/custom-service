<?php

namespace app\modules\mails\models;

use app\components\Model;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use yii\helpers\Html;
use yii\helpers\Url;

class WishNotifaction extends Model
{

    public static function tableName()
    {
        return '{{%wish_notifaction}}';
    }

    public function rules()
    {
        return [
            [['account_id', 'is_view'], 'integer'],
            [['view_by', 'view_time', 'create_by', 'create_time', 'modify_by', 'modify_time'], 'safe'],
            [['noti_id', 'title', 'message', 'perma_link'], 'string'],
        ];
    }

    public function attributes()
    {
        $attributes = parent::attributes();
        $extAttributes = [

        ];
        return array_merge($attributes, $extAttributes);
    }

    /**
     * 搜索过滤项
     */
    public function filterOptions()
    {
        return [
            [
                'name' => 'account_id',
                'type' => 'search',
                'data' => self::dropdown(),
                'search' => '='
            ],
            [
                'name' => 'title',
                'type' => 'text',
                'search' => 'FULL LIKE'
            ],
            [
                'name' => 'message',
                'type' => 'text',
                'search' => 'FULL LIKE'
            ],
            [
                'name' => 'noti_id',
                'type' => 'text',
                'search' => '='
            ],
            [
                'name' => 'is_view',
                'type' => 'dropDownList',
                'data' => [0 => '否', 1 => '是'],
                'search' => '='
            ],
        ];
    }

    /**
     * 账号列表
     */
    public static function dropdown()
    {

        $accountmodel = new Account();
        $all_account = $accountmodel->findAll(['status' => 1, 'platform_code' => Platform::PLATFORM_CODE_WISH]);
        $arr = [' ' => '全部'];
        foreach ($all_account as $key => $value) {
            $arr[$value['id']] = $value['account_name'];
        }
        return $arr;
    }

    public function attributeLabels()
    {
        return [
            'account_id' => '账号',
            'noti_id' => '通知ID',
            'title' => '通知标题',
            'message' => '通知消息',
            'perma_link' => '线上链接',
            'is_view' => '是否查看',
            'view_by' => '查看人',
            'view_time' => '查看时间',
            'create_by' => '创建人',
            'create_time' => '创建时间',
            'modify_by' => '修改人',
            'modify_time' => '修改时间',
        ];
    }

    public function searchList($params = [])
    {
        $query = self::find();

        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'create_time' => SORT_DESC,
            'id' => SORT_DESC
        );

        $dataProvider = parent::search($query, $sort, $params);
        $models = $dataProvider->getModels();
        $this->addition($models);
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    /**
     * 修改模型数据
     */
    public function addition(&$models)
    {
        foreach ($models as $model) {
            $account = Account::findOne($model->account_id);
            if (!empty($account)) {
                $model->setAttribute('account_id', $account->account_name);
            }

            $model->setAttribute('perma_link', Html::a($model->perma_link, $model->perma_link, ['target' => '_blank']));

            $model->setAttribute('is_view', $model->is_view ? '是' : '否');
        }
    }
}