<?php

if ( ! defined('CROCODOCEE_ADDON_NAME'))
{
	define('CROCODOCEE_ADDON_NAME',         'CrocodocEE');
	define('CROCODOCEE_ADDON_VERSION',      '2.0');
}

$config['name']=CROCODOCEE_ADDON_NAME;
$config['version']=CROCODOCEE_ADDON_VERSION;

$config['nsm_addon_updater']['versions_xml']='http://www.intoeetive.com/index.php/update.rss/171';