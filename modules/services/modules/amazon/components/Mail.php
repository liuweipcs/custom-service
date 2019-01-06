<?php

namespace app\modules\services\modules\amazon\components;

/**
 * @author mrlin <714480119@qq.com>
 * @package ~
 */

use Yii;
use yii\swiftmailer\Mailer;
use app\modules\services\modules\amazon\components\MailConfig;


class Mail
{
	/**
	 * smtp config
	 * 
	 * @var array
	 */
	public $config = [];

	/**
	 * swift mail object
	 * 
	 * @var object
	 */
	public $mail = null;

	/**
	 * error information
	 * 
	 * @var string
	 */
	public static $errorMsg = [];

	/**
	 * __construct
	 *
	 *  $config = [
	 *  	'server' => 'smtp.163.com',
	 *  	'port' => 25,
	 *  	'emailaddress' => '',
	 *  	'accesskey' => '',
	 *  ]
	 * 
	 * @param array $config 
	 */
	public function __construct(array $config)
	{
		if (empty($config)) {
			self::$errorMsg[] = 'Email configuration Not Found!';
			return;
		}

		$this->config = $config;
		$this->init();
	}

	/**
	 * Init Function
	 * 
	 * @noreturn 
	 */
	public function init()
	{
		try {
			$mailer = new Mailer();
			$userName = !empty($this->config['user_name']) ? trim($this->config['user_name']) : $this->config['emailaddress'];
			$passworld = !empty($this->config['password']) ? trim($this->config['password']) : $this->config['accesskey'];                        
                        $transport = [
                            'class'      => 'Swift_SmtpTransport',
				'host'       => $this->config['server'],
				'username'   => $userName,
				'password'   => $passworld,
				'port'       => $this->config['port'],
				'encryption' => $this->config['encryption'],
                                'timeout'    => 300,
                        ];
                        $mailer->transport = $transport;
			$segments = explode('@', $this->config['emailaddress']);

			$mailer->messageConfig = [
				'charset' => 'UTF-8',
				//'from' => [$this->config['emailaddress'] => $segments[0]],
			];
			$mailer->useFileTransport = false;

			$this->mail = $mailer->compose();
		} catch (\Exception $e) {
			self::$errorMsg[] = $e->getMessage();
		}
	}

	/**
	 * Static method
	 * 
	 * @param  string $emailaddress email address
	 * 
	 * @return Object
	 */
	public static function instance($emailaddress)
	{
		return new self(MailConfig::fetchSmtpConfig($emailaddress));
	}

	/**
	 * Set to someone
	 * 
	 * @param string $to
	 *
	 * @return  $this
	 */
	public function setTo($to)
	{
		try {
                    if($this->mail){
			$this->mail->setTo($to);
                    }else{
                        echo '<pre>';
                        var_dump($to);
                        echo '</pre>';
                    }
		} catch (\Exception $e){
			self::$errorMsg[] = $e->getMessage();
		}

		return $this;
	}

	/**
	 * Set title
	 * 
	 * @param string $title
	 *
	 * @return  $this
	 */
	public function setSubject($title)
	{
		try {
                    if($this->mail){
			$this->mail->setSubject($title);
                    }
		} catch (\Exception $e) {
			self::$errorMsg[] = $e->getMessage();
		}

		return $this;
	}

	/**
	 * Add an attachment
	 * 
	 * @param string $path
	 *
	 * @return $this
	 */
	public function addAttach($path)
	{
		if (!$path) goto ret;
		try {
                    if($this->mail){
			$this->mail->attach($path);
                    }
		} catch (\Exception $e) {
			self::$errorMsg[] = $e->getMessage();
		}

		ret:
		return $this;
	}

	/**
	 * Set mail html body message
	 * 
	 * @param string $body
	 *
	 * @return $this
	 */
	public function seHtmlBody($body)
	{
		try {
                    if($this->mail){
			$this->mail->setHtmlBody($body);
                    }
		} catch (\Exception $e) {
			self::$errorMsg[] = $e->getMessage();
		}

		return $this;
	}

	/**
	 * Set mail text body message
	 *
	 * @param string $body
	 *
	 * @return $this
	 */
	public function setTextBody($body)
	{
	    try {
                if($this->mail){
	        $this->mail->setTextBody($body);
                }
	    } catch (\Exception $e) {
	        self::$errorMsg[] = $e->getMessage();
	    }
	
	    return $this;
	}
	
	/**
	 * Finally send mail message
	 * 
	 * @return boolean
	 */
	public function sendmail()
	{
            try {
                if($this->mail){
                    $r = $this->mail->send();
                }else{
                    $r = false;
                    self::$errorMsg[] = '发件箱未设置';
                }
            } catch (\Exception $e) {
                $r = false;
                self::$errorMsg[] = $e->getMessage();
            }
            return $r;
	}

	/**
	 * Get error message
	 * 
	 * @return string
	 */
	public function getErrorMsg()
	{
		return !empty(self::$errorMsg) ? implode(',,', self::$errorMsg) : '';
	}
	
	/**
	 * @desc set from address
	 * @param unknown $fromEmail
	 * @return \app\modules\services\modules\amazon\components\Mail
	 */
	public function setFrom($from)
	{
	    try {
	        if($this->mail){
	            $this->mail->setFrom($from);
	        }
	    } catch (\Exception $e) {
	        self::$errorMsg[] = $e->getMessage();
	    }
	    return $this;
	}
}