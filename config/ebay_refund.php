<?php
return [
            // values: 'sandbox' for testing
            //		   'live' for production
            //         'tls' for testing if your server supports TLSv1.2
            //"mode" => "live",
            "mode" => "live",
            // TLSv1.2 Check: Comment the above line, and switch the mode to tls as shown below
            // "mode" => "tls"
        
            'log.LogEnabled' => true,
            'log.FileName' => \Yii::getAlias('@runtime') . '/logs/PayPal_' . date('Y-m-d') . '.log',
            'log.LogLevel' => 'FINE',
            // Signature Credential
            "acct1.UserName" => "1143621529_seller_api1.qq.com",
            "acct1.Password" => "SSF8V6276NGNDP2Y",
            "acct1.Signature" => "AFcWxV21C7fd0v3bYYYRCpSSRl31ArO0xKTWFUkq0Jurz2vQQJtD6-gP",
            // Subject is optional and is required only in case of third party authorization
            //"acct1.Subject" => "seller_1353049363_biz@gmail.com",
        
            // Sample Certificate Credential
            // "acct1.UserName" => "certuser_biz_api1.paypal.com",
            // "acct1.Password" => "D6JNKKULHN3G5B8A",
            // Certificate path relative to config folder or absolute path in file system
            // "acct1.CertPath" => "cert_key.pem",
            // Subject is optional and is required only in case of third party authorization
            // "acct1.Subject" => "",
        
            // These values are defaulted in SDK. If you want to override default values, uncomment it and add your value.
            // "http.ConnectionTimeOut" => "5000",
            // "http.Retry" => "2",
];
