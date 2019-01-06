<?php

namespace app\modules\services\modules\gbc\controllers;

use app\modules\accounts\models\Platform;
use yii\web\Controller;
use app\modules\orders\models\Order;

class GbcController extends Controller
{
    const GBC_ID_URL = 'https://sellerdefense.cn/gbc-id/';
    const GBC_ADDR_URL = 'https://sellerdefense.cn/gbc-addr/';

    /**
     * 获取GBC买手ID
     * /services/gbc/gbc/getgbcid
     */
    public function actionGetgbcid()
    {
        $gbcIds = [];

        $html = new \simple_html_dom();

        if ($html->load_file(self::GBC_ID_URL)) {
            die('load file error');
        }

        $gbcIdLists = $html->find('div[id=content] > p');
        if (empty($gbcIdLists)) {
            die('find content error');
        }

        //GBC买手ID
        foreach ($gbcIdLists as $gbcIdList) {
            $gbcIds[] = trim($gbcIdList->plaintext);
        }

        //GBC拉取的数据，默认是全平台
        $data['platform_code'] = 'ALL';
        //类型为账号类型
        $data['type'] = 1;
        //数据来源，默认为GBC
        $data['account_type'] = 1;
        //买家ID
        $data['ebay_id'] = $gbcIds;
        $data['modify_by'] = 'system';

        $result = Order::updateGbcData($data);
        if ($result) {
            die('update gbc buyer id success');
        } else {
            die('update gbc buyer id error');
        }
    }

    /**
     * 获取GBC地址
     * /services/gbc/gbc/getgbcaddr
     */
    public function actionGetgbcaddr()
    {
        $gbcAddrs = [];

        $html = new \simple_html_dom();

        if ($html->load_file(self::GBC_ADDR_URL)) {
            die('load file error');
        }

        $gbcAddrLists = $html->find('div[id=content]', 0);
        if (empty($gbcAddrLists)) {
            die('find content error');
        }

        $childs = $gbcAddrLists->children;

        //获取子类DIV的数量
        $count = count($childs);

        //根据当前GBC地址的格式，以2个DIV为一个步长，进行获取地址操作
        //不排除后期会有变动的可能
        $step = 2;

        $data = [];

        for ($ix = 0; $ix < $count; $ix += $step) {
            if (!empty($childs[$ix]) && !empty($childs[$ix + 1])) {
                //街道
                $address = trim($childs[$ix]->plaintext);
                //详情
                $details = $childs[$ix + 1]->find('div');
                //城市和州
                $citys = !empty($details[0]) ? trim($details[0]->plaintext) : '';

                //城市
                $city = '';
                //州
                $state = '';

                //去除普通空格
                $citys = str_replace(' ', '', $citys);
                //去除UTF-8空格
                $citys = str_replace(chr(194) . chr(160), '', $citys);
                $citys = explode(',', $citys);

                if (!empty($citys)) {
                    $city = !empty($citys[0]) ? $citys[0] : '';
                    $state = !empty($citys[1]) ? $citys[1] : '';
                }

                //邮编
                $postal_code = !empty($details[1]) ? trim($details[1]->plaintext) : '';
                //国家
                $country = !empty($details[2]) ? trim($details[2]->plaintext) : '';
                //电话
                $phone = !empty($details[3]) ? trim($details[3]->plaintext) : '';

                $data[] = [
                    'platform_code' => 'ALL',
                    'type' => 3,
                    'account_type' => 1,
                    'country' => $country,
                    'state' => $state,
                    'city' => $city,
                    'postal_code' => $postal_code,
                    'address' => $address,
                    'recipients' => $phone,
                    'modify_by' => 'system',
                ];
            }
        }

        $result = Order::updateGbcData($data);
        if ($result) {
            die('update gbc buyer addr success');
        } else {
            die('update gbc buyer addr error');
        }
    }
}