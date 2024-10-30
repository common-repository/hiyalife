<?php
/*
Plugin Name: Hiyalife plugin
Plugin URI: http://hiyalife.com/apps/plugins
Description: Share your posts in Hiyalife.com and view your lifeline in your Wordpress blog.
Version: 1.0
Author: Hiyalife
Author URI: http://www.hiyalife.com
*/
define('HIYWP_FILE', __FILE__);
define('HIYWP_PATH', plugin_dir_path(__FILE__));
define('HIYWP_PLUGIN_PATH','plugins/');
define('HIYWP_CLASS_PATH','class/');

/**
* Autoload hiyalife classes
*/
function hiyaAutoLoaderClass($class_to_load) {
    $class_to_load =strtolower($class_to_load);
	if (substr($class_to_load, 0, 4) == 'hiya') {
    	include HIYWP_CLASS_PATH . $class_to_load. '.class.php';
    }
    return true;
}

spl_autoload_register('hiyaAutoLoaderClass');

function _empty($val) { return empty($val); }

//Load Hiyalife Shortcodes plugin
require HIYWP_PATH.HIYWP_PLUGIN_PATH .'hiyashortcodes.php';

//Load Hiyalife Publish plugin
require HIYWP_PATH.HIYWP_PLUGIN_PATH .'hiyapublish.php';


?>