<?php

	namespace app\modules\systems\models;
	use yii\db\ActiveRecord;
	use Yii;
	/**
	* 
	*/
	class TransactionAddress extends ActiveRecord
	{
		
		public static function tableName(){

			return "{{%eb_transaction_address}}";
		}

		public function rules(){

			return[
				[['transaction_id','name','street1','city_name','state_or_province','country','country_name','postal_code'],'safe'],
				[['create_time','street2','phone'],'safe'],
//				['transaction_id', 'string', 'max' => 50],
//				[['name','street1','street2'], 'string', 'max' => 64],
//				[['city_name','state_or_province','country','country_name'], 'string', 'max' => 32],
//				[['phone','postal_code'], 'string', 'max' => 16],
			];
		}

		public function attributeLabels(){
			return [
				'transaction_id' => '交易ID',
				'name' => '姓名',
				'street1' =>'街道1',
				'street2' =>'街道2',
				'city_name' =>'城市名称',
				'state_or_province' =>'地区或省',
				'country' =>'国家(简称)',
				'country_name' =>'国家',
				'phone' =>'电话号码',
				'postal_code'=>'邮政编码',

			];
		}

		/*@desc 交易地址信息存入数据库*/
		public static function insertAddressData($result,$TransactionID)
		{

            $data = [
				'transaction_id' => $TransactionID,
				'name' => $result->Name,
				'street1' => $result->Street1,
				'street2' => $result->Street2,
				'city_name' => $result->CityName,
				'state_or_province' => $result->StateOrProvince,
				'country' => $result->Country,
				'country_name' => $result->CountryName,
				'phone' => $result->Phone,
				'postal_code' => $result->PostalCode,
            ];

            $model = self::findOne(['transaction_id'=>$TransactionID]);
            //存取数据
            if(empty($model))
                $model = new self();
			if ($model->load($data, '') && $model->save()) {
               return [true , 'save success'];
			}
			
			return [false, current(current($model->getErrors()))];
		}

	}