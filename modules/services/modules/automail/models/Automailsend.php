<?php

namespace app\modules\services\modules\automail\models;

use app\modules\accounts\models\Platform;
use app\modules\mails\models\AmazonInbox;
use app\modules\mails\models\AmazonReply;
use app\modules\mails\models\CdiscountInbox;
use app\modules\mails\models\EbayInbox;
use app\modules\mails\models\WalmartInbox;
use app\modules\orders\models\OrderKefu;
use app\modules\systems\models\MailAutoManage;
use app\modules\mails\models\MailOutbox;
use app\modules\orders\models\OrderDetail;
use wish\models\WishInbox;
use app\modules\mails\models\AliexpressInbox;
use app\modules\mails\models\AliexpressReply;
use app\modules\mails\models\MailAutoOutBox;
use app\modules\orders\models\Logistic;
use app\modules\systems\models\Country;

class Automailsend
{

    /**
     * 获取amazon ebay wish walmrat cd平台匹配规则邮件
     * @param $parameter
     * @throws \yii\db\Exception
     */
    public static function getPlatformmail($parameter)
    {
        if (empty($parameter['send_time'])) {
            die('发送时间必填');
        }
        
        $send_time  = intval($parameter['send_time']) * 3600;
        $begin_date = date('Y-m-d H:i:s', time() - $send_time);
        
        //echo $send_time.'----'.$begin_date;die;
        switch ($parameter['platform_code']) {
            case Platform::PLATFORM_CODE_AMAZON:
                $inbox_model = AmazonInbox::find()
                    ->Where(['is_replied' => 0])
                    ->andWhere('now() > date_add(receive_date, interval '.$parameter['send_time'].' hour)');
                    //->andWhere(['<=', 'receive_date', $begin_date]);
                break;
            case Platform::PLATFORM_CODE_EB:
                $inbox_model = EbayInbox::find()
                    ->Where(['is_replied' => 0])
                    ->andWhere(['<=', 'receive_date', $begin_date]);
                break;
            case Platform::PLATFORM_CODE_WISH:
                $inbox_model = WishInbox::find()->Where(['is_replied' => 0])
                    ->andWhere(['<=', 'receive_date', $begin_date]);

                break;
            case Platform::PLATFORM_CODE_WALMART:
                $inbox_model = WalmartInbox::find()
                    ->Where(['is_replied' => 0])
                    ->andWhere(['<=', 'receive_date', $begin_date]);

                break;
            case Platform::PLATFORM_CODE_CDISCOUNT:
                $inbox_model = CdiscountInbox::find()
                    ->Where(['is_replied' => 0])
                    ->andWhere(['<=', 'receive_date', $begin_date]);
                break;
            case Platform::PLATFORM_CODE_ALI:
                $inbox_model = AliexpressInbox::find()
                    ->select('t.*, t1.reply_content, t1.message_type, t1.type_id, t1.reply_by')
                    ->from('{{%aliexpress_inbox}} as t')
                    ->join('LEFT JOIN', '{{%aliexpress_reply}} t1','t.channel_id = t1.channel_id')
                    ->Where(['t.is_replied' => 0])
                    ->andWhere('now() > date_add(t.receive_date, interval '.$parameter['send_time'].' hour)');
                break;
        }
      if($parameter['platform_code'] == 'ALI'){

            //账号
          $account_ids = json_decode($parameter['account_id']);
          if (!empty($account_ids)) {
              if (!empty($account_ids)) {
                  $inbox_model->andWhere(['in', 't.account_id', $account_ids]);
              }
          }

          //邮件正文
          if (!empty($parameter['subject_body_content'])) {
              $rsubject_body_content = explode('<br />
', $parameter['subject_body_content']);
              if ($parameter['subject_body_type'] == 1) {
                  $inbox_model->andWhere(['like', 't1.reply_content', $rsubject_body_content]);
              } else {
                  $inbox_model->andWhere(['not like', 't1.reply_content', $rsubject_body_content]);
              }
          }
          //收件时间
          if (!empty($parameter['receive_time'])) {
              $nowdate = time() - ($parameter['receive_time'] * 60 * 60);
              $inbox_model->andWhere(['>', 't.receive_date', $nowdate]);
          }

          // 客户id
          if (!empty($parameter['buyer_id_content'])) {
              $buyer_id_content = explode('<br />
', $parameter['buyer_id_content']);
              if ($parameter['buyer_id_type'] == 1) {
                  $inbox_model->andWhere(['in', 't1.reply_by', $buyer_id_content]);
              } else {
                  $inbox_model->andWhere(['not in', 't1.reply_by', $buyer_id_content]);
              }
          }

          //平台订单号
          if (!empty($parameter['platform_order_id_content'])) {
              $platform_order_id_content = explode('<br />
', trim($parameter['platform_order_id_content']));
              if ($parameter['platform_order_id_type'] == 1) {
                  $inbox_model->andWhere(['in', 't1.type_id', $platform_order_id_content]);
              } else {
                  $inbox_model->andWhere(['not in', 't1.type_id', $platform_order_id_content]);
              }
          }

      }else{
          //渠道来源、账号、站点（如果是亚马逊就验证站点）
          $account_ids = json_decode($parameter['account_id']);
          if (!empty($account_ids)) {
              if (!empty($account_ids)) {
                  $inbox_model->andWhere(['in', 'account_id', $account_ids]);
              }
          }
          //发件人
          if (!empty($parameter['sender_content'])) {
              $receive_emails = explode('<br />
', $parameter['sender_content']);
              $inbox_model->andWhere(['=', 'mail_type', 2]);
              //模糊匹配
              foreach ($receive_emails as $v) {
                  if ($parameter['sender_type'] == 1) {
                      $inbox_model->andWhere([
                          'or',
                          ['like', 'sender_email', '%' . $v, false],
                      ]);
                  } else {
                      $inbox_model->andWhere([
                          'or',
                          ['not like', 'sender_email', '%' . $v, false],
                      ]);
                  }
              }
          }
          //邮件主题
          if (!empty($parameter['subject_content'])) {
              $subject_content = explode('<br />
', $parameter['subject_content']);
              if ($parameter['subject_type'] == 1) {
                  $inbox_model->andWhere(['like', 'subject', $subject_content]);
              } else {
                  $inbox_model->andWhere(['not like', 'subject', $subject_content]);
              }
          }
          //邮件正文
          if (!empty($parameter['subject_body_content'])) {
              $rsubject_body_content = explode('<br />
', $parameter['subject_body_content']);
              if ($parameter['subject_body_type'] == 1) {
                  $inbox_model->andWhere(['like', 'body', $rsubject_body_content]);
              } else {
                  $inbox_model->andWhere(['not like', 'body', $rsubject_body_content]);
              }
          }
          //收件时间
          if (!empty($parameter['receive_time'])) {
              $nowdate = time() - ($parameter['receive_time'] * 60 * 60);
              $inbox_model->andWhere(['>', 'receive_date', $nowdate]);
          }
          // 客户id
          if (!empty($parameter['buyer_id_content'])) {
              $buyer_id_content = explode('<br />
', $parameter['buyer_id_content']);
              if ($parameter['buyer_id_type'] == 1) {
                  $inbox_model->andWhere(['in', 'sender', $buyer_id_content]);
              } else {
                  $inbox_model->andWhere(['not in', 'sender', $buyer_id_content]);
              }
          }
          //邮箱类型
          if (!empty($parameter['customer_email_content'])) {
              $customer_email_content = explode('<br />
', $parameter['customer_email_content']);
              foreach ($customer_email_content as $v1) {
                  if ($parameter['customer_email_type'] == 1) {
                      $inbox_model->andWhere([
                          'or',
                          ['like', 'receive_email', '%' . $v1, false],
                      ]);
                  } else {
                      $inbox_model->andWhere([
                          'or',
                          ['not like', 'receive_email', '%' . $v1, false],
                      ]);
                  }
              }
          }
          //平台订单号
          if (!empty($parameter['platform_order_id_content'])) {
              $platform_order_id_content = explode('<br />
', trim($parameter['platform_order_id_content']));
              if ($parameter['platform_order_id_type'] == 1) {
                  $inbox_model->andWhere(['in', 'order_id', $platform_order_id_content]);
              } else {
                  $inbox_model->andWhere(['not in', 'order_id', $platform_order_id_content]);
              }
          }
      }



        $inbox_lists = $inbox_model->asArray()->all();
       // echo $inbox_model->createCommand()->getRawSql();die;

        $platform_code = $parameter['platform_code'];
        if (!empty($inbox_lists)) {
            foreach ($inbox_lists as $inbox_list) {
                //查询订单表数据
                switch ($platform_code) {
                    case 'EB':
                        $orders = OrderKefu::model('order_ebay');
                        break;
                    case 'ALI':
                        $orders = OrderKefu::model('order_aliexpress');
                        break;
                    case 'AMAZON':
                        $orders = OrderKefu::model('order_amazon');
                        break;
                    case 'WISH':
                        $orders = OrderKefu::model('order_wish');
                        break;
                    default:
                        $orders = OrderKefu::model('order_other');
                        break;
                }
                if($platform_code == 'ALI'){
                    $orders = $orders->where(['platform_order_id' => $inbox_list['type_id']])->one();
                }else{
                    $orders = $orders->where(['platform_order_id' => $inbox_list['order_id']])->one();
                }
                if (empty($orders)) {
                    //查询copy表数据
                    switch ($platform_code) {
                        case 'EB':
                            $orders = OrderKefu::model('order_ebay_copy');
                            break;
                        case 'ALI':
                            $orders = OrderKefu::model('order_aliexpress_copy');
                            break;
                        case 'AMAZON':
                            $orders = OrderKefu::model('order_amazon_copy');
                            break;
                        case 'WISH':
                            $orders = OrderKefu::model('order_wish_copy');
                            break;
                        default:
                            $orders = OrderKefu::model('order_other_copy');
                            break;
                    }
                    if($platform_code == 'ALI'){
                        $orders = $orders->where(['platform_order_id' => $inbox_list['type_id']])->one();
                    }else{
                        $orders = $orders->where(['platform_order_id' => $inbox_list['order_id']])->one();
                    }
                }

                if (!empty($orders)) {
                    //查询订单详情数据
                    $orderdetail = OrderDetail::getOrderdetail($parameter['platform_code'], $orders->order_id);
                    if (!empty($parameter['order_minimum_money']) && !empty($parameter['order_highest_money'])) {
                        if ($orders->total_price < $parameter['order_minimum_money'] && $orders->total_price > $parameter['order_highest_money'])
                            continue;
                    }

                    if (!empty($parameter['order_minimum_money']) && empty($parameter['order_highest_money'])) {
                        if ($orders->total_price < $parameter['order_minimum_money'])
                            continue;
                    }

                    if (empty($parameter['order_minimum_money']) && !empty($parameter['order_highest_money'])) {
                        if ($orders->total_price > $parameter['order_highest_money'])
                            continue;
                    }

                    //国家
                    if (!empty($parameter['country_content']) && $parameter['country_content'] != 'null') {
                        $country_content = explode('<br />
', $parameter['country_content']);
                        if ($parameter['country_type'] == 1) {
                            if (!in_array($orders->ship_country, $country_content))
                                continue;
                        } else {
                            if (in_array($orders->ship_country, $country_content))
                                continue;
                        }
                    }

                    //系统订单号
                    if (!empty($parameter['order_id_content'])) {
                        $order_id_content = explode('<br />
', $parameter['order_id_content']);
                        if ($parameter['order_id_type'] == 1) {
                            if (!in_array($orders->order_id, $order_id_content))
                                continue;
                        } else {
                            if (in_array($orders->order_id, $order_id_content))
                                continue;
                        }

                    }
                    $next   = true;
                    $itemid = true;
                    if (!empty($orderdetail)) {
                        $title_a = [];
                        $asin_a  = [];
                        foreach ($orderdetail as $detail) {
                            $title_a[] = $detail['title'] . '(item_number:' . $detail['item_id'] . ')';
                            if($platform_code != 'ALI'){
                                $asin_a[]  = $detail['asinval'];
                            }
                            //sku
                            if (!empty($parameter['erp_sku_content'])) {
                                $erp_sku_content = json_decode(trim($parameter['erp_sku_content']));
                                if (!empty($erp_sku_content)) {
                                    if ($parameter['erp_sku_type'] == 1) {
                                        if (in_array($detail['sku'], $erp_sku_content)) {
                                            $next = false;
                                        }
                                    } else {
                                        if (!in_array($detail['sku'], $erp_sku_content)) {
                                            $next = false;
                                        }
                                    }
                                }
                            }
                            //itemid
                            if (!empty($parameter['product_id_content'])) {
                                $product_id_content = explode('<br />
', $parameter['product_id_content']);
                                if ($parameter['product_id_type'] == 1) {
                                    if (in_array($detail['item_id'], $product_id_content)) {
                                        $itemid = false;
                                    }
                                } else {
                                    if (!in_array($detail['item_id'], $product_id_content)) {
                                        $itemid = false;
                                    }
                                }
                            }
                        }
                        $title = $title_a ? implode("、", $title_a) : '';
                        $asin  = $asin_a ? implode("、", $asin_a) : '';
                        if (!empty($parameter['erp_sku_content'])) {
                            if ($next) {
                                continue;
                            }
                        }
                        if (!empty($parameter['product_id_content'])) {
                            if ($itemid) {
                                continue;
                            }
                        }
                    }
                }


                if($platform_code == 'ALI'){
                    /** 4.将回复保存到发件箱**/
                    $transaction = MailAutoOutBox::getDb()->beginTransaction();
                    $modelOutBox = new MailAutoOutBox();
                    $send_params = array(
                        "account_id" => $inbox_list['account_id'],
                        "channel_id" => $inbox_list['channel_id'],
                        "msg_sources" => $inbox_list['msg_sources'],
                        "buyer_id" => $inbox_list['other_login_id'],
                    );
                    $countryList = Country::getCodeNamePairsList('en_name');
                    $buyer_id    = !empty($orders->buyer_id) ? $orders->buyer_id : '';
                    $log_info = Logistic:: getSendWayEng($orders->real_ship_code);
                    if (empty($log_info)) {
                        $logistic = Logistic:: getSendWayEng($orders->ship_code);
                    }else {
                        $logistic = '';
                    }
                    if (!empty($orders->track_number)) {
                        $track        = 'http://www.17track.net/zh-cn/track?nums=' . $orders->track_number;
                        $track_number = $orders->track_number;
                    } else {
                        $track        = '';
                        $track_number = '';
                    }
                    if (!empty($orders->ship_country)) {
                        $country      = $orders->ship_country;
                        $ship_country = array_key_exists($country, $countryList) ? $countryList[$country] : '';
                    } else {
                        $ship_country = '';
                    }

                    $sending_template = str_replace('{$buyer_id}', $buyer_id, $parameter['sending_template']);
                    $sending_template = str_replace('{$track_number}', $track_number, $sending_template);
                    $sending_template = str_replace('{$logistic}', $logistic, $sending_template);
                    $sending_template = str_replace('{$track}', $track, $sending_template);
                    $sending_template = str_replace('{$ship_country}', $ship_country, $sending_template);

                    $attributes = [
                        'platform_code'     => $parameter['platform_code'],
                        'reply_id'          => $inbox_list['last_message_id'],
                        'inbox_id'          => $inbox_list['id'],
                        'account_id'        => $inbox_list['account_id'],
                        'content'           => $sending_template,
                        'subject'           => '',
                        'send_params'       => trim(json_encode($send_params)),
                        'send_status'       => 0,
                        'create_by'         => 'system',
                        'modify_by'         => 'system',
                        'order_id'          => $orders['order_id'],
                        'platform_order_id' => $orders['platform_order_id'],
                        'send_rule_id'      => $parameter['id'],
                        'buyer_id'          => $orders['buyer_id'],
                        'receive_email'     => '',
                    ];
                    $modelOutBox->setAttributes($attributes);
                    $flag = $modelOutBox->save();

                }else{
                    /** 4.将回复保存到发件箱**/
                    $transaction = MailOutbox::getDb()->beginTransaction();

                    $modelOutBox = new MailOutbox();
                    //不同的平台不同的参数
                    $send_params = array("sender_email" => $inbox_list['receive_email'], "receive_email" => $inbox_list['sender_email'], 'attachments' => []);

                    $customer_name    = !empty($orders->buyer_id) ? $orders->buyer_id : '';
                    $customer_address = !empty($orders->ship_name) ? $orders->ship_name : '';
                    $platform_order   = !empty($orders->platform_order_id) ? $orders->platform_order_id : '';
                    $asin             = !empty($asin) ? $asin : '';
                    $product_name     = $title;
                    $sending_template = str_replace('{buyer_id}', $customer_name, $parameter['sending_template']);
                    $sending_template = str_replace('{$customer_name}', $customer_address, $sending_template);
                    $sending_template = str_replace('{$platform_order}', $platform_order, $sending_template);
                    $sending_template = str_replace('{$asin}', $asin, $sending_template);
                    $sending_template = str_replace('{$product_name}', $product_name, $sending_template);

                    $attributes = [
                        'platform_code'     => $parameter['platform_code'],
                        'reply_id'          => $inbox_list['mid'],
                        'inbox_id'          => $inbox_list['id'],
                        'account_id'        => $inbox_list['account_id'],
                        'content'           => $sending_template,
                        'subject'           => $inbox_list['subject'],
                        'send_params'       => trim(json_encode($send_params)),
                        'send_status'       => 0,
                        'create_by'         => 'system',
                        'modify_by'         => 'system',
                        'order_id'          => $inbox_list['order_id'],
                        'platform_order_id' => $inbox_list['platform_order_id'],
                        'send_rule_id'      => $parameter['id'],
                        'buyer_id'          => $inbox_list['buyer_id'],
                        'receive_email'     => $inbox_list['receive_email'],
                    ];
                    $modelOutBox->setAttributes($attributes);
                    $flag = $modelOutBox->save();
                }

                if (!$flag) {
                    $transaction->rollBack();
                } else {
                    // 回复表id加入到自动回信表
                    echo '<br>' . 'inbox_id' . $inbox_list['id'] . '--' . 'send_rule_id' . $parameter['id'];
                    $email_flag = false;
                    switch ($platform_code) {
                        case Platform::PLATFORM_CODE_AMAZON:
                            $email_flag = AmazonInbox::updateAll(['is_replied' => 1], 'id=:id', [':id' => $inbox_list['id']]);
                            break;
                        case Platform::PLATFORM_CODE_EB:
                            $email_flag = EbayInbox::updateAll(['is_replied' => 1], 'id=:id', [':id' => $inbox_list['id']]);
                            break;
                        case Platform::PLATFORM_CODE_WISH:
                            $email_flag = WishInbox::updateAll(['is_replied' => 1], 'id=:id', [':id' => $inbox_list['id']]);
                            break;
                        case Platform::PLATFORM_CODE_WALMART:
                            $email_flag = WalmartInbox::updateAll(['is_replied' => 1], 'id=:id', [':id' => $inbox_list['id']]);
                            break;
                        case Platform::PLATFORM_CODE_CDISCOUNT:
                            $email_flag = CdiscountInbox::updateAll(['is_replied' => 1], 'id=:id', [':id' => $inbox_list['id']]);
                            break;
                        case Platform::PLATFORM_CODE_ALI:
                            $email_flag = AliexpressInbox::updateAll(['is_replied' => 1], 'id=:id', [':id' => $inbox_list['id']]);
                            break;
                    }
                    if ($email_flag) {
                        $send_mail_flag = MailAutoManage::updateAllCounters(['sendmail' => 1], ['id' => $parameter['id']]);
                        if (!$send_mail_flag) {
                            $transaction->rollBack();
                        } else {
                            $transaction->commit();
//                            var_dump($email_flag,$send_mail_flag);die;
                        }
                    } else {
                        $transaction->rollBack();
                    }
                }
            }
        }
    }
}
