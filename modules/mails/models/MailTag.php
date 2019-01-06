<?php
/**
 * @desc 邮件标签映射表
 * @author Fun
 */
namespace app\modules\mails\models;
use app\modules\systems\models\Tag;
use app\modules\accounts\models\Platform;

class MailTag extends MailsModel
{   
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%mail_tag}}';
    }
    
    /**
     * @desc 保存消息标签关联
     * @param unknown $platformCode
     * @param unknown $inboxId
     * @param unknown $tagIds
     */
    public static function saveMailTags($platformCode, $inboxId, $tagIds)
    {
        $dbCommand = self::getDb()->createCommand();
        $columns = ['platform_code', 'tag_id', 'inbox_id'];
        $rows = [];
        foreach ($tagIds as $tagId)
            $rows[] = [$platformCode, $tagId, $inboxId];
        return $dbCommand->batchInsert(self::tableName(), $columns, $rows)
                    ->execute();
    }
    
    /**
     * @desc 删除消息标签关联
     * @param unknown $platformCode
     * @param unknown $inboxId
     * @return \yii\db\int
     */
    public static function deleteMialTags($platformCode, $inboxId)
    {
        return self::deleteAll('platform_code = :platform_code and inbox_id = :inbox_id', [
            'platform_code' => $platformCode,
            'inbox_id' => $inboxId,
        ]);
    }

    /**
     * 针对自动移动邮件到指定标签功能批量或者单条的维护消息标签关联
     * @param string $platform_code 平台code
     * @param array  $tag_id 标签id
     * @param array  $inbox_id 消息id
     */
    public static function batch_save_mail_tags($platform_code,$tag_id,$inbox_id)
    {   
        foreach ($inbox_id as $key_inbox => $value_inbox) 
        {
            foreach ($tag_id as $key_tag => $value_tag) 
            {   
                $exist = self::findOne(['platform_code'=>$platform_code,'inbox_id'=>$value_inbox,'tag_id'=>$value_tag]);
                if ($exist !== null) {
                    continue;
                }

                $model = new self();
                $model->platform_code = $platform_code;
                $model->inbox_id = $value_inbox;
                $model->tag_id = $value_tag;

                //存取数据
                if (!$model->save()) {
                    return [false,current(current($model->getErrors()))];
                }
            }
        }

        return [true,'操作成功'];
    }
    /**
     * 获取指定平台和指定消息的标签id
     */
    public static function get_tag_ids_by_platformcode_and_inbox($platform_code,$inbox_id)
    {
        $query = new \yii\db\Query();
        $query ->from(self::tableName())->select('tag_id');

        //查找有效状态的标签
        if (is_array($inbox_id)) {
            $query->andWhere('platform_code = :platform_code', [':platform_code'=>$platform_code]);
            $query->andWhere(['in', 'inbox_id', $inbox_id]);
        } else {
             $query->andWhere('platform_code = :platform_code and inbox_id = :inbox_id', [
                 ':platform_code'=>$platform_code,
                 ':inbox_id' => (int)$inbox_id
             ]);
        }
        
        //获取数据
        $data = $query->all();
        
        if (empty($data)) {
            return [];
        }

        $tag_ids = [];

        foreach ($data as $key => $value) {
            $tag_ids[] = $value['tag_id'];
        }

        return array_unique($tag_ids);
    }

    /**
     * 获取指定平台存在的标签id
     */
    public static function get_tag_by_platformcode_and_subject($platform_code,$inbox_id,$tag_id)
    {
        $result = self::findOne(['platform_code'=>$platform_code,'inbox_id'=>$inbox_id,'tag_id'=>$tag_id]);

        if(!$result){
            return false;
        }
        return $result;
    }

    /**
     * 获取指定平台和消息的所有的标签
     */
    public static function get_tags_by_platformcode_and_inbox($platform_code,$inbox_id)
    {
        $tag_ids = self::get_tag_ids_by_platformcode_and_inbox($platform_code,$inbox_id);
        
        if (empty($tag_ids)) {
            return [];
        }

        //根据标签id获取标签名称数据
        $tag_data = Tag::getTagAsArray($platform_code,$tag_ids);

        return $tag_data;
    }
    /**
     * 移除指定平台和指定消息的的标签
     */
    public static function delete_mail_tag($platform_code, $inbox_id, $tag_ids)
    { 
        foreach ($tag_ids as $key => $value) {
            $result = self::deleteAll('platform_code = :platform_code and inbox_id = :inbox_id and tag_id = :tag_id',[
                ':platform_code' => $platform_code,
                ':inbox_id' => $inbox_id,
                ':tag_id' => $value,
            ]);

            if (!$result) {
                return false;
            }
        }

        return true;
    }
}
