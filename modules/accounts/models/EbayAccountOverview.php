<?php

namespace app\modules\accounts\models;

use app\modules\mails\models\EbaySellerSpeedpakList;
use app\modules\mails\models\EbaySellerSpeedpakMisuse;
use Yii;
use app\components\Model;
use app\modules\mails\models\EbaySellerAccountOverview;
use app\modules\mails\models\EbaySellerEdsShippingPolicy;
use app\modules\mails\models\EbaySellerEpacketShippingPolicy;
use app\modules\mails\models\EbaySellerLtnp;
use app\modules\mails\models\EbaySellerPgcTracking;
use app\modules\mails\models\EbaySellerQclist;
use app\modules\mails\models\EbaySellerSdWarehouse;
use app\modules\mails\models\EbaySellerShip;
use app\modules\mails\models\EbaySellerStandardsProfile;
use app\modules\mails\models\EbaySellerTci;
use yii\data\ActiveDataProvider;
use yii\data\Sort;

/**
 * ebay账号表现
 */
class EbayAccountOverview extends Model
{
    /**
     * 返回操作表名
     */
    public static function tableName()
    {
        return '{{%account}}';
    }

    /**
     * 属性字段
     */
    public function attributes()
    {
        $attributes = parent::attributes();
        $extAttributes = [
            'account_id',
            'program',
            'program_status',
            'current_level',
            'current_level_status',
            'projected_level',
            'projected_level_status',
            'bad_trade_rate',
            'bad_trade_rate_status',
            'unresolve_dispute_rate',
            'unresolve_dispute_rate_status',
            'transport_delay_rate',
            'transport_delay_rate_status',
            'ltnp',
            'ltnp_status',
            'ship',
            'ship_status',
            'tci',
            'tci_status',
            'shipping_policy',
            'shipping_policy_status',
            'sd_warehouse',
            'sd_warehouse_status',
            'pgc_tracking',
            'pgc_tracking_status',
            'qclist',
            'qclist_status',
        ];
        return array_merge($attributes, $extAttributes);
    }

    public function rules()
    {
        return [
            [
                [
                    'account_id',
                    'program',
                    'program_status',
                    'current_level',
                    'current_level_status',
                    'projected_level',
                    'projected_level_status',
                    'bad_trade_rate',
                    'bad_trade_rate_status',
                    'unresolve_dispute_rate',
                    'unresolve_dispute_rate_status',
                    'transport_delay_rate',
                    'transport_delay_rate_status',
                    'ltnp',
                    'ltnp_status',
                    'ship',
                    'ship_status',
                    'tci',
                    'tci_status',
                    'shipping_policy',
                    'shipping_policy_status',
                    'sd_warehouse',
                    'sd_warehouse_status',
                    'pgc_tracking',
                    'pgc_tracking_status',
                    'qclist',
                    'qclist_status',
                ],
                'safe'
            ],
        ];
    }

    /**
     * 搜索
     */
    public function searchList($params = [])
    {
        $query = self::find()
            ->alias('a')
            ->select('a.id, a.account_name')
            ->andWhere(['a.status' => Account::STATUS_VALID]);

        //加载参数
        $this->load($params);

        //只能查询到客服绑定账号
        $accountIds = UserAccount::getCurrentUserPlatformAccountIds(Platform::PLATFORM_CODE_EB);
        $query->andWhere(['in', 'a.id', $accountIds]);

        //账号
        if (!empty($this->account_id)) {
            $query->andWhere(['a.id' => $this->account_id]);
        }

        $cond[] = 'and';

        //站点
        $standardsProfile = EbaySellerStandardsProfile::tableName();
        if (isset($this->program_status) && $this->program_status != '') {

            if (!empty($params['filter_date'])) {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile} 
                                    WHERE account_id = a.id
                                    AND program = '{$this->program_status}'
                                    AND DATE_FORMAT(evaluation_date, '%Y-%m-%d') = '{$params['filter_date']}'
                              )";
            } else {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile} 
                                    WHERE account_id = a.id
                                    AND program = '{$this->program_status}'
                                    AND evaluation_date = (
                                       SELECT evaluation_date FROM {$standardsProfile}
                                       WHERE account_id = a.id AND program = '{$this->program_status}'
                                       ORDER BY evaluation_date DESC
                                       LIMIT 1
                                    )
                              )";
            }
        }

        //当前账户等级
        if (isset($this->current_level_status) && $this->current_level_status != '') {
            if (!empty($params['filter_date'])) {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND cycle_type = 'CURRENT'
                                    AND standards_level = '{$this->current_level_status}'
                                    AND DATE_FORMAT(evaluation_date, '%Y-%m-%d') = '{$params['filter_date']}'
                              )";
            } else {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND cycle_type = 'CURRENT'
                                    AND standards_level = '{$this->current_level_status}'
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
        if (isset($this->projected_level_status) && $this->projected_level_status != '') {
            if (!empty($params['filter_date'])) {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND cycle_type = 'PROJECTED'
                                    AND standards_level = '{$this->projected_level_status}'
                                    AND DATE_FORMAT(evaluation_date, '%Y-%m-%d') = '{$params['filter_date']}'
                              )";
            } else {
                $cond[] = "EXISTS (SELECT * FROM {$standardsProfile}
                                    WHERE account_id = a.id
                                    AND cycle_type = 'PROJECTED'
                                    AND standards_level = '{$this->projected_level_status}'
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
        if (!empty($this->bad_trade_rate_status['low']) && !empty($this->bad_trade_rate_status['high'])) {
            $low = floatval($this->bad_trade_rate_status['low']);
            $high = floatval($this->bad_trade_rate_status['high']);

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

        } else if (!empty($this->bad_trade_rate_status['low'])) {
            $low = floatval($this->bad_trade_rate_status['low']);

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

        } else if (!empty($this->bad_trade_rate_status['high'])) {
            $high = floatval($this->bad_trade_rate_status['high']);

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
        if (!empty($this->unresolve_dispute_rate_status['low']) && !empty($this->unresolve_dispute_rate_status['high'])) {
            $low = floatval($this->unresolve_dispute_rate_status['low']);
            $high = floatval($this->unresolve_dispute_rate_status['high']);

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

        } else if (!empty($this->unresolve_dispute_rate_status['low'])) {
            $low = floatval($this->unresolve_dispute_rate_status['low']);

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

        } else if (!empty($this->unresolve_dispute_rate_status['high'])) {
            $high = floatval($this->unresolve_dispute_rate_status['high']);

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
        if (!empty($this->transport_delay_rate_status['low']) && !empty($this->transport_delay_rate_status['high'])) {
            $low = floatval($this->transport_delay_rate_status['low']);
            $high = floatval($this->transport_delay_rate_status['high']);

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

        } else if (!empty($this->transport_delay_rate_status['low'])) {
            $low = floatval($this->transport_delay_rate_status['low']);

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

        } else if (!empty($this->transport_delay_rate_status['high'])) {
            $high = floatval($this->transport_delay_rate_status['high']);

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
        if (isset($this->ltnp_status) && $this->ltnp_status != '') {
            $tableName = EbaySellerLtnp::tableName();
            if ($this->ltnp_status == -1) {

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
                                  AND status_lst_eval = '{$this->ltnp_status}' 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               ) OR ";

                    $sub .= "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND snad_status_lst_eval = '{$this->ltnp_status}' 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               ) OR ";

                    $sub .= "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND program_status_lst_eval = '{$this->ltnp_status}' 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               )";

                } else {
                    $sub = "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND status_lst_eval = '{$this->ltnp_status}' 
                                  AND refreshed_date = (
                                    SELECT refreshed_date FROM {$tableName} 
                                    WHERE account_id = a.id 
                                    ORDER BY refreshed_date DESC 
                                    LIMIT 1
                                  )
                               ) OR ";

                    $sub .= "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND snad_status_lst_eval = '{$this->ltnp_status}' 
                                  AND refreshed_date = (
                                    SELECT refreshed_date FROM {$tableName} 
                                    WHERE account_id = a.id 
                                    ORDER BY refreshed_date DESC 
                                    LIMIT 1
                                  )
                               ) OR ";

                    $sub .= "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND program_status_lst_eval = '{$this->ltnp_status}' 
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
        if (isset($this->ship_status) && $this->ship_status != '') {
            $tableName = EbaySellerShip::tableName();
            if ($this->ship_status == -1) {

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
                                  AND result = '{$this->ship_status}' 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               )";
                } else {
                    $cond[] = "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND result = '{$this->ship_status}' 
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
        if (isset($this->tci_status) && $this->tci_status != '') {
            $tableName = EbaySellerTci::tableName();
            if ($this->tci_status == -1) {
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
                                  AND result = '{$this->tci_status}' 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               )";
                } else {
                    $cond[] = "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND result = '{$this->tci_status}' 
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
        if (isset($this->shipping_policy_status) && $this->shipping_policy_status != '') {
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
                                  AND eds_status = '{$this->shipping_policy_status}' 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               ) OR ";


                    $sub .= "EXISTS (SELECT * FROM {$tableName2} 
                                  WHERE account_id = a.id 
                                  AND e_packet_status = '{$this->shipping_policy_status}' 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               ) OR ";

                    $sub .= "EXISTS (SELECT * FROM {$tableName3} 
                                  WHERE account_id = a.id 
                                  AND account_status = '{$this->shipping_policy_status}' 
                                  AND ('{$monday}' <= create_pst AND create_pst <= '{$sunday}')  
                               ) OR ";

                    $sub .= "EXISTS (SELECT * FROM {$tableName4} 
                                  WHERE account_id = a.id 
                                  AND account_status = '{$this->shipping_policy_status}' 
                                  AND ('{$monday}' <= create_pst AND create_pst <= '{$sunday}')  
                               )";
                } else {
                    $sub = "EXISTS (SELECT * FROM {$tableName1} 
                                  WHERE account_id = a.id 
                                  AND eds_status = '{$this->shipping_policy_status}' 
                                  AND refreshed_date = (
                                    SELECT refreshed_date FROM {$tableName1} 
                                    WHERE account_id = a.id 
                                    ORDER BY refreshed_date DESC 
                                    LIMIT 1
                                  )
                               ) OR ";


                    $sub .= "EXISTS (SELECT * FROM {$tableName2} 
                                  WHERE account_id = a.id 
                                  AND e_packet_status = '{$this->shipping_policy_status}' 
                                  AND refreshed_date = (
                                    SELECT refreshed_date FROM {$tableName2} 
                                    WHERE account_id = a.id 
                                    ORDER BY refreshed_date DESC 
                                    LIMIT 1
                                  )
                               ) OR ";

                    $sub .= "EXISTS (SELECT * FROM {$tableName3} 
                                  WHERE account_id = a.id 
                                  AND account_status = '{$this->shipping_policy_status}' 
                                  AND create_pst = (
                                    SELECT create_pst FROM {$tableName3} 
                                    WHERE account_id = a.id 
                                    ORDER BY create_pst DESC 
                                    LIMIT 1
                                  )
                               ) OR ";

                    $sub .= "EXISTS (SELECT * FROM {$tableName4} 
                                  WHERE account_id = a.id 
                                  AND account_status = '{$this->shipping_policy_status}' 
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
        if (isset($this->sd_warehouse_status) && $this->sd_warehouse_status != '') {
            $tableName = EbaySellerSdWarehouse::tableName();
            if ($this->sd_warehouse_status == -1) {
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
                                  AND warehouse_status = '{$this->sd_warehouse_status}' 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               )";
                } else {
                    $cond[] = "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND warehouse_status = '{$this->sd_warehouse_status}' 
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
        if (isset($this->pgc_tracking_status) && $this->pgc_tracking_status != '') {
            $tableName = EbaySellerPgcTracking::tableName();
            if ($this->pgc_tracking_status == -1) {
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
                                  AND pgc_status = '{$this->pgc_tracking_status}' 
                                  AND ('{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}')  
                               )";
                } else {
                    $cond[] = "EXISTS (SELECT * FROM {$tableName} 
                                  WHERE account_id = a.id 
                                  AND pgc_status = '{$this->pgc_tracking_status}' 
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
        if (isset($this->qclist_status) && $this->qclist_status != '') {
            $tableName = EbaySellerQclist::tableName();
            if ($this->qclist_status == 'Y') {
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

        $query->andWhere($cond);

        $sort = new Sort();
        $sort->defaultOrder = [
            'id' => SORT_DESC,
        ];

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => $sort,
            'pagination' => [
                'pageSize' => !empty($params['page_size']) ? $params['page_size'] : 20,
                'pageParam' => 'p',
                'pageSizeParam' => 'page_size',
            ],
        ]);

        $models = $dataProvider->getModels();
        $this->chgModel($models, $dataProvider, $params);
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    /**
     * 修改模型数据
     */
    public function chgModel(&$models, &$dataProvider, $params = [])
    {
        $siteList = self::getSiteList();
        $accountLevel = self::getAccountLevel();
        $ltnpStatus = self::getLtnpStatus();
        $shipStatus = self::getShippingStatus();
        $nonShipStatus = self::getNonShippingStatus();
        $edshippingStatus = self::getEdshippingStatus();
        $epacketShippingStatus = self::getEpacketShippingStatus();
        $speedPakListStatus = self::getSpeedPakListStatus();
        $speedPakMisuseStatus = self::getSpeedPakMisuseStatus();
        $wareHouseStatus = self::getWareHouseStatus();
        $pgcTrackingStatus = self::getPgcTrackingStatus();
        $qcListingStatus = self::getQcListingStatus();

        foreach ($models as $mkey => $model) {

            //DEFECTIVE_TRANSACTION_RATE 不良交易率
            //CLAIMS_SAF_RATE 未解决纠纷率
            //SHIPPING_MISS_RATE 延迟发货率

            if (!empty($params['filter_date'])) {
                //卖家成绩表
                $data = EbaySellerStandardsProfile::find()
                    ->andWhere(['account_id' => $model->id])
                    ->andWhere(['in', 'metric_key', ['DEFECTIVE_TRANSACTION_RATE', 'CLAIMS_SAF_RATE', 'SHIPPING_MISS_RATE']])
                    ->andWhere("DATE_FORMAT(evaluation_date, '%Y-%m-%d') = '{$params['filter_date']}'")
                    ->orderBy('id DESC, evaluation_date DESC')
                    ->asArray()
                    ->all();
            } else {
                //卖家成绩表
                $data = EbaySellerStandardsProfile::find()
                    ->andWhere(['account_id' => $model->id])
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

            if (!empty($data)) {
                foreach ($data as $item) {
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
                                        //判断是否是比率
                                        $seller[$item['program']]['bad_trade_is_rate'] = false;
                                    } else {
                                        $seller[$item['program']]['bad_trade_rate'] = $metricValue['value'];
                                        //判断是否是比率
                                        $seller[$item['program']]['bad_trade_is_rate'] = true;
                                    }
                                } else {
                                    $seller[$item['program']]['bad_trade_rate'] = $metricValue;
                                    //判断是否是比率
                                    $seller[$item['program']]['bad_trade_is_rate'] = false;
                                }
                                $seller[$item['program']]['bad_trade_rate_id'] = $item['id'];
                            }
                        }

                        //未解决纠纷率
                        if ($item['metric_key'] == 'CLAIMS_SAF_RATE') {
                            if (!isset($seller[$item['program']]['unresolve_dispute_rate'])) {
                                $metricValue = json_decode($item['metric_value'], true);
                                if (isset($metricValue['value'])) {
                                    if (empty($metricValue['value']) || $metricValue['value'] == '0.00') {
                                        $seller[$item['program']]['unresolve_dispute_rate'] = isset($metricValue['numerator']) ? $metricValue['numerator'] : 0;
                                        //判断是否是比率
                                        $seller[$item['program']]['unresolve_dispute_is_rate'] = false;
                                    } else {
                                        $seller[$item['program']]['unresolve_dispute_rate'] = $metricValue['value'];
                                        //判断是否是比率
                                        $seller[$item['program']]['unresolve_dispute_is_rate'] = true;
                                    }
                                } else {
                                    $seller[$item['program']]['unresolve_dispute_rate'] = $metricValue;
                                    //判断是否是比率
                                    $seller[$item['program']]['unresolve_dispute_is_rate'] = false;
                                }
                                $seller[$item['program']]['unresolve_dispute_rate_id'] = $item['id'];
                            }
                        }

                        //运送延迟率
                        if ($item['metric_key'] == 'SHIPPING_MISS_RATE') {
                            if (!isset($seller[$item['program']]['transport_delay_rate'])) {
                                $metricValue = json_decode($item['metric_value'], true);
                                if (isset($metricValue['value'])) {
                                    if (empty($metricValue['value']) || $metricValue['value'] == '0.00') {
                                        $seller[$item['program']]['transport_delay_rate'] = isset($metricValue['numerator']) ? $metricValue['numerator'] : 0;
                                        //判断是否是比率
                                        $seller[$item['program']]['transport_delay_is_rate'] = false;
                                    } else {
                                        $seller[$item['program']]['transport_delay_rate'] = $metricValue['value'];
                                        //判断是否是比率
                                        $seller[$item['program']]['transport_delay_is_rate'] = true;
                                    }
                                } else {
                                    $seller[$item['program']]['transport_delay_rate'] = $metricValue;
                                    //判断是否是比率
                                    $seller[$item['program']]['transport_delay_is_rate'] = false;
                                }
                                $seller[$item['program']]['transport_delay_rate_id'] = $item['id'];
                            }
                        }
                    } else {
                        if (empty($seller[$item['program']]['current_level'])) {
                            $seller[$item['program']]['current_level'] = $item['standards_level'];
                        }
                    }
                }
            }

            if (!empty($seller)) {
                foreach ($seller as $key => $item) {
                    $isProgram = true;
                    $isCurrentLevel = true;
                    $isProjectedLevel = true;
                    $isBadTradeRate = true;
                    $isUnresolveDisputeRate = true;
                    $isTransportDelayRate = true;

                    if (!empty($this->program_status) && $key != $this->program_status) {
                        $isProgram = false;
                    }
                    if (!empty($this->current_level_status) && $item['current_level'] != $this->current_level_status) {
                        $isCurrentLevel = false;
                    }
                    if (!empty($this->projected_level_status) && $item['projected_level'] != $this->projected_level_status) {
                        $isProjectedLevel = false;
                    }
                    if (!empty($this->bad_trade_rate_status['low']) && !empty($this->bad_trade_rate_status['high'])) {
                        if ($item['bad_trade_rate'] < $this->bad_trade_rate_status['low'] || $item['bad_trade_rate'] > $this->bad_trade_rate_status['high']) {
                            $isBadTradeRate = false;
                        }
                    } else if (!empty($this->bad_trade_rate_status['low'])) {
                        if ($item['bad_trade_rate'] < $this->bad_trade_rate_status['low']) {
                            $isBadTradeRate = false;
                        }
                    } else if (!empty($this->bad_trade_rate_status['high'])) {
                        if ($item['bad_trade_rate'] > $this->bad_trade_rate_status['high']) {
                            $isBadTradeRate = false;
                        }
                    }
                    if (!empty($this->unresolve_dispute_rate_status['low']) && !empty($this->unresolve_dispute_rate_status['high'])) {
                        if ($item['unresolve_dispute_rate'] < $this->unresolve_dispute_rate_status['low'] || $item['unresolve_dispute_rate'] > $this->unresolve_dispute_rate_status['high']) {
                            $isUnresolveDisputeRate = false;
                        }
                    } else if (!empty($this->unresolve_dispute_rate_status['low'])) {
                        if ($item['unresolve_dispute_rate'] < $this->unresolve_dispute_rate_status['low']) {
                            $isUnresolveDisputeRate = false;
                        }
                    } else if (!empty($this->unresolve_dispute_rate_status['high'])) {
                        if ($item['unresolve_dispute_rate'] > $this->unresolve_dispute_rate_status['high']) {
                            $isUnresolveDisputeRate = false;
                        }
                    }
                    if (!empty($this->transport_delay_rate_status['low']) && !empty($this->transport_delay_rate_status['high'])) {
                        if ($item['transport_delay_rate'] < $this->transport_delay_rate_status['low'] || $item['transport_delay_rate'] > $this->transport_delay_rate_status['high']) {
                            $isTransportDelayRate = false;
                        }
                    } else if (!empty($this->transport_delay_rate_status['low'])) {
                        if ($item['transport_delay_rate'] < $this->transport_delay_rate_status['low']) {
                            $isTransportDelayRate = false;
                        }
                    } else if (!empty($this->transport_delay_rate_status['high'])) {
                        if ($item['transport_delay_rate'] > $this->transport_delay_rate_status['high']) {
                            $isTransportDelayRate = false;
                        }
                    }

                    //最后判断该记录是否有显示必要
                    if (!($isProgram && $isCurrentLevel && $isProjectedLevel && $isBadTradeRate && $isUnresolveDisputeRate && $isTransportDelayRate)) {
                        unset($seller[$key]);
                    }
                }

                //如果最后该账号没有一个站点可以显示，则把该账号的一行也删除
                if (empty($seller)) {
                    unset($models[$mkey]);
                    continue;
                }
            }

            if (!empty($seller)) {
                $program = '<table class="nest-table">';
                $currentLevel = '<table class="nest-table">';
                $projectedLevel = '<table class="nest-table">';
                $badTradeRate = '<table class="nest-table">';
                $unresolveDisputeRate = '<table class="nest-table">';
                $transportDelayRate = '<table class="nest-table">';

                foreach ($seller as $key => $item) {
                    $programName = array_key_exists($key, $siteList) ? $siteList[$key] : '';
                    $program .= "<tr><td>{$programName}</td></tr>";

                    $currentLevelName = array_key_exists($item['current_level'], $accountLevel) ? $accountLevel[$item['current_level']] : '无';
                    $currentLevel .= "<tr><td><a class='status' data-status='{$currentLevelName}'>{$currentLevelName}</a></td></tr>";

                    $projectedLevelName = array_key_exists($item['projected_level'], $accountLevel) ? $accountLevel[$item['projected_level']] : '无';
                    $projectedLevel .= "<tr><td><a class='status' data-status='{$projectedLevelName}'>{$projectedLevelName}</a></td></tr>";

                    if (!empty($item['bad_trade_is_rate'])) {
                        $bad_trade_rate = !empty($item['bad_trade_rate']) ? $item['bad_trade_rate'] : '0.00';
                        $bad_trade_rate .= '%';
                    } else {
                        $bad_trade_rate = !empty($item['bad_trade_rate']) ? $item['bad_trade_rate'] : '0';
                    }
                    $badTradeRate .= "<tr><td><a class='status' title='不良交易率' data-type='bad_trade_rate' data-accountid='{$model->id}' data-id='{$item['bad_trade_rate_id']}'>{$bad_trade_rate}</a></td></tr>";

                    if (!empty($item['unresolve_dispute_is_rate'])) {
                        $unresolve_dispute_rate = !empty($item['unresolve_dispute_rate']) ? $item['unresolve_dispute_rate'] : '0.00';
                        $unresolve_dispute_rate .= '%';
                    } else {
                        $unresolve_dispute_rate = !empty($item['unresolve_dispute_rate']) ? $item['unresolve_dispute_rate'] : '0';
                    }
                    $unresolveDisputeRate .= "<tr><td><a class='status' title='未解决纠纷率' data-type='unresolve_dispute_rate' data-accountid='{$model->id}' data-id='{$item['unresolve_dispute_rate_id']}'>{$unresolve_dispute_rate}</a></td></tr>";

                    if (!empty($item['transport_delay_is_rate'])) {
                        $transport_delay_rate = !empty($item['transport_delay_rate']) ? $item['transport_delay_rate'] : '0.00';
                        $transport_delay_rate .= '%';
                    } else {
                        $transport_delay_rate = !empty($item['transport_delay_rate']) ? $item['transport_delay_rate'] : '0';
                    }
                    $transportDelayRate .= "<tr><td><a class='status' title='运送延迟率' data-type='transport_delay_rate' data-accountid='{$model->id}' data-id='{$item['transport_delay_rate_id']}'>{$transport_delay_rate}</a></td></tr>";
                }
                $program .= '</table>';
                $currentLevel .= '</table>';
                $projectedLevel .= '</table>';
                $badTradeRate .= '</table>';
                $unresolveDisputeRate .= '</table>';
                $transportDelayRate .= '</table>';

                $model->setAttribute('program', $program);
                $model->setAttribute('current_level', $currentLevel);
                $model->setAttribute('projected_level', $projectedLevel);
                $model->setAttribute('bad_trade_rate', $badTradeRate);
                $model->setAttribute('unresolve_dispute_rate', $unresolveDisputeRate);
                $model->setAttribute('transport_delay_rate', $transportDelayRate);
            } else {
                $model->setAttribute('program', '无');
                $model->setAttribute('current_level', '无');
                $model->setAttribute('projected_level', '无');
                $model->setAttribute('bad_trade_rate', '无');
                $model->setAttribute('unresolve_dispute_rate', '无');
                $model->setAttribute('transport_delay_rate', '无');
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
            if (!empty($params['filter_date'])) {
                $ltnp = EbaySellerLtnp::find()
                    ->select('program_status_lst_eval, status_lst_eval, snad_status_lst_eval')
                    ->where(['account_id' => $model->id])
                    ->andWhere("'{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}'")
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            } else {
                $ltnp = EbaySellerLtnp::find()
                    ->select('program_status_lst_eval, status_lst_eval, snad_status_lst_eval')
                    ->where(['account_id' => $model->id])
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->asArray()
                    ->one();
            }

            if (!empty($ltnp)) {
                $program = array_key_exists($ltnp['program_status_lst_eval'], $ltnpStatus) ? $ltnpStatus[$ltnp['program_status_lst_eval']] : '';
                $lst = array_key_exists($ltnp['status_lst_eval'], $ltnpStatus) ? $ltnpStatus[$ltnp['status_lst_eval']] : '';
                $snad = array_key_exists($ltnp['snad_status_lst_eval'], $ltnpStatus) ? $ltnpStatus[$ltnp['snad_status_lst_eval']] : '';
                $ltnp = "综合表现: <a class='status' title='综合表现' data-status='{$program}' data-type='ltnp' data-accountid='{$model->id}'>{$program}</a>";
                $ltnp .= "<br>";
                $ltnp .= "不良交易率表现: <a class='status' title='综合表现' data-status='{$lst}' data-type='ltnp' data-accountid='{$model->id}'>{$lst}</a>";
                $ltnp .= "<br>";
                $ltnp .= "纠纷表现: <a class='status' title='综合表现' data-status='{$snad}' data-type='ltnp' data-accountid='{$model->id}'>{$snad}</a>";
            } else {
                $ltnp = '无';
            }
            $model->setAttribute('ltnp', $ltnp);


            //货运
            if (!empty($params['filter_date'])) {
                $sellerShipStatus = EbaySellerShip::find()
                    ->select('result')
                    ->where(['account_id' => $model->id])
                    ->andWhere("'{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}'")
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->scalar();
            } else {
                $sellerShipStatus = EbaySellerShip::find()
                    ->select('result')
                    ->where(['account_id' => $model->id])
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->scalar();
            }

            //非货运
            if (!empty($params['filter_date'])) {
                $sellerTciStatus = EbaySellerTci::find()
                    ->select('result')
                    ->where(['account_id' => $model->id])
                    ->andWhere("'{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}'")
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->scalar();
            } else {
                $sellerTciStatus = EbaySellerTci::find()
                    ->select('result')
                    ->where(['account_id' => $model->id])
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->scalar();
            }

            //海外仓标准
            if (!empty($params['filter_date'])) {
                $sellerSdWarehouseStatus = EbaySellerSdWarehouse::find()
                    ->select('warehouse_status')
                    ->where(['account_id' => $model->id])
                    ->andWhere("'{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}'")
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->scalar();
            } else {
                $sellerSdWarehouseStatus = EbaySellerSdWarehouse::find()
                    ->select('warehouse_status')
                    ->where(['account_id' => $model->id])
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->scalar();
            }

            //商业计划追踪
            if (!empty($params['filter_date'])) {
                $sellerPgcTrackingStatus = EbaySellerPgcTracking::find()
                    ->select('pgc_status')
                    ->where(['account_id' => $model->id])
                    ->andWhere("'{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}'")
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->scalar();
            } else {
                $sellerPgcTrackingStatus = EbaySellerPgcTracking::find()
                    ->select('pgc_status')
                    ->where(['account_id' => $model->id])
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->scalar();
            }

            //待处理刊登
            //数据最后更新时间
            if (!empty($params['filter_date'])) {
                $qclistExists = EbaySellerQclist::find()
                    ->andWhere(['account_id' => $model->id])
                    ->andWhere("'{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}'")
                    ->exists();
            } else {
                $qclistRefreshedDate = EbaySellerQclist::find()
                    ->select('refreshed_date')
                    ->andWhere(['account_id' => $model->id])
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->scalar();
                $qclistExists = EbaySellerQclist::find()
                    ->andWhere(['account_id' => $model->id])
                    ->andWhere(['refreshed_date' => $qclistRefreshedDate])
                    ->exists();
            }

            if ($qclistExists) {
                $sellerQclistStatus = 'Y';
            } else {
                $sellerQclistStatus = 'N';
            }

            //物流标准(美国小于5美金)
            if (!empty($params['filter_date'])) {
                $edsStatus = EbaySellerEdsShippingPolicy::find()
                    ->select('eds_status')
                    ->where(['account_id' => $model->id])
                    ->andWhere("'{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}'")
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->scalar();
            } else {
                $edsStatus = EbaySellerEdsShippingPolicy::find()
                    ->select('eds_status')
                    ->where(['account_id' => $model->id])
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->scalar();
            }

            //物流标准(美国大于5美金)
            if (!empty($params['filter_date'])) {
                $packetStatus = EbaySellerEpacketShippingPolicy::find()
                    ->select('e_packet_status')
                    ->where(['account_id' => $model->id])
                    ->andWhere("'{$monday}' <= refreshed_date AND refreshed_date <= '{$sunday}'")
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->scalar();
            } else {
                $packetStatus = EbaySellerEpacketShippingPolicy::find()
                    ->select('e_packet_status')
                    ->where(['account_id' => $model->id])
                    ->orderBy('refreshed_date DESC')
                    ->limit(1)
                    ->scalar();
            }

            //SpeedPAK物流管理方案
            if (!empty($params['filter_date'])) {
                $spListStatus = EbaySellerSpeedpakList::find()
                    ->select('account_status')
                    ->where(['account_id' => $model->id])
                    ->andWhere("'{$monday}' <= create_pst AND create_pst <= '{$sunday}'")
                    ->orderBy('create_pst DESC')
                    ->limit(1)
                    ->scalar();
            } else {
                $spListStatus = EbaySellerSpeedpakList::find()
                    ->select('account_status')
                    ->where(['account_id' => $model->id])
                    ->orderBy('create_pst DESC')
                    ->limit(1)
                    ->scalar();
            }

            //获取卖家设置SpeedPAK物流选项
            if (!empty($params['filter_date'])) {
                $spMisuseStatus = EbaySellerSpeedpakMisuse::find()
                    ->select('account_status')
                    ->where(['account_id' => $model->id])
                    ->andWhere("'{$monday}' <= create_pst AND create_pst <= '{$sunday}'")
                    ->orderBy('create_pst DESC')
                    ->limit(1)
                    ->scalar();
            } else {
                $spMisuseStatus = EbaySellerSpeedpakMisuse::find()
                    ->select('account_status')
                    ->where(['account_id' => $model->id])
                    ->orderBy('create_pst DESC')
                    ->limit(1)
                    ->scalar();
            }

            $shippingPolicy = '';
            if ($packetStatus !== false) {
                $packetStatus = array_key_exists($packetStatus, $epacketShippingStatus) ? $epacketShippingStatus[$packetStatus] : '';
                $shippingPolicy .= "大于5美金: <a class='status' title='物流标准' data-status='{$packetStatus}' data-type='eds_shipping_policy' data-accountid='{$model->id}'>{$packetStatus}</a>";
            } else {
                $shippingPolicy .= '大于5美金: 无';
            }

            $shippingPolicy .= '<br>';

            if ($edsStatus !== false) {
                $edsStatus = array_key_exists($edsStatus, $edshippingStatus) ? $edshippingStatus[$edsStatus] : '';
                $shippingPolicy .= "小于5美金: <a class='status' title='物流标准' data-status='{$edsStatus}' data-type='eds_shipping_policy' data-accountid='{$model->id}'>{$edsStatus}</a>";
            } else {
                $shippingPolicy .= '小于5美金: 无';
            }

            $shippingPolicy .= '<br>';

            if ($spListStatus !== false) {
                $spListStatus = array_key_exists($spListStatus, $speedPakListStatus) ? $speedPakListStatus[$spListStatus] : '';
                $shippingPolicy .= "SpeedPAK物流管理方案: <a class='status' title='SpeedPAK管理方案' data-status='{$spListStatus}' data-type='eds_shipping_policy' data-accountid='{$model->id}'>{$spListStatus}</a>";
            } else {
                $shippingPolicy .= 'SpeedPAK物流管理方案: 无';
            }

            $shippingPolicy .= '<br>';

            if ($spMisuseStatus !== false) {
                $spMisuseStatus = array_key_exists($spMisuseStatus, $speedPakMisuseStatus) ? $speedPakMisuseStatus[$spMisuseStatus] : '';
                $shippingPolicy .= "卖家设置SpeedPAK: <a class='status' title='卖家设置SpeedPAK' data-status='{$spMisuseStatus}' data-type='eds_shipping_policy' data-accountid='{$model->id}'>{$spMisuseStatus}</a>";
            } else {
                $shippingPolicy .= '卖家设置SpeedPAK: 无';
            }

            //货运
            $ship = '无';
            //非货运
            $tci = '无';
            //海外仓标准
            $sdWarehouse = '无';
            //商业计划追踪
            $pgcTracking = '无';
            //待处理刊登
            $qclist = '无';

            if ($sellerShipStatus !== false) {
                $ship = array_key_exists($sellerShipStatus, $shipStatus) ? $shipStatus[$sellerShipStatus] : '无';
                $ship = "<a class='status' title='货运' data-status='{$ship}' data-type='ship' data-accountid='{$model->id}'>{$ship}</a>";
            }

            if ($sellerTciStatus !== false) {
                $tci = array_key_exists($sellerTciStatus, $nonShipStatus) ? $nonShipStatus[$sellerTciStatus] : '无';
                $tci = "<a class='status' title='非货运' data-status='{$tci}' data-type='tci' data-accountid='{$model->id}'>{$tci}</a>";
            }

            if ($sellerSdWarehouseStatus !== false) {
                $sdWarehouse = array_key_exists($sellerSdWarehouseStatus, $wareHouseStatus) ? $wareHouseStatus[$sellerSdWarehouseStatus] : '无';
                $sdWarehouse = "<a class='status' title='海外仓标准' data-status='{$sdWarehouse}' data-type='sd_warehouse' data-accountid='{$model->id}'>{$sdWarehouse}</a>";
            }

            if ($sellerPgcTrackingStatus !== false) {
                $pgcTracking = array_key_exists($sellerPgcTrackingStatus, $pgcTrackingStatus) ? $pgcTrackingStatus[$sellerPgcTrackingStatus] : '无';
                $pgcTracking = "<a class='status' title='商业计划追踪' data-status='{$pgcTracking}' data-type='pgc_tracking' data-accountid='{$model->id}'>{$pgcTracking}</a>";
            }

            if ($sellerQclistStatus !== false) {
                $qclist = array_key_exists($sellerQclistStatus, $qcListingStatus) ? $qcListingStatus[$sellerQclistStatus] : '无';
                $qclist = "<a class='status' title='待处理刊登' data-status='{$qclist}' data-type='qclist' data-accountid='{$model->id}'>{$qclist}</a>";
            }

            $model->setAttribute('ship', $ship);
            $model->setAttribute('tci', $tci);
            $model->setAttribute('sd_warehouse', $sdWarehouse);
            $model->setAttribute('pgc_tracking', $pgcTracking);
            $model->setAttribute('shipping_policy', $shippingPolicy);
            $model->setAttribute('qclist', $qclist);
        }
    }

    /**
     * 获取账号列表
     */
    public static function getAccountList()
    {
        $data = Account::find()
            ->select('id, account_name')
            ->where(['platform_code' => Platform::PLATFORM_CODE_EB, 'status' => Account::STATUS_VALID])
            ->asArray()
            ->all();

        $accountList = ['' => '--请选择账号--'];
        if (!empty($data)) {
            foreach ($data as $item) {
                $accountList[$item['id']] = $item['account_name'];
            }
        }

        return $accountList;
    }

    /**
     * 获取站点列表
     */
    public static function getSiteList()
    {
        return [
            '' => '全部',
            'PROGRAM_GLOBAL' => '全球',
            'PROGRAM_US' => '美国',
            'PROGRAM_UK' => '英国',
            'PROGRAM_DE' => '德国',
        ];
    }

    /**
     * 账户等级
     */
    public static function getAccountLevel()
    {
        return [
            '' => '全部',
            'TOP_RATED' => '最高评级',
            'ABOVE_STANDARD' => '高于标准',
            'BELOW_STANDARD' => '低于标准',
        ];
    }

    /**
     * 综合表现
     */
    public static function getLtnpStatus()
    {
        return [
            '' => '全部',
            '0' => '正常',
            '1' => '超标',
            '2' => '警告',
            '3' => '限制',
            '4' => '不考核',
            '-1' => '无',
        ];
    }

    /**
     * 货运状态
     */
    public static function getShippingStatus()
    {
        return [
            '' => '全部',
            '0' => '正常',
            '1' => '超标',
            '2' => '警告',
            '3' => '限制',
            '-1' => '无',
        ];
    }

    /**
     * 非货运状态
     */
    public static function getNonShippingStatus()
    {
        return [
            '' => '全部',
            '1' => '正常',
            '2' => '超标',
            '3' => '警告',
            '4' => '限制',
            '-1' => '无',
        ];
    }

    /**
     * 物流标准状态(小于5美金)
     */
    public static function getEdshippingStatus()
    {
        return [
            '' => '全部',
            '0' => '正常',
            '1' => '超标',
            '2' => '警告',
            '3' => '限制',
            '-1' => '无',
        ];
    }

    /**
     * 物流标准状态(>$5交易)
     */
    public static function getEpacketShippingStatus()
    {
        return [
            '' => '全部',
            '0' => '正常',
            '1' => '警告',
            '2' => '超标',
            '3' => '限制',
            '-1' => '无',
        ];
    }

    /**
     * SpeedPAK物流管理方案状态
     */
    public static function getSpeedPakListStatus()
    {
        return [
            '' => '全部',
            '0' => '正常',
            '1' => '超标',
            '2' => '警告',
            '3' => '限制',
            '-1' => '无',
        ];
    }

    /**
     * 卖家设置SpeedPAK物流状态
     */
    public static function getSpeedPakMisuseStatus()
    {
        return [
            '' => '全部',
            '0' => '正常',
            '1' => '超标',
            '2' => '警告',
            '3' => '限制',
            '-1' => '无',
        ];
    }

    /**
     * 海外仓标准状态
     */
    public static function getWareHouseStatus()
    {
        return [
            '' => '全部',
            '0' => '正常',
            '1' => '超标',
            '2' => '警告',
            '3' => '限制',
            '-1' => '无',
        ];
    }

    /**
     * 商业计划追踪状态
     */
    public static function getPgcTrackingStatus()
    {
        return [
            '' => '全部',
            '0' => '正常',
            '1' => '警告',
            '2' => '限制',
            '-1' => '无',
        ];
    }

    /**
     * 待处理刊登状态
     */
    public static function getQcListingStatus()
    {
        return [
            '' => '全部',
            'Y' => '有待处理刊登',
            'N' => '无待处理刊登',
        ];
    }
}