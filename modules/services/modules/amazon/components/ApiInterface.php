<?php

namespace app\modules\services\modules\amazon\components;

interface ApiInterface
{
	/**
	 * @inheritdoc
	 * 
	 * @noreturn 
	 */
	public function setRequest();	

	/**
	 * @inhreitdoc
	 * 
	 * @return object
	 */
	public function getRequest();

	/**
	 * @inheritdoc
	 * 
	 * @return object
	 */
	public function sendHttpRequest();

	/**
	 * @inheritdoc
	 * 
	 * @return object
	 */
	public function getResponse();

	/**
	 * @inheritdoc
	 * 
	 * @return object
	 */
	public function parseResponse($response = null);

}