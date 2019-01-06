<?php
/**
 * Created by PhpStorm.
 * User: wuyang
 * Date: 2017/4/20
 * Time: 下午 13:53
 */

namespace app\modules\mails\models;

use Yii;
use app\common\VHelper;
use app\modules\accounts\models\Platform;

class MailTemplateCategory extends MailsModel
{

    public static function tableName()
    {
        return '{{%mail_template_category}}';
    }

    public function attributes()
    {
        $attributes = parent::attributes();
        $extAttributes = [

        ];
        return array_merge($attributes, $extAttributes);
    }

    public function attributeLabels()
    {
        return [
            'parent_id' => '父级分类名称',
            'category_name' => '分类名称',
            'category_code' => '分类编码',
            'category_description' => '描述',
            'platform_code' => '所属平台',
            'status' => '状态',
            'sort_order' => '排序(值越小越前)',
            'create_by' => '创建人',
            'create_time' => '创建时间',
            'modify_by' => '修改人',
            'modify_time' => '修改时间',
        ];
    }

    /**
     * 搜索过滤项
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
                'name' => 'category_name',
                'type' => 'text',
                'search' => 'LIKE'
            ],
            [
                'name' => 'create_by',
                'type' => 'text',
                'search' => 'LIKE'
            ],
        ];
    }

    public function rules()
    {
        return [
            [['parent_id', 'status', 'sort_order', 'create_by', 'create_time', 'modify_by', 'modify_time'], 'safe'],
            [['platform_code', 'category_name', 'category_code', 'category_description'], 'required'],
        ];
    }

    /**
     * 返回平台下拉框数据
     */
    public static function platformDropdown()
    {
        return Platform::getPlatformAsArray();
    }

    public function searchList($params = [])
    {
        $query = self::find();

        $sort = new \yii\data\Sort();
        $sort->defaultOrder = array(
            'sort_order' => SORT_ASC,
            'id' => SORT_DESC
        );

        $dataProvider = parent::search($query, $sort, $params);
        $models = $dataProvider->getModels();
        $this->addition($models);
        $dataProvider->setModels($models);
        return $dataProvider;
    }

    /**
     * 修改模型中的数据
     */
    public function addition(&$models)
    {
        $categoryList = self::find()->select('id, category_name')->asArray()->all();
        if (!empty($categoryList)) {
            $categoryList = array_column($categoryList, 'category_name', 'id');
        }

        foreach ($models as $model) {
            if (!empty($model->status)) {
                $model->setAttribute('status', '是');
            } else {
                $model->setAttribute('status', '否');
            }

            $model->setAttribute('parent_id', array_key_exists($model->parent_id, $categoryList) ? $categoryList[$model->parent_id] : '');
        }
    }

    /**
     * 获取模板分类列表
     */
    public static function Getall($plat = '')
    {
        $model = New MailTemplateCategory();

        if ($plat) {
            $rs = $model::find()->select('id, parent_id, category_name')
                ->where(['status' => '1'])
                ->andWhere(['parent_id' => '0'])
                ->andWhere(['platform_code' => $plat])
                ->Orderby('sort_order asc')
                ->all();
        } else {
            $rs = $model::find()->select('id, parent_id, category_name')
                ->where(['status' => '1'])
                ->andWhere(['parent_id' => '0'])
                ->Orderby('sort_order asc')
                ->all();
        }

        $output = [];
        $first_level = 0;
        $dent = ' ';
        $vertical_horizontal = '|_';
        foreach ($rs as $key => $value) {
            $num = $first_level + 2;
            $output[$value['id']] = str_repeat($dent, $num) . $vertical_horizontal . $value['category_name'];
            $child_rs = $model::find()->select('id, parent_id, category_name')
                ->where(['status' => '1'])
                ->andWhere(['parent_id' => $value['id']])
                ->Orderby('sort_order asc')
                ->all();
            if ($child_rs) {

                $num2 = $first_level + 5;
                foreach ($child_rs as $kk => $vv) {
                    $output[$vv['id']] = str_repeat($dent, $num2) . $vertical_horizontal . $vv['category_name'];
                    $grandson_rs = $model::find()->select('id, parent_id, category_name')
                        ->where(['status' => '1'])
                        ->andWhere(['parent_id' => $vv['id']])
                        ->Orderby('sort_order asc')
                        ->all();
                    if ($grandson_rs) {
                        $num3 = $first_level + 8;
                        foreach ($grandson_rs as $k => $v) {
                            $output[$v['id']] = str_repeat($dent, $num3) . $vertical_horizontal . $v['category_name'];
                        }
                    }
                }
            }
        }
        return $output;
    }

    /**
     * 获取邮件模板分类列表
     */
    public static function getCategoryList($platformCode = '', $parentId = 0, $type = 'json')
    {
        $query = self::find()->select('id, parent_id, category_name')
            ->where(['status' => 1])
            ->orderBy('sort_order ASC, id DESC');

        if (!empty($platformCode)) {
            $query->andWhere(['platform_code' => $platformCode]);
        }
        if (!empty($parentId)) {
            $query->andWhere(['parent_id' => $parentId]);
        }

        $data = $query->asArray()->all();

        //生成树型结构
        $data = VHelper::genTree($data, 'id', 'parent_id');

        //最多循环四层
        $level = 0;
        if (!empty($data)) {
            if ($type == 'json') {
                $tmp[] = [
                    'id' => 0,
                    'name' => '请选择',
                ];
            } else {
                $tmp[0] = '请选择';
            }

            foreach ($data as $item1) {
                if ($type == 'json') {
                    $tmp[] = [
                        'id' => $item1['id'],
                        'name' => str_repeat(' ', $level * 4) . '|--' . $item1['category_name'],
                    ];
                } else {
                    $tmp[$item1['id']] = str_repeat(' ', $level * 4) . '|--' . $item1['category_name'];
                }

                if (!empty($item1['child'])) {
                    $level++;
                    $grandpa = $item1['child'];

                    foreach ($grandpa as $item2) {
                        if ($type == 'json') {
                            $tmp[] = [
                                'id' => $item2['id'],
                                'name' => str_repeat(' ', $level * 4) . '|--' . $item2['category_name'],
                            ];
                        } else {
                            $tmp[$item2['id']] = str_repeat(' ', $level * 4) . '|--' . $item2['category_name'];
                        }

                        if (!empty($item2['child'])) {
                            $level++;
                            $father = $item2['child'];

                            foreach ($father as $item3) {
                                if ($type == 'json') {
                                    $tmp[] = [
                                        'id' => $item3['id'],
                                        'name' => str_repeat(' ', $level * 4) . '|--' . $item3['category_name'],
                                    ];
                                } else {
                                    $tmp[$item3['id']] = str_repeat(' ', $level * 4) . '|--' . $item3['category_name'];
                                }

                                if (!empty($item3['child'])) {
                                    $level++;
                                    $child = $item3['child'];

                                    foreach ($child as $item4) {
                                        if ($type == 'json') {
                                            $tmp[] = [
                                                'id' => $item4['id'],
                                                'name' => str_repeat(' ', $level * 4) . '|--' . $item4['category_name'],
                                            ];
                                        } else {
                                            $tmp[$item4['id']] = str_repeat(' ', $level * 4) . '|--' . $item4['category_name'];
                                        }
                                    }
                                    $level--;
                                }
                            }
                            $level--;
                        }
                    }
                    $level--;
                }
            }
            $data = $tmp;
        }

        return $data;
    }


    /**
     *@ purpose For ebay trading api only
     *@ ebay 封装xml
     *@ author: wuyang
     *@ date: 2017 04 26
     */
    public function Buildtradingxml($arr, $api_name)
    {
        $result = '<?xml version="1.0" encoding="utf-8"?>';
        $result .= '<' . $api_name . 'Request xmlns="urn:ebay:apis:eBLBaseComponents">';
        foreach ($arr as $key => $value) {

            if (!is_array($value)) {
                $result .= '<' . $key . '>' . $value . '</' . $key . '>';
            } elseif (is_array($value)) {
                $sec_xml = $this->Buildsecondxml($value);
                $result .= '<' . $key . '>' . $sec_xml . '</' . $key . '>';
            } else {
            }
        }
        $result .= '</' . $api_name . 'Request>';
        return $result;
    }

    public function Buildsecondxml($arr)
    {
        $result = '';
        foreach ($arr as $key => $value) {
            $result .= '<' . $key . '>' . $value . '</' . $key . '>';
        }
        return $result;
    }


    /**
     *@ purpose For ebay resolution case management only
     *@ ebay 封装xml
     *@ author: wuyang
     *@ date: 2017 05 05
     */
    public function Buildresolutioncasexml($arr, $api_name)
    {
        $result = '<?xml version="1.0" encoding="utf-8"?>';
        $result .= '<' . $api_name . 'Request xmlns="http://www.ebay.com/marketplace/resolution/v1/services">';
        foreach ($arr as $key => $value) {

            if (!is_array($value)) {
                $result .= '<' . $key . '>' . $value . '</' . $key . '>';
            } elseif (is_array($value)) {
                $sec_xml = $this->Buildsecondxml($value);
                $result .= '<' . $key . '>' . $sec_xml . '</' . $key . '>';
            } else {
            }
        }
        $result .= '</' . $api_name . 'Request>';
        return $result;
    }


    /**
     * ebay header 头信息
     * api: resolution case api
     * author: wuyang
     * date: 2017 05 05
     */
    public function Buildresolutioncaseheader($apiname, $token)
    {
        $headers = array(
            'X-EBAY-SOA-SERVICE-NAME: ResolutionCaseManagementService',
            'X-EBAY-SOA-OPERATION-NAME: ' . $apiname,
            'X-EBAY-SOA-SERVICE-VERSION: 1.1.0',
            'X-EBAY-SOA-SECURITY-TOKEN: ' . $token,
            'X-EBAY-SOA-REQUEST-DATA-FORMAT: XML'
        );
        return $headers;

    }


    /**
     * ebay header 头信息
     * api: trading api
     * author: wuyang
     * date: 2017 04 27
     */
    public function Buildtradingheader($devID, $appID, $certID, $callName, $siteId = 0)
    {
        $headers = array(
            //Regulates versioning of the XML interface for the API
            'X-EBAY-API-COMPATIBILITY-LEVEL: 1003',
            //set the keys
            'X-EBAY-API-DEV-NAME: ' . $devID,
            'X-EBAY-API-APP-NAME: ' . $appID,
            'X-EBAY-API-CERT-NAME: ' . $certID,
            //the name of the call we are requesting
            'X-EBAY-API-CALL-NAME: ' . $callName,
            //SiteID must also be set in the Request's XML
            //SiteID = 0  (US) - UK = 3, Canada = 2, Australia = 15, ....
            //SiteID Indicates the eBay site to associate the call with
            'X-EBAY-API-SITEID: ' . $siteId,
            //'X-EBAY-API-DETAIL-LEVEL:0'
        );
        return $headers;
    }


    /**
     * @purpose  for Ebay trading API only
     * @param $headers    curl 里面的头信息
     * @param $serverlUrl api里面的 Gateway URI
     * @param $requestBody 组装的XML 文件
     * @return mixed
     * @author wuyang
     * @date   2017 04 27
     */
    public function sendtradingHttpRequest($headers, $serverlUrl, $requestBody)
    {

        // trading api serverl Url:  https://api.ebay.com/ws/api.dll
        $connection = curl_init();
        curl_setopt($connection, CURLOPT_URL, $serverlUrl);
        //stop CURL from verifying the peer's certificate
        curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);

        //set the headers using the array of headers
        curl_setopt($connection, CURLOPT_HTTPHEADER, $headers);

        //set method as POST
        curl_setopt($connection, CURLOPT_POST, 1);

        //set the XML body of the request
        curl_setopt($connection, CURLOPT_POSTFIELDS, $requestBody);

        //set it to return the transfer as a string from curl_exec
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($connection, CURLOPT_CONNECTTIMEOUT, 120);

        //Send the Request
        $response = curl_exec($connection);

        //close the connection
        curl_close($connection);
        $response = simplexml_load_string($response);
        //return the response
        return $response;
    }

    /**
     * @author wuyang
     * @purpose: build post order api header
     * @date: 2017 04 27
     */
    public function Buildpostorderheader($token, $site)
    {
        $header = array(
            'Authorization TOKEN ' . $token,
            'Content-Type application/json',
            //$site is only limited to : EBAY_US, EBAY_UK, EBAY_DE, EBAY_AU, and EBAY_CA
            'X-EBAY-C-MARKETPLACE-ID ' . $site,
            'Accept: application/json'
        );
        return $header;
    }

    /**
     * @author wuyang
     * @purpose： send post order api request
     * @date: 2017 04 27
     * @  post order api 未进行测试，以后需要进行进一步测试
     */
    public function SendpostorderRequest($serverlUrl, $headers, $method = 'GET')
    {
        $connection = curl_init();
        curl_setopt($connection, CURLOPT_URL, $serverlUrl);
        //stop CURL from verifying the peer's certificate
        curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);

        //set the headers using the array of headers
        curl_setopt($connection, CURLOPT_HTTPHEADER, $headers);

        //set method as POST
        if (strtoupper($method) == 'POST') {
            curl_setopt($connection, CURLOPT_POST, 1);
        }
        //set it to return the transfer as a string from curl_exec
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($connection, CURLOPT_CONNECTTIMEOUT, 120);

        //Send the Request
        $response = curl_exec($connection);

        //close the connection
        curl_close($connection);

        //return the response
        return $response;
    }
}

?>