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

class jtt_predefined_default
{
	public function _default()
	{	
		$output = array('success' => true, 'message' => '', 'data' => array());

		$output['data']['tasks'] = array();

		$predefined_rows = Capsule::table('mod_ticketstasks_predefined')
			->orderBy('id', 'desc')
			->get();

		foreach($predefined_rows as $predefined_details)
		{
			$predefined_details = (array) $predefined_details;

			$predefined_details['task_abort'] = unserialize($predefined_details['task_abort']);
			$predefined_details['status_details'] = ticketstasks::getInstance()->getTicketStatus($predefined_details['ticket_status_id']);
			$predefined_details['ticket_admin_details'] = ticketstasks::getInstance()->getAdmin($predefined_details['ticket_admin_id']);
			$predefined_details['department_details'] = ticketstasks::getInstance()->getTicketDepartment($predefined_details['ticket_dept_id']);
			
			$output['data']['tasks'][$predefined_details['id']] = $predefined_details;
		}

		return $output;
	}

	public function delete()
	{
		$output = array('success' => false, 'message' => '', 'data' => array());

		$selectedtasks = ticketstasks::request_var('selectedtasks', array());

		if(sizeof($selectedtasks))
		{
			Capsule::table('mod_ticketstasks_predefined')
				->whereIn('id', $selectedtasks)
				->delete();

			$output['success'] = true;
			$output['message'] = ticketstasks::getInstance()->lang('predefineddeleted');
		}

		return $output;
	}
}

?>