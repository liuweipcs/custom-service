<?php

namespace app\modules\accounts\controllers;

use Yii;
use app\components\Controller;
use app\common\VHelper;
use app\modules\accounts\models\Account;
use app\modules\accounts\models\EbayAccountOverview;
use app\modules\accounts\models\UserAccount;
use app\modules\accounts\models\Platform;
use app\modules\mails\models\EbaySellerEdsShippingPolicy;
use app\modules\mails\models\EbaySellerEpacketShippingPolicy;
use app\modules\mails\models\EbaySellerLtnp;
use app\modules\mails\models\EbaySellerPgcTracking;
use app\modules\mails\models\EbaySellerQclist;
use app\modules\mails\models\EbaySellerSdWarehouse;
use app\modules\mails\models\EbaySellerShip;
use app\modules\mails\models\EbaySellerStandardsProfile;
use app\modules\mails\models\EbaySellerTci;
use app\modules\mails\models\EbaySellerAccountOverview;
use app\modules\mails\models\EbaySellerShipOld;
use app\modules\mails\models\EbaySellerSpeedpakList;
use app\modules\mails\models\EbaySellerSpeedpakMisuse;
use yii\db\Query;
use app\modules\mails\models\EbayListDownload;
use app\modules\mails\models\EbayMisuseDownload;
/**
 * ebay账号表现
 */
class EbayaccountoverviewController extends Controller
{
    /**
     * 列表
     */
    public function actionList()
    {
        $searchModel = new EbayAccountOverview();
        $params = Yii::$app->request->queryParams;
        $dataProvider = $searchModel->searchList($params);

        //默认一页显示20条记录
        $params['page_size'] = !empty($params['page_size']) ? $params['page_size'] : 20;

        return $this->renderList('list', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'params' => $params,
        ]);
    }

    /**
     * 显示账号表现详情
     */
    public function actionOverviewdetails()
    {
        $type = Yii::$app->request->get('type', '');
        $accountId = Yii::$app->request->get('account_id', 0);
        $id = Yii::$app->request->get('id', 0);

        if (empty($type)) {
            $this->_showMessage('账号表现类型为空', false);
        }

        if (empty($accountId)) {
            $this->_showMessage('账号ID不能为空', false);
        }

        //当前数据
        $data = [];
        //上周数据
        $preData = [];
        switch ($type) {
            case 'ltnp' :
                //综合表现
                $data = EbaySellerLtnp::find()
                    ->andWhere(['account_id' => $accountId])
                    ->orderBy('refreshed_date DESC')
                    ->asArray()
                    ->limit(1)
                    ->one();
                break;
            case 'ship' :
                //货运(1-8周)
                $data['1to8'] = EbaySellerShip::find()
                    ->andWhere(['account_id' => $accountId])
                    ->orderBy('refreshed_date DESC')
                    ->asArray()
                    ->offset(0)
                    ->limit(1)
                    ->one();

                $preData['1to8'] = EbaySellerShip::find()
                    ->andWhere(['account_id' => $accountId])
                    ->orderBy('refreshed_date DESC')
                    ->asArray()
                    ->offset(1)
                    ->limit(1)
                    ->one();

                //货运(5-12周)
                $data['5to12'] = EbaySellerShipOld::find()
                    ->andWhere(['account_id' => $accountId])
                    ->orderBy('refreshed_date DESC')
                    ->asArray()
                    ->offset(0)
                    ->limit(1)
                    ->one();

                $preData['5to12'] = EbaySellerShipOld::find()
                    ->andWhere(['account_id' => $accountId])
                    ->orderBy('refreshed_date DESC')
                    ->asArray()
                    ->offset(1)
                    ->limit(1)
                    ->one();

                break;
            case 'tci' :
                //非货运
                $data = EbaySellerTci::find()
                    ->andWhere(['account_id' => $accountId])
                    ->orderBy('refreshed_date DESC')
                    ->asArray()
                    ->offset(0)
                    ->limit(1)
                    ->one();

                $preData = EbaySellerTci::find()
                    ->andWhere(['account_id' => $accountId])
                    ->orderBy('refreshed_date DESC')
                    ->asArray()
                    ->offset(1)
                    ->limit(1)
                    ->one();

                break;
            case 'eds_shipping_policy' :
                //物流标准
                $data['eds'] = EbaySellerEdsShippingPolicy::find()
                    ->andWhere(['account_id' => $accountId])
                    ->orderBy('refreshed_date DESC')
                    ->asArray()
                    ->limit(1)
                    ->one();

                $data['epacket'] = EbaySellerEpacketShippingPolicy::find()
                    ->andWhere(['account_id' => $accountId])
                    ->orderBy('refreshed_date DESC')
                    ->asArray()
                    ->limit(1)
                    ->one();

                //SpeedPAK物流管理方案
                $data['speedpakList'] = EbaySellerSpeedpakList::find()
                    ->andWhere(['account_id' => $accountId])
                    ->orderBy('create_pst DESC')
                    ->asArray()
                    ->limit(1)
                    ->one();

                //卖家设置SpeedPAK物流选项
                $data['speedpakMisuse'] = EbaySellerSpeedpakMisuse::find()
                    ->andWhere(['account_id' => $accountId])
                    ->orderBy('create_pst DESC')
                    ->asArray()
                    ->limit(1)
                    ->one();

                break;
            case 'sd_warehouse' :
                //海外仓标准
                $data = EbaySellerSdWarehouse::find()
                    ->andWhere(['account_id' => $accountId])
                    ->orderBy('refreshed_date DESC')
                    ->asArray()
                    ->limit(1)
                    ->one();
                break;
            case 'pgc_tracking' :
                //商业计划追踪
                $data = EbaySellerPgcTracking::find()
                    ->andWhere(['account_id' => $accountId])
                    ->orderBy('refreshed_date DESC')
                    ->asArray()
                    ->limit(1)
                    ->one();
                break;
            case 'qclist':
                //数据最后更新时间
                $refreshedDate = EbaySellerQclist::find()
                    ->select('refreshed_date')
                    ->andWhere(['account_id' => $accountId])
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->scalar();

                if (!empty($refreshedDate)) {
                    $data = EbaySellerQclist::find()
                        ->andWhere(['account_id' => $accountId])
                        ->andWhere(['refreshed_date' => $refreshedDate])
                        ->orderBy('refreshed_date DESC')
                        ->asArray()
                        ->all();

                    if (!empty($data)) {
                        $itemIds = array_column($data, 'item_id');

                        $accountInfo = Account::findOne($accountId);
                        //获取刊登信息
                        $listing = (new Query())->from('{{%ebay_online_listing}}')
                            ->select('itemid, sku, title, seller_user, product_line, site, listing_status')
                            ->andWhere(['account_id' => $accountInfo->old_account_id])
                            ->andWhere(['in', 'itemid', $itemIds])
                            ->createCommand(Yii::$app->db_product)
                            ->queryAll();

                        if (!empty($listing)) {
                            $tmp = [];
                            foreach ($listing as $item) {
                                $tmp[$item['itemid']] = $item;
                            }
                            $listing = $tmp;
                        }

                        foreach ($data as &$item) {
                            $item['listing_status'] = array_key_exists($item['item_id'], $listing) ? $listing[$item['item_id']]['listing_status'] : '';
                        }
                        unset($item);
                    }

                }
                break;
            case 'bad_trade_rate':
                //不良交易率
                $data = EbaySellerStandardsProfile::find()->where(['id' => $id])->asArray()->one();

                break;
            case 'unresolve_dispute_rate':
                //未解决纠纷率
                $data = EbaySellerStandardsProfile::find()->where(['id' => $id])->asArray()->one();

                break;
            case 'transport_delay_rate':
                //运送延迟率
                $data = EbaySellerStandardsProfile::find()->where(['id' => $id])->asArray()->one();

                break;
            default:
                $this->_showMessage('没有该类型的账号表现数据', false);
                break;
        }

        $this->isPopup = true;
        return $this->render('overviewdetails', [
            'type' => $type,
            'data' => $data,
            'preData' => $preData,
        ]);
    }

    /**
     * 导出查询
     */
    protected function exportFilter(&$cond, $params)
    {
        //站点
        $standardsProfile = EbaySellerStandardsProfile::tableName();
        if (isset($params['program_status']) && $params['program_status'] != '') {
            if (!empty($params['filter_date'])) {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile} 
                                    WHERE account_id = a.id
                                    AND program = '{$params['program_status']}'
                                    AND DATE_FORMAT(evaluation_date, '%Y-%m-%d') = '{$params['filter_date']}'
                              )";
            } else {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile} 
                                    WHERE account_id = a.id
                                    AND program = '{$params['program_status']}'
                                    AND evaluation_date = (
                                       SELECT evaluation_date FROM {$standardsProfile}
                                       WHERE account_id = a.id AND program = '{$params['program_status']}'
                                       ORDER BY evaluation_date DESC
                                       LIMIT 1
                                    )
                              )";
            }
        }

        //当前账户等级
        if (isset($params['current_level_status']) && $params['current_level_status'] != '') {
            if (!empty($params['filter_date'])) {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND cycle_type = 'CURRENT'
                                    AND standards_level = '{$params['current_level_status']}'
                                    AND DATE_FORMAT(evaluation_date, '%Y-%m-%d') = '{$params['filter_date']}'
                              )";
            } else {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND cycle_type = 'CURRENT'
                                    AND standards_level = '{$params['current_level_status']}'
                                    AND evaluation_date = (
                                       SELECT evaluation_date FROM {$standardsProfile}
                                       WHERE account_id = a.id AND cycle_type = 'CURRENT'
                                       ORDER BY evaluation_date DESC
                                       LIMIT 1
                                    )
                              )";
            }
        }

        //预测账户等级
        if (isset($params['projected_level_status']) && $params['projected_level_status'] != '') {
            if (!empty($params['filter_date'])) {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND cycle_type = 'PROJECTED'
                                    AND standards_level = '{$params['projected_level_status']}'
                                    AND DATE_FORMAT(evaluation_date, '%Y-%m-%d') = '{$params['filter_date']}'
                              )";
            } else {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND cycle_type = 'PROJECTED'
                                    AND standards_level = '{$params['projected_level_status']}'
                                    AND evaluation_date = (
                                       SELECT evaluation_date FROM {$standardsProfile}
                                       WHERE account_id = a.id AND cycle_type = 'PROJECTED'
                                       ORDER BY evaluation_date DESC
                                       LIMIT 1
                                    )
                              )";
            }
        }

        //不良交易率
        if (!empty($params['bad_trade_rate_status']['low']) && !empty($params['bad_trade_rate_status']['high'])) {
            $low = floatval($params['bad_trade_rate_status']['low']);
            $high = floatval($params['bad_trade_rate_status']['high']);

            if (!empty($params['filter_date'])) {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND metric_key = 'DEFECTIVE_TRANSACTION_RATE'
                                    AND metric_value_scalar >= {$low}
                                    AND metric_value_scalar <= {$high}
                                    AND DATE_FORMAT(evaluation_date, '%Y-%m-%d') = '{$params['filter_date']}'
                              )";
            } else {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND metric_key = 'DEFECTIVE_TRANSACTION_RATE'
                                    AND metric_value_scalar >= {$low}
                                    AND metric_value_scalar <= {$high}
                                    AND evaluation_date = (
                                        SELECT evaluation_date FROM {$standardsProfile}
                                        WHERE account_id = a.id AND metric_key = 'DEFECTIVE_TRANSACTION_RATE'
                                        ORDER BY evaluation_date DESC
                                        LIMIT 1
                                    )
                              )";
            }

        } else if (!empty($params['bad_trade_rate_status']['low'])) {
            $low = floatval($params['bad_trade_rate_status']['low']);

            if (!empty($params['filter_date'])) {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND metric_key = 'DEFECTIVE_TRANSACTION_RATE'
                                    AND metric_value_scalar >= {$low}
                                    AND DATE_FORMAT(evaluation_date, '%Y-%m-%d') = '{$params['filter_date']}'
                              )";
            } else {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND metric_key = 'DEFECTIVE_TRANSACTION_RATE'
                                    AND metric_value_scalar >= {$low}
                                    AND evaluation_date = (
                                        SELECT evaluation_date FROM {$standardsProfile}
                                        WHERE account_id = a.id AND metric_key = 'DEFECTIVE_TRANSACTION_RATE'
                                        ORDER BY evaluation_date DESC
                                        LIMIT 1
                                    )
                              )";
            }

        } else if (!empty($params['bad_trade_rate_status']['high'])) {
            $high = floatval($params['bad_trade_rate_status']['high']);

            if (!empty($params['filter_date'])) {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND metric_key = 'DEFECTIVE_TRANSACTION_RATE'
                                    AND metric_value_scalar <= {$high}
                                    AND DATE_FORMAT(evaluation_date, '%Y-%m-%d') = '{$params['filter_date']}'
                              )";
            } else {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND metric_key = 'DEFECTIVE_TRANSACTION_RATE'
                                    AND metric_value_scalar <= {$high}
                                    AND evaluation_date = (
                                        SELECT evaluation_date FROM {$standardsProfile}
                                        WHERE account_id = a.id AND metric_key = 'DEFECTIVE_TRANSACTION_RATE'
                                        ORDER BY evaluation_date DESC
                                        LIMIT 1
                                    )
                              )";
            }
        }

        //未解决纠纷率
        if (!empty($params['unresolve_dispute_rate_status']['low']) && !empty($params['unresolve_dispute_rate_status']['high'])) {
            $low = floatval($params['unresolve_dispute_rate_status']['low']);
            $high = floatval($params['unresolve_dispute_rate_status']['high']);

            if (!empty($params['filter_date'])) {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND metric_key = 'CLAIMS_SAF_RATE'
                                    AND metric_value_scalar >= {$low}
                                    AND metric_value_scalar <= {$high}
                                    AND DATE_FORMAT(evaluation_date, '%Y-%m-%d') = '{$params['filter_date']}'
                              )";
            } else {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND metric_key = 'CLAIMS_SAF_RATE'
                                    AND metric_value_scalar >= {$low}
                                    AND metric_value_scalar <= {$high}
                                    AND evaluation_date = (
                                        SELECT evaluation_date FROM {$standardsProfile}
                                        WHERE account_id = a.id AND metric_key = 'CLAIMS_SAF_RATE'
                                        ORDER BY evaluation_date DESC
                                        LIMIT 1
                                    )
                              )";
            }

        } else if (!empty($params['unresolve_dispute_rate_status']['low'])) {
            $low = floatval($params['unresolve_dispute_rate_status']['low']);

            if (!empty($params['filter_date'])) {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND metric_key = 'CLAIMS_SAF_RATE'
                                    AND metric_value_scalar >= {$low}
                                    AND DATE_FORMAT(evaluation_date, '%Y-%m-%d') = '{$params['filter_date']}'
                              )";
            } else {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND metric_key = 'CLAIMS_SAF_RATE'
                                    AND metric_value_scalar >= {$low}
                                    AND evaluation_date = (
                                        SELECT evaluation_date FROM {$standardsProfile}
                                        WHERE account_id = a.id AND metric_key = 'CLAIMS_SAF_RATE'
                                        ORDER BY evaluation_date DESC
                                        LIMIT 1
                                    )
                              )";
            }

        } else if (!empty($params['unresolve_dispute_rate_status']['high'])) {
            $high = floatval($params['unresolve_dispute_rate_status']['high']);

            if (!empty($params['filter_date'])) {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND metric_key = 'CLAIMS_SAF_RATE'
                                    AND metric_value_scalar <= {$high}
                                    AND DATE_FORMAT(evaluation_date, '%Y-%m-%d') = '{$params['filter_date']}'
                              )";
            } else {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND metric_key = 'CLAIMS_SAF_RATE'
                                    AND metric_value_scalar <= {$high}
                                    AND evaluation_date = (
                                        SELECT evaluation_date FROM {$standardsProfile}
                                        WHERE account_id = a.id AND metric_key = 'CLAIMS_SAF_RATE'
                                        ORDER BY evaluation_date DESC
                                        LIMIT 1
                                    )
                              )";
            }
        }

        //运送延迟率
        if (!empty($params['transport_delay_rate_status']['low']) && !empty($params['transport_delay_rate_status']['high'])) {
            $low = floatval($params['transport_delay_rate_status']['low']);
            $high = floatval($params['transport_delay_rate_status']['high']);

            if (!empty($params['filter_date'])) {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND metric_key = 'SHIPPING_MISS_RATE'
                                    AND metric_value_scalar >= {$low}
                                    AND metric_value_scalar <= {$high}
                                    AND DATE_FORMAT(evaluation_date, '%Y-%m-%d') = '{$params['filter_date']}'
                              )";
            } else {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND metric_key = 'SHIPPING_MISS_RATE'
                                    AND metric_value_scalar >= {$low}
                                    AND metric_value_scalar <= {$high}
                                    AND evaluation_date = (
                                        SELECT evaluation_date FROM {$standardsProfile}
                                        WHERE account_id = a.id AND metric_key = 'SHIPPING_MISS_RATE'
                                        ORDER BY evaluation_date DESC
                                        LIMIT 1
                                    )
                              )";
            }

        } else if (!empty($params['transport_delay_rate_status']['low'])) {
            $low = floatval($params['transport_delay_rate_status']['low']);

            if (!empty($params['filter_date'])) {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND metric_key = 'SHIPPING_MISS_RATE'
                                    AND metric_value_scalar >= {$low}
                                    AND DATE_FORMAT(evaluation_date, '%Y-%m-%d') = '{$params['filter_date']}'
                              )";
            } else {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND metric_key = 'SHIPPING_MISS_RATE'
                                    AND metric_value_scalar >= {$low}
                                    AND evaluation_date = (
                                        SELECT evaluation_date FROM {$standardsProfile}
                                        WHERE account_id = a.id AND metric_key = 'SHIPPING_MISS_RATE'
                                        ORDER BY evaluation_date DESC
                                        LIMIT 1
                                    )
                              )";
            }

        } else if (!empty($params['transport_delay_rate_status']['high'])) {
            $high = floatval($params['transport_delay_rate_status']['high']);

            if (!empty($params['filter_date'])) {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND metric_key = 'SHIPPING_MISS_RATE'
                                    AND metric_value_scalar <= {$high}
                                    AND DATE_FORMAT(evaluation_date, '%Y-%m-%d') = '{$params['filter_date']}'
                              )";
            } else {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND metric_key = 'SHIPPING_MISS_RATE'
                                    AND metric_value_scalar <= {$high}
                                    AND evaluation_date = (
                                        SELECT evaluation_date FROM {$standardsProfile}
                                        WHERE account_id = a.id AND metric_key = 'SHIPPING_MISS_RATE'
                                        ORDER BY evaluation_date DESC
                                        LIMIT 1
                                    )
                              )";
            }
        }

        //获取当前筛选时间所在的周一和周日
        if (!empty($params['filter_date'])) {
            $time = strtotime($params['filter_date']);
            //周一
            $monday = date('Y-m-d', strtotime('monday this week', $time));
            //周日
            $sunday = date('Y-m-d', strtotime('sunday this week', $time));
        }

        //综合表现
        if (isset($params['ltnp_status']) && $params['ltnp_status'] != '') {
            $tableName = EbaySellerLtnp::tableName();

            if ($params['ltnp_status'] == -1) {
                if (!empty($params['filter_date'])) {
                    $sub = "NOT EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               )";
                } else {
                    $sub = "NOT EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND refreshed_date = (
                                    SELECT refreshed_date FROM {$tableName} 
                                    WHERE account_id = a.id 
                                    ORDER BY refreshed_date DESC 
                                    LIMIT 1
                                  )
                               )";
                }

            } else {
                if (!empty($params['filter_date'])) {
                    $sub = "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND status_lst_eval = '{$params['ltnp_status']}' 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               ) OR ";

                    $sub .= "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND snad_status_lst_eval = '{$params['ltnp_status']}' 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               ) OR ";

                    $sub .= "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND program_status_lst_eval = '{$params['ltnp_status']}' 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               )";
                } else {
                    $sub = "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND status_lst_eval = '{$params['ltnp_status']}' 
                                  AND refreshed_date = (
                                    SELECT refreshed_date FROM {$tableName} 
                                    WHERE account_id = a.id 
                                    ORDER BY refreshed_date DESC 
                                    LIMIT 1
                                  )
                               ) OR ";

                    $sub .= "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND snad_status_lst_eval = '{$params['ltnp_status']}' 
                                  AND refreshed_date = (
                                    SELECT refreshed_date FROM {$tableName} 
                                    WHERE account_id = a.id 
                                    ORDER BY refreshed_date DESC 
                                    LIMIT 1
                                  )
                               ) OR ";

                    $sub .= "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND program_status_lst_eval = '{$params['ltnp_status']}' 
                                  AND refreshed_date = (
                                    SELECT refreshed_date FROM {$tableName} 
                                    WHERE account_id = a.id 
                                    ORDER BY refreshed_date DESC 
                                    LIMIT 1
                                  )
                               )";
                }
            }

            $cond[] = $sub;
        }

        //货运
        if (isset($params['ship_status']) && $params['ship_status'] != '') {
            $tableName = EbaySellerShip::tableName();
            if ($params['ship_status'] == -1) {
                if (!empty($params['filter_date'])) {
                    $cond[] = "NOT EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               )";
                } else {
                    $cond[] = "NOT EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND refreshed_date = (
                                    SELECT refreshed_date FROM {$tableName} 
                                    WHERE account_id = a.id 
                                    ORDER BY refreshed_date DESC 
                                    LIMIT 1
                                  )
                               )";
                }
            } else {
                if (!empty($params['filter_date'])) {
                    $cond[] = "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND result = '{$params['ship_status']}' 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               )";
                } else {
                    $cond[] = "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND result = '{$params['ship_status']}' 
                                  AND refreshed_date = (
                                    SELECT refreshed_date FROM {$tableName} 
                                    WHERE account_id = a.id 
                                    ORDER BY refreshed_date DESC 
                                    LIMIT 1
                                  )
                               )";
                }
            }
        }
        //非货运
        if (isset($params['tci_status']) && $params['tci_status'] != '') {
            $tableName = EbaySellerTci::tableName();
            if ($params['tci_status'] == -1) {
                if (!empty($params['filter_date'])) {
                    $cond[] = "NOT EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               )";
                } else {
                    $cond[] = "NOT EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND refreshed_date = (
                                    SELECT refreshed_date FROM {$tableName} 
                                    WHERE account_id = a.id 
                                    ORDER BY refreshed_date DESC 
                                    LIMIT 1
                                  )
                               )";
                }
            } else {
                if (!empty($params['filter_date'])) {
                    $cond[] = "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND result = '{$params['tci_status']}' 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               )";
                } else {
                    $cond[] = "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND result = '{$params['tci_status']}' 
                                  AND refreshed_date = (
                                    SELECT refreshed_date FROM {$tableName} 
                                    WHERE account_id = a.id 
                                    ORDER BY refreshed_date DESC 
                                    LIMIT 1
                                  )
                               )";
                }
            }
        }
        //物流标准
        if (isset($params['shipping_policy_status']) && $params['shipping_policy_status'] != '') {
            $tableName1 = EbaySellerEdsShippingPolicy::tableName();
            $tableName2 = EbaySellerEpacketShippingPolicy::tableName();
            $tableName3 = EbaySellerSpeedpakList::tableName();
            $tableName4 = EbaySellerSpeedpakMisuse::tableName();

            if ($this->shipping_policy_status == -1) {
                if (!empty($params['filter_date'])) {
                    $sub = "NOT EXISTS (SELECT * FROM {$tableName1} 
                                  WHERE account_id = a.id 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               ) AND ";


                    $sub .= "NOT EXISTS (SELECT * FROM {$tableName2} 
                                  WHERE account_id = a.id 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               ) AND ";

                    $sub .= "NOT EXISTS (SELECT * FROM {$tableName3} 
                                  WHERE account_id = a.id 
                                  AND ('{$monday}' <= create_pst AND create_pst <= '{$sunday}')  
                               ) AND ";

                    $sub .= "NOT EXISTS (SELECT * FROM {$tableName4} 
                                  WHERE account_id = a.id 
                                  AND ('{$monday}' <= create_pst AND create_pst <= '{$sunday}')  
                               )";
                } else {
                    $sub = "NOT EXISTS (SELECT * FROM {$tableName1} 
                                  WHERE account_id = a.id 
                                  AND refreshed_date = (
                                    SELECT refreshed_date FROM {$tableName1} 
                                    WHERE account_id = a.id 
                                    ORDER BY refreshed_date DESC 
                                    LIMIT 1
                                  )
                               ) AND ";


                    $sub .= "NOT EXISTS (SELECT * FROM {$tableName2} 
                                  WHERE account_id = a.id 
                                  AND refreshed_date = (
                                    SELECT refreshed_date FROM {$tableName2} 
                                    WHERE account_id = a.id 
                                    ORDER BY refreshed_date DESC 
                                    LIMIT 1
                                  )
                               ) AND ";

                    $sub .= "NOT EXISTS (SELECT * FROM {$tableName3} 
                                  WHERE account_id = a.id 
                                  AND create_pst = (
                                    SELECT create_pst FROM {$tableName3} 
                                    WHERE account_id = a.id 
                                    ORDER BY create_pst DESC 
                                    LIMIT 1
                                  )
                               ) AND ";

                    $sub .= "NOT EXISTS (SELECT * FROM {$tableName4} 
                                  WHERE account_id = a.id 
                                  AND create_pst = (
                                    SELECT create_pst FROM {$tableName4} 
                                    WHERE account_id = a.id 
                                    ORDER BY create_pst DESC 
                                    LIMIT 1
                                  )
                               )";
                }
            } else {
                if (!empty($params['filter_date'])) {
                    $sub = "EXISTS (SELECT * FROM {$tableName1} 
                                  WHERE account_id = a.id 
                                  AND eds_status = '{$params['shipping_policy_status']}' 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               ) OR ";


                    $sub .= "EXISTS (SELECT * FROM {$tableName2} 
                                  WHERE account_id = a.id 
                                  AND e_packet_status = '{$params['shipping_policy_status']}' 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               ) OR ";

                    $sub .= "EXISTS (SELECT * FROM {$tableName3} 
                                  WHERE account_id = a.id 
                                  AND account_status = '{$params['shipping_policy_status']}' 
                                  AND ('{$monday}' <= create_pst AND create_pst <= '{$sunday}')  
                               ) OR ";

                    $sub .= "EXISTS (SELECT * FROM {$tableName4} 
                                  WHERE account_id = a.id 
                                  AND account_status = '{$params['shipping_policy_status']}' 
                                  AND ('{$monday}' <= create_pst AND create_pst <= '{$sunday}')  
                               )";
                } else {
                    $sub = "EXISTS (SELECT * FROM {$tableName1} 
                                  WHERE account_id = a.id 
                                  AND eds_status = '{$params['shipping_policy_status']}' 
                                  AND refreshed_date = (
                                    SELECT refreshed_date FROM {$tableName1} 
                                    WHERE account_id = a.id 
                                    ORDER BY refreshed_date DESC 
                                    LIMIT 1
                                  )
                               ) OR ";


                    $sub .= "EXISTS (SELECT * FROM {$tableName2} 
                                  WHERE account_id = a.id 
                                  AND e_packet_status = '{$params['shipping_policy_status']}' 
                                  AND refreshed_date = (
                                    SELECT refreshed_date FROM {$tableName2} 
                                    WHERE account_id = a.id 
                                    ORDER BY refreshed_date DESC 
                                    LIMIT 1
                                  )
                               ) OR ";

                    $sub .= "EXISTS (SELECT * FROM {$tableName3} 
                                  WHERE account_id = a.id 
                                  AND account_status = '{$params['shipping_policy_status']}' 
                                  AND create_pst = (
                                    SELECT create_pst FROM {$tableName3} 
                                    WHERE account_id = a.id 
                                    ORDER BY create_pst DESC 
                                    LIMIT 1
                                  )
                               ) OR ";

                    $sub .= "EXISTS (SELECT * FROM {$tableName4} 
                                  WHERE account_id = a.id 
                                  AND account_status = '{$params['shipping_policy_status']}' 
                                  AND create_pst = (
                                    SELECT create_pst FROM {$tableName4} 
                                    WHERE account_id = a.id 
                                    ORDER BY create_pst DESC 
                                    LIMIT 1
                                  )
                               )";
                }
            }

            $cond[] = $sub;
        }
        //海外仓标准
        if (isset($params['sd_warehouse_status']) && $params['sd_warehouse_status'] != '') {
            $tableName = EbaySellerSdWarehouse::tableName();
            if ($params['sd_warehouse_status'] == -1) {
                if (!empty($params['filter_date'])) {
                    $cond[] = "NOT EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               )";
                } else {
                    $cond[] = "NOT EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND refreshed_date = (
                                    SELECT refreshed_date FROM {$tableName} 
                                    WHERE account_id = a.id 
                                    ORDER BY refreshed_date DESC 
                                    LIMIT 1
                                  )
                               )";
                }
            } else {
                if (!empty($params['filter_date'])) {
                    $cond[] = "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND warehouse_status = '{$params['sd_warehouse_status']}' 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               )";
                } else {
                    $cond[] = "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND warehouse_status = '{$params['sd_warehouse_status']}' 
                                  AND refreshed_date = (
                                    SELECT refreshed_date FROM {$tableName} 
                                    WHERE account_id = a.id 
                                    ORDER BY refreshed_date DESC 
                                    LIMIT 1
                                  )
                               )";
                }
            }
        }
        //商业计划追踪
        if (isset($params['pgc_tracking_status']) && $params['pgc_tracking_status'] != '') {
            $tableName = EbaySellerPgcTracking::tableName();
            if ($params['pgc_tracking_status'] == -1) {
                if (!empty($params['filter_date'])) {
                    $cond[] = "NOT EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               )";
                } else {
                    $cond[] = "NOT EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND refreshed_date = (
                                    SELECT refreshed_date FROM {$tableName} 
                                    WHERE account_id = a.id 
                                    ORDER BY refreshed_date DESC 
                                    LIMIT 1
                                  )
                               )";
                }
            } else {
                if (!empty($params['filter_date'])) {
                    $cond[] = "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND pgc_status = '{$params['pgc_tracking_status']}' 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               )";
                } else {
                    $cond[] = "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND pgc_status = '{$params['pgc_tracking_status']}' 
                                  AND refreshed_date = (
                                    SELECT refreshed_date FROM {$tableName} 
                                    WHERE account_id = a.id 
                                    ORDER BY refreshed_date DESC 
                                    LIMIT 1
                                  )
                               )";
                }
            }
        }
        //待处理刊登
        if (isset($params['qclist_status']) && $params['qclist_status'] != '') {
            $tableName = EbaySellerQclist::tableName();
            if ($params['qclist_status'] == 'Y') {
                if (!empty($params['filter_date'])) {
                    $cond[] = "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               )";
                } else {
                    $cond[] = "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND refreshed_date = (
                                    SELECT refreshed_date FROM {$tableName} 
                                    WHERE account_id = a.id 
                                    ORDER BY refreshed_date DESC 
                                    LIMIT 1
                                  )
                               )";
                }
            } else {
                if (!empty($params['filter_date'])) {
                    $cond[] = "NOT EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               )";
                } else {
                    $cond[] = "NOT EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND refreshed_date = (
                                    SELECT refreshed_date FROM {$tableName} 
                                    WHERE account_id = a.id 
                                    ORDER BY refreshed_date DESC 
                                    LIMIT 1
                                  )
                               )";
                }
            }
        }
    }

    /**
     * 导出账号表现
     */
    public function actionExportaccountoverview()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        //获取get参数
        $get = YII::$app->request->get();
        //id数组
        $ids = !empty($get['ids']) ? $get['ids'] : [];
        //导出数据
        $data = [];

        //只能查询到客服绑定账号
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_EB);

        if (is_array($ids) && !empty($ids)) {
            $data = EbayAccountOverview::find()
                ->select('id, account_name')
                ->andWhere(['in', 'id', $accountIds])
                ->andWhere(['status' => Account::STATUS_VALID])
                ->andWhere(['in', 'id', $ids])
                ->orderBy('id DESC')
                ->asArray()
                ->all();

        } else {
            $query = EbayAccountOverview::find()
                ->alias('a')
                ->select('a.id, a.account_name')
                ->andWhere(['in', 'a.id', $accountIds])
                ->andWhere(['a.status' => Account::STATUS_VALID]);

            //请求参数
            $params = !empty($get['EbayAccountOverview']) ? $get['EbayAccountOverview'] : [];
            $params['filter_date'] = !empty($get['filter_date']) ? $get['filter_date'] : '';
            //账号
            if (!empty($params['account_id'])) {
                $query->andWhere(['a.id' => $params['account_id']]);
            }

            $cond[] = 'and';

            //导出筛选条件
            $this->exportFilter($cond, $params);

            $data = $query->andWhere($cond)
                ->orderBy('id DESC')
                ->asArray()
                ->all();
        }

        if (empty($data)) {
            $this->_showMessage('数据为空', false);
        }

        //标题数组
        $fieldArr = [
            '账号',
            '站点',
            '当前账户等级',
            '预测账户等级',
            '不良交易率',
            '未解决纠纷率',
            '运送延迟率',
            '综合表现',
            '货运',
            '非货运',
            '物流标准',
            '海外仓标准',
            '商业计划追踪',
            '待处理刊登',
        ];
        //导出数据数组
        $dataArr = [];

        $siteList = EbayAccountOverview::getSiteList();
        $accountLevel = EbayAccountOverview::getAccountLevel();
        $ltnpStatus = EbayAccountOverview::getLtnpStatus();
        $shipStatus = EbayAccountOverview::getShippingStatus();
        $nonShipStatus = EbayAccountOverview::getNonShippingStatus();
        $edshippingStatus = EbayAccountOverview::getEdshippingStatus();
        $wareHouseStatus = EbayAccountOverview::getWareHouseStatus();
        $pgdTrackingStatus = EbayAccountOverview::getPgcTrackingStatus();
        $qcListingStatus = EbayAccountOverview::getQcListingStatus();

        foreach ($data as $account) {
            //DEFECTIVE_TRANSACTION_RATE 不良交易率
            //CLAIMS_SAF_RATE 未解决纠纷率
            //SHIPPING_MISS_RATE 延迟发货率

            //卖家成绩表
            if (!empty($get['filter_date'])) {
                $result = EbaySellerStandardsProfile::find()
                    ->andWhere(['account_id' => $account['id']])
                    ->andWhere(['in', 'metric_key', ['DEFECTIVE_TRANSACTION_RATE', 'CLAIMS_SAF_RATE', 'SHIPPING_MISS_RATE']])
                    ->andWhere("DATE_FORMAT(evaluation_date, '%Y-%m-%d') = '{$get['filter_date']}'")
                    ->orderBy('id DESC, evaluation_date DESC')
                    ->asArray()
                    ->all();
            } else {
                $result = EbaySellerStandardsProfile::find()
                    ->andWhere(['account_id' => $account['id']])
                    ->andWhere(['in', 'metric_key', ['DEFECTIVE_TRANSACTION_RATE', 'CLAIMS_SAF_RATE', 'SHIPPING_MISS_RATE']])
                    ->orderBy('id DESC, evaluation_date DESC')
                    ->asArray()
                    ->all();
            }

            //卖家成绩表
            $seller = [
                'PROGRAM_GLOBAL' => [
                    'current_level' => '0',
                    'projected_level' => '0',
                ],
                'PROGRAM_US' => [
                    'current_level' => '0',
                    'projected_level' => '0',
                ],
                'PROGRAM_UK' => [
                    'current_level' => '0',
                    'projected_level' => '0',
                ],
                'PROGRAM_DE' => [
                    'current_level' => '0',
                    'projected_level' => '0',
                ],
            ];

            if (!empty($result)) {
                foreach ($result as $item) {
                    if (!isset($seller[$item['program']])) {
                        $seller[$item['program']] = [];
                    }

                    //获取预测的数据
                    if ($item['cycle_type'] == 'PROJECTED') {
                        if (empty($seller[$item['program']]['projected_level'])) {
                            $seller[$item['program']]['projected_level'] = $item['standards_level'];
                        }

                        //不良交易率
                        if ($item['metric_key'] == 'DEFECTIVE_TRANSACTION_RATE') {
                            if (!isset($seller[$item['program']]['bad_trade_rate'])) {
                                $metricValue = json_decode($item['metric_value'], true);
                                if (isset($metricValue['value'])) {
                                    if (empty($metricValue['value']) || $metricValue['value'] == '0.00') {
                                        $seller[$item['program']]['bad_trade_rate'] = isset($metricValue['numerator']) ? $metricValue['numerator'] : 0;
                                        $seller[$item['program']]['bad_trade_is_rate'] = false;
                                    } else {
                                        $seller[$item['program']]['bad_trade_rate'] = $metricValue['value'];
                                        $seller[$item['program']]['bad_trade_is_rate'] = true;
                                    }
                                } else {
                                    $seller[$item['program']]['bad_trade_rate'] = $metricValue;
                                    $seller[$item['program']]['bad_trade_is_rate'] = false;
                                }
                            }
                        }

                        //未解决纠纷率
                        if ($item['metric_key'] == 'CLAIMS_SAF_RATE') {
                            if (!isset($seller[$item['program']]['unresolve_dispute_rate'])) {
                                $metricValue = json_decode($item['metric_value'], true);
                                if (isset($metricValue['value'])) {
                                    if (empty($metricValue['value']) || $metricValue['value'] == '0.00') {
                                        $seller[$item['program']]['unresolve_dispute_rate'] = isset($metricValue['numerator']) ? $metricValue['numerator'] : 0;
                                        $seller[$item['program']]['unresolve_dispute_is_rate'] = false;
                                    } else {
                                        $seller[$item['program']]['unresolve_dispute_rate'] = $metricValue['value'];
                                        $seller[$item['program']]['unresolve_dispute_is_rate'] = true;
                                    }
                                } else {
                                    $seller[$item['program']]['unresolve_dispute_rate'] = $metricValue;
                                    $seller[$item['program']]['unresolve_dispute_is_rate'] = false;
                                }
                            }
                        }

                        //运送延迟率
                        if ($item['metric_key'] == 'SHIPPING_MISS_RATE') {
                            if (!isset($seller[$item['program']]['transport_delay_rate'])) {
                                $metricValue = json_decode($item['metric_value'], true);
                                if (isset($metricValue['value'])) {
                                    if (empty($metricValue['value']) || $metricValue['value'] == '0.00') {
                                        $seller[$item['program']]['transport_delay_rate'] = isset($metricValue['numerator']) ? $metricValue['numerator'] : 0;
                                        $seller[$item['program']]['transport_delay_is_rate'] = false;
                                    } else {
                                        $seller[$item['program']]['transport_delay_rate'] = $metricValue['value'];
                                        $seller[$item['program']]['transport_delay_is_rate'] = true;
                                    }
                                } else {
                                    $seller[$item['program']]['transport_delay_rate'] = $metricValue;
                                    $seller[$item['program']]['transport_delay_is_rate'] = false;
                                }
                            }
                        }
                    } else {
                        if (empty($seller[$item['program']]['current_level'])) {
                            $seller[$item['program']]['current_level'] = $item['standards_level'];
                        }
                    }
                }
            }

            //获取当前筛选时间所在的周一和周日
            if (!empty($get['filter_date'])) {
                $time = strtotime($get['filter_date']);
                //周一
                $monday = date('Y-m-d', strtotime('monday this week', $time));
                //周日
                $sunday = date('Y-m-d', strtotime('sunday this week', $time));
            }

            //买家体验报告
            if (!empty($get['filter_date'])) {
                $buyer = EbaySellerAccountOverview::find()
                    ->where(['account_id' => $account['id']])
                    ->andWhere("'{$monday}' <= DATE_FORMAT(create_time, '%Y-%m-%d') AND DATE_FORMAT(create_time, '%Y-%m-%d') <= '{$sunday}'")
                    ->orderBy('create_time DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            } else {
                $buyer = EbaySellerAccountOverview::find()
                    ->where(['account_id' => $account['id']])
                    ->orderBy('create_time DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            }

            //综合表现
            $ltnp = '无';
            //货运
            $ship = '无';
            //非货运
            $tci = '无';
            //物流标准
            $shippingPolicy = '无';
            //海外仓标准
            $sdWarehouse = '无';
            //商业计划追踪
            $pgcTracking = '无';
            //待处理刊登
            $qclist = '无';

            if (!empty($buyer)) {
                $ltnp = array_key_exists($buyer['long_term_status'], $ltnpStatus) ? $ltnpStatus[$buyer['long_term_status']] : '无';

                $ship = array_key_exists($buyer['shipping_status'], $shipStatus) ? $shipStatus[$buyer['shipping_status']] : '无';

                $tci = array_key_exists($buyer['non_shipping_status'], $nonShipStatus) ? $nonShipStatus[$buyer['non_shipping_status']] : '无';

                $shippingPolicy = array_key_exists($buyer['edshipping_status'], $edshippingStatus) ? $edshippingStatus[$buyer['edshipping_status']] : '无';

                $sdWarehouse = array_key_exists($buyer['ware_house_status'], $wareHouseStatus) ? $wareHouseStatus[$buyer['ware_house_status']] : '无';

                $pgcTracking = array_key_exists($buyer['pgc_tracking_status'], $pgdTrackingStatus) ? $pgdTrackingStatus[$buyer['pgc_tracking_status']] : '无';

                $qclist = array_key_exists($buyer['qc_listing_status'], $qcListingStatus) ? $qcListingStatus[$buyer['qc_listing_status']] : '无';
            }

            if (!empty($seller)) {
                foreach ($seller as $key => $item) {

                    if (!empty($item['bad_trade_is_rate'])) {
                        $bad_trade_rate = !empty($item['bad_trade_rate']) ? $item['bad_trade_rate'] : '0.00';
                        $bad_trade_rate .= '%';
                    } else {
                        $bad_trade_rate = !empty($item['bad_trade_rate']) ? $item['bad_trade_rate'] : '0';
                    }

                    if (!empty($item['unresolve_dispute_is_rate'])) {
                        $unresolve_dispute_rate = !empty($item['unresolve_dispute_rate']) ? $item['unresolve_dispute_rate'] : '0.00';
                        $unresolve_dispute_rate .= '%';
                    } else {
                        $unresolve_dispute_rate = !empty($item['unresolve_dispute_rate']) ? $item['unresolve_dispute_rate'] : '0';
                    }

                    if (!empty($item['transport_delay_is_rate'])) {
                        $transport_delay_rate = !empty($item['transport_delay_rate']) ? $item['transport_delay_rate'] : '0.00';
                        $transport_delay_rate .= '%';
                    } else {
                        $transport_delay_rate = !empty($item['transport_delay_rate']) ? $item['transport_delay_rate'] : '0';
                    }

                    $dataArr[] = [
                        $account['account_name'],
                        array_key_exists($key, $siteList) ? $siteList[$key] : '',
                        array_key_exists($item['current_level'], $accountLevel) ? $accountLevel[$item['current_level']] : '无',
                        array_key_exists($item['projected_level'], $accountLevel) ? $accountLevel[$item['projected_level']] : '无',
                        $bad_trade_rate,
                        $unresolve_dispute_rate,
                        $transport_delay_rate,
                        $ltnp,
                        $ship,
                        $tci,
                        $shippingPolicy,
                        $sdWarehouse,
                        $pgcTracking,
                        $qclist,
                    ];
                }
            }
        }

        //创建PHPExcel对象
        $obj = new \PHPExcel();
        //创建excel写入对象
        $writer = new \PHPExcel_Writer_Excel5($obj);
        //得到当前工作表对象
        $curSheet = $obj->getActiveSheet();

        $fieldNum = count($fieldArr);
        $dataRow = count($dataArr) + 3;

        //设置单元格居中
        $curSheet->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $curSheet->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        //设置表头单元格合并
        $curSheet->setCellValue('C1', '卖家成绩表');
        $curSheet->getStyle('C1:G1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
        $curSheet->getStyle('C1:G1')->getFill()->getStartColor()->setARGB('fffffb8f');
        $curSheet->mergeCells('C1:G1');
        $curSheet->setCellValue('H1', '买家体验报告');
        $curSheet->getStyle('H1:N1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
        $curSheet->getStyle('H1:N1')->getFill()->getStartColor()->setARGB('ffb7eb8f');
        $curSheet->mergeCells('H1:N1');

        for ($col = 0; $col < $fieldNum; ++$col) {
            $cellName = \PHPExcel_Cell::stringFromColumnIndex($col) . '2';
            $curSheet->setCellValue($cellName, $fieldArr[$col]);
        }

        for ($row = 3; $row < $dataRow; ++$row) {
            for ($col = 0; $col < $fieldNum; ++$col) {
                $cellName = \PHPExcel_Cell::stringFromColumnIndex($col) . $row;

                $value = $dataArr[$row - 3][$col];
                if (strpos($value, '正常') !== false) {
                    $curSheet->getStyle($cellName)->getFont()->getColor()->setARGB('ff52c41a');
                } else if (strpos($value, '超标') !== false) {
                    $curSheet->getStyle($cellName)->getFont()->getColor()->setARGB('fffa541c');
                } else if (strpos($value, '警告') !== false) {
                    $curSheet->getStyle($cellName)->getFont()->getColor()->setARGB('fffaad14');
                } else if (strpos($value, '限制') !== false) {
                    $curSheet->getStyle($cellName)->getFont()->getColor()->setARGB('ffeb2f96');
                } else if (strpos($value, '不考核') !== false) {
                    $curSheet->getStyle($cellName)->getFont()->getColor()->setARGB('ff1890ff');
                }

                if (strpos($value, '最高评级') !== false) {
                    $curSheet->getStyle($cellName)->getFont()->getColor()->setARGB('ff52c41a');
                } else if (strpos($value, '低于标准') !== false) {
                    $curSheet->getStyle($cellName)->getFont()->getColor()->setARGB('fffa541c');
                }

                $curSheet->setCellValue($cellName, $value);
            }
        }

        //把需要合并的单元格合并
        $step = 4;
        for ($row = 3; $row < $dataRow; $row += $step) {
            $off = $row + $step - 1;
            $curSheet->mergeCells("A{$row}:A{$off}");
            $curSheet->mergeCells("H{$row}:H{$off}");
            $curSheet->mergeCells("I{$row}:I{$off}");
            $curSheet->mergeCells("J{$row}:J{$off}");
            $curSheet->mergeCells("K{$row}:K{$off}");
            $curSheet->mergeCells("L{$row}:L{$off}");
            $curSheet->mergeCells("M{$row}:M{$off}");
            $curSheet->mergeCells("N{$row}:N{$off}");
        }

        $fileName = 'ebayaccountoverview_' . date('YmdHis', time());
        header('Content-Type: application/vnd.ms-execl');
        header('Content-Disposition: attachment;filename="' . $fileName . '.xls"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
    }

    /**
     * 导出账号表现明细
     */
    public function actionExportaccountoverviewdetails()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        //获取get参数
        $get = YII::$app->request->get();
        //id数组
        $ids = !empty($get['ids']) ? $get['ids'] : [];
        //导出数据
        $data = [];

        //只能查询到客服绑定账号
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_EB);

        if (is_array($ids) && !empty($ids)) {
            $data = EbayAccountOverview::find()
                ->select('id, account_name, old_account_id')
                ->andWhere(['in', 'id', $accountIds])
                ->andWhere(['status' => Account::STATUS_VALID])
                ->andWhere(['in', 'id', $ids])
                ->orderBy('id DESC')
                ->asArray()
                ->all();

        } else {
            $query = EbayAccountOverview::find()
                ->alias('a')
                ->select('a.id, a.account_name, old_account_id')
                ->andWhere(['in', 'a.id', $accountIds])
                ->andWhere(['a.status' => Account::STATUS_VALID]);

            //请求参数
            $params = !empty($get['EbayAccountOverview']) ? $get['EbayAccountOverview'] : [];
            $params['filter_date'] = !empty($get['filter_date']) ? $get['filter_date'] : '';
            //账号
            if (!empty($params['account_id'])) {
                $query->andWhere(['a.id' => $params['account_id']]);
            }

            $cond[] = 'and';

            //导出筛选条件
            $this->exportFilter($cond, $params);

            $data = $query->andWhere($cond)
                ->orderBy('id DESC')
                ->asArray()
                ->all();
        }

        if (empty($data)) {
            $this->_showMessage('数据为空', false);
        }

        //标题数组
        $fieldArr = [
            '账号',
            '表现',
            '状态',
            '站点',
            '详情',
            '考核时间',
        ];
        //导出数据数组
        $dataArr = [];

        $siteList = EbayAccountOverview::getSiteList();
        $accountLevel = EbayAccountOverview::getAccountLevel();
        $ltnpStatus = EbayAccountOverview::getLtnpStatus();
        $shipStatus = EbayAccountOverview::getShippingStatus();
        $nonShipStatus = EbayAccountOverview::getNonShippingStatus();
        $edshippingStatus = EbayAccountOverview::getEdshippingStatus();
        $epacketShippingStatus = EbayAccountOverview::getEpacketShippingStatus();
        $speedPakListStatus = EbayAccountOverview::getSpeedPakListStatus();
        $speedPakMisuseStatus = EbayAccountOverview::getSpeedPakMisuseStatus();
        $wareHouseStatus = EbayAccountOverview::getWareHouseStatus();
        $pgcTrackingStatus = EbayAccountOverview::getPgcTrackingStatus();
        $qcListingStatus = EbayAccountOverview::getQcListingStatus();

        foreach ($data as $account) {

            //卖家成绩表
            if (!empty($get['filter_date'])) {
                $profiles = EbaySellerStandardsProfile::find()
                    ->andWhere(['account_id' => $account['id']])
                    ->andWhere(['in', 'metric_key', ['DEFECTIVE_TRANSACTION_RATE', 'CLAIMS_SAF_RATE', 'SHIPPING_MISS_RATE']])
                    ->andWhere("DATE_FORMAT(evaluation_date, '%Y-%m-%d') = '{$get['filter_date']}'")
                    ->orderBy('id DESC, evaluation_date DESC')
                    ->asArray()
                    ->all();
            } else {
                $profiles = EbaySellerStandardsProfile::find()
                    ->andWhere(['account_id' => $account['id']])
                    ->andWhere(['in', 'metric_key', ['DEFECTIVE_TRANSACTION_RATE', 'CLAIMS_SAF_RATE', 'SHIPPING_MISS_RATE']])
                    ->orderBy('id DESC, evaluation_date DESC')
                    ->asArray()
                    ->all();
            }

            //卖家成绩表
            $seller = [];
            if (!empty($profiles)) {
                foreach ($profiles as $item) {
                    if (!isset($seller[$item['program']])) {
                        $seller[$item['program']] = [];
                    }

                    $program = '';
                    switch ($item['program']) {
                        case 'PROGRAM_DE':
                            $program = '德国';
                            break;
                        case 'PROGRAM_UK':
                            $program = '英国';
                            break;
                        case 'PROGRAM_US':
                            $program = '美国';
                            break;
                        case 'PROGRAM_GLOBAL':
                            $program = '全球';
                            break;
                    }

                    //获取预测的数据
                    if ($item['cycle_type'] == 'PROJECTED') {
                        //不良交易率
                        if ($item['metric_key'] == 'DEFECTIVE_TRANSACTION_RATE') {
                            if (!isset($seller[$item['program']]['bad_trade_rate'])) {

                                $metricValue = json_decode($item['metric_value'], true);
                                $upperBound = json_decode($item['metric_threshold_upper_bound'], true);

                                $bad_trade_rate_details = "不良交易率 {$metricValue['value']}% \n";
                                $bad_trade_rate_details .= "不良交易 {$metricValue['numerator']} 项, 共产生 {$metricValue['denominator']} 项 \n";
                                $bad_trade_rate_details .= "标准是低于 {$upperBound['value']}% \n";

                                $standards_level = array_key_exists($item['standards_level'], $accountLevel) ? $accountLevel[$item['standards_level']] : '无';

                                $dataArr[] = [
                                    $account['account_name'],
                                    '不良交易率',
                                    $standards_level,
                                    $program,
                                    $bad_trade_rate_details,
                                    "{$item['metric_lookback_startdate']} - {$item['metric_lookback_enddate']}",
                                ];

                                $seller[$item['program']]['bad_trade_rate'] = true;
                            }
                        }

                        //未解决纠纷率
                        if ($item['metric_key'] == 'CLAIMS_SAF_RATE') {
                            if (!isset($seller[$item['program']]['unresolve_dispute_rate'])) {
                                $metricValue = json_decode($item['metric_value'], true);
                                $upperBound = json_decode($item['metric_threshold_upper_bound'], true);

                                $unresolve_dispute_rate_details = "未解决纠纷率为 {$metricValue['value']}% \n";
                                $unresolve_dispute_rate_details .= "未解决纠纷 {$metricValue['numerator']} 项, 共产生 {$metricValue['denominator']} 项 \n";
                                $unresolve_dispute_rate_details .= "标准是低于 {$upperBound['value']}% \n";

                                $standards_level = array_key_exists($item['standards_level'], $accountLevel) ? $accountLevel[$item['standards_level']] : '无';

                                $dataArr[] = [
                                    $account['account_name'],
                                    '未解决纠纷率',
                                    $standards_level,
                                    $program,
                                    $unresolve_dispute_rate_details,
                                    "{$item['metric_lookback_startdate']} - {$item['metric_lookback_enddate']}",
                                ];

                                $seller[$item['program']]['unresolve_dispute_rate'] = true;
                            }
                        }

                        //运送延迟率
                        if ($item['metric_key'] == 'SHIPPING_MISS_RATE') {
                            if (!isset($seller[$item['program']]['transport_delay_rate'])) {
                                $metricValue = json_decode($item['metric_value'], true);
                                $upperBound = json_decode($item['metric_threshold_upper_bound'], true);

                                $transport_delay_rate_details = "运送延迟率 {$metricValue['value']}% \n";
                                $transport_delay_rate_details .= "运送延迟 {$metricValue['numerator']} 项, 共产生 {$metricValue['denominator']} 项 \n";

                                $upperBoundValue = $upperBound['value'];
                                //运送延迟率的最低标准是英国德国9%，美国7%，全球10%
                                switch ($item['program']) {
                                    case 'PROGRAM_DE':
                                    case 'PROGRAM_UK':
                                        $upperBoundValue = '9';
                                        break;
                                    case 'PROGRAM_US':
                                        $upperBoundValue = '7';
                                        break;
                                    case 'PROGRAM_GLOBAL':
                                        $upperBoundValue = '10';
                                        break;
                                }
                                $transport_delay_rate_details .= "最低标准是低于 {$upperBoundValue}% \n";

                                $standards_level = array_key_exists($item['standards_level'], $accountLevel) ? $accountLevel[$item['standards_level']] : '无';

                                $dataArr[] = [
                                    $account['account_name'],
                                    '运送延迟率',
                                    $standards_level,
                                    $program,
                                    $transport_delay_rate_details,
                                    "{$item['metric_lookback_startdate']} - {$item['metric_lookback_enddate']}",
                                ];

                                $seller[$item['program']]['transport_delay_rate'] = true;
                            }
                        }
                    }
                }
            }

            //获取当前筛选时间所在的周一和周日
            if (!empty($get['filter_date'])) {
                $time = strtotime($get['filter_date']);
                //周一
                $monday = date('Y-m-d', strtotime('monday this week', $time));
                //周日
                $sunday = date('Y-m-d', strtotime('sunday this week', $time));
            }

            //综合表现
            if (!empty($get['filter_date'])) {
                $ltnp = EbaySellerLtnp::find()
                    ->where(['account_id' => $account['id']])
                    ->andWhere("'{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}'")
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            } else {
                $ltnp = EbaySellerLtnp::find()
                    ->where(['account_id' => $account['id']])
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            }

            if (!empty($ltnp)) {
                $program_status = array_key_exists($ltnp['program_status_lst_eval'], $ltnpStatus) ? $ltnpStatus[$ltnp['program_status_lst_eval']] : '无';

                $ltnp_status = array_key_exists($ltnp['status_lst_eval'], $ltnpStatus) ? $ltnpStatus[$ltnp['status_lst_eval']] : '无';

                $ltnp_details = "您 {$ltnp['dft_lst_eval_beg_dt']} - {$ltnp['dft_lst_eval_end_dt']} 期间的综合交易表现为 {$program_status}\n";
                $ltnp_details .= "不良交易率表现状态: {$ltnp_status}\n";
                $ltnp_details .= "当前评价(下次评估时间: {$ltnp['next_review_dt']}) | 标准值 | 当前值 | 状态\n";
                $ltnp_details .= "小于等于10美金12月不良交易率" . "  |  " . ($ltnp['dft_rt_lt10_12m_th'] * 100) . "%  |  " . ($ltnp['dft_rt_lt10_12m_lst_eval'] * 100) . "%  |  " . (array_key_exists($ltnp['status_lt10_lst_eval'], $ltnpStatus) ? $ltnpStatus[$ltnp['status_lt10_lst_eval']] : '无') . "\n";
                $ltnp_details .= "大于10美金12月不良交易率" . "  |  " . ($ltnp['dft_rt_gt10_12m_th'] * 100) . "%  |  " . ($ltnp['dft_rt_gt10_12m_lst_eval'] * 100) . "%  |  " . (array_key_exists($ltnp['status_gt10_lst_eval'], $ltnpStatus) ? $ltnpStatus[$ltnp['status_gt10_lst_eval']] : '无') . "\n";
                $ltnp_details .= "综合12月不良交易率" . "  |  " . ($ltnp['adj_dft_rt_12m_th'] * 100) . "%  |  " . ($ltnp['adj_dft_rt_12m_lst_eval'] * 100) . "%  |  " . (array_key_exists($ltnp['status_adj_lst_eval'], $ltnpStatus) ? $ltnpStatus[$ltnp['status_adj_lst_eval']] : '无') . "\n";

                $ltnp_snad_status = array_key_exists($ltnp['snad_status_lst_eval'], $ltnpStatus) ? $ltnpStatus[$ltnp['snad_status_lst_eval']] : '无';

                $ltnp_snad_details = "您 {$ltnp['dft_lst_eval_beg_dt']} - {$ltnp['dft_lst_eval_end_dt']} 期间的综合交易表现为 {$program_status}\n";
                $ltnp_snad_details .= "纠纷表现状态: {$ltnp_snad_status}\n";
                $ltnp_snad_details .= "下次评估时间: {$ltnp['next_review_dt']}\n";
                $ltnp_snad_details .= "纠纷率 超出标准" . ($ltnp['delta_snad_rt_12m_lst_eval'] * 100) . "%, 预期状态为  " . (array_key_exists($ltnp['snad_status_wk_eval'], $ltnpStatus) ? $ltnpStatus[$ltnp['snad_status_wk_eval']] : '无') . "\n";

                $dataArr[] = [
                    $account['account_name'],
                    '综合表现(不良交易率)',
                    $ltnp_status,
                    '',
                    $ltnp_details,
                    "{$ltnp['dft_lst_eval_beg_dt']} - {$ltnp['dft_lst_eval_end_dt']}",
                ];
                $dataArr[] = [
                    $account['account_name'],
                    '综合表现(纠纷)',
                    $ltnp_snad_status,
                    '',
                    $ltnp_snad_details,
                    "{$ltnp['dft_lst_eval_beg_dt']} - {$ltnp['dft_lst_eval_end_dt']}",
                ];
            }

            //货运(1-8周)
            if (!empty($get['filter_date'])) {
                $ship = EbaySellerShip::find()
                    ->where(['account_id' => $account['id']])
                    ->andWhere("'{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}'")
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            } else {
                $ship = EbaySellerShip::find()
                    ->where(['account_id' => $account['id']])
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            }

            if (!empty($ship)) {
                $ship_status = array_key_exists($ship['result'], $shipStatus) ? $shipStatus[$ship['result']] : '无';

                $ship_details = "您 {$ship['review_start_date']} - {$ship['review_end_date']} 期间的货运表现为 {$ship_status}\n";
                $ship_details .= "超出标准货运问题交易率  " . ($ship['glb_shtm_de_rate_pre'] * 100) . "%\n";
                $ship_details .= "北美货运问题交易率 " . ($ship['na_shtm_rate_pre'] * 100) . "%\n";
                $ship_details .= "英国货运问题交易率 " . ($ship['uk_shtm_rate_pre'] * 100) . "%\n";
                $ship_details .= "德国货运问题交易率 " . ($ship['de_shtm_rate_pre'] * 100) . "%\n";
                $ship_details .= "澳大利亚货运问题交易率 " . ($ship['au_shtm_rate_pre'] * 100) . "%\n";
                $ship_details .= "其他货运问题交易率 " . ($ship['oth_shtm_rate_pre'] * 100) . "%\n";

                $dataArr[] = [
                    $account['account_name'],
                    '货运表现(1-8周)',
                    $ship_status,
                    '',
                    $ship_details,
                    "{$ship['review_start_date']} - {$ship['review_end_date']}",
                ];
            }

            //货运(5-12周)
            if (!empty($get['filter_date'])) {
                $ship_old = EbaySellerShipOld::find()
                    ->where(['account_id' => $account['id']])
                    ->andWhere("'{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}'")
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            } else {
                $ship_old = EbaySellerShipOld::find()
                    ->where(['account_id' => $account['id']])
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            }

            if (!empty($ship_old)) {
                $ship_old_status = array_key_exists($ship_old['result'], $shipStatus) ? $shipStatus[$ship_old['result']] : '无';

                $ship_old_details = "您 {$ship_old['review_start_date']} - {$ship_old['review_end_date']} 期间的货运表现为 {$ship_old_status}\n";
                $ship_old_details .= "超出标准货运问题交易率  " . ($ship_old['glb_shtm_de_rate_pre'] * 100) . "%\n";
                $ship_old_details .= "北美货运问题交易率 " . ($ship_old['na_shtm_rate_pre'] * 100) . "%\n";
                $ship_old_details .= "英国货运问题交易率 " . ($ship_old['uk_shtm_rate_pre'] * 100) . "%\n";
                $ship_old_details .= "德国货运问题交易率 " . ($ship_old['de_shtm_rate_pre'] * 100) . "%\n";
                $ship_old_details .= "澳大利亚货运问题交易率 " . ($ship_old['au_shtm_rate_pre'] * 100) . "%\n";
                $ship_old_details .= "其他货运问题交易率 " . ($ship_old['oth_shtm_rate_pre'] * 100) . "%\n";

                $dataArr[] = [
                    $account['account_name'],
                    '货运表现(5-12周)',
                    $ship_old_status,
                    '',
                    $ship_old_details,
                    "{$ship_old['review_start_date']} - {$ship_old['review_end_date']}",
                ];
            }

            //非货运
            if (!empty($get['filter_date'])) {
                $tci = EbaySellerTci::find()
                    ->where(['account_id' => $account['id']])
                    ->andWhere("'{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}'")
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            } else {
                $tci = EbaySellerTci::find()
                    ->where(['account_id' => $account['id']])
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            }

            if (!empty($tci)) {
                $tci_status = array_key_exists($tci['result'], $nonShipStatus) ? $nonShipStatus[$tci['result']] : '无';

                $tci_details = "您 {$tci['review_start_dt']} - {$tci['review_end_dt']} 期间的非货运表现为 {$tci_status}\n";
                $tci_details .= "超出标准非货运问题交易率  " . (rtrim($tci['ns_defect_adj_rt8wk'], '%')) . "%\n";
                $tci_details .= "北美非货运问题交易率 " . (rtrim($tci['na_ns_defect_adj_rt8wk'], '%')) . "%\n";
                $tci_details .= "英国非货运问题交易率 " . (rtrim($tci['uk_ns_defect_adj_rt8wk'], '%')) . "%\n";
                $tci_details .= "德国非货运问题交易率 " . (rtrim($tci['de_ns_defect_adj_rt8wk'], '%')) . "%\n";
                $tci_details .= "澳大利亚非货运问题交易率 " . (rtrim($tci['au_ns_defect_adj_rt8wk'], '%')) . "%\n";
                $tci_details .= "其他非货运问题交易率 " . (rtrim($tci['gl_ns_defect_adj_rt8wk'], '%')) . "%\n";

                $dataArr[] = [
                    $account['account_name'],
                    '非货运表现',
                    $tci_status,
                    '',
                    $tci_details,
                    "{$tci['review_start_dt']} - {$tci['review_end_dt']}",
                ];
            }

            //物流标准(美国小于5美金)
            if (!empty($get['filter_date'])) {
                $eds = EbaySellerEdsShippingPolicy::find()
                    ->where(['account_id' => $account['id']])
                    ->andWhere("'{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}'")
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            } else {
                $eds = EbaySellerEdsShippingPolicy::find()
                    ->where(['account_id' => $account['id']])
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            }

            if (!empty($eds)) {
                $eds_status = array_key_exists($eds['eds_status'], $edshippingStatus) ? $edshippingStatus[$eds['eds_status']] : '无';

                $eds_details = "小于5美金及其他25个主要国家的物流使用合规状态为 {$eds_status}\n";
                $eds_details .= "评估总交易数 {$eds['add_trans_cnt']} 物流使用合规比例 " . (rtrim($eds['eds_comply_rate'], '%')) . "%\n";
                $eds_details .= "其中：买家选择使用标准型及以上物流占 {$eds['add_buyer_std_trans_cnt']} 笔";
                $eds_details .= "使用全程追踪物流比例 " . (rtrim($eds['eds_std_comply_rate'], '%')) . "%\n";
                $eds_details .= "其中：买家选择使用经济型物流占 {$eds['add_buyer_econ_trans_cnt']} 笔";
                $eds_details .= "使用至少含揽收信息或全程跟踪物流比例 " . (rtrim($eds['eds_econ_comply_rate'], '%')) . "%\n";

                $dataArr[] = [
                    $account['account_name'],
                    '物流标准(小于5美金)',
                    $eds_status,
                    '',
                    $eds_details,
                    "{$eds['review_start_date']} - {$eds['review_end_date']}",
                ];
            }

            //物流标准(美国大于5美金)
            if (!empty($get['filter_date'])) {
                $packet = EbaySellerEpacketShippingPolicy::find()
                    ->where(['account_id' => $account['id']])
                    ->andWhere("'{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}'")
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            } else {
                $packet = EbaySellerEpacketShippingPolicy::find()
                    ->where(['account_id' => $account['id']])
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            }

            if (!empty($packet)) {
                $packet_status = array_key_exists($packet['e_packet_status'], $epacketShippingStatus) ? $epacketShippingStatus[$packet['e_packet_status']] : '无';

                $packet_details = "您目前美国>$5交易全程跟踪物流的使用状态为 {$packet_status}\n";
                $packet_details .= ">$5交易中使用ePacket+带有效追踪物流的比例 " . (rtrim($packet['adoption'], '%')) . "%, 标准是高于 " . (rtrim($packet['standard_value'], '%')) . "%\n";
                $packet_details .= "评估总交易数 {$packet['evaluated_tnx_cnt']} 笔 使用全程跟踪物流且揽收扫描满足时效要求的比例 " . (rtrim($packet['adoption'], '%')) . "%\n";
                $packet_details .= "其中：跨境发货占 {$packet['cbt_tnx_cnt']} 笔 使用ePacket+且揽收扫描满足时效要求的比例 " . (rtrim($packet['cbt_adoption'], '%')) . "%\n";
                $packet_details .= "其中：海外仓发货占 {$packet['wh_tnx_cnt']} 笔 使用带有效追踪物流且揽收扫描满足时效要求的比例 " . (rtrim($packet['wh_adoption'], '%')) . "%\n";

                $dataArr[] = [
                    $account['account_name'],
                    '物流标准(大于5美金)',
                    $packet_status,
                    '',
                    $packet_details,
                    "{$packet['review_start_date']} - {$packet['review_end_date']}",
                ];
            }

            //SpeedPAK 物流管理方案
            if (!empty($get['filter_date'])) {
                $speedPakList = EbaySellerSpeedpakList::find()
                    ->where(['account_id' => $account['id']])
                    ->andWhere("'{$monday}' <= create_pst AND create_pst <= '{$sunday}'")
                    ->orderBy('create_pst DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            } else {
                $speedPakList = EbaySellerSpeedpakList::find()
                    ->where(['account_id' => $account['id']])
                    ->orderBy('create_pst DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            }

            if (!empty($speedPakList)) {
                $speedPakList_status = array_key_exists($speedPakList['account_status'], $speedPakListStatus) ? $speedPakListStatus[$speedPakList['account_status']] : '无';

                $speedPakList_details = "SpeedPAK物流管理方案及其他符合政策要求的物流服务使用状态为: {$speedPakList_status} \n";
                $speedPakList_details .= "美国>$5直邮交易  |  被评估交易数 {$speedPakList['us_trans']} 笔  |  SpeedPAK+使用比例 " . (rtrim($speedPakList['us_adoption'], '%') * 100 . '%') . "  |  最低要求 >=" . (rtrim($speedPakList['us_requirement'], '%') * 100 . '%') . " \n";
                $speedPakList_details .= "英国>￡5直邮交易  |  被评估交易数 {$speedPakList['uk_trans']} 笔  |  SpeedPAK+使用比例 " . (rtrim($speedPakList['uk_adoption'], '%') * 100 . '%') . "  |  最低要求 >=" . (rtrim($speedPakList['uk_requirement'], '%') * 100 . '%') . " \n";
                $speedPakList_details .= "德国>€5直邮交易  |  被评估交易数 {$speedPakList['de_trans']} 笔  |  SpeedPAK+使用比例 " . (rtrim($speedPakList['de_adoption'], '%') * 100 . '%') . "  |  最低要求 >=" . (rtrim($speedPakList['de_requirement'], '%') * 100 . '%') . " \n";

                $dataArr[] = [
                    $account['account_name'],
                    '物流标准(SpeedPAK物流管理方案)',
                    $speedPakList_status,
                    '',
                    $speedPakList_details,
                    "{$speedPakList['start_date']} - {$speedPakList['end_date']}",
                ];
            }

            //卖家设置SpeedPAK物流选项
            if (!empty($get['filter_date'])) {
                $speedPakMisuse = EbaySellerSpeedpakMisuse::find()
                    ->where(['account_id' => $account['id']])
                    ->andWhere("'{$monday}' <= create_pst AND create_pst <= '{$sunday}'")
                    ->orderBy('create_pst DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            } else {
                $speedPakMisuse = EbaySellerSpeedpakMisuse::find()
                    ->where(['account_id' => $account['id']])
                    ->orderBy('create_pst DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            }

            if (!empty($speedPakMisuse)) {
                $speedPakMisuse_status = array_key_exists($speedPakMisuse['account_status'], $speedPakMisuseStatus) ? $speedPakMisuseStatus[$speedPakMisuse['account_status']] : '无';

                $speedPakMisuse_details = "卖家设置SpeedPAK物流选项与实际使用物流不符表现为 {$speedPakMisuse_status} \n";
                $speedPakMisuse_details .= "卖家设置加快型SpeedPAK物流选项  |  被评估交易数 {$speedPakMisuse['expedited_trans']} 笔  |  合规率 " . (rtrim($speedPakMisuse['expedited_comply_rate'], '%') * 100 . '%') . "  |  最低要求 >=" . (rtrim($speedPakMisuse['expedited_required_rate'], '%') * 100 . '%') . " \n";
                $speedPakMisuse_details .= "卖家设置标准型SpeedPAK物流选项  |  被评估交易数 {$speedPakMisuse['standard_trans']} 笔  |  合规率 " . (rtrim($speedPakMisuse['standard_comply_rate'], '%') * 100 . '%') . "  |  最低要求 >=" . (rtrim($speedPakMisuse['standard_required_rate'], '%') * 100 . '%') . " \n";
                $speedPakMisuse_details .= "卖家设置经济型SpeedPAK物流选项  |  被评估交易数 {$speedPakMisuse['economy_trans']} 笔  |  合规率 " . (rtrim($speedPakMisuse['economy_comply_rate'], '%') * 100 . '%') . "  |  最低要求 >=" . (rtrim($speedPakMisuse['economy_required_rate'], '%') * 100 . '%') . " \n";

                $dataArr[] = [
                    $account['account_name'],
                    '物流标准(卖家设置SpeedPAK)',
                    $speedPakMisuse_status,
                    '',
                    $speedPakMisuse_details,
                    "{$speedPakMisuse['start_date']} - {$speedPakMisuse['end_date']}",
                ];
            }

            //海外仓标准
            if (!empty($get['filter_date'])) {
                $sdWarehouse = EbaySellerSdWarehouse::find()
                    ->where(['account_id' => $account['id']])
                    ->andWhere("'{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}'")
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            } else {
                $sdWarehouse = EbaySellerSdWarehouse::find()
                    ->where(['account_id' => $account['id']])
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            }

            if (!empty($sdWarehouse)) {
                $sdWarehouse_status = array_key_exists($sdWarehouse['warehouse_status'], $wareHouseStatus) ? $wareHouseStatus[$sdWarehouse['warehouse_status']] : '无';

                $us = "您的海外仓表现状态为 {$sdWarehouse_status}\n";
                $us .= "物流不良交易比例\n";
                $us .= "标准值  |  当前值\n";
                $us .= ($sdWarehouse['us_ship_defect_sd'] * 100) . "%  |  " . ($sdWarehouse['us_wh_shipping_defect_rate'] * 100) . "%\n";
                $us .= "非当地发货比例\n";
                $us .= "标准值  |  当前值\n";
                $us .= ($sdWarehouse['us_cbt_sd'] * 100) . "%  |  " . ($sdWarehouse['us_wh_cbt_trans_rate'] * 100) . "%\n";

                $dataArr[] = [
                    $account['account_name'],
                    '海外仓表现',
                    $sdWarehouse_status,
                    '美国',
                    $us,
                    "{$sdWarehouse['review_start_date']} - {$sdWarehouse['review_end_date']}",
                ];

                $uk = "您的海外仓表现状态为 {$sdWarehouse_status}\n";
                $uk .= "物流不良交易比例\n";
                $uk .= "标准值  |  当前值\n";
                $uk .= ($sdWarehouse['uk_ship_defect_sd'] * 100) . "%  |  " . ($sdWarehouse['uk_wh_shipping_defect_rate'] * 100) . "%\n";
                $uk .= "非当地发货比例\n";
                $uk .= "标准值  |  当前值\n";
                $uk .= ($sdWarehouse['uk_cbt_sd'] * 100) . "%  |  " . ($sdWarehouse['uk_wh_cbt_trans_rate'] * 100) . "%\n";

                $dataArr[] = [
                    $account['account_name'],
                    '海外仓表现',
                    $sdWarehouse_status,
                    '英国',
                    $uk,
                    "{$sdWarehouse['review_start_date']} - {$sdWarehouse['review_end_date']}",
                ];

                $de = "您的海外仓表现状态为 {$sdWarehouse_status}\n";
                $de .= "物流不良交易比例\n";
                $de .= "标准值  |  当前值\n";
                $de .= ($sdWarehouse['de_ship_defect_sd'] * 100) . "%  |  " . ($sdWarehouse['de_wh_shipping_defect_rate'] * 100) . "%\n";
                $de .= "非当地发货比例\n";
                $de .= "标准值  |  当前值\n";
                $de .= ($sdWarehouse['de_cbt_sd'] * 100) . "%  |  " . ($sdWarehouse['de_wh_cbt_trans_rate'] * 100) . "%\n";

                $dataArr[] = [
                    $account['account_name'],
                    '海外仓表现',
                    $sdWarehouse_status,
                    '德国',
                    $de,
                    "{$sdWarehouse['review_start_date']} - {$sdWarehouse['review_end_date']}",
                ];

                $au = "您的海外仓表现状态为 {$sdWarehouse_status}\n";
                $au .= "物流不良交易比例\n";
                $au .= "标准值  |  当前值\n";
                $au .= ($sdWarehouse['au_ship_defect_sd'] * 100) . "%  |  " . ($sdWarehouse['au_wh_shipping_defect_rate'] * 100) . "%\n";
                $au .= "非当地发货比例\n";
                $au .= "标准值  |  当前值\n";
                $au .= ($sdWarehouse['au_cbt_sd'] * 100) . "%  |  " . ($sdWarehouse['au_wh_cbt_trans_rate'] * 100) . "%\n";

                $dataArr[] = [
                    $account['account_name'],
                    '海外仓表现',
                    $sdWarehouse_status,
                    '澳大利亚',
                    $au,
                    "{$sdWarehouse['review_start_date']} - {$sdWarehouse['review_end_date']}",
                ];

                $other = "您的海外仓表现状态为 {$sdWarehouse_status}\n";
                $other .= "物流不良交易比例\n";
                $other .= "标准值  |  当前值\n";
                $other .= ($sdWarehouse['other_ship_defect_sd'] * 100) . "%  |  " . ($sdWarehouse['other_wh_shipping_defect_rate'] * 100) . "%\n";
                $other .= "非当地发货比例\n";
                $other .= "标准值  |  当前值\n";
                $other .= ($sdWarehouse['other_cbt_sd'] * 100) . "%  |  " . ($sdWarehouse['other_wh_cbt_trans_rate'] * 100) . "%\n";

                $dataArr[] = [
                    $account['account_name'],
                    '海外仓表现',
                    $sdWarehouse_status,
                    '其他海外仓',
                    $other,
                    "{$sdWarehouse['review_start_date']} - {$sdWarehouse['review_end_date']}",
                ];
            }

            //商业计划追踪
            if (!empty($get['filter_date'])) {
                $pgcTracking = EbaySellerPgcTracking::find()
                    ->where(['account_id' => $account['id']])
                    ->andWhere("'{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}'")
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            } else {
                $pgcTracking = EbaySellerPgcTracking::find()
                    ->where(['account_id' => $account['id']])
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            }

            if (!empty($pgcTracking)) {
                $pgcTracking_status = array_key_exists($pgcTracking['pgc_status'], $pgcTrackingStatus) ? $pgcTrackingStatus[$pgcTracking['pgc_status']] : '无';

                $pgcTracking_details = "数据刷新时间 {$pgcTracking['refreshed_date']}, 期间的商业计划追踪表现为 {$pgcTracking_status}\n";
                $pgcTracking_details .= "按照您提交的商业计划，我们做了以下审核  |  标准值  |  状态\n";
                $pgcTracking_details .= "账号累计营业额(美金)  |  {$pgcTracking['account_cmltv_std']}  |  " . (empty($pgcTracking['account_cmltv']) ? '达标' : '不达标') . "\n";
                $pgcTracking_details .= "是否已被冻结  |  {$pgcTracking['suspension_std']}  |  " . (empty($pgcTracking['suspension_sts']) ? '达标' : '不达标') . "\n";
                $pgcTracking_details .= "重复刊登违规  |  {$pgcTracking['duplicate_std']}  |  " . (empty($pgcTracking['duplicate_sts']) ? '达标' : '不达标') . "\n";
                $pgcTracking_details .= "商业计划完成总体表现\n";
                $pgcTracking_details .= "  目标站点完成情况  |  {$pgcTracking['cridr_as_std']}  |  " . (empty($pgcTracking['cridr_as_promised']) ? '达标' : '不达标') . "\n";
                $pgcTracking_details .= "  目标品类完成情况  |  {$pgcTracking['cat_as_std']}  |  " . (empty($pgcTracking['cat_as_promised']) ? '达标' : '不达标') . "\n";
                $pgcTracking_details .= "  目标平均单价完成情况  |  {$pgcTracking['asp_as_std']}  |  " . (empty($pgcTracking['asp_as_promised']) ? '达标' : '不达标') . "\n";
                $pgcTracking_details .= "账号不良交易率  |  {$pgcTracking['dft_std']}  |  " . (empty($pgcTracking['dft_sts']) ? '达标' : '不达标') . "\n";
                $pgcTracking_details .= "海外仓使用率  |  {$pgcTracking['wh_std']}  |  " . (empty($pgcTracking['wh_sts']) ? '达标' : '不达标') . "\n";
                $pgcTracking_details .= "平均月销售额  |  {$pgcTracking['avg_gmv_std']}  |  " . (empty($pgcTracking['avg_gmv_sts']) ? '达标' : '不达标') . "\n";

                $dataArr[] = [
                    $account['account_name'],
                    '商业计划追踪',
                    $pgcTracking_status,
                    '',
                    $pgcTracking_details,
                    $pgcTracking['refreshed_date'],
                ];
            }

            if (!empty($get['filter_date'])) {
                //待处理刊登
                $qclist = EbaySellerQclist::find()
                    ->andWhere(['account_id' => $account['id']])
                    ->andWhere("'{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}'")
                    ->asArray()
                    ->all();

            } else {
                //数据更新时间
                $refreshedDate = EbaySellerQclist::find()
                    ->select('refreshed_date')
                    ->andWhere(['account_id' => $account['id']])
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->scalar();

                //待处理刊登
                $qclist = EbaySellerQclist::find()
                    ->andWhere(['account_id' => $account['id']])
                    ->andWhere(['refreshed_date' => $refreshedDate])
                    ->asArray()
                    ->all();
            }

            if (!empty($qclist)) {
                if ($qclist) {
                    $itemIds = array_column($qclist, 'item_id');

                    //获取刊登信息
                    $listing = (new Query())->from('{{%ebay_online_listing}}')
                        ->select('itemid, sku, title, seller_user, product_line, site, listing_status')
                        ->andWhere(['account_id' => $account['old_account_id']])
                        ->andWhere(['in', 'itemid', $itemIds])
                        ->createCommand(Yii::$app->db_product)
                        ->queryAll();

                    if (!empty($listing)) {
                        $tmp = [];
                        foreach ($listing as $item) {
                            $tmp[$item['itemid']] = $item;
                        }
                        $listing = $tmp;
                    }
                }

                $qclist_refreshed_date = $qclist['refreshed_date'];
                $qclist_details = "刊登编号  |  到期时间  |  下线时间  |  刊登状态  |  交易额  |  交易量  |  问题交易量\n";
                foreach ($qclist as $qc) {
                    $listing_status = '';
                    if (array_key_exists($qc['item_id'], $listing)) {
                        if ($listing[$qc['item_id']]['listing_status'] == 'Active') {
                            $listing_status = '上线';
                        } else {
                            $listing_status = '下线';
                        }
                    }

                    $qclist_details .= "{$qc['item_id']}  |  {$qc['rm_dead_dt']}  |  {$qc['auct_end_dt']}  |  {$listing_status}  |  {$qc['gmv_usd']}  |  {$qc['total_trans']}  |  {$qc['bbe_trans']} \n";
                }

                $dataArr[] = [
                    $account['account_name'],
                    '待处理刊登',
                    '',
                    '',
                    $qclist_details,
                    $qclist_refreshed_date,
                ];
            }
        }

        //创建PHPExcel对象
        $obj = new \PHPExcel();
        //创建excel写入对象
        $writer = new \PHPExcel_Writer_Excel5($obj);
        //得到当前工作表对象
        $curSheet = $obj->getActiveSheet();

        //设置行高
        $curSheet->getDefaultRowDimension()->setRowHeight(120);
        //设置单元格居中
        $curSheet->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

        $fieldNum = count($fieldArr);
        $dataRow = count($dataArr) + 2;

        for ($col = 0; $col < $fieldNum; ++$col) {
            $colName = \PHPExcel_Cell::stringFromColumnIndex($col);
            $cellName = $colName . '1';
            $curSheet->setCellValue($cellName, $fieldArr[$col]);
            if ($fieldArr[$col] == '详情') {
                $curSheet->getColumnDimension($colName)->setWidth(65);
            } else if ($fieldArr[$col] == '考核时间') {
                $curSheet->getColumnDimension($colName)->setWidth(30);
            } else {
                $curSheet->getColumnDimension($colName)->setWidth(20);
            }
        }

        for ($row = 2; $row < $dataRow; ++$row) {
            for ($col = 0; $col < $fieldNum; ++$col) {
                $cellName = \PHPExcel_Cell::stringFromColumnIndex($col) . $row;

                $value = $dataArr[$row - 2][$col];
                if ($value == '正常') {
                    $curSheet->getStyle($cellName)->getFont()->getColor()->setARGB('ff52c41a');
                } else if ($value == '超标') {
                    $curSheet->getStyle($cellName)->getFont()->getColor()->setARGB('fffa541c');
                } else if ($value == '警告') {
                    $curSheet->getStyle($cellName)->getFont()->getColor()->setARGB('fffaad14');
                } else if ($value == '限制') {
                    $curSheet->getStyle($cellName)->getFont()->getColor()->setARGB('ffeb2f96');
                } else if ($value == '不考核') {
                    $curSheet->getStyle($cellName)->getFont()->getColor()->setARGB('ff1890ff');
                }

                if (strpos($value, '最高评级') !== false) {
                    $curSheet->getStyle($cellName)->getFont()->getColor()->setARGB('ff52c41a');
                } else if (strpos($value, '低于标准') !== false) {
                    $curSheet->getStyle($cellName)->getFont()->getColor()->setARGB('fffa541c');
                }

                $curSheet->setCellValue($cellName, $value);
                $curSheet->getStyle($cellName)->getAlignment()->setWrapText(true);
            }
        }

        $fileName = 'ebayaccountoverviewdetails_' . date('YmdHis', time());
        header('Content-Type: application/vnd.ms-execl');
        header('Content-Disposition: attachment;filename="' . $fileName . '.xls"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
    }

    /**
     * 导出待处理刊登
     */
    public function actionExportqclist()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        //获取get参数
        $get = YII::$app->request->get();
        //id数组
        $ids = !empty($get['ids']) ? $get['ids'] : [];
        //导出数据
        $data = [];

        //只能查询到客服绑定账号
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_EB);

        if (is_array($ids) && !empty($ids)) {
            $data = EbayAccountOverview::find()
                ->select('id, account_name')
                ->andWhere(['in', 'id', $accountIds])
                ->andWhere(['status' => Account::STATUS_VALID])
                ->andWhere(['in', 'id', $ids])
                ->orderBy('id DESC')
                ->asArray()
                ->all();

        } else {
            $query = EbayAccountOverview::find()
                ->alias('a')
                ->select('a.id, a.account_name')
                ->andWhere(['in', 'a.id', $accountIds])
                ->andWhere(['a.status' => Account::STATUS_VALID]);

            //请求参数
            $params = !empty($get['EbayAccountOverview']) ? $get['EbayAccountOverview'] : [];
            $params['filter_date'] = !empty($get['filter_date']) ? $get['filter_date'] : '';

            //账号
            if (!empty($params['account_id'])) {
                $query->andWhere(['a.id' => $params['account_id']]);
            }

            $cond[] = 'and';

            //导出筛选条件
            $this->exportFilter($cond, $params);

            $data = $query->andWhere($cond)
                ->orderBy('id DESC')
                ->asArray()
                ->all();
        }

        if (empty($data)) {
            $this->_showMessage('数据为空', false);
        }

        //标题数组
        $fieldArr = [
            '账号',
            '刊登标题',
            'itemID',
            '刊登状态',
            '交易额',
            '交易量',
            '问题交易量',
            '问题交易量比例',
            'SKU',
            '站点',
            '大仓',
            '产品线',
            '销售员',
        ];
        //导出数据数组
        $dataArr = [];

        foreach ($data as $account) {
            //数据更新时间
            $refreshedDate = EbaySellerQclist::find()
                ->select('refreshed_date')
                ->andWhere(['account_id' => $account['id']])
                ->orderBy('refreshed_date DESC')
                ->limit(1)
                ->scalar();

            if (!empty($refreshedDate)) {
                //待处理刊登列表
                if (!empty($get['filter_date'])) {
                    $time = strtotime($get['filter_date']);
                    //周一
                    $monday = date('Y-m-d', strtotime('monday this week', $time));
                    //周日
                    $sunday = date('Y-m-d', strtotime('sunday this week', $time));

                    $qcList = EbaySellerQclist::find()
                        ->andWhere(['account_id' => $account['id']])
                        ->andWhere("'{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}'")
                        ->asArray()
                        ->all();
                } else {
                    $qcList = EbaySellerQclist::find()
                        ->andWhere(['account_id' => $account['id']])
                        ->andWhere(['refreshed_date' => $refreshedDate])
                        ->asArray()
                        ->all();
                }

                $accountInfo = Account::findOne($account['id']);

                if (!empty($qcList) && !empty($accountInfo)) {
                    $itemIds = array_column($qcList, 'item_id');

                    //获取刊登信息
                    $listing = (new Query())->from('{{%ebay_online_listing}}')
                        ->select('itemid, sku, title, seller_user, product_line, site, listing_status')
                        ->andWhere(['account_id' => $accountInfo->old_account_id])
                        ->andWhere(['in', 'itemid', $itemIds])
                        ->createCommand(Yii::$app->db_product)
                        ->queryAll();

                    if (!empty($listing)) {
                        $tmp = [];
                        foreach ($listing as $item) {
                            $tmp[$item['itemid']] = $item;
                        }
                        $listing = $tmp;
                    }
                }

                if (!empty($qcList)) {
                    foreach ($qcList as $qc) {
                        $listing_status = '';
                        if (array_key_exists($qc['item_id'], $listing)) {
                            if ($listing[$qc['item_id']]['listing_status'] == 'Active') {
                                $listing_status = '上线';
                            } else {
                                $listing_status = '下线';
                            }
                        }

                        $dataArr[] = [
                            $account['account_name'],
                            array_key_exists($qc['item_id'], $listing) ? $listing[$qc['item_id']]['title'] : '',
                            $qc['item_id'],
                            $listing_status,
                            $qc['gmv_usd'],
                            $qc['total_trans'],
                            $qc['bbe_trans'],
                            round($qc['bbe_trans'] / $qc['total_trans'], 2) . '%',
                            array_key_exists($qc['item_id'], $listing) ? $listing[$qc['item_id']]['sku'] : '',
                            array_key_exists($qc['item_id'], $listing) ? $listing[$qc['item_id']]['site'] : '',
                            '',
                            array_key_exists($qc['item_id'], $listing) ? $listing[$qc['item_id']]['product_line'] : '',
                            array_key_exists($qc['item_id'], $listing) ? $listing[$qc['item_id']]['seller_user'] : '',
                        ];
                    }
                }
            }
        }

        VHelper::exportExcel($fieldArr, $dataArr, 'ebayqclist_' . date('YmdHis'));
    }
    
    //导出SpeedPAK物流管理方案数据
    public function actionListdownload(){
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        //获取get参数
        $get = YII::$app->request->get();
        //id数组
        $ids = !empty($get['ids']) ? $get['ids'] : [];
        //导出数据
        $data = [];

        //只能查询到客服绑定账号
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_EB);

        if (is_array($ids) && !empty($ids)) {
            $data = EbayAccountOverview::find()
                ->select('id, account_name')
                ->andWhere(['in', 'id', $accountIds])
                ->andWhere(['status' => Account::STATUS_VALID])
                ->andWhere(['in', 'id', $ids])
                ->orderBy('id DESC')
                ->asArray()
                ->all();

        } else {
            $query = EbayAccountOverview::find()
                ->alias('a')
                ->select('a.id, a.account_name')
                ->andWhere(['in', 'a.id', $accountIds])
                ->andWhere(['a.status' => Account::STATUS_VALID]);

            //请求参数
            $params = !empty($get['EbayAccountOverview']) ? $get['EbayAccountOverview'] : [];
            $params['filter_date'] = !empty($get['filter_date']) ? $get['filter_date'] : '';

            //账号
            if (!empty($params['account_id'])) {
                $query->andWhere(['a.id' => $params['account_id']]);
            }

            $cond[] = 'and';

            //导出筛选条件
            $this->exportFilter($cond, $params);
            $data = $query->andWhere($cond)
                ->orderBy('id DESC')
                ->asArray()
                ->all();
        }     
        if (empty($data)) {
            $this->_showMessage('数据为空', false);
        }

        //标题数组
        $fieldArr = [
            '数据创建时间',
            '交易号',
            '刊登号',
            '买家付款时间',
            '买家向路',
            '单价',
            '单价货币币种',
            '物品所在地',
            '买家选择物流选项',
            '买家选择物流类型',
            '卖家上传的跟踪号',
            '卖家填写的物流供应商',
            '卖家使用的物流服务',
            '揽收扫描时间',
            '卖家承诺订单处理时(天)',
            '是否使用SpeedPAK及以上服务',
            '揽收扫描是否及时',
            '卖家使用的物流服务是否与买家选择相匹配',
            '交易是否合格',
        ];
        //导出数据数组
         $dataArr = [];
         foreach($data as $v){
             //获取SpeedPAK 物流管理方案数据
             $list=EbayListDownload::find()->where(['account_id'=>$v['id']])->asArray()->all();
             foreach ($list as $key => $vo) {
                 $dataArr[]=[
                     $vo['createPst'],
                     $vo['transId'],
                     $vo['itemId'],
                     $vo['transPaidDate'],
                     $vo['buyerAddressCntry'],
                     $vo['asp'],
                     $vo['aspCurrency'],
                     $vo['itemLocation'], 
                     $vo['buyerSelShipOpt'],
                     $vo['buyerSelShipType'],
                     $vo['trackingNumber'],
                     $vo['carrierName'],
                     $vo['shippingService'],
                     $vo['aScanDate'],
                     $vo['promisedHandlingTime'],
                     $vo['useSpeedpakPlusFlag'],
                     $vo['aScanOnTimeFlag'],
                     $vo['serviceLevelMatchFlag'],
                     $vo['transComplyFlag'],
                 ];
             }               
         }          
        VHelper::exportExcel($fieldArr, $dataArr, 'ebay_list_download_' . date('YmdHis'));
    }
    //导出买家选择SpeedPAK物流选项数据
    public function actionMisusedownload(){
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //获取get参数
        $get = YII::$app->request->get();
        //id数组
        $ids = !empty($get['ids']) ? $get['ids'] : [];
        //导出数据
        $data = [];

        //只能查询到客服绑定账号
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_EB);
        if (is_array($ids) && !empty($ids)) {
            $data = EbayAccountOverview::find()
                ->select('id, account_name')
                ->andWhere(['in', 'id', $accountIds])
                ->andWhere(['status' => Account::STATUS_VALID])
                ->andWhere(['in', 'id', $ids])
                ->orderBy('id DESC')
                ->asArray()
                ->all();
        } else {
            $query = EbayAccountOverview::find()
                ->alias('a')
                ->select('a.id, a.account_name')
                ->andWhere(['in', 'a.id', $accountIds])
                ->andWhere(['a.status' => Account::STATUS_VALID]);
            //请求参数
            $params = !empty($get['EbayAccountOverview']) ? $get['EbayAccountOverview'] : [];
            $params['filter_date'] = !empty($get['filter_date']) ? $get['filter_date'] : '';
            //账号
            if (!empty($params['account_id'])) {
                $query->andWhere(['a.id' => $params['account_id']]);
            }
            $cond[] = 'and';
            //导出筛选条件
            $this->exportFilter($cond, $params);
            $data = $query->andWhere($cond)
                ->orderBy('id DESC')
                ->asArray()
                ->all();
        }     
        if (empty($data)) {
            $this->_showMessage('数据为空', false);
        }
        //标题数组
        $fieldArr = [
            '数据创建时间',
            '账号名字',
            '交易号',
            '刊登号',
            '买家付款时间',
            '卖家上传的跟踪号',
            '买家选择物流选项',
            '卖家使用SpeedPAK服务类型',
        ];
        //导出数据数组
         $dataArr = [];
         foreach($data as $v){
            $ebaymisuse= EbayMisuseDownload::find()->where(['account_id'=>$v['id']])->asArray()->all();
            foreach($ebaymisuse as $vo){
               $dataArr[]=[
                   $vo['createPst'],
                   $v['account_name'],
                   $vo['transId'],
                   $vo['itemId'],
                   $vo['transPaidDate'],
                   $vo['trackingNumber'],
                   $vo['buyerSelShipOpt'],
                   $vo['speedpakServiceLevel'],
               ]; 
                
            }      
         }
        VHelper::exportExcel($fieldArr, $dataArr, 'ebay_misuse_download_' . date('YmdHis')); 
        
    }
    
     
}