<?php

/**
 * Hiyalife meemmo class
 *
 * @package Hiyalife
 */
class HiyaMeemo {

	/**
	 * 
	 * @var  $meemo_text
	 */
	public $meemo_text = '';

	/**
	 *
	 * @var  $id_meemo
	 */
	public $id_meemo = '';

	/**
	 *
	 * @var  $meemo_images
	 */
	public $meemo_images = array();

	/**
	 *
	 * @var $meemo_title
	 */
	public $meemo_title='';

	/**
	 *
	 * @var $meemo_ini_date
	 */
	private $meemo_ini_date;


	/**
	 * Class constructor
	 * @param $title
	 * @param $content
	 * @param $date 
	 * @param $images
	 * @param $hiya_id_meemo
	 * @param $post_thumbnails
	 *
	 */
	public function __construct($title, $content, $date, $images, $hiya_id_meemo, $post_thumbnails=null)
   	{
   		// id Meemo
   		$this->id_meemo = $hiya_id_meemo;
   		// Set title
   		$this->meemo_title = json_encode($title);
		//Remove images
		$auxText = preg_replace("[\n|\r|\n\r]", '', preg_replace("/<a[^>]+><img[^>]+\><\/a>/i", "", $content));
   		$auxText = preg_replace("[\n|\r|\n\r]", '', preg_replace("/<img[^>]+\>/i", "", $auxText));
   		$auxText = strip_shortcodes($auxText);
   		$this->meemo_text = json_encode($auxText);
   		//Get images
   		if(!empty($post_thumbnails) && !in_array($post_thumbnails, $images)){
				array_push($this->meemo_images,$post_thumbnails);
			}
		$doc=new DOMDocument;
		$doc->loadHTML($content);
		$path=new DOMXPath($doc);
		foreach ($path->query('//img/@src') as $found){
			if(!in_array($found->nodeValue, $images)){
				array_push($this->meemo_images,$found->nodeValue);
			}
		}
		// Set dates. For now the same for both.
		$this->meemo_ini_date = $date;
   	}

   	/**
	 * Returns the meemo in json format to send into the request
	 *
	 * @return Array json 
	 */
	public function getMeemo(){
		$meemoContent='{"text":'.$this->meemo_text.',';
		$meemoContent.= '"privacy":"public",';
		$meemoContent.= '"date":{"ini":'.$this->getDateHiyaFormat($this->meemo_ini_date).'},';
		$meemoContent.= '"title":'.$this->meemo_title;
		$meemoContent.= count($this->meemo_images)>0 ? ", ".$this->getImageHiyaFormat(): "";
		$meemoContent.= '}';
		$data = array("data" => $meemoContent);
		return $data;
	}

	/**
	 * Returns a String in json format with the information images
	 *
	 * @return String
	 */
	private function getImageHiyaFormat(){
		$content = '"photosinfo":[';
		for($x=0;$x<count($this->meemo_images);$x++) {
			$content .= '{"caption":"'.basename($this->meemo_images[$x]).'","name":"photo'.$x.'.'.pathinfo($this->meemo_images[$x], PATHINFO_EXTENSION).'"},';
		}
		$content = rtrim($content, ',');
		$content .= "]";
		return $content;
	}

	/**
	 * Returns the $date in Hiyalife format
	 * @param $date
	 *
	 * @return String 
	 */
	private function getDateHiyaFormat($date){
		try {
			$date = new DateTime($date);
		} catch (Exception $e) {
			echo $e->getMessage();
			exit(1);
		}
		$year = $date->format('Y');
		$month = $date->format('m');
		$day = $date->format('d');
		return '{"y":"'.$year.'","m":"'.$month.'","d":"'.$day.'"}';
	}
}
?>