<?php

/**
 * Hiyalife keys class
 *
 * @package Hiyalife
 */
final class HiyaKeys{

	/**
	* @var $access_token
	*/
	public $access_token="";

	/**
	* @var $refresh_token
	*/
	public $refresh_token="";

	/**
	 * Set access_token and refresh_token properties 
	 * @param $access_token
	 * @param $refresh_token
	 *
	 */
	public function setTokens($access_token, $refresh_token){
		$this->access_token = $access_token;
		$this->refresh_token = $refresh_token;
	}

	/**
	* @var $consumer_key
	*/
	public $consumer_key="";

	/**
	* @var $consumer_secret
	*/
	public $consumer_secret="";

	/**
	 * Set consumer_key and consumer_secret properties
	 * @param $consumer_key
	 * @param $consumer_secret
	 *
	 */
	public function setKeys($consumer_key,$consumer_secret){
		$this->consumer_key=$consumer_key;
		$this->consumer_secret=$consumer_secret;
	}
}

?>