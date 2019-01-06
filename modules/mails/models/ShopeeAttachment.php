<?php

namespace app\modules\mails\models;

use app\components\Model;
use app\modules\orders\models\OrderOtherSearch;
use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\Platform;
use app\modules\accounts\models\UserAccount;

class ShopeeAttachment extends Model
{

    public static function tableName()
    {
        return '{{%shopee_attachment}}';
    }
}