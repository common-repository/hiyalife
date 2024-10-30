<?php
/**
 * Hiyalife Wrapper class Connection API 
 *
 * @package Hiyalife
 */
final class HiyaConn {

	/**
	 * @var String URL of the API
	 */
	public  $api_url = 'http://api.hiyalife.com:8080';

	/**
	 * @var String URL of the AuthHost
	 */
	public  $auth_url = 'http://hiyalife.com';

	/**
	 * @var String App_id 
	 */
	private static $app_id="2";

	/**
	 * @var String App_key
	 */
	private static $app_key="124203a78f5b0994e83da32026377ad8a88bd0e1";

	/**
	 * @var String last request result. For debug
	 */
	public $lastResult="";

	/**
	 * @var String last request. For debug
	 */
	public $lastRequest="";

	/**
	 * @var String to meemo API
	*/
	private static $meemoService ="/meemo";

	/**
	 * @var String to auth API
	*/
	private static $authService ="/auth";

	/**
	 * @var String to me API
	*/
	private static $meService ="/me";

	/**
	 * Return the auth and refresh tokens
	 * @param $keys hiyakeys.class.php
	 * @param $blog_name String the name of the blog
	 *
	 * @return Object hiyakeys.class.php
	 */
	public function authenticate($keys, $blog_name) {
		$data = array('grant_type' => 'client', 'consumer_key' => $keys->consumer_key,'consumer_secret'=>$keys->consumer_secret,'device'=> $blog_name);

		$result = HiyaConn::rest_helper($this->auth_url,self::$authService,$data,$keys);
		if (property_exists($result,'error')){
			$error=$result->error;
			if(@property_exists($error, 'code')){
				if($error->code==401) return "noExist";
			}
			return '';
		}
		$keys->setTokens($result->access_token, $result->refresh_token);
		return $keys;
	}

	/**
	 * Return a update of auth and refresh tokens when the session expires.
	 * @param $keys hiyakeys.class.php
	 *
	 * @return Object hiyakeys.class.php
	 */
	public function auth_refresh($keys){
		$data = array('grant_type' => 'refresh_token', 'refresh_token' => $keys->refresh_token,'client_id'=>'97da4711fa6a073b720d24a0c582f1e01a5ca530','client_secret'=>'dd6e33bc16bb0189793ff871e97a6018dc92052d');

		$result = HiyaConn::rest_helper($this->auth_url,self::$authService,$data);
		if (property_exists($result,'error')){
			return "";
		}
		$keys->setTokens($result->access_token, $result->refresh_token);
		return $keys;
	}

	/**
	 * Return me Hiyalife data
	 *
	 * @return Object (json_decode) 
	 */
	public function me_hiyalife_data() {
		$result = HiyaConn::rest_helper($this->api_url,self::$meService);
		return $result;
	}

	/**
	 * Return the url to Reedem consumer key and secret
	 *
	 * @return String
	 */
	public function getReedemKeysURL($blog_name){
		return $this->auth_url."/apps/credentials?app=".self::$app_id."&app_key=".self::$app_key.'&device='.$blog_name;
	}

	/**
	* Publish meemo on Hiyalife
	* @param $keys  hiyakeys.class.php
	* @param $meemo hiyameemo.class.php
	*
	* @return id_meemo
	*/
	public function publishMeemo($keys,$meemo){
		$result = $this-> MeemoRequest($keys,$this->api_url,self::$meemoService, $meemo,"POST");
		if (property_exists($result,'idmeemo')){
			return $result->idmeemo;
		}
		return "";
	}

	/**
	* Update meemo on Hiyalife
	* @param $keys  hiyakeys.class.php
	* @param $meemo hiyameemo.class.php
	*
	* @return id_meemo
	*/
	public function updateMeemo($keys,$meemo){
		$result = $this-> MeemoRequest($keys,$this->api_url,self::$meemoService, $meemo,"PUT");
		if (property_exists($result,'error')){
			$error=$result->error;
			if(@property_exists($error, 'code')){
				if($error->code==403) return "unlinked";
			}
			if($result->error==false)
				return "Updated";
			return '';
		}
		return "Updated";
	}

	/**
	 * Return a request. Create headers and request parameters. Call to function doRequest.
	 * @param $keys hiyakeys.class.php
	 * @param $url String url where send a request
	 * @param $meemo hiyameemo.class.php
	 * @param $verb String POST or PUT request
	 *
	 * @return Object (json_decode) 
	 */
	private function MeemoRequest($keys, $url, $serv, $meemo, $verb){
		$files = $meemo->meemo_images;
		$semi_rand = md5(time());  
		$meemoData = $meemo->getMeemo();
		$mime_boundary = "Boundary+{$semi_rand}";  
		$headers =  array("Authentication: Bearer ".$keys->access_token, "Content-Type: multipart/form-data; boundary={$mime_boundary}", "Connection: close");  
		$message = "--{$mime_boundary}\r\n" . 'Content-Disposition: form-data; name="data"'."\r\n\r\n" . $meemoData["data"] . "\r\n";  
		$message .= "--{$mime_boundary}"; 
		if($verb=="PUT"){
			$message .= "--{$mime_boundary}\r\n" . 'Content-Disposition: form-data; name="meemo"'."\r\n\r\n" . $meemo->id_meemo. "\r\n";  
			$message .= "--{$mime_boundary}"; 
		}
		for($x=0;$x<count($files);$x++) 
		{ 
			$message.= "\r\n";
			//$fileLocal = str_ireplace(parse_url($files[$x], PHP_URL_HOST).":".$_SERVER['SERVER_PORT'], '..', $files[$x]);
			$fileLocal = "../".substr($files[$x],strpos($files[$x], "wp-content"));
			$file = fopen($fileLocal,"rb"); 
			if($file)
			{
				$data = stream_get_contents($file);
				fclose($file); 
			}else{$data=0;}
			$message .= 'Content-Disposition: form-data; name="photos"; filename="photo'.$x.'.'.pathinfo($fileLocal, PATHINFO_EXTENSION).'"'."\r\n".'Content-Type: image/jpg;'."\r\n\r\n" .  $data . "\r\n"; 
			$message .= "--{$mime_boundary}"; 
		} 
		$message.="--\r\n";
		$cparams = array(
			'http' => array(
				'method' => $verb,
				'header' => $headers,
				'user_agent' => 'hylwp',
				'content' => $message,
				)
			);
		return $this->doRequest($url.$serv,$cparams);
	}

	/**
	 * Make a rest request and return json Object
	 * @param $url
	 * @param $service
	 * @param $params
	 * @param $verb
	 *
	 * @return Object (json)
	 */
	private  function rest_helper($url, $service='', $params = null,$keys= null, $verb = 'GET')
	{
		$more_header='';
		if ($service !='/auth'){
			$more_header='Authentication: Bearer '.$keys->access_token;
		}
		if($service!=='')
			$url=$url.$service;
		$cparams = array(
			'http' => array(
				'method' => $verb,
				'header' => "Content-type: application/x-www-form-urlencoded;\r\n".$more_header,
				'user_agent' => 'hylwp',
				)
			);
		if ($params !== null) {
			$params = http_build_query($params);
			if ($verb != 'GET') {
				$cparams['http']['content'] = $params;
			} else {
				$url .= '?' . $params;
				$cparams['http']['content'] ="";
			}
		}
		return $this->doRequest($url,$cparams);
	}

	/**
	 * Return a request in json
	 * @param $url
	 * @param $cparams
	 * @param $format format request in json or xml. Default json.
	 *
	 * @return Object (json_decode) 
	 */
	private function doRequest($url,$cparams,$format = "json"){
		$this->lastRequest = $cparams;
		ini_set("allow_url_fopen", 1);
		if (ini_get("allow_url_fopen") == 1) {		
			$context = stream_context_create($cparams);
			$fp = file_get_contents($url,  false, $context);
		} else {
			$fp = $this->getWithSocket($url, $cparams);
		}

		if (!$fp) {
			$res = false;
		} else {
			$res = $fp;
		}
		if ($res === false) {
			throw new Exception("failed: $php_errormsg");
		}
		$this->lastResult = $res;

		switch ($format) {
			case 'json':
			$r = json_decode($res);
			if ($r === null) {
				throw new Exception("failed to decode $res as json");
			}
			return $r;

			case 'xml':
			$r = simplexml_load_string($res);
			if ($r === null) {
				throw new Exception("failed to decode $res as xml");
			}
			return $r;
		}
		return $res;
	}

	/**
	 * Return a request in json. Use fsockopen if allow_url_fopen is Off.
	 * @param $url
	 * @param $context
	 *
	 * @return Object (json_decode) 
	 */
	private function getWithSocket($url, $context){
		$parts = parse_url($url);
		$fp = fsockopen($parts['host'], isset($parts['port']) ? $parts['port'] : 80, $errno, $errstr, 30);
		if ($fp)
		{
			$query = isset($parts['query'])? "?".$parts['query'] : "";
			$p = $context['http']['method']." /". str_replace("/", "", $parts['path']). $query ." HTTP/1.0\r\n";
			$p.= "Host: ". $parts['host'] ."\r\n";
			$p.= "User-Agent: ".$context['http']['user_agent']."\r\n";
			if(!is_array($context['http']['header']))
				$p.= $context['http']['header']."\r\n";
			else{
				foreach ($context['http']['header'] as $auxHeader) {
					$p.= $auxHeader."\r\n";
				}
			}
			$p.= "Content-length: ".strlen($context['http']['content'])."\r\n";
			$p.= "\r\n";
			$p.= $context['http']['content'];
			fputs ($fp, $p);
		}
		else die("dagnabbit : $errstr");
		$body="";
		$header="";
		$foundBody = false;
		while (!feof($fp)) {
			$s = fgets($fp, 128);
			if ( $s == "\r\n" ) {
				$foundBody = true;
				continue;
			}
			if ( $foundBody ) {
				$body .= $s;
			} else {
				$header .= $s;
			}
		}
		fclose($fp);
		//$rtn['header'] = trim($header);
		//$rtn['body'] = trim($body);
		return trim($body);
	}

}

?>