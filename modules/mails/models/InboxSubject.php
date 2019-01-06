<?php
/**
 * @desc 消息模型基类
 * @author Fun
 */
namespace app\modules\mails\models;
use app\modules\accounts\models\Platform;
use app\modules\systems\models\Tag;
use app\modules\accounts\models\UserAccount;
use app\modules\systems\models\Rule;
use app\modules\mails\models\MailTemplate;
use app\modules\users\models\User;
use app\modules\accounts\models\Account;
use yii\db\Query;
use yii\data\Sort;
class InboxSubject extends MailsModel
{
    const SYNC_STATUS_NO = 0;           //未同步
    const SYNC_STATUS_YES = 1;          //已同步

    const REPLY_YES = 1;                //已回复

    const REPLY_YES_UNSYNC = 1;         //已回复未同步
    const REPLY_NO = 0;                 //未回复
    const REPLY_YES_SYNC = 2;           //已回复已同步
    const REPLY_MARK = 3;               //标记已回复
    
    const READ_STATUS_NO_READ = 0;      //未读
    const READ_STATUS_NO_SYNC = 1;      //已读未同步
    const READ_STATUS_READ = 2;         //已读
    
    const STATUS_VALID = 1;     //有效
    const STATUS_INVALID = 0;   //无效
    
    public $is_read_text = null;
    public $is_replied_text = null;
    
    /**
     * @desc 获取消息模型
     * @param unknown $platformCode
     * @return \app\modules\mails\models\AliexpressInbox|NULL
     */
    public static function getInboxModel($platformCode)
    {
        switch ($platformCode)
        {
            case Platform::PLATFORM_CODE_ALI:
                return new AliexpressInbox();
            case Platform::PLATFORM_CODE_AMAZON:
                return new AmazonInbox();
            case Platform::PLATFORM_CODE_EB:
                return new EbayInbox();
            default:
                return null;
        }
    }
    
    /**
     * @desc search list
     * @param unknown $params
     * @param string $query
     */
    public function searchList($params = [], $sort = null)
    {
        //清除已处理消息列表
//        self::destroyProcessedList();
        $query = self::find();
        $query->from(self::tableName() . ' as t');

        if (isset($_REQUEST['tag_id']) && !empty($_REQUEST['tag_id']))
        {
            $query->innerJoin(MailSubjectTag::tableName() . ' as t1', 't.id = t1.subject_id and t1.platform_code = :platform_code'
                , ['platform_code' => static::PLATFORM_CODE])
                ->where('t1.tag_id = ' . $_REQUEST['tag_id']);
        }
        if(isset($params['account_id']) && !empty(trim($params['account_id'])))
        {
            $query->andWhere(['t.account_id'=>$params['account_id']]);
        }
        else
        {
            $accountIds = UserAccount::getCurrentUserPlatformAccountIds(static::PLATFORM_CODE);

            $query->andWhere(['in', 't.account_id', $accountIds]);
        }



        if (is_null($sort))
        {
            $sort = new \yii\data\Sort([
                    'attributes' => ['id', 't.receive_date']
                ]);
            $sort->defaultOrder = array(
                't.receive_date' => SORT_ASC,
            );
        }
        $dataProvider = parent::search($query, $sort, $params);
        $models = $dataProvider->getModels();
        $this->addition($models);
/*         foreach ($models as  $key => $model)
        {
            $models[$key]->setAttribute('read_stat', self::getReadStat($model->read_stat));
            $models[$key]->setAttribute('deal_stat', self::getDealStat($model->deal_stat));
            $models[$key]->setAttribute('msg_sources', self::getMsgSources($model->msg_sources));
            $models[$key]->setAttribute('last_message_content',
                '<a target="_blank" href="/mails/aliexpress/details?id='.$model->id.'">'
                .$model->last_message_content.'</a>');
        } */
        $dataProvider->setModels($models);
        return $dataProvider;
    }
    
    /**
     * @desc 获取标签列表
     * @return multitype:multitype:NULL \yii\db\array
     */
    public static function getTagsList()
    {
        $tagList = \app\modules\systems\models\Tag::getPlatformTagList(static::PLATFORM_CODE);
        $tags = [];
        $query = new \yii\db\Query();
        $query->from(static::tableName() . ' as t')
            ->leftJoin(MailSubjectTag::tableName() . ' as t1', 't.id = t1.subject_id and t1.platform_code = :platform_code1',[
                'platform_code1' => static::PLATFORM_CODE
            ])
            ->leftJoin(Tag::tableName() . ' as t2', 't2.id = t1.tag_id and t1.platform_code = :platform_code2', [
                'platform_code2' => static::PLATFORM_CODE
            ])
            ->select(['t2.id', 't2.tag_name as name', 'count' => 'count(*)'])
            ->where('t2.status = 1')
            ->groupBy('t2.id')
            ->orderBy(null);
        if (!isset($_REQUEST['account_id']) || $_REQUEST['account_id'] === '')
        {
            $userAccountIds = UserAccount::getCurrentUserPlatformAccountIds(static::PLATFORM_CODE, 1);
            $query->andWhere(['in', 'account_id', $userAccountIds]);
        }
//var_dump($_REQUEST);exit;
        (new static())->setFilterOptions($query, $_REQUEST);
        
        $res = $query
//            ->createCommand()->getRawSql();var_dump($res);exit;
            ->all();
        $tagCounts = [];
        if (!empty($res))
        {
            foreach ($res as $row)
            {
                $tags[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'count' => $row['count'],
                ];
            }
//                $tagCounts[$row['id']] = $row['count'];
        }
        $allCount = 0;
//        foreach ($tagList as $tag)
//        {
//            $count = 0;
//            if (array_key_exists($tag->id, $tagCounts))
//                $count = $tagCounts[$tag->id];
//            $tags[] = [
//                'id' => $tag->id,
//                'name' => $tag->tag_name,
//                'count' => $count,
//            ];
//        }
        return $tags;
    }

    /*
     * @desc 统计用户管理的帐号未处理的邮件数量
     * @param string platformCode
     **/
    public static function getAccountEmail($platformCode)
    {
        $user_id = \Yii::$app->user->identity->id;

        // 查询用户管理的帐号id、name
        $accountInfo = new Query();
        $userAccountInfos = $accountInfo->select('t.id,t.account_short_name as name')
            ->from(Account::tableName().' as t')
            ->innerJoin(UserAccount::tableName().' as t1','t1.account_id = t.id and t1.user_id = '.$user_id)
            ->where(['t.platform_code'=>$platformCode,'t.status'=>1])
            ->all();

        $userAccountIds = [];
        foreach($userAccountInfos as $key => $userAccountInfo)
        {
            $userAccountIds[] = $userAccountInfo['id'];
        }
        $userAccountInfos = array_column($userAccountInfos,'name','id');

        $query = new Query();

//        $query->select('t.id,t.account_name as name,count(t1.id) as count')
//            ->from(Account::tableName().' as t')
//            ->innerjoin(self::tableName().' as t1','t1.account_id = t.id')
//            ->where(['t.platform_code'=>$platformCode])
//            ->groupBy('t.id');

        $query->select('t1.id,t1.account_name as name,count(t.id) as count')
            ->from(self::tableName().' as t')
            ->innerjoin(Account::tableName().' as t1',' t1.id =t.account_id')
            ->where(['t1.platform_code'=>$platformCode])
            ->groupBy('t1.id');

        if (isset($_REQUEST['tag_id']) && $_REQUEST['tag_id'] != '')
        {
//            $query->leftJoin(MailSubjectTag::tableName().' as t2','t2.subject_id = t1.id and t2.tag_id = :tag_id',[':tag_id'=>$_REQUEST['tag_id']]);
            $query->innerJoin(MailSubjectTag::tableName().' as t2','t2.subject_id = t.id')->andWhere(['t2.tag_id'=>$_REQUEST['tag_id']]);
        }

        (new static())->setFilterOptions($query, $_REQUEST,0);

        $query->andWhere(['in', 't1.id', $userAccountIds]);

        $array = $query->all();
//               $sql =  $query->createCommand()->getRawSql();
//               echo $sql;die;
//var_dump($array);exit;
        $accountCount = [];
        if (!empty($array))
        {
            foreach ($array as $row)
                $accountCount[$row['id']] = $row['count'];
        }
        $account_email = [];
//        var_dump($accountCount);exit;
        foreach ($userAccountInfos as $key => $userAccountInfo)
        {
            $count = 0;
            if (array_key_exists($key, $accountCount))
                $count = $accountCount[$key];
            $account_email[$userAccountInfo] = [
                'id' => $key,
                'name' => $userAccountInfo,
                'count' => $count,
            ];
        }
        ksort($account_email);
//        uasort($account_email,function($x,$y){return strcasecmp($x['name'],$y['name']);});
        return $account_email;
    }
    
    /**
     * @desc 获取待匹配标签的消息列表
     * @param number $limit
     */
    public function getWattingMatchTagList($limit = 1000) {
        $query = self::find();
        return $query->from(static::tableName())
            ->where('tag_match_flag <> 1')
            ->limit($limit)
            ->orderBy(['id' => SORT_ASC])
            ->all();
    }
    
    /**
     * @desc 匹配标签
     * @param unknown $inbox
     * @return boolean
     */
    public function matchTags($inbox)
    {
        $matchClass = new \stdClass();
        $matchClass->content = $inbox->getContent();
        $matchClass->subject = $inbox->getSubject();
        $matchClass->platform_code = static::PLATFORM_CODE;
        $matchClass->account_id = $inbox->getAccountId();
        $matchClass->order_id = $inbox->getOrderId();
        $rule = new Rule();
        $tagIds = $rule->getTagIdByCondition($matchClass);
        if (empty($tagIds))
            return true;
        $tagIds = explode(',', $tagIds);
        //删除已经关联的标签
        MailSubjectTag::deleteMialTags(static::PLATFORM_CODE, $inbox->inbox_subject_id);
        $flag = MailSubjectTag::saveMailTags(static::PLATFORM_CODE, $inbox->inbox_subject_id, $tagIds);
        if (!$flag)
            throw new \Exception('Save Mail Tags Failed');
        return true;
    }



    /**
     * @desc 匹配标签,打标签
     * @param unknown $inbox
     * @return boolean
     */
    public function matchTagsPlat($inbox)
    {
        $matchClass                = new \stdClass();
        $matchClass->platform_code = Platform::PLATFORM_CODE_EB;
        $matchClass->account_id    = $inbox->account_id;
        $matchClass->order_id      = $inbox->order_id;
        $rule                      = new Rule();
        $tagIds                    = $rule->getTagIdByCondition($matchClass);
        if (empty($tagIds))
            return true;
        $tagIds = explode(',', $tagIds);
        //删除已经关联的标签
        MailSubjectTag::deleteMialTags($matchClass->platform_code, $inbox->id);
        $flag = MailSubjectTag::saveMailTags($matchClass->platform_code, $inbox->id, $tagIds);
        if (!$flag)
            throw new \Exception('Save Mail Tags Failed');
        return true;
    }

    /** 
     * @desc 匹配模板
     * @param object $inbox 消息模型对象
     * @return boolean
     */
    public function matchTemplates($inbox)
    {
        $matchClass = new \stdClass();
        $matchClass->content = $inbox->getContent();
        $matchClass->subject = $inbox->getSubject();
        $matchClass->platform_code = static::PLATFORM_CODE;
        $matchClass->account_id = $inbox->getAccountId();
        $matchClass->order_id = $inbox->getOrderId();
        $rule = new Rule();
        $templateId = $rule->getMailTemplateIdByCondition($matchClass);
        //没有匹配到标签id
        if (empty($templateId)) {
            return true;
        }
        //匹配到了标签下面就是对模板，使用模板进行回复
        /** 1.获取模板内容  **/
        $templateInfo = MailTemplate::findById($templateId);
        if (empty($templateInfo))
            return true;
        $templateTitle = $templateInfo->template_title;
        $templateContent = $templateInfo->template_content;
        /** 2.替换模板占位符 **/
        //$matchClass->order_id = '500193669540552';
        $mailmodel  = New MailTemplateStrReplacement();
        $match_arr   = $mailmodel->circlematch($templateContent);
        $match_value = $mailmodel->replace_arr_value($match_arr, $matchClass->platform_code, $matchClass->order_id,$inbox->item_id);
        $content = $mailmodel->replace_content_str($match_value,$templateContent);
        $match_arr   = $mailmodel->circlematch($templateTitle);
        $match_value = $mailmodel->replace_arr_value($match_arr, $matchClass->platform_code, $matchClass->order_id);
        $title = $mailmodel->replace_content_str($match_value,$templateTitle);
        /** 3.用模板内容回复 **/
        $replyData = [
            'subject' => $title,
            'content' => $content,
            'reply_by' => User::SYSTEM_USER,
        ];
        $reply = Reply::addReply($matchClass->platform_code, $inbox, $replyData);
        if (empty($reply))
            throw new \Exception('Save Reply Failed');
        /** 4.将回复保存到发件箱 **/
        $modelOutBox = new MailOutbox();
        $attributes = [
            'inbox_id' => $inbox->id,
            'platform_code' => $matchClass->platform_code,
            'reply_id' => $reply->id,
            'account_id' => $reply->account_id,
            'content' => $content,
            'subject' => $title,
            'send_params' => $reply->getSendParams($inbox),
            'send_status' => MailOutbox::SEND_STATUS_WAITTING,
        ];
        $modelOutBox->setAttributes($attributes);
        if (!$modelOutBox->save())
            throw new \Exception('Save Mail Outinbox Failed');
        /** 5.将消息标记成已回复 **/
        $this->is_replied = self::REPLY_YES;
        $flag = $this->save(false);
        if (!$flag)
            throw new \Exception('Set InboxSubject Is Replied Failed');
        return true;
    }
    
    /**
     * @desc 设置回复状态
     * @param unknown $inboxId
     * @param unknown $replyStatus
     * @return \yii\db\int
     */
    public function setReplyStatus($inboxId, $replyStatus)
    {
        return self::updateAll(['is_replied' => $replyStatus], 'id = :id', ['id' => $inboxId]);
    }
    
    public static function getNextWattingProcessInbox()
    {
        $query = static::find();
        $query->select("*")
            ->where(['is_read' => self::READ_STATUS_NO_READ]);
        $userAccountIds = UserAccount::getCurrentUserPlatformAccountIds(static::PLATFORM_CODE);
        if (!empty($userAccountIds) && is_array($userAccountIds))
            $query->andWhere(['in', 'account_id', $userAccountIds]);
        $query->orderBy(['create_time' => SORT_ASC])
            ->limit(1);
        return $query->one();
    }
    
    /**
     * @desc 账号列表
     * @param string $status
     * @return unknown
     */
    public static function getAccountList($status = null){
        $accountList = Account::getCurrentUserPlatformAccountList(static::PLATFORM_CODE, $status);
        if(!empty($accountList)){
            foreach ($accountList as $value){
                $list[$value->attributes['id']] = $value->attributes['account_name'];
            }
        }
        return $list;
    }
    
    /**
     * @desc 获取下一个未处理主题
     * @return mixed|boolean
     */
    public static function getNextNoProcessId()
    {
        $noProcessIds = static::getNoProcessSubjectIds();
        $session = \Yii::$app->session;
        $sessionKey = static::PLATFORM_CODE . '_INBOX_SUBJECT_PROCESSED_LIST';
        $processedList = $session->get($sessionKey);
        if (empty($processedList))
            $processedList = [];
        while ($nextInboxId = current($noProcessIds))
        {
            next($noProcessIds);
            if (!in_array($nextInboxId, $processedList))
                return $nextInboxId;
        }
        return false;
    }
    
    /**
     * @desc 将主题id添加到处理列表
     * @param unknown $inboxId
     * @return boolean
     */
    public static function pushProccessedList($inboxId)
    {
        $sessionKey = static::PLATFORM_CODE . '_INBOX_SUBJECT_PROCESSED_LIST';
        $session = \Yii::$app->session;
        $processedList = $session->get($sessionKey);
        if (empty($processedList))
            $processedList = [];
        if (!in_array($inboxId, $processedList))
            $processedList[] = $inboxId;
        $session->set($sessionKey, $processedList);
        return true;
    }
    
    /**
     * @desc 清除主题处理列表
     * @return boolean
     */
    public static function destroyProcessedList()
    {
        $sessionKey = static::PLATFORM_CODE . '_INBOX_SUBJECT_PROCESSED_LIST';
        $session = \Yii::$app->session;
        $session->remove($sessionKey);
        return true;
    }
    
    /**
     * @desc 获取所有未处理主题ids
     */
    public static function getNoProcessSubjectIds()
    {
        $queryParams = (new static)->getSearchQuerySession();
//        $currentUserAccountId = UserAccount::getCurrentUserPlatformAccountIds(static::PLATFORM_CODE);
//        if (!empty($currentUserAccountId))
    //$queryParams['query']->andWhere(['account_id' => $currentUserAccountId]);
    //$query = new \yii\db\Query();
    //$query->orderBy([null]);
        if (!empty($queryParams['sort']))
            $queryParams['query']->addOrderBy($queryParams['sort']->getOrders());
        //$queryParams['query']->select = ['id'];
        $queryParams['query']->limit(200);
        //echo $queryParams['query']->createCommand()->getRawSql();exit;
        return $queryParams['query']->column();
    }

    /**
     * @desc 获取所有未处理主题ids
     */
    public static function getSubjectQuery()
    {
        $queryParams = (new static)->getSearchQuerySession();
        if (!empty($queryParams['sort']))
            $queryParams['query']->addOrderBy($queryParams['sort']->getOrders());
        return $queryParams;
    }
}
