<?php
namespace app\modules\services\modules\cdiscount\components;

class cdConfig{
	//配置文件
	//将规则写进配置文件当中，
	static $tokenUrl = 'https://sts.cdiscount.com/users/httpIssue.svc/?realm=https://wsvc.cdiscount.com/MarketplaceAPIService.svc';
	static $methodUrl = 'https://wsvc.cdiscount.com/MarketplaceAPIService.svc?wsdl';
	static $soapUrl = 'http://www.cdiscount.com/IMarketplaceAPIService/';
	static $xmlValues = array(
		'Context'=>array(
				'CatalogID'=>1,
				'CustomerPoolID'=>1,
				'SiteID'=>100,
		),
		'Localization'=>array(
			'Country'=>'Fr',
			'Currency'=>'Eur',
			'DecimalPosition'=>'2',
			'Language'=>'Fr',
		),
		'Security'=>array(
				'DomainRightsList'=>array(
						'i:nil'=>'true'
				),
				'IssuerID'=>array(
						'i:nil'=>'true'
				),
				'SessionID'=>array(
						'i:nil'=>'true'
				),
				'SubjectLocality'=>array(
						'i:nil'=>'true'
				),
				'TokenId'=>'',
				'UserName'=>array(
						'i:nil'=>'true'
				),
		),
		'Version'=>'1.0',
		'productPackageRequest'=>array(
				'ZipFileFullPath'=>'123'
		),
		'productFilter'=>array(
				
		)
	);
	
	static $xmlAttributes = array(
		'Context'=>array(
				'prefix'=>'a:',
		),
		'Localization'=>array(
				'prefix'=>'a:',
		),
		'Security'=>array(
				'prefix'=>'a:',
		),
		'Version'=>array(
				'prefix'=>'a:',
		),
		'headerMessage'=>array(
				'attr'=>array(
						'xmlns:a="http://schemas.datacontract.org/2004/07/Cdiscount.Framework.Core.Communication.Messages"',
						'xmlns:i="http://www.w3.org/2001/XMLSchema-instance"'
				),
				'son'=>array(
						'Context','Localization','Security','Version'
				)
		),
		'Envelope'=>array(
				'prefix'=>'s:',
				'attr'=>array(
						'xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"'
				)
		),
		'Body'=>array(
				'prefix'=>'s:'
		),

		'default'=>array(
				'attr'=>array(
						'xmlns="http://www.cdiscount.com"'
				)
		),
		'productPackageRequest'=>array(
				'attr'=>array(
					'xmlns:i="http://www.w3.org/2001/XMLSchema-instance"'		
				)		
		),
	);
}