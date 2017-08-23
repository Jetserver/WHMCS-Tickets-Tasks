<?php
/*
 *
 * JetBackupManager @ whmcs module package
 * Created By Idan Ben-Ezra
 *
 * Copyrights @ Jetserver Web Hosting
 * http://jetserver.net
 *
 **/

if (!defined("JETTICKETSTASKS"))
	die("This file cannot be accessed directly");

use Illuminate\Database\Capsule\Manager as Capsule;

class jtt_settings_default
{
	public function _default()
	{
		global $CONFIG;
		
		$output = array('success' => true, 'message' => '', 'data' => array());
		
		return $output;
	}

	public function save()
	{	
		$output = array('success' => false, 'message' => '', 'data' => array());

		$config_values = ticketstasks::request_var('config', array());

		if(sizeof($config_values))
		{
			foreach($config_values as $config_key => $config_value)
			{
				ticketstasks::getInstance()->setConfig($config_key, $config_value);
			}

			$output['success'] = true;
			$output['message'] = ticketstasks::getInstance()->lang('settings_saved');
		}
		else
		{
			$output['message'] = ticketstasks::getInstance()->lang('no_config_values');
		}
		
		return $output;
	}
}

?>