<?php
namespace app\modules\mails\models;
use app\common\VHelper;
use app\modules\accounts\models\Platform;
use app\modules\users\models\User;
use app\modules\services\modules\aliexpress\models\AddEvaluation;
class AliexpressEvaluateReply extends Inbox
{
    const PLATFORM_CODE = Platform::PLATFORM_CODE_ALI;
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%aliexpress_evaluate_reply}}';
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
            ]
        ];
    }
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '自增ID',
            'order_id' => '订单id',
            'platform_order_id' => '平台订单ID',
            'appraiser' => '评价人姓名',
            'score' => '打分分数',
            'feedback_content' => '评价内容',
            'return_times' => '回传次数',
            'transit_time' => '回传时间',
            'return_state' => '回传状态',
            'check' => '是否将已回传数据转移到历史表中',
            'create_by' => '创建人',
            'create_time' => '创建时间',
            'is_evaluate' => '是否已评价',

        ];
    }

    /**
     * !CodeTemplates.overridecomment.nonjd!
     * @see \app\modules\mails\models\Inbox::addition()
     */
    public function addition (&$models)
    {
        foreach ($models as  $key => $model)
        {
            $models[$key]->setAttribute('is_evaluate', self::getIsAvaluate($model->is_evaluate));
        }
    }
    public function getIsAvaluate($is_evaluate){
        $data = [0=>'已评价',1=>'未评价'];
        return $data[$is_evaluate];
    }
    public function getAccountId()
    {
        return $this->account_id;
    }
    /*添加评价*/
    public function getAdd($data){

        try{
            $models = $this::findOne(['platform_order_id'=>$data['order_id']]);
            if(empty($models)){
                $evaluationModule = new AddEvaluation();
                $retuelt = $evaluationModule->getToStoreInformation($data);
                if($retuelt){
                    $User = User::findIdentity(\Yii::$app->user->id);
                    $this->platform_order_id = $data['order_id'];
                    $this->score = $data['score'];
                    $this->appraiser = $User->user_name;
                    $this->create_by = $User->user_name;
                    $this->create_time = date('Y-m-d H:i:s');
                    $this->feedback_content = $data['feedback_content'];
                    $this->save();
                    return true;
                }
                return false;
            }
            return false;
        } catch (\Exception $e) {
            echo $e->getMessage();
            return false;
        }

    }
}
