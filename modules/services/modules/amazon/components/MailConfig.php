<?php
/**
 * @author mrlin <714480119@qq.com>
 * @package ~
 */

namespace app\modules\services\modules\amazon\components;

class MailConfig
{
    /**
     * Get Mail List Config
     *
     * @return array
     */
    public static function data()
    {
        $model = new \app\modules\systems\models\Email;

        $data = $model->getList();
        $emailData = [];

        foreach ($data as $key => $value) {
            $emailAddress = strtolower(trim($value['emailaddress']));
            $emailData[$emailAddress] = $value;
//                        if($value['is_encrypt']){
//                            $emailData[$emailAddress]['tls'] = true;
//                        }else{
//                            $emailData[$emailAddress]['tls'] = false;
//                        }
//			if($value['ssl'] == 1)
//			{
//				$emailData[$emailAddress]['ssl'] = true;
//			}
//			else
//			{
//				$emailData[$emailAddress]['ssl'] = false;
//			}
        }
        return $emailData;
        /*		return [
                     'sococodirect@163.com' => [
                         'imap_server' => 'imap.163.com',
                         'smtp_server' => 'smtp.163.com',
                         'imap_protocol' => 'imap',
                         'smtp_protocol' => 'smtp',
                         'imap_port' => '993',
                         'smtp_port' => '25',
                         'ssl' => true,
                         'emailaddress' => 'sococodirect@163.com',
                         'accesskey' => 'comeon666',
                     ],
                     'fastkk2016uk@163.com' => [
                         'imap_server' => 'imap.163.com',
                         'smtp_server' => 'smtp.163.com',
                       'imap_protocol' => 'imap',
                         'smtp_protocol' => 'smtp',
                         'imap_port' => '993',
                         'smtp_port' => '25',
                         'ssl' => true,
                         'emailaddress' => 'fastkk2016uk@163.com',
                         'accesskey' => 'comeon888',
                     ],
                     'hufanjin2013@163.com' => [
                         'imap_server' => 'imap.163.com',
                         'smtp_server' => 'smtp.163.com',
                         'imap_protocol' => 'imap',
                         'smtp_protocol' => 'smtp',
                         'imap_port' => '993',
                         'smtp_port' => '25',
                         'ssl' => true,
                         'emailaddress' => 'hufanjin2013@163.com',
                         'accesskey' => 'comeon777',
                     ],
                 ];			*/
    }


    /**
     * Fetch one row by specify key
     *
     * @param  string $key
     *
     * @return array
     */
    public static function get($key)
    {
        $data = self::data();
        $key = strtolower(trim($key));
        return isset($data[$key]) ? $data[$key] : [];
    }

    /**
     * Get The IMAP config
     *
     * @param  string $key
     *
     * @return array
     */
    public static function fetchImapConfig($key)
    {
        $conf = self::get($key);
        if ($conf) {
            return [
                'server' => $conf['imap_server'],
                'protocol' => $conf['imap_protocol'],
                'port' => $conf['imap_port'],
                'ssl' => $conf['ssl'],
                'emailaddress' => $conf['emailaddress'],
                'accesskey' => $conf['accesskey'],
                'password' => $conf['password'],
            ];
        }

        return [];
    }

    /**
     * Get The SMTP config
     *
     * @param  string $key
     *
     * @return array
     */
    public static function fetchSmtpConfig($key)
    {
        $conf = self::get($key);
        if ($conf) {

            $returnDatas = [
                'server' => $conf['smtp_server'],
                'protocol' => $conf['smtp_protocol'],
                'port' => $conf['smtp_port'],
//				'ssl'          => $conf['ssl'],
                'emailaddress' => $conf['emailaddress'],
                'accesskey' => $conf['accesskey'],
                'user_name' => $conf['user_name'],
                'password' => $conf['password'],
                'is_encrypt' => $conf['is_encrypt'],
                'encryption' => $conf['encryption'],
            ];
            if (isset($_REQUEST['is_debug']))
                var_dump($returnDatas);
            return $returnDatas;
        }

        return [];
    }

    /**
     * Get imap dsn
     *
     * @param  string $server imap server
     *
     * @param  int $port imap server port
     *
     * @param  boolean $ssl whether using ssl?
     *
     * @return string
     */
    public static function getDsn($server, $protocol, $port, $ssl = true)
    {
        return sprintf('{%s/%d/%s/%s}INBOX', $server, $protocol, $port, 'ssl');
    }
}
